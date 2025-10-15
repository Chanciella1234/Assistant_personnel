<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ActivationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $code;

    public function __construct($user, $code)
    {
        $this->user = $user;
        $this->code = $code;
    }

    public function build()
    {
        return $this->subject('Code d’activation de votre compte')
                    ->markdown('emails.activation')
                    ->with([
                        'name' => $this->user->name,
                        'code' => $this->code,
                    ]);
    }
}
