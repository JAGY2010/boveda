/**
 * Escáner de código de barras PDF417 (cédula amarilla y tarjeta de propiedad).
 * Corre 100% en el navegador del usuario: la foto NUNCA sale del celular.
 * - Primario: BarcodeDetector nativo (Android/Chrome).
 * - Respaldo: @zxing/browser cargado desde CDN (iPhone/Safari/otros).
 * Siempre degrada a digitación manual si no se puede leer.
 *
 * Los offsets de la cédula son ingeniería inversa de la comunidad (no es un
 * estándar oficial), así que puede requerir ajuste con cédulas reales. Por eso
 * se guarda el último crudo en window.__ultimoEscaneo para poder afinar.
 */
(function () {
    'use strict';

    function strToBytes(s) {
        var b = new Uint8Array(s.length);
        for (var i = 0; i < s.length; i++) b[i] = s.charCodeAt(i) & 0xff;
        return b;
    }

    function texto(bytes, a, b) {
        var out = '';
        for (var i = a; i < b && i < bytes.length; i++) {
            var c = bytes[i];
            if (c === 0) continue; // relleno null
            if (c >= 32) out += String.fromCharCode(c);
        }
        return out.replace(/\s+/g, ' ').trim();
    }

    function soloDigitos(bytes, a, b) {
        return texto(bytes, a, b).replace(/[^0-9]/g, '').replace(/^0+/, '');
    }

    function titulo(s) {
        return s.toLowerCase().replace(/(^|\s)([a-záéíóúñ])/g, function (m, p1, p2) {
            return p1 + p2.toUpperCase();
        }).trim();
    }

    // ---- Cédula amarilla: PDF417 en bytes, offsets Eitol (aprox.) ----
    function parseCedula(bytes) {
        try {
            var cedula = soloDigitos(bytes, 48, 58);
            var ap1 = texto(bytes, 58, 81);
            var ap2 = texto(bytes, 81, 104);
            var no1 = texto(bytes, 104, 127);
            var no2 = texto(bytes, 127, 150);
            var sexo = texto(bytes, 151, 152);
            var nombre = [no1, no2, ap1, ap2].filter(Boolean).join(' ').replace(/[^A-Za-zÁÉÍÓÚÑáéíóúñ ]/g, '').replace(/\s+/g, ' ').trim();
            if (!/^[0-9]{6,11}$/.test(cedula) || nombre.length < 4) return null;
            return { ok: true, cedula: cedula, nombre: titulo(nombre), sexo: sexo };
        } catch (e) {
            return null;
        }
    }

    // ---- Tarjeta de propiedad: layout no publicado -> extracción por patrones ----
    function parseTarjeta(texto) {
        var t = (texto || '').toUpperCase();
        var placa = (t.match(/\b([A-Z]{3}[ -]?[0-9]{2,3}[A-Z]?)\b/) || [])[1];
        var anio = (t.match(/\b(19[8-9][0-9]|20[0-4][0-9])\b/) || [])[1];
        if (placa) placa = placa.replace(/[ -]/g, '');
        return { ok: !!placa, placa: placa || '', anio: anio || '', crudo: texto };
    }

    // ---- Decodificar una imagen (File) a {text, bytes} ----
    async function detectarNativo(file) {
        if (!('BarcodeDetector' in window)) return null;
        try {
            var soportados = await window.BarcodeDetector.getSupportedFormats();
            if (soportados.indexOf('pdf417') < 0) return null;
            var det = new window.BarcodeDetector({ formats: ['pdf417'] });
            var bmp = await createImageBitmap(file);
            var codes = await det.detect(bmp);
            if (codes && codes.length) {
                return { text: codes[0].rawValue, bytes: strToBytes(codes[0].rawValue) };
            }
        } catch (e) {}
        return null;
    }

    var zxingCargado = null;
    function cargarZxing() {
        if (zxingCargado) return zxingCargado;
        zxingCargado = new Promise(function (resolve) {
            if (window.ZXingBrowser) return resolve(window.ZXingBrowser);
            var s = document.createElement('script');
            s.src = 'https://unpkg.com/@zxing/browser@0.1.5/umd/index.min.js';
            s.onload = function () { resolve(window.ZXingBrowser || null); };
            s.onerror = function () { resolve(null); };
            document.head.appendChild(s);
        });
        return zxingCargado;
    }

    async function detectarZxing(file) {
        try {
            var ZX = await cargarZxing();
            if (!ZX || !ZX.BrowserPDF417Reader) return null;
            var url = URL.createObjectURL(file);
            try {
                var reader = new ZX.BrowserPDF417Reader();
                var res = await reader.decodeFromImageUrl(url);
                if (!res) return null;
                var text = res.getText ? res.getText() : (res.text || '');
                var raw = null;
                try { raw = res.getRawBytes && res.getRawBytes(); } catch (e) {}
                var bytes = raw ? Uint8Array.from(raw) : strToBytes(text);
                return { text: text, bytes: bytes };
            } finally {
                URL.revokeObjectURL(url);
            }
        } catch (e) {
            return null;
        }
    }

    async function decodificar(file) {
        var r = await detectarNativo(file);
        if (!r) r = await detectarZxing(file);
        return r;
    }

    // ---- API pública ----
    window.Escaner = {
        soportado: function () {
            return ('BarcodeDetector' in window) || true; // zxing como respaldo siempre disponible
        },
        async leer(kind, file) {
            var d = await decodificar(file);
            window.__ultimoEscaneo = d ? d.text : null;
            if (!d) return { ok: false, motivo: 'no-lectura' };
            if (kind === 'cedula') {
                var c = parseCedula(d.bytes);
                return c || { ok: false, motivo: 'no-parse', crudo: d.text };
            }
            return parseTarjeta(d.text);
        },
    };

    // ---- Auto-cableado de botones [data-escaner] ----
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest('[data-escaner]');
        if (!btn) return;
        ev.preventDefault();
        var input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.setAttribute('capture', 'environment');
        input.style.display = 'none';
        document.body.appendChild(input);
        input.addEventListener('change', async function () {
            var file = input.files && input.files[0];
            document.body.removeChild(input);
            if (!file) return;
            var kind = btn.getAttribute('data-escaner');
            var box = document.getElementById(btn.getAttribute('data-result') || '');
            if (box) box.textContent = 'Leyendo código…';
            var res = await window.Escaner.leer(kind, file);
            if (kind === 'cedula') aplicarCedula(res, box);
            else aplicarTarjeta(res, box);
        });
        input.click();
    });

    function aplicarCedula(res, box) {
        if (res && res.ok) {
            if (typeof window.toggleCli === 'function') window.toggleCli('nu');
            setVal('nuevo_nombre', res.nombre);
            setVal('nuevo_cedula', res.cedula);
            if (box) box.innerHTML = '✓ Leído: <b>' + res.nombre + '</b> · C.C. ' + res.cedula + ' <span class="text-zinc-400">(revísalo)</span>';
        } else if (box) {
            box.innerHTML = 'No se pudo leer el código. Escribe los datos a mano. ' + verCrudo();
        }
    }

    function aplicarTarjeta(res, box) {
        if (res && res.ok) {
            if (typeof window.llenarDesdeTarjeta === 'function') window.llenarDesdeTarjeta(res);
            if (box) box.innerHTML = '✓ Placa: <b>' + res.placa + '</b>' + (res.anio ? ' · Modelo ' + res.anio : '') + ' <span class="text-zinc-400">(completa lo demás)</span>';
        } else if (box) {
            box.innerHTML = 'No se pudo leer el código. Escribe los datos a mano. ' + verCrudo();
        }
    }

    function verCrudo() {
        return '<a href="#" onclick="alert(window.__ultimoEscaneo||\'(sin datos)\');return false;" class="font-semibold text-emerald-600">ver datos leídos</a>';
    }

    function setVal(name, val) {
        var el = document.querySelector('[name="' + name + '"]');
        if (el && val) el.value = val;
    }
})();
