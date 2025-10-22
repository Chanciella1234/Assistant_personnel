<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Activite;

class AlerteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $activite;
    public $messageAlerte;

    public function __construct(Activite $activite, $messageAlerte)
    {
        $this->activite = $activite;
        $this->messageAlerte = $messageAlerte;
    }

    public function build()
    {
        return $this->subject('📅 Alerte Activité Imminente')
            ->markdown('emails.alerte')
            ->with([
                'activite' => $this->activite,
                'messageAlerte' => $this->messageAlerte,
            ]);
    }
}
