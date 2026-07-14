@php
    $neg = $empeno->negocio;
    $cli = $empeno->cliente;
    $parts = explode(' ', trim($neg->nombre), 2);
    $w1 = \Illuminate\Support\Str::upper($parts[0]);
    $w2 = isset($parts[1]) ? \Illuminate\Support\Str::upper($parts[1]) : '';
    $pct = rtrim(rtrim((string) $empeno->pct, '0'), '.');
    $enLetras = \Illuminate\Support\Str::upper(numeroALetras((int) $empeno->principal)).' PESOS M/CTE';

    $attrs = $empeno->atributos ?? [];
    $get = fn ($k) => $attrs[$k] ?? null;
    $marcaModelo = collect(['marca', 'modelo', 'cilindraje', 'pulgadas', 'capacidad', 'material', 'tipo'])->map($get)->filter()->implode(' ');
    $anio = $get('anio');
    $serie = collect(['placa', 'imei'])->map(fn ($k) => ($v = $get($k)) ? strtoupper($k).' '.$v : null)->filter()->implode(' · ');
    if ($empeno->serial) {
        $serie = trim($serie.($serie ? ' · ' : '').$empeno->serial);
    }

    $im = (int) round($empeno->principal * $empeno->pct / 100); // interés mensual del contrato
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contrato No. {{ $empeno->numero }} — {{ $neg->nombre }}</title>
    <style>
        @page { size: letter portrait; margin: 0.4in 0.5in; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #71716f; font-family: Arial, Helvetica, sans-serif; color: #222; font-size: 9.6px; line-height: 1.34; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        .bar { width: 7.5in; max-width: 96%; margin: 14px auto; display: flex; gap: 10px; }
        .btn { font-size: 13px; font-weight: 600; padding: 9px 16px; border-radius: 8px; border: none; cursor: pointer; background: #0e5c43; color: #fff; text-decoration: none; }
        .btn.sec { background: #fff; color: #333; border: 1px solid #bbb; }

        .head { position: relative; text-align: center; border-bottom: 2.5px solid #b0281e; padding-bottom: 6px; margin-bottom: 8px; min-height: 58px; }
        .logo { position: absolute; left: 0; top: 0; width: 60px; height: 58px; object-fit: contain; border: 1px solid #e3e3e3; border-radius: 6px; background: #fff; padding: 2px; }
        .corner { position: absolute; right: 0; top: 0; text-align: right; }
        .copia { font-size: 8.5px; color: #888; text-transform: uppercase; letter-spacing: .08em; }
        .no { font-size: 11px; margin-top: 2px; }
        .no b { color: #b0281e; font-size: 16px; }
        .wm { font-size: 22px; font-weight: bold; letter-spacing: .01em; padding-top: 4px; }
        .wm .r { color: #b0281e; }
        .sub { font-size: 8.6px; color: #555; margin-top: 3px; }

        .banner { position: relative; background: #b0281e; color: #fff; font-weight: bold; text-align: center; padding: 5px 92px; margin: 8px 0; font-size: 11px; letter-spacing: .03em; }
        .banner .amt { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); background: #fff; color: #16130f; padding: 2px 10px; border-radius: 3px; font-size: 11px; }

        table { width: 100%; border-collapse: collapse; }
        .datos td { padding: 2px 4px; vertical-align: bottom; }
        .v { font-weight: bold; border-bottom: 1px solid #999; padding: 0 3px; display: inline-block; min-width: 55px; }

        .sec { font-weight: bold; font-size: 9.8px; margin: 7px 0 3px; }
        .sec .dim { font-weight: normal; color: #666; font-size: 8.4px; }

        .desc { border: 1.4px solid #333; }
        .desc td { border: 1px solid #cfcfcf; padding: 3px 6px; }
        .desc b { color: #111; }

        p { margin: 0 0 5px; text-align: justify; }
        p b { color: #111; }

        .liq { text-align: center; }
        .liq th, .liq td { border: 1px solid #ccc; padding: 3px 4px; }
        .liq .lh th { background: #b0281e; color: #fff; font-weight: bold; }
        .liq td { font-weight: bold; }

        .ab th, .ab td { border: 1px solid #bbb; padding: 3px 4px; text-align: center; }
        .ab .lh th { background: #f0eae9; font-weight: bold; }
        .ab td { height: 17px; }

        .prorroga { margin: 6px 0; }
        .blank { display: inline-block; min-width: 95px; border-bottom: 1px solid #999; }

        .constancia { text-align: center; margin: 8px 0 4px; }

        .firmas { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-top: 10px; }
        .firmas .l { flex: 1; }
        .firmas .line { border-top: 1px solid #222; margin-top: 26px; padding-top: 3px; text-align: center; }
        .hu { text-align: center; }
        .hbox { width: 52px; height: 58px; border: 1.2px solid #222; border-radius: 3px; }
        .hlbl { font-size: 8px; margin-top: 2px; text-transform: uppercase; }

        .foot { margin-top: 9px; padding-top: 6px; border-top: 1px solid #ddd; font-size: 8px; color: #555; text-align: center; text-transform: uppercase; letter-spacing: .02em; line-height: 1.4; }
        .foot b { color: #b0281e; }
        .wa { text-align: center; font-size: 8.2px; color: #0e5c43; margin-top: 3px; }

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
                @if ($neg->logo_path)
                    <img class="logo" src="{{ asset($neg->logo_path) }}" alt="">
                @endif
                <div class="corner">
                    <div class="copia">{{ $copia }}</div>
                    <div class="no">No. <b>{{ $empeno->numero }}</b></div>
                </div>
                <div class="wm"><span class="r">{{ $w1 }}</span> {{ $w2 }}</div>
                <div class="sub">
                    {{ $neg->representante }} @if($neg->nit) · NIT {{ $neg->nit }} @endif @if($neg->direccion) · {{ $neg->direccion }} @endif @if($neg->ciudad) · {{ $neg->ciudad }} @endif @if($neg->telefono) · Cel/WhatsApp: {{ $neg->telefono }} @endif
                </div>
            </div>

            <div class="banner">
                CONTRATO DE COMPRAVENTA CON PACTO DE RETROVENTA
                <span class="amt">{{ cop($empeno->principal) }}</span>
            </div>

            <table class="datos">
                <tr>
                    <td>Fecha de iniciación: <span class="v">{{ $empeno->inicio->format('d/m/Y') }}</span></td>
                    <td colspan="2">FECHA DE VENCIMIENTO: <span class="v">{{ $empeno->vencimiento()->format('d/m/Y') }}</span></td>
                </tr>
                <tr>
                    <td>VENDEDOR: <span class="v">{{ $cli->nombre }}</span></td>
                    <td>C.C. No. <span class="v">{{ $cli->cedula }}</span></td>
                    <td>Vecino(a) de <span class="v">{{ $neg->ciudad }}</span></td>
                </tr>
                <tr>
                    <td>Dirección: <span class="v">{{ $cli->direccion }}</span></td>
                    <td>Cel: <span class="v">{{ $cli->tel }}</span></td>
                    <td>Tel. referencia / 2do contacto: <span class="v">{{ $cli->contacto2 }}</span></td>
                </tr>
            </table>

            <div class="sec">DESCRIPCIÓN DEL BIEN <span class="dim">(de exclusiva propiedad del vendedor, de origen lícito — Art. 1939 y ss. C.C.)</span></div>
            <table class="desc">
                <tr>
                    <td style="width:50%">Artículo: <b>{{ $empeno->articulo }}</b></td>
                    <td>Marca / Modelo / Año: <b>{{ $marcaModelo }}{{ $anio ? ' - Mod. '.$anio : '' }}</b></td>
                </tr>
                <tr>
                    <td colspan="2">Serie / Placa / IMEI / Motor / Chasis: <b>{{ $serie ?: '—' }}</b></td>
                </tr>
                <tr>
                    <td>Color: <b>{{ $empeno->color }}</b></td>
                    <td>Estado y observaciones: <b>{{ $empeno->observaciones }}</b></td>
                </tr>
            </table>

            <p style="margin-top:6px"><b>PRIMERA — VENTA.</b> Manifiesto que TRANSFIERO A TÍTULO DE VENTA REAL Y ENAJENACIÓN PERPETUA a favor de {{ $neg->nombre }}, bajo condición resolutoria de <i>pacto de retroventa</i>; el vendedor se reserva la facultad de recobrar la(s) cosa(s) vendida(s) (Art. 1939 del C.C.). Como precio de venta hemos convenido la suma de <b>{{ cop($empeno->principal) }}</b> (<b>{{ $enLetras }}</b>), dinero que declaro recibir a entera satisfacción de manos del comprador.</p>

            <p><b>SEGUNDA — RETROVENTA.</b> El vendedor podrá recobrar el artículo pagando al comprador el valor de la venta más un porcentaje del <b>{{ $pct }}%</b> por mes o fracción de mes, sobre el valor de la venta, dentro del término de <b>{{ $empeno->plazo }} meses</b> contados a partir de la fecha de este contrato, plazo modificable por escrito y mutuo acuerdo.</p>

            <p><b>TERCERA — VENCIMIENTO.</b> Vencido el término sin que el vendedor ejerza el pacto de retroventa ni acuerde prórroga por escrito, la propiedad del bien se consolida de manera plena y definitiva en cabeza del comprador, quien podrá disponer libremente de él sin lugar a reclamación alguna.</p>

            <p><b>CUARTA — RESPONSABILIDAD.</b> El comprador no responde por fuerza mayor, caso fortuito, hurto calificado ni por el normal deterioro del objeto. En caso de pérdida del bien imputable al comprador, éste entregará al vendedor un artículo similar o su valor comercial acordado.</p>

            <p><b>QUINTA — DECLARACIÓN JURAMENTADA.</b> El vendedor declara bajo la gravedad del juramento que el bien es de su exclusiva propiedad, de origen lícito, que no soporta prenda, embargo, limitación al dominio ni reporte de hurto, y que responde civil y penalmente por la veracidad de esta declaración. Autoriza la verificación del bien en bases de datos públicas (RUNT, listas de equipos reportados, etc.) y el tratamiento de sus datos personales (Ley 1581 de 2012) para la gestión de esta operación y el envío de recordatorios de pago. Se rige por los Arts. 1939 y ss. del Código Civil Colombiano, el Código de Comercio y normas concordantes.</p>

            <div class="sec">VALOR PARA RECUPERAR EL ARTÍCULO <span class="dim">(liquidación informativa)</span></div>
            <table class="liq">
                <tr class="lh">
                    <th>Valor recibido</th>
                    @for ($n = 1; $n <= $empeno->plazo; $n++)
                        <th>Hasta mes {{ $n }}</th>
                    @endfor
                </tr>
                <tr>
                    <td>{{ cop($empeno->principal) }}</td>
                    @for ($n = 1; $n <= $empeno->plazo; $n++)
                        <td>{{ cop($empeno->principal + $im * $n) }}</td>
                    @endfor
                </tr>
            </table>

            <div class="sec">REGISTRO DE ABONOS <span class="dim">(todo abono debe quedar registrado aquí y firmado por quien recibe)</span></div>
            <table class="ab">
                <tr class="lh">
                    <th>Fecha</th><th>Valor abonado</th><th>Saldo</th><th>Recibe (firma)</th><th>Nuevo vencimiento</th>
                </tr>
                @for ($i = 0; $i < 3; $i++)
                    <tr><td></td><td></td><td></td><td></td><td></td></tr>
                @endfor
            </table>

            <div class="prorroga"><b>PRÓRROGA (solo por escrito):</b> Nuevo vencimiento: <span class="blank"></span> Firma comprador: <span class="blank"></span> Firma vendedor: <span class="blank"></span></div>

            <p class="constancia">Para constancia se firma en {{ $neg->ciudad }}, el {{ $empeno->inicio->isoFormat('D [de] MMMM [de] YYYY') }}.</p>

            <div class="firmas">
                <div class="l"><div class="line">Firma comprador</div></div>
                <div class="l"><div class="line">Firma vendedor · C.C. {{ $cli->cedula }}</div></div>
                <div class="hu"><div class="hbox"></div><div class="hlbl">Huella vendedor</div></div>
                <div class="hu"><div class="hbox"></div><div class="hlbl">Huella comprador</div></div>
            </div>

            <div class="foot">
                No respondemos por contratos comprados en la calle · No se muestran artículos para negociar con ellos · No bote este contrato; si lo extravía informe de inmediato · <b>Plazo {{ $empeno->plazo }} meses</b>
            </div>
            @if ($neg->telefono)
                <div class="wa">Consulte su saldo o solicite prórroga por WhatsApp: {{ $neg->telefono }}</div>
            @endif
        </div>
    @endforeach
</body>
</html>
