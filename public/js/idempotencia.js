/**
 * Le pone a cada formulario de escritura una clave unica.
 *
 * Con esa clave el servidor sabe distinguir "el empleado quiere cobrar otra
 * vez" de "esto es lo mismo que ya llego". Hace falta cuando se reenvia algo:
 * porque se cayo el internet a mitad, porque le dieron dos veces al boton, o
 * porque la cola sin conexion esta vaciando lo que tenia pendiente.
 *
 * La clave se genera UNA vez por formulario y no cambia aunque se reintente:
 * ahi esta la gracia.
 */
(function () {
    'use strict';
    if (window.__idemInit) return;
    window.__idemInit = true;

    function uuid() {
        if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
        // Navegadores viejos o sin contexto seguro.
        return 'k-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 12);
    }

    function marcar(form) {
        if (!form || form.method.toLowerCase() !== 'post') return;
        if (form.querySelector('input[name="_clave"]')) return;
        // El logout no necesita clave y ensucia la tabla.
        if ((form.getAttribute('action') || '').indexOf('logout') !== -1) return;

        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_clave';
        input.value = uuid();
        form.appendChild(input);
    }

    function marcarTodos() {
        document.querySelectorAll('form').forEach(marcar);
    }

    // Tambien los formularios que aparecen despues (modales, Livewire).
    document.addEventListener('submit', function (e) {
        marcar(e.target);
    }, true);

    document.addEventListener('DOMContentLoaded', marcarTodos);
    document.addEventListener('livewire:navigated', marcarTodos);
    marcarTodos();

    window.Idempotencia = { uuid: uuid, marcar: marcar };
})();
