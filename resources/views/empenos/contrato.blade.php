@php
    $neg = $empeno->negocio;
    $cli = $empeno->cliente;
    $parts = explode(' ', trim($neg->nombre), 2);
    $w1 = \Illuminate\Support\Str::upper($parts[0]);
    $w2 = isset($parts[1]) ? \Illuminate\Support\Str::upper($parts[1]) : '';
    $pct = rtrim(rtrim((string) $empeno->pct, '0'), '.');
    $enLetras = null;
    try {
        $enLetras = \Illuminate\Support\Str::upper(\Illuminate\Support\Number::spell((int) $empeno->principal, 'es')).' PESOS M/CTE';
    } catch (\Throwable $e) {
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contrato No. {{ $empeno->numero }} — {{ $neg->nombre }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #71716f; font-family: Georgia, 'Times New Roman', serif; color: #16130f; padding: 22px 14px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bar { max-width: 720px; margin: 0 auto 14px; display: flex; gap: 10px; }
        .btn { font-family: system-ui, sans-serif; font-size: 13px; font-weight: 600; padding: 9px 16px; border-radius: 8px; border: none; cursor: pointer; background: #0e5c43; color: #fff; text-decoration: none; }
        .btn.sec { background: #fff; color: #333; border: 1px solid #bbb; }

        .sheet { max-width: 720px; margin: 0 auto; background: #fff; padding: 30px 36px 26px; box-shadow: 0 8px 30px rgba(0,0,0,.35); position: relative; font-size: 13.5px; line-height: 1.5; }
        .no { position: absolute; top: 24px; right: 30px; font-size: 15px; }
        .no b { color: #b0281e; font-size: 19px; }
        .logo { position: absolute; top: 18px; left: 28px; max-width: 92px; max-height: 82px; object-fit: contain; }
        .head { text-align: center; border-bottom: 2px solid #16130f; padding-bottom: 10px; margin-bottom: 4px; }
        .wm { font-size: 27px; font-weight: bold; letter-spacing: .01em; line-height: 1.05; }
        .wm .r { color: #b0281e; }
        .sub { font-size: 11px; color: #444; margin-top: 4px; }

        .banner { background: #b0281e; color: #fff; font-weight: bold; text-align: center; padding: 6px 10px; margin: 14px 0 12px; font-size: 13px; letter-spacing: .02em; display: flex; justify-content: space-between; align-items: center; }
        .banner .amt { background: #fff; color: #16130f; padding: 1px 12px; border-radius: 3px; font-size: 13px; }

        p { margin: 0 0 9px; text-align: justify; }
        .fill { border-bottom: 1px solid #666; padding: 0 5px; font-weight: bold; white-space: nowrap; }
        .row { margin-bottom: 9px; }
        .muted-note { text-align: center; }

        .dataauth { font-size: 11px; color: #333; margin-top: 14px; text-align: justify; }
        .firmas { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-top: 20px; font-size: 13px; }
        .firmas .l { flex: 1; }
        .firmas .line { border-top: 1px solid #16130f; margin-top: 36px; padding-top: 3px; text-align: center; }
        .huella { text-align: center; flex-shrink: 0; }
        .huella-box { width: 76px; height: 88px; border: 1.5px solid #16130f; border-radius: 3px; }
        .huella-lbl { font-size: 10px; margin-top: 3px; text-transform: uppercase; letter-spacing: .04em; }

        .foot { margin-top: 16px; font-size: 10px; color: #555; text-align: center; text-transform: uppercase; letter-spacing: .02em; line-height: 1.5; }
        .foot b { color: #b0281e; }

        .tear { border-top: 2px dashed #999; margin: 20px -36px 0; }
        .recibo { padding: 12px 36px 0; font-size: 12px; }
        .recibo .rno { text-align: right; }
        .recibo .rno b { color: #b0281e; font-size: 16px; }
        .recibo .rl { margin: 4px 0; }

        @media print {
            body { background: #fff; padding: 0; }
            .bar { display: none; }
            .sheet { box-shadow: none; max-width: none; padding: 12px 18px; }
        }
    </style>
</head>
<body>
    <div class="bar">
        <button class="btn" onclick="window.print()">🖨 Imprimir / Guardar PDF</button>
        <a class="btn sec" href="{{ route('empenos.show', $empeno) }}">← Volver</a>
    </div>

    <div class="sheet">
        <div class="no">No. <b>{{ $empeno->numero }}</b></div>
        @if ($neg->logo_path)
            <img class="logo" src="{{ asset($neg->logo_path) }}" alt="">
        @endif

        <div class="head">
            <div class="wm"><span class="r">{{ $w1 }}</span> {{ $w2 }}</div>
            <div class="sub">
                {{ $neg->representante }} @if($neg->nit) · NIT {{ $neg->nit }} @endif<br>
                {{ $neg->direccion }} @if($neg->ciudad) · {{ $neg->ciudad }} @endif @if($neg->telefono) · Cel: {{ $neg->telefono }} @endif
            </div>
        </div>

        <div class="banner">
            <span>CONTRATO DE COMPRAVENTA CON PACTO DE RETROVENTA</span>
            <span class="amt">{{ cop($empeno->principal) }}</span>
        </div>

        <div class="row">
            Fecha de iniciación: <span class="fill">{{ $empeno->inicio->format('d/m/Y') }}</span>&nbsp;&nbsp;
            Fecha de vencimiento: <span class="fill">{{ $empeno->vencimiento()->format('d/m/Y') }}</span>
        </div>

        <p>VENDEDOR: <span class="fill">{{ $cli->nombre }}</span>, identificado(a) con Cédula de Ciudadanía No. <span class="fill">{{ $cli->cedula }}</span>, mayor y vecino(a) de <span class="fill">{{ $neg->ciudad }}</span>, dirección <span class="fill">{{ $cli->direccion }}</span>, Cel. <span class="fill">{{ $cli->tel }}</span>.</p>

        <p>Manifiesto que TRANSFIERO A TÍTULO DE VENTA REAL Y ENAJENACIÓN PERPETUA a favor de {{ $neg->nombre }}, bajo condición resolutoria de <i>pacto de retroventa</i>, es decir, el vendedor se reserva la facultad de recobrar la(s) cosa(s) vendida(s) (Art. 1939 del C.C.). Los bienes que declaro son de mi exclusiva propiedad, de origen lícito, los describo a continuación: <span class="fill">{{ $empeno->articulo }}{{ $empeno->serial ? ' · '.$empeno->serial : '' }}</span>.</p>

        <p>Como precio de venta hemos convenido la suma de <span class="fill">{{ cop($empeno->principal) }}</span> @if($enLetras)(<span class="fill">{{ $enLetras }}</span>)@endif, dinero que declaro recibir a entera satisfacción de manos del comprador.</p>

        <p>Como precio por RETROTRAER LA VENTA hemos convenido lo siguiente: el vendedor pagará al comprador el valor de la venta más un porcentaje del <span class="fill">{{ $pct }}%</span> por mes o fracción de mes, sobre el valor de la venta. El término pactado para recobrar el artículo vendido es de <span class="fill">{{ $empeno->plazo }} meses</span>, contados a partir de la fecha de este contrato; plazo que se puede modificar por escrito, por mutuo acuerdo entre las partes. El comprador no responde por fuerza mayor o caso fortuito, hurto calificado o por el normal deterioro del objeto dado en venta. En caso de pérdida del bien mueble, el comprador entregará al vendedor, que retrotrae la venta, un artículo similar. Este contrato se rige por los artículos 1939 y ss. del Código Civil Colombiano, el Código de Comercio y demás normas concordantes.</p>

        <p class="muted-note">Para constancia se firma en {{ $neg->ciudad }}, el {{ $empeno->inicio->isoFormat('D [de] MMMM [de] YYYY') }}.</p>

        <p class="dataauth">El vendedor declara que el bien es de origen lícito y autoriza el tratamiento de sus datos personales conforme a la Ley 1581 de 2012, para la gestión de esta operación y el envío de recordatorios de pago.</p>

        <div class="firmas">
            <div class="l"><div class="line">Firma comprador</div></div>
            <div class="l"><div class="line">Firma vendedor · C.C. {{ $cli->cedula }}</div></div>
            <div class="huella">
                <div class="huella-box"></div>
                <div class="huella-lbl">Huella vendedor</div>
            </div>
        </div>

        <div class="foot">
            No respondemos por contratos comprados en la calle · No se muestran artículos para negociar con ellos<br>
            No bote este contrato, si lo extravía informe inmediatamente · <b>Plazo {{ $empeno->plazo }} meses</b>
        </div>

        <div class="tear"></div>
        <div class="recibo">
            <div class="rno">No. <b>{{ $empeno->numero }}</b></div>
            <div class="rl"><b>COMPRADOR:</b> {{ $neg->nombre }} &nbsp;&nbsp; <b>Valor:</b> {{ cop($empeno->principal) }}</div>
            <div class="rl"><b>VENDEDOR:</b> {{ $cli->nombre }} &nbsp;&nbsp; <b>C.C.:</b> {{ $cli->cedula }}</div>
            <div class="rl"><b>Vence:</b> {{ $empeno->vencimiento()->format('d/m/Y') }} &nbsp;&nbsp; {{ $neg->ciudad }}</div>
        </div>
    </div>
</body>
</html>
