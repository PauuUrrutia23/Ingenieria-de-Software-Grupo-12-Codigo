<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public $admin;
    public $token;

    public function __construct($admin, $token)
    {
        $this->admin = $admin;
        $this->token = $token;
    }

    public function build()
    {
        return $this->subject('Recuperación de contraseña - Ingecon')
                    ->view('emails.recuperacion_password');
    }
}
