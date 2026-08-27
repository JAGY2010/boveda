/**
 * Numeros de contrato para trabajar sin conexion.
 *
 * El numero del empeno lo asigna el servidor ("el mayor que haya + 1"), pero
 * sin internet no hay a quien preguntarle: dos equipos contestarian lo mismo
 * y saldrian dos contratos firmados con el mismo numero.
 *
 * Por eso, mientras hay senal, este dispositivo aparta por adelantado un
 * bloque de numeros que son solo suyos. Cuando se va el internet, gasta los
 * de su bloque y nadie mas los puede usar.
 *
 * Con internet no se toca nada: manda el servidor, como siempre.
 */
(function () {
    'use strict';
    if (window.__numerosInit) return;
    window.__numerosInit = true;

    var CLAVE_DISPOSITIVO = 'boveda_dispositivo';
    var CLAVE_BLOQUE = 'boveda_bloque_numeros';
    var MINIMO = 5;   // por debajo de esto se pide otro bloque
    var PEDIR = 20;

    function leer(clave) {
        try { return localStorage.getItem(clave); } catch (e) { return null; }
    }

    function guardar(clave, valor) {
        try { localStorage.setItem(clave, valor); } catch (e) { /* modo privado */ }
    }

    function dispositivo() {
        var id = leer(CLAVE_DISPOSITIVO);
        if (!id) {
            id = (window.crypto && crypto.randomUUID)
                ? crypto.randomUUID()
                : 'd-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 10);
            guardar(CLAVE_DISPOSITIVO, id);
        }
        return id;
    }

    function bloque() {
        try { return JSON.parse(leer(CLAVE_BLOQUE) || 'null'); } catch (e) { return null; }
    }

    function guardarBloque(b) {
        guardar(CLAVE_BLOQUE, JSON.stringify(b));
    }

    function quedan() {
        var b = bloque();
        return b ? (b.hasta - b.siguiente + 1) : 0;
    }

    /** Saca el siguiente numero del bloque. Devuelve null si no queda ninguno. */
    function tomar() {
        var b = bloque();
        if (!b || b.siguiente > b.hasta) return null;

        var n = b.siguiente;
        b.siguiente = n + 1;
        guardarBloque(b);
        return n;
    }

    var pidiendo = null;

    function pedirBloque() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (!meta) return Promise.resolve();

        /* Una sola peticion a la vez. Sin esto, dos llamadas casi simultaneas
           (carga del script y DOMContentLoaded) apartan dos bloques y el
           primero se pierde, dejando un hueco en la numeracion de contratos. */
        if (pidiendo) return pidiendo;

        pidiendo = fetch('/numeros/reservar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': meta.content,
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ dispositivo: dispositivo(), cuantos: PEDIR }),
        })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) {
                if (d && d.desde) guardarBloque({ desde: d.desde, hasta: d.hasta, siguiente: d.desde });
            })
            .catch(function () { /* sin senal: se pedira mas adelante */ })
            .finally(function () { pidiendo = null; });

        return pidiendo;
    }

    function asegurarBloque() {
        if (!navigator.onLine) return;
        if (quedan() > MINIMO) return;
        pedirBloque();
    }

    /* Al enviar un empeno SIN conexion se le pone el numero del bloque.
       Con conexion no se toca: lo pone el servidor. */
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.action || form.action.indexOf('/empenos') === -1) return;
        if (form.method.toLowerCase() !== 'post') return;
        if (navigator.onLine) return;

        var campo = form.querySelector('[name="numero"]');
        if (campo && campo.value) return; // lo escribio una persona

        var n = tomar();
        if (n === null) return; // sin bloque: el servidor decidira al sincronizar

        if (!campo) {
            campo = document.createElement('input');
            campo.type = 'hidden';
            campo.name = 'numero';
            form.appendChild(campo);
        }
        campo.value = n;
    }, true);

    window.addEventListener('online', asegurarBloque);
    document.addEventListener('DOMContentLoaded', asegurarBloque);
    asegurarBloque();

    window.Numeros = { dispositivo: dispositivo, quedan: quedan, tomar: tomar, pedirBloque: pedirBloque };
})();
