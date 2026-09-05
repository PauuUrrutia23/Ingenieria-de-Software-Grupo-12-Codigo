<!DOCTYPE html>
<html>
<head>
    <title>Recuperación de contraseña</title>
</head>
<body style="font-family: sans-serif; color: #333;">
    <h2>Hola {{ $admin->correo }},</h2>
    <p>Recibimos una solicitud para restablecer la contraseña de su cuenta en el Panel de Gestión de Ingecon.</p>
    <p>
        <a href="{{ url('/password/restablecer/' . $token) }}"
           style="background:#28533c;color:#fff;padding:12px 24px;border-radius:4px;text-decoration:none;display:inline-block;">
           Restablecer contraseña
        </a>
    </p>
    <p>Este enlace es válido por 60 minutos y solo puede usarse una vez.</p>
    <p>Si usted no solicitó este cambio, puede ignorar este correo con tranquilidad.</p>
    <br>
    <p>Atentamente,<br>Equipo Ingecon</p>
</body>
</html>
