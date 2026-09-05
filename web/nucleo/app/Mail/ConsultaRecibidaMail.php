<?php
namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Consulta;

class ConsultaRecibidaMail extends Mailable
{
    use Queueable, SerializesModels;
    public $consulta;

    public function __construct(Consulta $consulta)
    {
        $this->consulta = $consulta;
    }

    public function build()
    {
        return $this->subject('Hemos recibido su consulta - Ingecon')
                    ->view('emails.consulta_recibida');
    }
}