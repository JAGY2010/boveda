<x-layouts::app :title="'Nuevo empeño'">
    <div class="mx-auto w-full max-w-5xl">
        <a href="{{ route('empenos.index') }}" wire:navigate class="mb-3 inline-block text-sm text-zinc-500 hover:text-emerald-600">← Empeños</a>
        <x-flash />

        <h1 class="mb-1 text-2xl font-semibold text-zinc-900 dark:text-white">Nuevo empeño</h1>
        <p class="mb-5 text-sm text-zinc-500">Cliente y artículo en un solo paso. El % y el plazo los decides tú.</p>

        <div class="grid gap-5 lg:grid-cols-3">
            <form method="POST" action="{{ route('empenos.store') }}" id="empForm"
                  class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm lg:col-span-2 dark:border-zinc-700 dark:bg-zinc-900">
                @csrf

                {{-- Cliente --}}
                <p class="text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-500">Cliente</p>
                <div class="inline-flex rounded-lg border border-zinc-200 bg-zinc-100 p-1 dark:border-zinc-700 dark:bg-zinc-800">
                    <button type="button" id="tab_ex" onclick="toggleCli('ex')" class="rounded-md px-3 py-1.5 text-sm font-semibold">Existente</button>
                    <button type="button" id="tab_nu" onclick="toggleCli('nu')" class="rounded-md px-3 py-1.5 text-sm font-semibold">Nuevo</button>
                </div>

                <div id="cli_ex">
                    @if ($clientes->count())
                        <select name="cliente_id" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                            @foreach ($clientes as $c)
                                <option value="{{ $c->id }}">{{ $c->nombre }} — {{ $c->cedula }}</option>
                            @endforeach
                        </select>
                    @else
                        <p class="text-sm text-zinc-500">No hay clientes aún. Usa la pestaña “Nuevo”.</p>
                    @endif
                </div>

                <div id="cli_nu" class="hidden space-y-3">
                    <input name="nuevo_nombre" placeholder="Nombre completo" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                    <div class="grid gap-3 sm:grid-cols-2">
                        <input name="nuevo_cedula" placeholder="Cédula" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                        <input name="nuevo_tel" placeholder="Celular" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                    </div>
                    <input name="nuevo_direccion" placeholder="Dirección" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>

                {{-- Artículo --}}
                <p class="pt-2 text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-500">Artículo</p>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Categoría</label>
                    <select name="categoria" id="e_cat" onchange="onCatChange()" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"></select>
                    <input id="e_cat_free" name="categoria" type="text" placeholder="Escribe la categoría" class="mt-2 hidden w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" disabled>
                    <button type="button" id="e_cat_back" onclick="catBack()" class="mt-1 hidden text-xs font-semibold text-emerald-600">↺ elegir de la lista</button>
                </div>
                <div id="attrs" class="space-y-3"></div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Serial / IMEI / Motor (opcional)</label>
                    <input name="serial" placeholder="Para verificar y proteger el negocio" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>

                {{-- Sugerido --}}
                <div id="sugerido" class="rounded-xl border border-emerald-600/40 bg-emerald-500/10 px-4 py-3 text-center"></div>

                {{-- Préstamo --}}
                <p class="text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-500">Préstamo</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Valor a prestar</label>
                        <input type="number" name="principal" id="e_val" value="{{ old('principal') }}" required class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Interés mensual (%)</label>
                        <input type="number" step="0.01" name="pct" value="{{ old('pct', $negocio->pct_default) }}" required class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Plazo (meses)</label>
                    <input type="number" name="plazo" value="{{ old('plazo', $negocio->plazo_default) }}" required class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>

                <div class="flex gap-3 pt-1">
                    <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Registrar empeño</button>
                    <a href="{{ route('empenos.index') }}" wire:navigate class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-600 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancelar</a>
                </div>
            </form>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="mb-3 text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-500">Cómo funcionará</p>
                <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                    <li>Sale de <b>Caja</b> el valor prestado.</li>
                    <li>Cada mes genera el interés (tu ganancia).</li>
                    <li>Cada cuota pagada corre el vencimiento <b>+1 mes</b>.</li>
                    <li>A los {{ $negocio->plazo_default }} meses sin pagar, el artículo es tuyo.</li>
                </ul>
                <div class="mt-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800/60">
                    ¿No aparece en la lista? En cualquier campo elige <b>“✏️ Otra (escribir)”</b> y escríbelo a mano.
                </div>
            </div>
        </div>
    </div>

    <script>window.NEG = { ltv: {{ (int) $negocio->ltv_default }} };</script>
    @verbatim
    <script>
    (function () {
        var OTRO = '__otro__';

        var CATS = {
            "Moto": [
                { k: "marca", label: "Marca", opts: ["Yamaha", "Honda", "Bajaj", "AKT", "Suzuki", "Kawasaki", "Hero", "KTM", "TVS", "Auteco"] },
                { k: "cilindraje", label: "Cilindraje (cc)", opts: ["100", "110", "125", "150", "180", "200", "250", "300+"] },
                { k: "modelo", label: "Referencia / Línea (ej: Boxer, NKD, Pulsar)" },
                { k: "anio", label: "Modelo (año)", tipo: "year" },
                { k: "placa", label: "Placa" }
            ],
            "Celular": [
                { k: "marca", label: "Marca", opts: ["Apple (iPhone)", "Samsung", "Xiaomi", "Motorola", "Huawei", "Oppo", "Realme", "Honor"] },
                { k: "modelo", label: "Modelo" },
                { k: "capacidad", label: "Capacidad", opts: ["32 GB", "64 GB", "128 GB", "256 GB", "512 GB"] },
                { k: "anio", label: "Año", tipo: "year" },
                { k: "imei", label: "IMEI" }
            ],
            "Televisor": [
                { k: "marca", label: "Marca", opts: ["LG", "Samsung", "Sony", "Kalley", "Challenger", "Hyundai", "TCL"] },
                { k: "pulgadas", label: "Pulgadas", opts: ["24", "32", "40", "43", "50", "55", "65", "75"] },
                { k: "resolucion", label: "Resolución", opts: ["HD", "Full HD", "4K UHD"] },
                { k: "anio", label: "Año", tipo: "year" }
            ],
            "Computador": [
                { k: "marca", label: "Marca", opts: ["Apple", "HP", "Lenovo", "Dell", "Asus", "Acer"] },
                { k: "tipo", label: "Tipo", opts: ["Portátil", "Escritorio"] },
                { k: "ram", label: "RAM", opts: ["4 GB", "8 GB", "16 GB", "32 GB"] },
                { k: "almacenamiento", label: "Almacenamiento", opts: ["128 GB", "256 GB", "512 GB", "1 TB"] },
                { k: "anio", label: "Año", tipo: "year" }
            ],
            "Electrodoméstico": [
                { k: "tipo", label: "Tipo", opts: ["Nevera", "Lavadora", "Microondas", "Estufa", "Licuadora"] },
                { k: "marca", label: "Marca", opts: ["LG", "Samsung", "Haceb", "Mabe", "Whirlpool", "Kalley"] },
                { k: "anio", label: "Año", tipo: "year" }
            ],
            "Joya / Oro": [
                { k: "material", label: "Material", opts: ["Oro 18k", "Oro 14k", "Oro 10k", "Plata"] },
                { k: "peso", label: "Peso (gramos)", tipo: "number" }
            ],
            "Herramienta": [
                { k: "tipo", label: "Tipo" },
                { k: "marca", label: "Marca", opts: ["Bosch", "DeWalt", "Makita", "Truper", "Stanley"] },
                { k: "anio", label: "Año", tipo: "year" }
            ],
            "Otro": [{ k: "desc", label: "Descripción del artículo" }],
            "__custom__": [
                { k: "marca", label: "Marca" },
                { k: "modelo", label: "Modelo / Referencia" },
                { k: "anio", label: "Año", tipo: "year" }
            ]
        };

        var LTV = (window.NEG && window.NEG.ltv) || 50;
        var input = 'w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white';

        function cop(n) {
            n = Math.round(n || 0);
            return (n < 0 ? '-$' : '$') + Math.abs(n).toLocaleString('es-CO');
        }

        // Un <select> con opción "✏️ Otra (escribir)" que revela un campo de texto libre.
        function combo(f, optsHtml) {
            var id = 'at_' + f.k;
            var sel = '<select id="' + id + '" name="atributos[' + f.k + ']" onchange="onCombo(\'' + f.k + '\')" class="' + input + '">' +
                '<option value="">— elegir —</option>' + optsHtml +
                '<option value="' + OTRO + '">✏️ Otra (escribir)</option></select>';
            var free = '<input id="' + id + '_free" name="atributos[' + f.k + ']" type="text" oninput="recalcSug()" placeholder="Escribe aquí" class="' + input + ' mt-2 hidden" disabled>';
            var back = '<button type="button" id="' + id + '_back" onclick="comboBack(\'' + f.k + '\')" class="mt-1 hidden text-xs font-semibold text-emerald-600">↺ elegir de la lista</button>';
            return sel + free + back;
        }

        function field(f) {
            var inner;
            if (f.tipo === 'year') {
                var yn = new Date().getFullYear(), o = '';
                for (var y = yn; y >= yn - 25; y--) o += '<option>' + y + '</option>';
                inner = combo(f, o);
            } else if (f.opts) {
                inner = combo(f, f.opts.map(function (op) { return '<option>' + op + '</option>'; }).join(''));
            } else {
                inner = '<input id="at_' + f.k + '" name="atributos[' + f.k + ']" type="' + (f.tipo === 'number' ? 'number' : 'text') + '" oninput="recalcSug()" placeholder="' + f.label + '" class="' + input + '">';
            }
            return '<div><label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">' + f.label + '</label>' + inner + '</div>';
        }

        window.onCombo = function (k) {
            var sel = document.getElementById('at_' + k), free = document.getElementById('at_' + k + '_free'), back = document.getElementById('at_' + k + '_back');
            if (sel.value === OTRO) {
                sel.classList.add('hidden'); sel.disabled = true;
                free.classList.remove('hidden'); free.disabled = false; free.focus();
                if (back) back.classList.remove('hidden');
            }
            recalcSug();
        };

        window.comboBack = function (k) {
            var sel = document.getElementById('at_' + k), free = document.getElementById('at_' + k + '_free'), back = document.getElementById('at_' + k + '_back');
            sel.classList.remove('hidden'); sel.disabled = false; sel.value = '';
            free.classList.add('hidden'); free.disabled = true; free.value = '';
            if (back) back.classList.add('hidden');
            recalcSug();
        };

        function currentCat() {
            var sel = document.getElementById('e_cat');
            return (sel.disabled || sel.value === OTRO) ? '__custom__' : sel.value;
        }

        function attrVal(k) {
            var sel = document.getElementById('at_' + k);
            if (!sel) return '';
            if (sel.disabled) { var f = document.getElementById('at_' + k + '_free'); return f ? f.value : ''; }
            return sel.value === OTRO ? '' : sel.value;
        }

        function readAttrs(cat) {
            var a = {};
            (CATS[cat] || []).forEach(function (f) {
                var v = attrVal(f.k);
                if (v) a[f.k] = v;
            });
            return a;
        }

        function renderAttrsFor(cat) {
            var rows = (CATS[cat] || []).map(field);
            var html = '';
            for (var i = 0; i < rows.length; i += 2) {
                html += (i + 1 < rows.length)
                    ? '<div class="grid gap-3 sm:grid-cols-2">' + rows[i] + rows[i + 1] + '</div>'
                    : rows[i];
            }
            document.getElementById('attrs').innerHTML = html;
            recalcSug();
        }

        window.renderAttrs = function () { renderAttrsFor(currentCat()); };

        window.onCatChange = function () {
            var sel = document.getElementById('e_cat');
            if (sel.value === OTRO) {
                sel.classList.add('hidden'); sel.disabled = true;
                var f = document.getElementById('e_cat_free'); f.classList.remove('hidden'); f.disabled = false; f.focus();
                document.getElementById('e_cat_back').classList.remove('hidden');
                renderAttrsFor('__custom__');
            } else {
                renderAttrsFor(sel.value);
            }
        };

        window.catBack = function () {
            var sel = document.getElementById('e_cat');
            sel.classList.remove('hidden'); sel.disabled = false;
            var f = document.getElementById('e_cat_free'); f.classList.add('hidden'); f.disabled = true; f.value = '';
            document.getElementById('e_cat_back').classList.add('hidden');
            renderAttrsFor(sel.value);
        };

        function depFactor(cat, anio) {
            if (!anio) return 1;
            var rates = { "Moto": [0.08, 0.35], "Celular": [0.22, 0.15], "Computador": [0.20, 0.15], "Televisor": [0.15, 0.20], "Electrodoméstico": [0.12, 0.25], "Herramienta": [0.10, 0.30] };
            var r = rates[cat];
            if (!r) return 1;
            var y = parseInt(anio, 10);
            if (isNaN(y)) return r[1];
            var age = Math.max(0, new Date().getFullYear() - y);
            return Math.max(r[1], 1 - r[0] * age);
        }

        function estimarValor(cat, a) {
            var v = 0;
            if (cat === "Moto") { var b = { "100": 3500000, "110": 4000000, "125": 5000000, "150": 6500000, "180": 8000000, "200": 9500000, "250": 12000000, "300+": 15000000 }; v = b[a.cilindraje] || 0; }
            else if (cat === "Televisor") { var p = { "24": 400000, "32": 700000, "40": 1100000, "43": 1400000, "50": 1900000, "55": 2400000, "65": 3300000, "75": 4600000 }; var r = { "HD": .85, "Full HD": 1, "4K UHD": 1.3 }; v = (p[a.pulgadas] || 0) * (r[a.resolucion] || 1); }
            else if (cat === "Celular") { var m = { "Apple (iPhone)": 2600000, "Samsung": 1600000, "Xiaomi": 950000, "Motorola": 850000, "Huawei": 900000, "Oppo": 800000, "Realme": 800000, "Honor": 800000 }; var cp = { "32 GB": .85, "64 GB": .95, "128 GB": 1, "256 GB": 1.15, "512 GB": 1.3 }; v = (m[a.marca] || 0) * (cp[a.capacidad] || 1); }
            else if (cat === "Computador") { var mm = { "Apple": 3500000, "HP": 1600000, "Lenovo": 1600000, "Dell": 1700000, "Asus": 1600000, "Acer": 1400000 }; v = (mm[a.marca] || 0) * (a.tipo === "Escritorio" ? .9 : 1); }
            else if (cat === "Joya / Oro") { var g = { "Oro 18k": 210000, "Oro 14k": 160000, "Oro 10k": 115000, "Plata": 3500 }; v = (g[a.material] || 0) * (parseFloat(a.peso) || 0); }
            return Math.round(v * depFactor(cat, a.anio));
        }

        window.recalcSug = function () {
            var cat = currentCat();
            var est = estimarValor(cat, readAttrs(cat));
            var box = document.getElementById('sugerido');
            if (!est) {
                box.innerHTML = '<div class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Préstamo sugerido</div><div class="text-zinc-500">Completa los datos del artículo</div>';
                return;
            }
            var sug = Math.round(est * LTV / 100);
            box.innerHTML = '<div class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Préstamo sugerido (aprox.)</div>' +
                '<div class="my-0.5 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-white">' + cop(sug) + '</div>' +
                '<div class="text-xs text-zinc-500">valor estimado ' + cop(est) + ' × ' + LTV + '% · <a href="#" onclick="usarSug(' + sug + ');return false;" class="font-bold text-emerald-600">Usar este valor</a></div>';
        };

        window.usarSug = function (v) { document.getElementById('e_val').value = v; };

        window.toggleCli = function (mode) {
            var ex = document.getElementById('cli_ex'), nu = document.getElementById('cli_nu');
            var tex = document.getElementById('tab_ex'), tnu = document.getElementById('tab_nu');
            var on = 'bg-emerald-600 text-white', off = 'text-zinc-500';
            if (mode === 'nu') {
                ex.classList.add('hidden'); nu.classList.remove('hidden');
                tnu.className = 'rounded-md px-3 py-1.5 text-sm font-semibold ' + on;
                tex.className = 'rounded-md px-3 py-1.5 text-sm font-semibold ' + off;
            } else {
                nu.classList.add('hidden'); ex.classList.remove('hidden');
                tex.className = 'rounded-md px-3 py-1.5 text-sm font-semibold ' + on;
                tnu.className = 'rounded-md px-3 py-1.5 text-sm font-semibold ' + off;
                document.querySelector('[name="nuevo_nombre"]').value = '';
            }
        };

        function init() {
            var sel = document.getElementById('e_cat');
            if (!sel) return;
            sel.innerHTML = Object.keys(CATS).filter(function (k) { return k !== '__custom__'; })
                .map(function (k) { return '<option value="' + k + '">' + k + '</option>'; }).join('') +
                '<option value="' + OTRO + '">✏️ Otra categoría (escribir)</option>';
            renderAttrsFor(sel.value);
            toggleCli(document.querySelector('#cli_ex select') ? 'ex' : 'nu');
        }
        init();
    })();
    </script>
    @endverbatim
</x-layouts::app>
