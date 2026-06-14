<?php

namespace App\Mail;

use App\Models\Administrador;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class CuentaBloqueadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Administrador $admin,
        public readonly Carbon        $momentoBloqueo,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Ingecon] Alerta de seguridad: cuenta bloqueada temporalmente',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'auth.emails.cuenta-bloqueada',
            with: [
                'correo'          => $this->admin->correo,
                'momentoBloqueo'  => $this->momentoBloqueo->format('d/m/Y H:i:s'),
                'desbloqueoHora'  => $this->momentoBloqueo->copy()->addMinutes(60)->format('H:i'),
                'desbloqueoFecha' => $this->momentoBloqueo->copy()->addMinutes(60)->format('d/m/Y'),
                'duracionMinutos' => 60,
            ],
        );
    }
}
