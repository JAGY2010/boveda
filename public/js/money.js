/**
 * Formatea los campos de dinero con separador de miles colombiano (1.200.000)
 * mientras se escribe. Se aplica a cualquier <input class="money">.
 * Al enviar el formulario quita los puntos para que el servidor reciba el entero.
 * Funciona con inputs creados dinámicamente y con navegación de Livewire.
 */
(function () {
    'use strict';
    if (window.__moneyInit) return;
    window.__moneyInit = true;

    function fmt(v) {
        var d = String(v == null ? '' : v).replace(/\D/g, '').replace(/^0+(?=\d)/, '');
        return d ? Number(d).toLocaleString('es-CO') : '';
    }

    // Formatear mientras se escribe (delegado -> sirve también para inputs dinámicos).
    document.addEventListener('input', function (e) {
        var el = e.target;
        if (el && el.matches && el.matches('input.money')) {
            el.value = fmt(el.value);
        }
    });

    // Al enviar, quitar los puntos para que el servidor reciba el número entero.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.querySelectorAll) return;
        form.querySelectorAll('input.money').forEach(function (el) {
            el.value = String(el.value || '').replace(/\D/g, '');
        });
    }, true);

    // Formatear los valores que ya vienen cargados.
    function formatExisting() {
        document.querySelectorAll('input.money').forEach(function (el) {
            if (el.value) el.value = fmt(el.value);
        });
    }
    document.addEventListener('DOMContentLoaded', formatExisting);
    document.addEventListener('livewire:navigated', formatExisting);
    formatExisting();

    window.Money = {
        format: fmt,
        set: function (el, val) { if (el) { el.value = fmt(val); } },
    };
})();
