<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta de seguridad — Ingecon</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f5;
            margin: 0;
            padding: 0;
            color: #18181b;
        }
        .container {
            max-width: 560px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
        }
        .header {
            background-color: #1a1a2e;
            padding: 28px 32px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .header span {
            color: #f59e0b;
        }
        .alert-badge {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px 20px;
            margin: 24px 32px 0;
            border-radius: 4px;
            font-size: 14px;
            color: #92400e;
            font-weight: 600;
        }
        .body {
            padding: 24px 32px 32px;
        }
        .body p {
            font-size: 15px;
            line-height: 1.6;
            color: #3f3f46;
            margin: 0 0 16px;
        }
        .info-box {
            background-color: #f4f4f5;
            border-radius: 6px;
            padding: 16px 20px;
            margin: 20px 0;
        }
        .info-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .info-box td {
            padding: 6px 0;
            color: #52525b;
        }
        .info-box td:first-child {
            font-weight: 600;
            color: #18181b;
            width: 160px;
        }
        .footer {
            background-color: #f4f4f5;
            padding: 16px 32px;
            text-align: center;
            font-size: 12px;
            color: #a1a1aa;
            border-top: 1px solid #e4e4e7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>INGE<span>CON</span> — Seguridad</h1>
        </div>

        <div class="alert-badge">
            ⚠ Alerta de seguridad: Tu cuenta ha sido bloqueada temporalmente
        </div>

        <div class="body">
            <p>Hola,</p>
            <p>
                Hemos detectado <strong>5 intentos fallidos de inicio de sesión</strong>
                consecutivos en tu cuenta de administrador (<strong>{{ $correo }}</strong>).
                Como medida de protección, el acceso ha sido bloqueado de forma temporal.
            </p>

            <div class="info-box">
                <table>
                    <tr>
                        <td>Momento del bloqueo:</td>
                        <td>{{ $momentoBloqueo }}</td>
                    </tr>
                    <tr>
                        <td>Duración del bloqueo:</td>
                        <td>{{ $duracionMinutos }} minutos</td>
                    </tr>
                    <tr>
                        <td>Acceso habilitado a:</td>
                        <td>{{ $desbloqueoHora }} hrs del {{ $desbloqueoFecha }}</td>
                    </tr>
                </table>
            </div>

            <p>
                Si fuiste tú quien realizó estos intentos, no se requiere ninguna acción.
                Tu cuenta se desbloqueará automáticamente al término del período indicado.
            </p>
            <p>
                Si <strong>no reconoces</strong> estos intentos de acceso, te recomendamos
                cambiar tu contraseña inmediatamente una vez que el bloqueo expire y contactar
                al administrador del sistema.
            </p>
        </div>

        <div class="footer">
            Este es un mensaje automático del sistema de seguridad de Ingecon.<br>
            Por favor no respondas a este correo.
        </div>
    </div>
</body>
</html>
