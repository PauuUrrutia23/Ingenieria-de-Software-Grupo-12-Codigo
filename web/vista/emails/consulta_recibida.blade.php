<!DOCTYPE html>
<html>
<head><title>Consulta Recibida</title></head>
<body style="font-family: sans-serif; color: #333;">
    <h2>Hola {{ $consulta->visitante->nombre }},</h2>
    <p>Hemos recibido su mensaje de forma exitosa.</p>
    <p><strong>Detalle de su consulta:</strong><br>{{ $consulta->mensaje }}</p>
    <p>Nuestro equipo comercial revisará sus requerimientos y se pondrá en contacto a la brevedad.</p>
    <br>
    <p>Atentamente,<br>Equipo Comercial Ingecon</p>
</body>
</html>