<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $token;

    public function __construct($user, $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    public function build()
    {
        $verifyUrl = config('app.url') . '/api/auth/verify-email?token=' . $this->token;
        return $this->subject('Vérifiez votre adresse e-mail')
                    ->markdown('emails.verify')
                    ->with([
                        'name' => $this->user->name,
                        'verifyUrl' => $verifyUrl,
                    ]);
    }
}
