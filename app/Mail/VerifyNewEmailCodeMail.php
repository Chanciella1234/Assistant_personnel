<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyNewEmailCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $code;

    /**
     * Crée une nouvelle instance du message.
     */
    public function __construct($name, $code)
    {
        $this->name = $name;
        $this->code = $code;
    }

    /**
     * Construit le message e-mail.
     */
    public function build()
    {
        return $this->subject('🔐 Vérification de votre nouvelle adresse e-mail')
            ->markdown('emails.verify_new_email_code')
            ->with([
                'name' => $this->name,
                'code' => $this->code,
            ]);
    }
}
