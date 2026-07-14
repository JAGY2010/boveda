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
        @page { size: letter portrait; margin: 0.25in; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #71716f; font-family: Georgia, 'Times New Roman', serif; color: #16130f; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        .bar { width: 8in; max-width: 96%; margin: 14px auto; display: flex; gap: 10px; }
        .btn { font-family: system-ui, sans-serif; font-size: 13px; font-weight: 600; padding: 9px 16px; border-radius: 8px; border: none; cursor: pointer; background: #0e5c43; color: #fff; text-decoration: none; }
        .btn.sec { background: #fff; color: #333; border: 1px solid #bbb; }

        .page { width: 8in; margin: 0 auto; background: #fff; }
        .copy { position: relative; height: 5.2in; padding: 12px 14px; overflow: hidden; display: flex; gap: 10px; font-size: 9.7px; line-height: 1.3; }
        .copy.first { border-bottom: 1px dashed #999; }

        .main { flex: 1; min-width: 0; position: relative; }
        .stub { width: 1.55in; flex-shrink: 0; border-left: 1px dashed #999; padding-left: 9px; font-size: 8.7px; line-height: 1.35; }

        .corner { position: absolute; top: 0; right: 0; text-align: right; }
        .copytag { font-family: system-ui, sans-serif; font-size: 7.5px; color: #9a9a9a; text-transform: uppercase; letter-spacing: .07em; }
        .no { text-align: right; font-size: 10px; }
        .no b { color: #b0281e; font-size: 14px; }
        .logo { position: absolute; top: 0; left: 0; max-width: 50px; max-height: 44px; object-fit: contain; }
        .head { text-align: center; border-bottom: 1.5px solid #16130f; padding-bottom: 4px; margin-bottom: 4px; }
        .wm { font-size: 17px; font-weight: bold; line-height: 1.02; }
        .wm .r { color: #b0281e; }
        .sub { font-size: 8px; color: #444; margin-top: 2px; }

        .banner { background: #b0281e; color: #fff; font-weight: bold; text-align: center; padding: 3px 6px; margin: 6px 0; font-size: 9px; display: flex; justify-content: space-between; align-items: center; letter-spacing: .02em; }
        .banner .amt { background: #fff; color: #16130f; padding: 0 6px; border-radius: 2px; }

        p { margin: 0 0 5px; text-align: justify; }
        .fill { border-bottom: 1px solid #666; padding: 0 3px; font-weight: bold; }
        .row { margin-bottom: 5px; }
        .muted-note { text-align: center; }
        .dataauth { font-size: 7.6px; color: #444; margin-top: 6px; text-align: justify; }

        .firmas { display: flex; align-items: flex-end; justify-content: space-between; gap: 10px; margin-top: 10px; font-size: 9px; }
        .firmas .l { flex: 1; }
        .firmas .line { border-top: 1px solid #16130f; margin-top: 22px; padding-top: 2px; text-align: center; }
        .huella-box { width: 44px; height: 52px; border: 1px solid #16130f; border-radius: 2px; }
        .huella-lbl { font-size: 7px; margin-top: 2px; text-transform: uppercase; text-align: center; }

        .foot { margin-top: 6px; font-size: 7px; color: #555; text-align: center; text-transform: uppercase; line-height: 1.4; }
        .foot b { color: #b0281e; }

        .stub .sbox { width: 100%; height: 46px; border: 1px solid #16130f; border-radius: 2px; margin: 6px 0; }
        .stub .samt { text-align: center; font-weight: bold; margin-bottom: 6px; }
        .stub .sl { margin: 5px 0; }
        .stub .sl b { color: #16130f; }

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
                <div class="main">
                    @if ($neg->logo_path)
                        <img class="logo" src="{{ asset($neg->logo_path) }}" alt="">
                    @endif
                    <div class="corner">
                        <div class="copytag">{{ $copia }}</div>
                        <div class="no">No. <b>{{ $empeno->numero }}</b></div>
                    </div>

                    <div class="head">
                        <div class="wm"><span class="r">{{ $w1 }}</span> {{ $w2 }}</div>
                        <div class="sub">
                            {{ $neg->representante }} @if($neg->nit) · NIT {{ $neg->nit }} @endif ·
                            {{ $neg->direccion }} @if($neg->ciudad) · {{ $neg->ciudad }} @endif @if($neg->telefono) · Cel: {{ $neg->telefono }} @endif
                        </div>
                    </div>

                    <div class="banner">
                        <span>COMPRAVENTA CON PACTO DE RETROVENTA</span>
                        <span class="amt">{{ cop($empeno->principal) }}</span>
                    </div>

                    <div class="row">
                        Iniciación: <span class="fill">{{ $empeno->inicio->format('d/m/Y') }}</span>&nbsp;&nbsp;
                        Vencimiento: <span class="fill">{{ $empeno->vencimiento()->format('d/m/Y') }}</span>
                    </div>

                    <p>VENDEDOR: <span class="fill">{{ $cli->nombre }}</span>, C.C. <span class="fill">{{ $cli->cedula }}</span>, mayor y vecino(a) de <span class="fill">{{ $neg->ciudad }}</span>, dir. <span class="fill">{{ $cli->direccion }}</span>, Cel. <span class="fill">{{ $cli->tel }}</span>.</p>

                    <p>Manifiesto que TRANSFIERO A TÍTULO DE VENTA REAL Y ENAJENACIÓN PERPETUA a favor de {{ $neg->nombre }}, bajo condición resolutoria de <i>pacto de retroventa</i>; el vendedor se reserva la facultad de recobrar la(s) cosa(s) vendida(s) (Art. 1939 del C.C.). Bienes de mi exclusiva propiedad y origen lícito, que describo: <span class="fill">{{ $empeno->articulo }}{{ $empeno->serial ? ' · '.$empeno->serial : '' }}</span>.</p>

                    <p>Precio de venta convenido: <span class="fill">{{ cop($empeno->principal) }}</span>@if($enLetras) (<span class="fill">{{ $enLetras }}</span>)@endif, dinero que declaro recibir a entera satisfacción de manos del comprador.</p>

                    <p>Para RETROTRAER LA VENTA: el vendedor pagará al comprador el valor de la venta más un <span class="fill">{{ $pct }}%</span> por mes o fracción, sobre el valor de la venta. Término para recobrar: <span class="fill">{{ $empeno->plazo }} meses</span> desde la fecha, modificable por escrito y mutuo acuerdo. El comprador no responde por fuerza mayor, caso fortuito, hurto calificado ni deterioro normal del bien. En caso de pérdida, el comprador entregará un artículo similar. Se rige por los Arts. 1939 y ss. del Código Civil, el Código de Comercio y normas concordantes.</p>

                    <p class="muted-note">Para constancia se firma en {{ $neg->ciudad }}, el {{ $empeno->inicio->isoFormat('D [de] MMMM [de] YYYY') }}.</p>

                    <div class="firmas">
                        <div class="l"><div class="line">Firma comprador</div></div>
                        <div class="l"><div class="line">Firma vendedor · C.C. {{ $cli->cedula }}</div></div>
                        <div><div class="huella-box"></div><div class="huella-lbl">Huella</div></div>
                    </div>

                    <div class="dataauth">El vendedor declara el origen lícito del bien y autoriza el tratamiento de sus datos personales (Ley 1581 de 2012) para la gestión de esta operación y el envío de recordatorios de pago.</div>

                    <div class="foot">
                        No respondemos por contratos comprados en la calle · No bote este contrato; si lo extravía informe de inmediato · <b>Plazo {{ $empeno->plazo }} meses</b>
                    </div>
                </div>

                <div class="stub">
                    <div class="no">No. <b>{{ $empeno->numero }}</b></div>
                    <div class="sbox"></div>
                    <div class="samt">{{ cop($empeno->principal) }}</div>
                    <div class="sl"><b>COMPRADOR:</b><br>{{ $neg->nombre }}</div>
                    <div class="sl"><b>VENDEDOR:</b><br>{{ $cli->nombre }}</div>
                    <div class="sl"><b>C.C.:</b> {{ $cli->cedula }}</div>
                    <div class="sl"><b>Vence:</b> {{ $empeno->vencimiento()->format('d/m/Y') }}</div>
                    <div class="sl">{{ $neg->ciudad }}</div>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
