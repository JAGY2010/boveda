/**
 * Registra el service worker y le cuenta al empleado como esta la cosa.
 *
 * Sin esto la app funcionaria sin internet pero nadie lo sabria, y peor: no
 * se veria que hay operaciones esperando a subir. Con dinero de por medio eso
 * no puede quedar invisible.
 */
(function () {
    'use strict';
    if (window.__offlineInit) return;
    window.__offlineInit = true;

    if (!('serviceWorker' in navigator)) return;

    navigator.serviceWorker.register('/sw.js').catch(function () {
        // Sin service worker la app sigue funcionando como siempre, con internet.
    });

    function aviso() {
        var el = document.getElementById('aviso-offline');
        if (el) return el;

        el = document.createElement('div');
        el.id = 'aviso-offline';
        el.style.cssText = [
            'position:fixed', 'left:0', 'right:0', 'bottom:0', 'z-index:9999',
            'padding:10px 16px', 'font:600 13px/1.4 system-ui,sans-serif',
            'text-align:center', 'color:#fff', 'display:none',
        ].join(';');
        document.body.appendChild(el);
        return el;
    }

    function mostrar(texto, color) {
        var el = aviso();
        el.textContent = texto;
        el.style.background = color;
        el.style.display = 'block';
    }

    function ocultar() {
        var el = document.getElementById('aviso-offline');
        if (el) el.style.display = 'none';
    }

    var pendientes = 0;

    function pintar() {
        if (!navigator.onLine) {
            mostrar(
                pendientes > 0
                    ? 'Sin internet · ' + pendientes + ' operación(es) guardada(s), se enviarán solas'
                    : 'Sin internet · lo que registres se guarda y se envía al volver la señal',
                '#b45309'
            );
            return;
        }
        if (pendientes > 0) {
            mostrar('Enviando ' + pendientes + ' operación(es) pendiente(s)…', '#0e5c43');
            return;
        }
        ocultar();
    }

    function preguntarPendientes() {
        if (navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage({ tipo: 'cuantos-pendientes' });
        }
    }

    function sincronizar() {
        if (navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage({ tipo: 'sincronizar' });
        }
    }

    navigator.serviceWorker.addEventListener('message', function (e) {
        var d = e.data || {};
        if (d.tipo === 'pendientes' || d.tipo === 'sincronizado') {
            pendientes = d.pendientes || 0;
            pintar();
            // Al terminar de subir, se recarga para ver los datos de verdad.
            if (d.tipo === 'sincronizado' && d.enviadas > 0 && pendientes === 0) {
                location.reload();
            }
        }
    });

    /* Al cerrar sesion: avisar si queda algo sin enviar (se perderia, porque
       al volver no habra sesion que lo acepte) y borrar del dispositivo las
       pantallas guardadas, que llevan datos de clientes y de plata. */
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || (form.getAttribute('action') || '').indexOf('logout') === -1) return;

        if (pendientes > 0) {
            var seguir = window.confirm(
                'Hay ' + pendientes + ' operación(es) sin enviar. Si cierras sesión ahora se pueden perder.\n\n' +
                'Conéctate y espera a que se envíen. ¿Cerrar sesión de todos modos?'
            );
            if (!seguir) {
                e.preventDefault();
                e.stopPropagation();
                return;
            }
        }

        if (navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage({ tipo: 'olvidar' });
        }
    }, true);

    window.addEventListener('online', function () { pintar(); sincronizar(); });
    window.addEventListener('offline', pintar);

    // Si venimos de guardar algo sin internet, decirlo claro.
    if (location.search.indexOf('pendiente=1') !== -1) {
        mostrar('Guardado sin internet · se enviará solo al volver la señal', '#b45309');
    }

    navigator.serviceWorker.ready.then(function () {
        preguntarPendientes();
        if (navigator.onLine) sincronizar();
    });

    document.addEventListener('DOMContentLoaded', pintar);
    pintar();
})();
