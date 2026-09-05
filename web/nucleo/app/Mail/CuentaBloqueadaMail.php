<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CuentaBloqueadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $admin;

    public function __construct($admin)
    {
        $this->admin = $admin;
    }

    public function build()
    {
        return $this->subject('Aviso: Su cuenta ha sido bloqueada temporalmente')
                    ->view('emails.cuenta_bloqueada');
    }
}