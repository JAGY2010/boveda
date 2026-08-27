/**
 * Service worker de Boveda.
 *
 * Hace tres cosas:
 *
 *  1. La app abre sin internet. Los archivos (css, js, fuentes) se guardan y
 *     se sirven desde el dispositivo.
 *  2. Las pantallas se pueden consultar sin internet, con lo ultimo que se
 *     vio. Se avisa que los datos son de esa fecha: no se puede fingir que
 *     estan al dia.
 *  3. Lo que se registra sin internet no se pierde. La operacion se guarda
 *     en una cola y se envia sola en cuanto vuelve la conexion. Cada una
 *     lleva su clave unica, asi que reenviarla nunca cobra dos veces.
 */

const VERSION = 'boveda-v1';
const CACHE_ESTATICO = VERSION + '-estatico';
const CACHE_PAGINAS = VERSION + '-paginas';
const RESPALDO = '/offline';

// Rutas que jamas se guardan: son documentos y dinero al dia.
const NUNCA_CACHEAR = [/\/health$/, /\/recibo$/, /\/contrato$/, /\/acta$/, /\/livewire\//];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_ESTATICO)
            .then((c) => c.addAll([RESPALDO, '/js/money.js', '/js/idempotencia.js', '/js/offline.js', '/js/numeros.js']))
            .then(() => self.skipWaiting())
            .catch(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((claves) => Promise.all(
                claves.filter((c) => c.indexOf(VERSION) !== 0).map((c) => caches.delete(c))
            ))
            .then(() => self.clients.claim())
    );
});

/* ---------------------------------------------------------------
   La cola: operaciones hechas sin internet, esperando a que vuelva.
   --------------------------------------------------------------- */

const BD = 'boveda-offline';
const TIENDA = 'pendientes';

function abrirBd() {
    return new Promise((ok, mal) => {
        const req = indexedDB.open(BD, 1);
        req.onupgradeneeded = () => {
            if (!req.result.objectStoreNames.contains(TIENDA)) {
                req.result.createObjectStore(TIENDA, { keyPath: 'id', autoIncrement: true });
            }
        };
        req.onsuccess = () => ok(req.result);
        req.onerror = () => mal(req.error);
    });
}

function conTienda(modo, fn) {
    return abrirBd().then((db) => new Promise((ok, mal) => {
        const tx = db.transaction(TIENDA, modo);
        const res = fn(tx.objectStore(TIENDA));
        tx.oncomplete = () => ok(res.result !== undefined ? res.result : res);
        tx.onerror = () => mal(tx.error);
    }));
}

const encolar = (op) => conTienda('readwrite', (t) => t.add(op));
const listarPendientes = () => conTienda('readonly', (t) => t.getAll());
const borrarPendiente = (id) => conTienda('readwrite', (t) => t.delete(id));
const guardarPendiente = (op) => conTienda('readwrite', (t) => t.put(op));

async function contarPendientes() {
    try {
        // Las rechazadas ya no van a salir solas: no cuentan como "en camino".
        return (await listarPendientes()).filter((o) => !o.rechazada).length;
    } catch (e) {
        return 0;
    }
}

async function avisarAPaginas(mensaje) {
    const clientes = await self.clients.matchAll({ includeUncontrolled: true, type: 'window' });
    clientes.forEach((c) => c.postMessage(mensaje));
}

/**
 * Vacia la cola. Se llama al volver la senal y cada cierto tiempo mientras
 * quede algo pendiente.
 *
 * Lo delicado aqui es cuando NO hay que borrar una operacion. Borrarla de mas
 * es perder plata en silencio: nadie se entera de que ese abono nunca entro.
 */
async function enviarPendientes() {
    let pendientes = [];
    try {
        pendientes = await listarPendientes();
    } catch (e) {
        return;
    }

    let enviadas = 0;
    let rechazadas = 0;
    let necesitaSesion = false;

    for (const op of pendientes) {
        if (op.rechazada) { rechazadas++; continue; }

        let r;
        try {
            r = await fetch(op.url, {
                method: 'POST',
                headers: { 'Content-Type': op.tipo || 'application/x-www-form-urlencoded' },
                body: op.cuerpo,
                credentials: 'same-origin',
                redirect: 'follow',
            });
        } catch (e) {
            break; // sigue sin haber red: se reintenta en la proxima
        }

        /* La sesion se cayo (caduco mientras estaba sin senal, o el token CSRF
           ya no vale). El servidor manda al login y fetch, que sigue las
           redirecciones, devuelve un 200 tranquilizador. Si aqui se borrara,
           el abono desapareceria sin dejar rastro. Se para todo y se avisa. */
        if (r.status === 419 || r.status === 401 || (r.redirected && r.url.indexOf('/login') !== -1)) {
            necesitaSesion = true;
            break;
        }

        if (r.status >= 500) {
            // El servidor esta mal, no la operacion: se deja para luego.
            continue;
        }

        if (r.status >= 400) {
            /* El servidor la rechazo. Reintentarla solo la volveria a
               rechazar, pero tampoco se puede borrar como si hubiera entrado:
               se marca para que el empleado la revise. */
            op.rechazada = true;
            op.motivo = 'El servidor la rechazó (' + r.status + ')';
            try { await guardarPendiente(op); } catch (e) { /* no se pudo marcar */ }
            rechazadas++;
            continue;
        }

        await borrarPendiente(op.id);
        enviadas++;
    }

    const quedan = await contarPendientes();
    await avisarAPaginas({
        tipo: 'sincronizado',
        enviadas: enviadas,
        rechazadas: rechazadas,
        necesitaSesion: necesitaSesion,
        pendientes: quedan,
    });
}

self.addEventListener('sync', (event) => {
    if (event.tag === 'boveda-pendientes') event.waitUntil(enviarPendientes());
});

/**
 * Borra las pantallas guardadas. Se llama al cerrar sesion: quedan datos de
 * clientes y de plata en el dispositivo, y el siguiente que entre no tiene
 * por que poder verlos sin contrasena.
 *
 * La cola de pendientes NO se borra: esas operaciones son del negocio y
 * tienen que llegar.
 */
async function olvidarPantallas() {
    await caches.delete(CACHE_PAGINAS);
}

self.addEventListener('message', (event) => {
    const dato = event.data || {};
    if (dato.tipo === 'sincronizar') event.waitUntil(enviarPendientes());
    if (dato.tipo === 'olvidar') event.waitUntil(olvidarPantallas());
    if (dato.tipo === 'cuantos-pendientes') {
        event.waitUntil(contarPendientes().then((n) => avisarAPaginas({ tipo: 'pendientes', pendientes: n })));
    }
});

/* ---------------------------------------------------------------
   Interceptar peticiones
   --------------------------------------------------------------- */

function noCachear(url) {
    return NUNCA_CACHEAR.some((re) => re.test(url));
}

async function guardarOperacion(request) {
    const cuerpo = await request.clone().text();

    await encolar({
        url: request.url,
        cuerpo: cuerpo,
        tipo: request.headers.get('Content-Type') || 'application/x-www-form-urlencoded',
        fecha: new Date().toISOString(),
    });

    if (self.registration.sync) {
        try { await self.registration.sync.register('boveda-pendientes'); } catch (e) { /* da igual */ }
    }

    // Se devuelve a donde estaba, marcando que quedo pendiente de enviar.
    const volver = request.referrer || '/dashboard';
    const sep = volver.indexOf('?') === -1 ? '?' : '&';

    return Response.redirect(volver + sep + 'pendiente=1', 303);
}

self.addEventListener('fetch', (event) => {
    const req = event.request;
    const url = req.url;

    if (req.method === 'POST') {
        /* Se intenta de verdad primero: si hay internet, esto no cambia nada.
           Solo cuando la red falla se guarda para despues. */
        event.respondWith(
            fetch(req.clone()).catch(() => guardarOperacion(req))
        );
        return;
    }

    if (req.method !== 'GET' || noCachear(url) || url.indexOf('http') !== 0) return;

    // Archivos compilados: no cambian nunca (llevan hash en el nombre).
    if (url.indexOf('/build/') !== -1 || url.indexOf('/js/') !== -1) {
        event.respondWith(
            caches.match(req).then((hit) => hit || fetch(req).then((r) => {
                const copia = r.clone();
                caches.open(CACHE_ESTATICO).then((c) => c.put(req, copia));
                return r;
            }))
        );
        return;
    }

    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req)
                .then((r) => {
                    if (r.ok) {
                        const copia = r.clone();
                        caches.open(CACHE_PAGINAS).then((c) => c.put(req, copia));
                    }
                    return r;
                })
                /* ignoreSearch: al guardar sin conexion se vuelve con
                   "?pendiente=1", y esa URL exacta no esta guardada. Sin esto
                   el empleado caeria en la pantalla de "sin internet" justo
                   despues de registrar bien su abono. */
                .catch(() => caches.match(req, { ignoreSearch: true })
                    .then((hit) => hit || caches.match(RESPALDO)))
        );
    }
});
