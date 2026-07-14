@php
    $neg = $empeno->negocio;
    $cli = $empeno->cliente;
    $parts = explode(' ', trim($neg->nombre), 2);
    $w1 = \Illuminate\Support\Str::upper($parts[0]);
    $w2 = isset($parts[1]) ? \Illuminate\Support\Str::upper($parts[1]) : '';
    $pct = rtrim(rtrim((string) $empeno->pct, '0'), '.');
    $enLetras = \Illuminate\Support\Str::upper(numeroALetras((int) $empeno->principal)).' PESOS M/CTE';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contrato No. {{ $empeno->numero }} — {{ $neg->nombre }}</title>
    <style>
        @page { size: letter portrait; margin: 0.24in; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #71716f; font-family: Georgia, 'Times New Roman', serif; color: #16130f; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        .bar { width: 8in; max-width: 96%; margin: 14px auto; display: flex; gap: 10px; }
        .btn { font-family: system-ui, sans-serif; font-size: 13px; font-weight: 600; padding: 9px 16px; border-radius: 8px; border: none; cursor: pointer; background: #0e5c43; color: #fff; text-decoration: none; }
        .btn.sec { background: #fff; color: #333; border: 1px solid #bbb; }

        .page { width: 8in; margin: 0 auto; background: #fff; }
        .copy { position: relative; height: 5.25in; padding: 14px 22px; overflow: hidden; display: flex; flex-direction: column; font-size: 10.4px; line-height: 1.4; }
        .copy.first { border-bottom: 1.5px dashed #b8b8b8; }

        .head { position: relative; text-align: center; border-bottom: 2px solid #16130f; padding: 2px 0 6px; margin-bottom: 2px; min-height: 54px; }
        .logo { position: absolute; left: 0; top: 0; width: 72px; height: 64px; object-fit: contain; border: 1px solid #e3e3e3; border-radius: 6px; background: #fff; padding: 3px; }
        .corner { position: absolute; right: 0; top: 2px; text-align: right; }
        .copytag { font-family: system-ui, sans-serif; font-size: 8.5px; color: #9a9a9a; text-transform: uppercase; letter-spacing: .08em; }
        .no { font-size: 12px; }
        .no b { color: #b0281e; font-size: 18px; }
        .wm { font-size: 26px; font-weight: bold; letter-spacing: .01em; line-height: 1.04; padding-top: 5px; }
        .wm .r { color: #b0281e; }
        .sub { font-size: 9.2px; color: #555; margin-top: 5px; }

        .banner { position: relative; background: #b0281e; color: #fff; font-weight: bold; text-align: center; padding: 5px 96px; margin: 8px 0; font-size: 12.5px; letter-spacing: .04em; }
        .banner .amt { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: #fff; color: #16130f; padding: 2px 12px; border-radius: 3px; font-size: 12px; font-weight: bold; }

        p { margin: 0 0 5px; text-align: justify; }
        .fill { border-bottom: 1px solid #555; padding: 0 4px; font-weight: bold; }
        .row { margin-bottom: 6px; }
        .muted-note { text-align: center; margin-top: 4px; }

        .bottom { margin-top: auto; }
        .firmas { display: flex; align-items: flex-end; justify-content: space-between; gap: 26px; padding-top: 8px; font-size: 10.5px; }
        .firmas .l { flex: 1; }
        .firmas .line { border-top: 1px solid #16130f; margin-top: 22px; padding-top: 4px; text-align: center; }
        .huella-box { width: 52px; height: 50px; border: 1.2px solid #16130f; border-radius: 3px; }
        .huella-lbl { font-size: 8.5px; margin-top: 3px; text-transform: uppercase; text-align: center; letter-spacing: .04em; }

        .dataauth { font-size: 8.3px; color: #555; margin-top: 6px; text-align: justify; }
        .foot { margin-top: 5px; font-size: 8.3px; color: #666; text-align: center; text-transform: uppercase; letter-spacing: .02em; line-height: 1.4; }
        .foot b { color: #b0281e; }

        @media screen {
            body { padding: 16px 10px; }
            .page { box-shadow: 0 8px 30px rgba(0,0,0,.35); }
        }
        @media print {
            body { background: #fff; padding: 0; }
            .bar { display: none; }
            .page { width: auto; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="bar">
        <button class="btn" onclick="window.print()">🖨 Imprimir / Guardar PDF</button>
        <a class="btn sec" href="{{ route('empenos.show', $empeno) }}">← Volver</a>
    </div>

    <div class="page">
        @foreach (['Copia comercio', 'Copia cliente'] as $i => $copia)
            <div class="copy {{ $i === 0 ? 'first' : '' }}">
                <div class="head">
                    @if ($neg->logo_path)
                        <img class="logo" src="{{ asset($neg->logo_path) }}" alt="">
                    @endif
                    <div class="corner">
                        <div class="copytag">{{ $copia }}</div>
                        <div class="no">No. <b>{{ $empeno->numero }}</b></div>
                    </div>
                    <div class="wm"><span class="r">{{ $w1 }}</span> {{ $w2 }}</div>
                    <div class="sub">
                        {{ $neg->representante }} @if($neg->nit) · NIT {{ $neg->nit }} @endif ·
                        {{ $neg->direccion }} @if($neg->ciudad) · {{ $neg->ciudad }} @endif @if($neg->telefono) · Cel: {{ $neg->telefono }} @endif
                    </div>
                </div>

                <div class="banner">
                    CONTRATO DE COMPRAVENTA CON PACTO DE RETROVENTA
                    <span class="amt">{{ cop($empeno->principal) }}</span>
                </div>

                <div class="row">
                    Fecha de iniciación: <span class="fill">{{ $empeno->inicio->format('d/m/Y') }}</span>&nbsp;&nbsp;&nbsp;
                    Fecha de vencimiento: <span class="fill">{{ $empeno->vencimiento()->format('d/m/Y') }}</span>
                </div>

                <p>VENDEDOR: <span class="fill">{{ $cli->nombre }}</span>, identificado(a) con Cédula de Ciudadanía No. <span class="fill">{{ $cli->cedula }}</span>, mayor y vecino(a) de <span class="fill">{{ $neg->ciudad }}</span>, dirección <span class="fill">{{ $cli->direccion }}</span>, Cel. <span class="fill">{{ $cli->tel }}</span>.</p>

                <p>Manifiesto que TRANSFIERO A TÍTULO DE VENTA REAL Y ENAJENACIÓN PERPETUA a favor de {{ $neg->nombre }}, bajo condición resolutoria de <i>pacto de retroventa</i>; el vendedor se reserva la facultad de recobrar la(s) cosa(s) vendida(s) (Art. 1939 del C.C.). Los bienes que declaro son de mi exclusiva propiedad, de origen lícito, los describo a continuación: <span class="fill">{{ $empeno->articulo }}{{ $empeno->serial ? ' · '.$empeno->serial : '' }}</span>.</p>

                <p>Como precio de venta hemos convenido la suma de <span class="fill">{{ cop($empeno->principal) }}</span> (<span class="fill">{{ $enLetras }}</span>), dinero que declaro recibir a entera satisfacción de manos del comprador.</p>

                <p>Como precio por RETROTRAER LA VENTA hemos convenido lo siguiente: el vendedor pagará al comprador el valor de la venta más un porcentaje del <span class="fill">{{ $pct }}%</span> por mes o fracción de mes, sobre el valor de la venta. El término pactado para recobrar el artículo es de <span class="fill">{{ $empeno->plazo }} meses</span> contados a partir de la fecha de este contrato, plazo que se puede modificar por escrito y mutuo acuerdo entre las partes. El comprador no responde por fuerza mayor, caso fortuito, hurto calificado ni por el normal deterioro del objeto. En caso de pérdida del bien mueble, el comprador entregará al vendedor un artículo similar. Se rige por los Arts. 1939 y ss. del Código Civil Colombiano, el Código de Comercio y normas concordantes.</p>

                <p class="muted-note">Para constancia se firma en {{ $neg->ciudad }}, el {{ $empeno->inicio->isoFormat('D [de] MMMM [de] YYYY') }}.</p>

                <div class="bottom">
                    <div class="firmas">
                        <div class="l"><div class="line">Firma comprador</div></div>
                        <div class="l"><div class="line">Firma vendedor · C.C. {{ $cli->cedula }}</div></div>
                        <div><div class="huella-box"></div><div class="huella-lbl">Huella vendedor</div></div>
                    </div>

                    <div class="dataauth">El vendedor declara el origen lícito del bien y autoriza el tratamiento de sus datos personales (Ley 1581 de 2012) para la gestión de esta operación y el envío de recordatorios de pago.</div>

                    <div class="foot">
                        No respondemos por contratos comprados en la calle · No se muestran artículos para negociar con ellos · No bote este contrato; si lo extravía informe de inmediato · <b>Plazo {{ $empeno->plazo }} meses</b>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
