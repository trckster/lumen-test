<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class VerificationCode extends Mailable
{
    use Queueable;

    /**
     * @var string
     */
    public $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function build()
    {
        return $this->view('emails.verification_code');
    }
}
