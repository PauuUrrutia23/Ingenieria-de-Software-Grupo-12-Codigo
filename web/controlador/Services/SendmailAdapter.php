<?php
namespace App\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

/**
 * SendmailAdapter («Boundary») — Diagrama de Componentes, capa Servicios.
 *
 * Última milla del envío de correo: entrega el Mailable ya armado al transporte
 * configurado en MAIL_MAILER (`/usr/sbin/sendmail` local en producción, según la
 * Dimensión Técnica; `log` en desarrollo). Es un adaptador delgado sobre el
 * facade Mail de Laravel — no hay llamada a una API externa ni webhook de
 * retorno, el correo sale directo del propio servidor.
 */
class SendmailAdapter
{
    public function enviar(string $destinatario, Mailable $correo): void
    {
        Mail::to($destinatario)->send($correo);
    }
}
