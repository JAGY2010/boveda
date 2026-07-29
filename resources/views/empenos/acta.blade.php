@php
    $neg = $empeno->negocio;
    $cli = $empeno->cliente;
    $parts = explode(' ', trim($neg->nombre), 2);
    $w1 = \Illuminate\Support\Str::upper($parts[0]);
    $w2 = isset($parts[1]) ? \Illuminate\Support\Str::upper($parts[1]) : '';

    $attrs = $empeno->atributos ?? [];
    $get = fn ($k) => $attrs[$k] ?? null;
    $marcaModelo = collect(['marca', 'modelo', 'cilindraje', 'pulgadas', 'capacidad', 'material', 'tipo'])->map($get)->filter()->implode(' ');
    $anio = $get('anio');
    $serie = collect(['placa', 'imei'])->map(fn ($k) => ($v = $get($k)) ? strtoupper($k).' '.$v : null)->filter()->implode(' · ');
    if ($empeno->serial) {
        $serie = trim($serie.($serie ? ' · ' : '').$empeno->serial);
    }

    // ---- Historia del artículo ----
    $principal = (int) $empeno->principal;
    $abonos = $empeno->totalAbonos();
    $interesPagos = $empeno->totalInteresPagos();
    $interesRetiro = $empeno->interesRetiro();
    $totalIntereses = $empeno->totalIntereses();
    $valorRetiro = (int) $empeno->valor_retiro;
    $totalPagado = $empeno->totalPagado();
    $dias = $empeno->diasEnPrenda();
    $meses = intdiv($dias, 30);
    $restoDias = $dias % 30;
    $tiempo = $meses > 0
        ? $meses.' '.($meses === 1 ? 'mes' : 'meses').($restoDias > 0 ? ' y '.$restoDias.' '.($restoDias === 1 ? 'día' : 'días') : '')
        : $dias.' '.($dias === 1 ? 'día' : 'días');
    $fechaRetiro = $empeno->fecha_retiro ?: now();
    $totalLetras = \Illuminate\Support\Str::upper(numeroALetras($totalPagado)).' PESOS M/CTE';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acta de entrega No. {{ $empeno->numero }} — {{ $neg->nombre }}</title>
    <style>
        @page { size: letter portrait; margin: 0.4in 0.5in; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #71716f; font-family: Arial, Helvetica, sans-serif; color: #222; font-size: 10px; line-height: 1.38; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        .bar { width: 7.5in; max-width: 96%; margin: 14px auto; display: flex; gap: 10px; }
        .btn { font-size: 13px; font-weight: 600; padding: 9px 16px; border-radius: 8px; border: none; cursor: pointer; background: #0e5c43; color: #fff; text-decoration: none; }
        .btn.sec { background: #fff; color: #333; border: 1px solid #bbb; }

        .head { position: relative; text-align: center; border-bottom: 2.5px solid #0e5c43; padding-bottom: 6px; margin-bottom: 8px; min-height: 58px; }
        .logo { position: absolute; left: 0; top: 0; width: 58px; height: 56px; object-fit: contain; border: 1px solid #e3e3e3; border-radius: 6px; background: #fff; padding: 2px; }
        .corner { position: absolute; right: 0; top: 0; text-align: right; }
        .copia { font-size: 8.5px; color: #888; text-transform: uppercase; letter-spacing: .08em; }
        .no { font-size: 11px; margin-top: 2px; }
        .no b { color: #0e5c43; font-size: 16px; }
        .wm { font-size: 23px; font-weight: bold; letter-spacing: .01em; padding-top: 4px; }
        .wm .r { color: #b0281e; }
        .sub { font-size: 8.8px; color: #555; margin-top: 3px; }

        .banner { position: relative; background: #0e5c43; color: #fff; font-weight: bold; text-align: center; padding: 5px 96px; margin: 8px 0; font-size: 12px; letter-spacing: .04em; }
        .banner .amt { position: absolute; right: 7px; top: 50%; transform: translateY(-50%); background: #fff; color: #16130f; padding: 2px 10px; border-radius: 3px; font-size: 11px; }

        table { width: 100%; border-collapse: collapse; }
        .datos td { padding: 3px 4px; vertical-align: bottom; }
        .v { font-weight: bold; border-bottom: 1px solid #999; padding: 0 4px; display: inline-block; min-width: 60px; }

        .sec { font-weight: bold; font-size: 10.6px; margin: 9px 0 4px; color: #0e5c43; border-bottom: 1px solid #cfe0da; padding-bottom: 2px; }
        .sec .dim { font-weight: normal; color: #666; font-size: 8.6px; }

        .desc td { border: 1px solid #cfcfcf; padding: 3.5px 6px; }
        .desc b { color: #111; }

        p { margin: 0 0 5px; text-align: justify; }
        p b { color: #111; }

        /* Resumen económico */
        .hist th, .hist td { border: 1px solid #c9c9c9; padding: 3.5px 8px; }
        .hist th { background: #f1f5f4; text-align: left; font-weight: bold; width: 62%; }
        .hist td { text-align: right; font-weight: bold; font-variant-numeric: tabular-nums; }
        .hist .tot th { background: #0e5c43; color: #fff; font-size: 11.5px; }
        .hist .tot td { background: #0e5c43; color: #fff; font-size: 12.5px; }
        .hist .dimr { font-weight: normal; color: #666; font-size: 8.6px; }

        /* Detalle de pagos */
        .pg th, .pg td { border: 1px solid #bbb; padding: 3px 6px; text-align: center; }
        .pg .lh th { background: #f0eae9; font-weight: bold; }
        .pg td { font-variant-numeric: tabular-nums; }
        .pg .fin td { background: #f1f5f4; font-weight: bold; }

        .firmas { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-top: 14px; }
        .firmas .l { flex: 1; }
        .firmas .line { border-top: 1px solid #222; margin-top: 26px; padding-top: 3px; text-align: center; font-size: 9.2px; }
        .hu { text-align: center; }
        .hbox { width: 52px; height: 56px; border: 1.2px solid #222; border-radius: 3px; }
        .hlbl { font-size: 8px; margin-top: 2px; text-transform: uppercase; }

        .foot { margin-top: 9px; padding-top: 6px; border-top: 1px solid #ddd; font-size: 8px; color: #555; text-align: center; text-transform: uppercase; letter-spacing: .02em; line-height: 1.4; }
        .foot b { color: #0e5c43; }

        @media screen {
            body { padding: 16px 10px; }
            .sheet { width: 7.5in; padding: 0.4in 0.5in; margin: 0 auto 20px; background: #fff; box-shadow: 0 8px 30px rgba(0,0,0,.35); }
        }
        @media print {
            body { background: #fff; }
            .bar { display: none; }
            .sheet { page-break-after: always; }
            .sheet:last-child { page-break-after: auto; }
        }
    </style>
</head>
<body>
    <div class="bar">
        <button class="btn" onclick="window.print()">🖨 Imprimir / Guardar PDF</button>
        <a class="btn sec" href="{{ route('empenos.show', $empeno) }}">← Volver</a>
    </div>

    @foreach (['Copia comercio', 'Copia cliente'] as $copia)
        <div class="sheet">
            <div class="head">
                @if ($neg->logo_data || $neg->logo_path)
                    <img class="logo" src="{{ $neg->logo_data ?: asset($neg->logo_path) }}" alt="">
                @endif
                <div class="corner">
                    <div class="copia">{{ $copia }}</div>
                    <div class="no">Contrato No. <b>{{ $empeno->numero }}</b></div>
                </div>
                <div class="wm"><span class="r">{{ $w1 }}</span> {{ $w2 }}</div>
                <div class="sub">
                    {{ $neg->representante }} @if($neg->nit) · NIT {{ $neg->nit }} @endif @if($neg->direccion) · {{ $neg->direccion }} @endif @if($neg->ciudad) · {{ $neg->ciudad }} @endif @if($neg->telefono) · Cel/WhatsApp: {{ $neg->telefono }} @endif
                </div>
            </div>

            <div class="banner">
                ACTA DE ENTREGA Y PAZ Y SALVO
                <span class="amt">{{ $fechaRetiro->format('d/m/Y') }}</span>
            </div>

            <table class="datos">
                <tr>
                    <td>Señor(a): <span class="v">{{ $cli->nombre }}</span></td>
                    <td>C.C. No. <span class="v">{{ $cli->cedula }}</span></td>
                </tr>
                <tr>
                    <td>Dirección: <span class="v">{{ $cli->direccion }}</span></td>
                    <td>Cel: <span class="v">{{ $cli->tel }}</span></td>
                </tr>
            </table>

            <div class="sec">ARTÍCULO ENTREGADO</div>
            <table class="desc">
                <tr>
                    <td style="width:50%">Artículo: <b>{{ $empeno->articulo }}</b></td>
                    <td>Marca / Modelo / Año: <b>{{ $marcaModelo ?: '—' }}{{ $anio ? ' - Mod. '.$anio : '' }}</b></td>
                </tr>
                <tr>
                    <td colspan="2">Serie / Placa / IMEI / Motor / Chasis: <b>{{ $serie ?: '—' }}</b></td>
                </tr>
                <tr>
                    <td>Color: <b>{{ $empeno->color ?: '—' }}</b></td>
                    <td>Estado y observaciones: <b>{{ $empeno->observaciones ?: '—' }}</b></td>
                </tr>
            </table>

            <div class="sec">HISTORIA DEL ARTÍCULO <span class="dim">(resumen de toda la operación)</span></div>
            <table class="hist">
                <tr>
                    <th>Fecha en que dejó el artículo <span class="dimr">— inicio del contrato</span></th>
                    <td>{{ $empeno->inicio->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <th>Fecha en que retiró el artículo <span class="dimr">— cierre</span></th>
                    <td>{{ $fechaRetiro->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <th>Tiempo total que estuvo en garantía</th>
                    <td>{{ $tiempo }}</td>
                </tr>
                <tr>
                    <th>Valor que recibió por el artículo <span class="dimr">— precio de la venta</span></th>
                    <td>{{ cop($principal) }}</td>
                </tr>
                <tr>
                    <th>Intereses pagados durante el contrato <span class="dimr">— {{ $empeno->pagos->count() }} pago(s)</span></th>
                    <td>{{ cop($interesPagos) }}</td>
                </tr>
                <tr>
                    <th>Abonos a capital</th>
                    <td>{{ cop($abonos) }}</td>
                </tr>
                <tr>
                    <th>Pago final para retirar el artículo <span class="dimr">— incluye {{ cop($interesRetiro) }} de interés</span></th>
                    <td>{{ cop($valorRetiro) }}</td>
                </tr>
                <tr class="tot">
                    <th>TOTAL PAGADO AL COMERCIO</th>
                    <td>{{ cop($totalPagado) }}</td>
                </tr>
            </table>
            <p style="margin-top:5px; font-size:9.2px; color:#555">
                Es decir: recibió <b>{{ cop($principal) }}</b> y pagó en total <b>{{ cop($totalPagado) }}</b>;
                el costo del servicio fue de <b>{{ cop($totalIntereses) }}</b> en intereses.
                Son <b>{{ $totalLetras }}</b>.
            </p>

            @if ($empeno->pagos->count())
                <div class="sec">DETALLE DE LOS PAGOS</div>
                <table class="pg">
                    <tr class="lh">
                        <th>#</th><th>Fecha</th><th>Concepto</th><th>Interés</th><th>Abono a capital</th><th>Total del pago</th>
                    </tr>
                    @foreach ($empeno->pagos as $i => $p)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($p->fecha)->format('d/m/Y') }}</td>
                            <td>{{ $p->tipo }}</td>
                            <td>{{ cop((int) $p->interes) }}</td>
                            <td>{{ cop((int) $p->abono) }}</td>
                            <td>{{ cop((int) $p->interes + (int) $p->abono) }}</td>
                        </tr>
                    @endforeach
                    <tr class="fin">
                        <td>{{ $empeno->pagos->count() + 1 }}</td>
                        <td>{{ $fechaRetiro->format('d/m/Y') }}</td>
                        <td>retiro final</td>
                        <td>{{ cop($interesRetiro) }}</td>
                        <td>{{ cop($principal - $abonos) }}</td>
                        <td>{{ cop($valorRetiro) }}</td>
                    </tr>
                </table>
            @endif

            <div class="sec">CONSTANCIA</div>
            <p><b>PRIMERA — ENTREGA.</b> {{ $neg->nombre }} hace <b>entrega real y material</b> del artículo descrito en la presente acta al señor(a) <b>{{ $cli->nombre }}</b>, identificado(a) con cédula de ciudadanía No. <b>{{ $cli->cedula }}</b>, quien declara <b>recibirlo a entera satisfacción</b>, en el estado en que lo entregó y sin observación alguna.</p>

            <p><b>SEGUNDA — PACTO DE RETROVENTA EJERCIDO.</b> El vendedor ejerció en debida forma la facultad de retroventa pactada en el contrato de compraventa No. {{ $empeno->numero }} del {{ $empeno->inicio->format('d/m/Y') }} (Art. 1939 y ss. del Código Civil), pagando la totalidad de lo convenido. En consecuencia, se resuelve la venta y la propiedad del bien <b>retorna al vendedor</b>.</p>

            <p><b>TERCERA — PAZ Y SALVO.</b> Las partes se declaran a <b>PAZ Y SALVO</b> por todo concepto derivado de esta operación. El cliente <b>nada adeuda</b> al comercio y el comercio <b>nada adeuda</b> al cliente, quedando extinguidas todas las obligaciones recíprocas. El cliente renuncia a cualquier reclamación posterior por el bien aquí entregado.</p>

            <p style="text-align:center; margin-top:8px">Para constancia se firma en {{ $neg->ciudad }}, el {{ $fechaRetiro->isoFormat('D [de] MMMM [de] YYYY') }}.</p>

            <div class="firmas">
                <div class="l"><div class="line">Firma quien entrega · {{ $neg->nombre }}</div></div>
                <div class="l"><div class="line">Firma quien recibe · C.C. {{ $cli->cedula }}</div></div>
                <div class="hu"><div class="hbox"></div><div class="hlbl">Huella cliente</div></div>
            </div>

            <div class="foot">
                Este documento acredita la entrega del artículo y la cancelación total de la obligación · Consérvelo · <b>Contrato No. {{ $empeno->numero }} — cerrado el {{ $fechaRetiro->format('d/m/Y') }}</b>
            </div>
        </div>
    @endforeach
</body>
</html>
