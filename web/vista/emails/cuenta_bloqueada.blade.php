<!DOCTYPE html>
<html>
<head>
    <title>Cuenta Bloqueada</title>
</head>
<body style="font-family: sans-serif; color: #333;">
    <h2>Hola {{ $admin->correo }},</h2>
    <p>Hemos detectado 5 intentos fallidos de inicio de sesión en su cuenta.</p>
    <p>Por seguridad, su cuenta ha sido bloqueada temporalmente durante 60 minutos.</p>
    <p>Si no fue usted quien intentó acceder, le recomendamos contactar al administrador jefe.</p>
    <br>
    <p>Atentamente,<br>Equipo Ingecon</p>
</body>
</html>