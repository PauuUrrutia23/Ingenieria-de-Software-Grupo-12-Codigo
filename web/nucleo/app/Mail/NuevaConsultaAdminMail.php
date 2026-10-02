<?php

namespace App\Mail;

use App\Models\Consulta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NuevaConsultaAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Consulta $consulta)
    {
    }

    public function build()
    {
        return $this->subject('Nueva consulta recibida - Ingecon')
            ->view('emails.nueva_consulta_admin');
    }
}
