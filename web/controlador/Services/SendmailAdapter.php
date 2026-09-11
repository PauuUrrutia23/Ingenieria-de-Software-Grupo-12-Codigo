<?php

namespace App\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class SendmailAdapter
{
    public function enviar(string $destinatario, Mailable $correo): void
    {
        Mail::to($destinatario)->send($correo);
    }
}
