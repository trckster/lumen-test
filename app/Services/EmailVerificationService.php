<?php

namespace App\Services;

use App\Mail\VerificationCode;
use Illuminate\Support\Facades\Mail;

class EmailVerificationService
{
    private function generateCode(): string
    {
        return strval(rand(1000, 9999));
    }

    public function sendVerificationEmail(string $email): void
    {
        $code = $this->generateCode();

        Mail::to($email)->queue(new VerificationCode($code));
    }
}
