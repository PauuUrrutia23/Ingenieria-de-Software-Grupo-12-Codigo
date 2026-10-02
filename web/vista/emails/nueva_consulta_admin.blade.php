<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title>Nueva consulta</title></head>
<body>
    <h1>Nueva consulta recibida</h1>
    <p>Consulta N° {{ $consulta->id_consulta }} · {{ $consulta->created_at?->format('d/m/Y H:i') }}</p>
    <p>Nombre: {{ trim($consulta->visitante->nombre . ' ' . ($consulta->visitante->apellido ?? '')) }}</p>
    <p>Correo: {{ $consulta->visitante->email }}</p>
    <p>Mensaje:</p>
    <p style="white-space:pre-wrap">{{ $consulta->mensaje }}</p>
</body>
</html>
