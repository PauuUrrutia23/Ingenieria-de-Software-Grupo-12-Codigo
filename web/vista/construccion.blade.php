<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Bienvenido a Ingecon Ltda.</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    {{-- Subida al hosting: página de espera mientras Ingecon carga el contenido. --}}
    <style>
        body { margin: 0; min-height: 100vh; display: flex; flex-direction: column; font-family: Georgia, "Times New Roman", Times, serif; background: #fff; }
        .franja { background: #1361b6; }
        .arriba { height: 75px; }
        .centro { max-width: 700px; margin: 0 auto; padding: 24px 16px 64px; text-align: center; }
        .centro img { max-width: 100%; height: auto; }
        h1 { font-family: "Times New Roman", Times, serif; font-size: 25px; color: #1b324b; margin: 24px 0 32px; }
        .abajo { flex: 1; color: #fff; text-align: center; padding: 32px 16px 96px; font-size: 20px; }
        .abajo p { margin: 0 0 24px; }
    </style>
</head>
<body>
    <div class="franja arriba"></div>
    <main class="centro">
        <img src="{{ asset('img/logo-ingecon.jpg') }}" alt="Ingecon" width="160" height="90">
        <h1>Bienvenido a Ingecon Ltda.</h1>
        <img src="{{ asset('img/en-construccion.png') }}" alt="Página en construcción" width="409" height="252">
    </main>
    <footer class="franja abajo">
        <p>Página web alojada en Hostname.cl</p>
        <p>en construcción</p>
    </footer>
</body>
</html>
