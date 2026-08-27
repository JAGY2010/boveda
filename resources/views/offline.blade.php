<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sin internet · Bóveda</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
               font-family: system-ui, -apple-system, Segoe UI, Arial, sans-serif; background: #f4f4f5; color: #27272a; padding: 24px; }
        .caja { max-width: 26rem; text-align: center; background: #fff; border: 1px solid #e4e4e7; border-radius: 14px; padding: 28px 26px; }
        h1 { font-size: 1.35rem; margin: 0 0 10px; }
        p { font-size: .95rem; line-height: 1.55; color: #52525b; margin: 0 0 10px; }
        .nota { font-size: .82rem; color: #71717a; margin-top: 16px; }
        .btn { display: inline-block; margin-top: 16px; background: #0e5c43; color: #fff; text-decoration: none;
               font-weight: 600; font-size: .9rem; padding: 10px 18px; border-radius: 9px; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="caja">
        <h1>Sin internet</h1>
        <p>Esta pantalla todavía no se había abierto en este dispositivo, así que no hay una copia guardada para mostrarte.</p>
        <p>Las pantallas que ya visitaste sí se pueden consultar, y lo que registres se guarda y se envía solo cuando vuelva la señal.</p>
        <button class="btn" onclick="location.reload()">Reintentar</button>
        <p class="nota">Bóveda</p>
    </div>
</body>
</html>
