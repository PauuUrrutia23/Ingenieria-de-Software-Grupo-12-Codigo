<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Archivo demasiado grande · Ingecon</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f2f0ec; color: #17191b; font: 16px/1.5 Arial, sans-serif; }
        main { width: min(34rem, calc(100% - 3rem)); padding: 2rem; background: white; border: 1px solid #dedbd5; }
        h1 { font-size: 1.6rem; margin: 0 0 1rem; }
        p { color: #53565a; }
        a { display: inline-block; margin-top: 1rem; padding: .7rem 1rem; background: #17191b; color: white; text-decoration: none; }
    </style>
</head>
<body>
<main>
    <h1>El archivo es demasiado grande</h1>
    <p>La carga completa supera el límite del servidor (64 MB). Las imágenes y los archivos PDF tienen límites menores indicados en cada formulario; los videos MP4 de contenido admiten hasta 50 MB.</p>
    <p>Selecciona un archivo más liviano y vuelve a intentarlo. Los cambios de este formulario no se guardaron.</p>
    <a href="{{ url('/admin/dashboard') }}">Volver al panel</a>
</main>
</body>
</html>
