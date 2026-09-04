{{--
    Bienvenida: lo primero que ve quien abre el enlace sin sesion.

    Antes "/" mandaba directo al tablero, que a su vez rebotaba al formulario
    de entrada: quien no conociera Boveda veia un cuadro de correo y clave
    —en ingles, ademas— y se iba sin saber de que se trata.

    Nadie que ya trabaje aqui pasa por esta pantalla: con sesion abierta "/"
    sigue llevando derecho al tablero, y /login existe igual para quien lo
    tenga guardado.
--}}
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    @include('partials.head')
    <meta name="theme-color" content="#0e5c43">
    <meta name="description" content="Bóveda — sistema para compraventas y prenderías: empeños, pagos, inventario, separados y contabilidad del local.">
</head>
<body class="min-h-screen bg-zinc-900 text-zinc-100 antialiased">

@php
    $modulos = [
        ['icono' => '📊', 'titulo' => 'Tablero',
         'texto' => 'El estado del local de un vistazo: empeños activos, lo que vence esta semana, capital en la calle y caja del día. Es la pantalla con la que se abre por la mañana.'],
        ['icono' => '👥', 'titulo' => 'Clientes',
         'texto' => 'Ficha de cada persona con su documento y su historial: qué empeñó, qué pagó y qué retiró. Sin volver a pedirle los datos en cada visita.'],
        ['icono' => '💍', 'titulo' => 'Empeños',
         'texto' => 'El ciclo completo: contrato numerado, abonos parciales, renovación, retiro de la prenda o pérdida por vencimiento. Cada movimiento deja su recibo, y el contrato y el acta salen imprimibles.'],
        ['icono' => '📦', 'titulo' => 'Ventas e inventario',
         'texto' => 'La prenda que se perdió entra al inventario y se vende. También se compra mercancía directa y se manejan separados: el cliente abona hasta completar y se le entrega.'],
        ['icono' => '🧮', 'titulo' => 'Contabilidad',
         'texto' => 'Capital que se mete al local, gastos del día y cuánto hay realmente disponible para prestar. Sin esto, la caja llena engaña: buena parte ya está comprometida en la calle.'],
        ['icono' => '📈', 'titulo' => 'Reporte y consolidado',
         'texto' => 'Reporte del local por período, y consolidado de todos los locales para el dueño que tiene más de uno. Ingresos por intereses, ventas y saldo real.'],
        ['icono' => '🧑‍💼', 'titulo' => 'Empleados y permisos',
         'texto' => 'El dueño crea a sus empleados y decide qué puede tocar cada quien. Un empleado no ve la contabilidad ni borra un empeño.'],
        ['icono' => '🗑️', 'titulo' => 'Historial de eliminados',
         'texto' => 'Nada desaparece en silencio: un empeño borrado queda registrado con quién lo borró y cuándo. En un negocio de plata prestada, eso no es opcional.'],
        ['icono' => '🏬', 'titulo' => 'Multi-local y suscripción',
         'texto' => 'Cada compraventa ve solo lo suyo. El administrador da de alta locales, renueva o suspende la suscripción, y el local vencido queda bloqueado sin perder un solo dato.'],
    ];
    $stack = ['Laravel 13', 'Livewire 4', 'PHP 8.4', 'Tailwind 4', 'PostgreSQL', 'PWA', 'Railway'];
@endphp

<div class="mx-auto max-w-5xl px-5 pb-14">

    <header class="flex items-center justify-between py-5">
        <span class="flex items-center gap-2 text-base font-extrabold">
            <span class="text-xl">🔐</span> Bóveda
        </span>
        <a href="{{ route('login') }}"
           class="rounded-full border border-zinc-600 px-5 py-2 text-sm font-semibold text-zinc-100 transition hover:border-emerald-500 hover:bg-emerald-500/10">
            Ingresar
        </a>
    </header>

    <section class="mx-auto max-w-2xl py-12 text-center">
        <p class="mb-4 inline-block rounded-full border border-emerald-500/40 bg-emerald-500/10 px-3 py-1.5 text-xs font-bold uppercase tracking-widest text-emerald-400">
            Sistema para compraventas y prenderías
        </p>
        <h1 class="text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl">
            La plata que está en la calle,<br>siempre a la vista.
        </h1>
        <p class="mx-auto mt-5 max-w-xl text-base leading-relaxed text-zinc-400">
            Empeños con su contrato, abonos, vencimientos, inventario, separados y
            contabilidad del local. Para saber en cualquier momento cuánto se prestó,
            cuánto volvió y cuánto queda para prestar.
        </p>

        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('login') }}"
               class="rounded-xl bg-emerald-600 px-8 py-3.5 text-base font-bold text-white shadow-lg transition hover:bg-emerald-500">
                Entrar a mi local
            </a>
        </div>

        <ul class="mt-9 flex flex-wrap justify-center gap-3">
            @foreach ([['9', 'módulos'], ['∞', 'locales'], ['0', 'borrados sin rastro']] as [$n, $t])
                <li class="min-w-[9rem] rounded-xl border border-zinc-700 bg-zinc-800/60 px-6 py-3.5">
                    <b class="block text-2xl font-extrabold text-emerald-400">{{ $n }}</b>
                    <span class="mt-0.5 block text-[11px] font-bold uppercase tracking-wider text-zinc-400">{{ $t }}</span>
                </li>
            @endforeach
        </ul>
    </section>

    <section>
        <h2 class="mb-6 text-center text-2xl font-extrabold tracking-tight">Qué hace cada parte</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($modulos as $m)
                <article class="rounded-2xl border border-zinc-700 bg-zinc-800/60 p-5 transition hover:-translate-y-0.5 hover:border-zinc-500">
                    <span class="mb-3 grid h-11 w-11 place-items-center rounded-xl bg-emerald-500/15 text-xl">{{ $m['icono'] }}</span>
                    <h3 class="mb-1.5 text-base font-bold">{{ $m['titulo'] }}</h3>
                    <p class="text-sm leading-relaxed text-zinc-400">{{ $m['texto'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <footer class="mt-12 border-t border-zinc-700 pt-7 text-center">
        <div class="mb-4 flex flex-wrap justify-center gap-2">
            @foreach ($stack as $t)
                <span class="rounded-full border border-zinc-700 bg-zinc-800/60 px-3.5 py-1.5 text-xs font-bold text-zinc-400">{{ $t }}</span>
            @endforeach
        </div>
        <p class="text-sm text-zinc-500">
            Desarrollado por
            <a href="https://gaviriapp.dev" target="_blank" rel="noopener noreferrer"
               class="font-bold text-emerald-400 hover:underline">Julián Gaviria</a>
        </p>
    </footer>

</div>
</body>
</html>
