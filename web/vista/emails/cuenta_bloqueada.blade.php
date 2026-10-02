<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cuenta Bloqueada</title>
</head>
<body style="margin:0;padding:0;background-color:#F7F6F3;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F7F6F3;">
        <tr>
            <td align="center" style="padding:40px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                       style="max-width:560px;background-color:#FFFFFF;border:1px solid #E2DFD8;font-family:Inter,'Segoe UI',Arial,Helvetica,sans-serif;color:#111315;font-size:15px;line-height:1.7;">

                    {{-- Cabecera carbón --}}
                    <tr>
                        <td style="background-color:#111315;padding:22px 32px;font-family:Outfit,'Segoe UI',Arial,Helvetica,sans-serif;font-size:16px;font-weight:400;letter-spacing:6px;color:#FFFFFF;">
                            INGECON<span style="color:#B88A58;">.</span>
                        </td>
                    </tr>

                    {{-- Etiqueta de seguridad --}}
                    <tr>
                        <td style="border-bottom:1px solid #E2DFD8;background-color:#F7F6F3;padding:12px 32px;font-family:'IBM Plex Mono',Consolas,monospace;font-size:11px;letter-spacing:0.18em;text-transform:uppercase;color:#82603A;">
                            Alerta de seguridad
                        </td>
                    </tr>

                    {{-- Cuerpo --}}
                    <tr>
                        <td style="padding:36px 32px;font-weight:300;">
                            <p style="margin:0 0 20px;font-family:Outfit,'Segoe UI',Arial,Helvetica,sans-serif;font-size:22px;font-weight:400;letter-spacing:-0.01em;line-height:1.3;color:#111315;">
                                Hola {{ $admin->correo }},
                            </p>

                            <p style="margin:0 0 24px;">Hemos detectado 5 intentos fallidos de inicio de sesión en su cuenta.</p>

                            <div style="border:1px solid #E2DFD8;border-top:2px solid #B88A58;background-color:#F7F6F3;padding:16px 20px;margin:0 0 24px;color:#111315;">
                                Por seguridad, su cuenta ha sido bloqueada temporalmente durante 60 minutos.
                            </div>

                            <p style="margin:0 0 28px;">Si no fue usted quien intentó acceder, le recomendamos contactar al administrador jefe.</p>

                            <p style="margin:0;">Atentamente,<br>
                                <span style="color:#82603A;font-weight:500;">Equipo Ingecon</span>
                            </p>
                        </td>
                    </tr>

                    {{-- Pie --}}
                    <tr>
                        <td style="border-top:1px solid #E2DFD8;padding:18px 32px;font-size:12px;line-height:1.6;color:#6F6A62;">
                            Industrialización de la madera desde 1994 · Aviso automático de Ingecon.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
