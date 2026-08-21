@php
    $neg = $separado->negocio;
    $cli = $separado->cliente;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprobante de abono · {{ $separado->item->descripcion }}</title>
    <style>
        @page { size: letter portrait; margin: 0.4in; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #71716f; font-family: Arial, Helvetica, sans-serif; color: #222; font-size: 12px; line-height: 1.4; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bar { width: 4.6in; max-width: 96%; margin: 14px auto; display: flex; gap: 10px; }
        .btn { font-size: 13px; font-weight: 600; padding: 9px 16px; border-radius: 8px; border: none; cursor: pointer; background: #0e5c43; color: #fff; text-decoration: none; }
        .btn.sec { background: #fff; color: #333; border: 1px solid #bbb; }

        .recibo { width: 4.6in; margin: 0 auto; background: #fff; padding: 20px 24px; }
        .head { position: relative; text-align: center; border-bottom: 2px solid #b0281e; padding-bottom: 8px; margin-bottom: 10px; min-height: 48px; }
        .logo { position: absolute; left: 0; top: 0; width: 48px; height: 46px; object-fit: contain; border: 1px solid #e3e3e3; border-radius: 5px; background: #fff; padding: 2px; }
        .wm { font-size: 17px; font-weight: bold; }
        .sub { font-size: 8.5px; color: #555; margin-top: 3px; }
        .no { position: absolute; right: 0; top: 0; font-size: 10px; }
        .no b { color: #b0281e; font-size: 15px; }

        .title { text-align: center; font-weight: bold; letter-spacing: .08em; color: #b0281e; margin-bottom: 10px; }
        .row { display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding: 3px 0; }
        .row .k { color: #666; }
        .row .v { font-weight: bold; text-align: right; }
        .total { display: flex; justify-content: space-between; margin: 10px 0; padding: 8px 10px; background: #f0eae9; border-radius: 6px; font-weight: bold; }
        .total .amt { color: #0e5c43; font-size: 16px; }
        .saldo { display: flex; justify-content: space-between; margin: 8px 0; padding: 7px 10px; border: 1px dashed #b0281e; border-radius: 6px; font-weight: bold; }
        .saldo .amt { color: #b0281e; }
        .letras { font-size: 9.5px; color: #555; font-style: italic; margin-top: 6px; }
        .firma { margin-top: 26px; border-top: 1px solid #222; padding-top: 3px; text-align: center; font-size: 10px; }
        .foot { margin-top: 12px; text-align: center; font-size: 8.5px; color: #777; }

        @media screen { body { padding: 16px 10px; } .recibo { box-shadow: 0 8px 30px rgba(0,0,0,.35); } }
        @media print { body { background: #fff; } .bar { display: none; } }
    </style>
</head>
<body>
    <div class="bar">
        <button class="btn" onclick="window.print()">🖨 Imprimir / Guardar PDF</button>
        <a class="btn sec" href="{{ route('separados.show', $separado) }}">← Volver</a>
    </div>

    <div class="recibo">
        <div class="head">
            @if ($neg->logo_data || $neg->logo_path)
                <img class="logo" src="{{ $neg->logo_data ?: asset($neg->logo_path) }}" alt="">
            @endif
            <div class="no">Abono <b>{{ $abono->id }}</b></div>
            <div class="wm">{{ \Illuminate\Support\Str::upper($neg->nombre) }}</div>
            <div class="sub">
                {{ $neg->representante }} @if($neg->nit) · NIT {{ $neg->nit }} @endif @if($neg->telefono) · Cel: {{ $neg->telefono }} @endif
            </div>
        </div>

        <div class="title">COMPROBANTE DE ABONO · ARTÍCULO SEPARADO</div>

        <div class="row"><span class="k">Fecha</span><span class="v">{{ $abono->fecha->format('d/m/Y') }}</span></div>
        <div class="row"><span class="k">Cliente</span><span class="v">{{ $cli->nombre }}</span></div>
        <div class="row"><span class="k">C.C.</span><span class="v">{{ $cli->cedula ?: '—' }}</span></div>
        <div class="row"><span class="k">Artículo</span><span class="v">{{ $separado->item->descripcion }}</span></div>
        <div class="row"><span class="k">Precio pactado</span><span class="v">{{ cop($separado->precio) }}</span></div>

        <div class="total"><span>ABONO RECIBIDO</span><span class="amt">{{ cop($abono->monto) }}</span></div>
        <div class="letras">Son: {{ numeroALetras((int) $abono->monto) }} pesos m/cte.</div>

        <div class="row"><span class="k">Total abonado a la fecha</span><span class="v">{{ cop($abonadoHasta) }}</span></div>
        <div class="saldo">
            <span>SALDO PENDIENTE</span>
            <span class="amt">{{ cop($saldoHasta) }}</span>
        </div>

        <div class="firma">Recibí conforme · {{ $neg->nombre }}</div>

        <div class="foot">
            El artículo se entrega cuando el saldo quede en cero. Conserve este comprobante.
        </div>
    </div>
</body>
</html>
