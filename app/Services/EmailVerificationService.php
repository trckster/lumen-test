<?php

namespace App\Services;

use App\Mail\VerificationCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class EmailVerificationService
{
    const CODE_LIFE_TIME = 5 * 60;

    const CODE_SENT_KEY = 'code-sent-';

    private function generateCode(): string
    {
        return strval(rand(1000, 9999));
    }

    private function codeAlreadySent(string $email): bool
    {
        return Cache::has(self::CODE_SENT_KEY . $email);
    }

    private function processCodeSendingAftermath(string $email): void
    {
        Cache::put(self::CODE_SENT_KEY . $email, true, self::CODE_LIFE_TIME);
    }

    public function sendVerificationEmail(string $email): void
    {
        if ($this->codeAlreadySent($email)) {
            abort(429,'code_has_been_already_sent');
        }

        $code = $this->generateCode();

        Mail::to($email)->queue(new VerificationCode($code));

        $this->processCodeSendingAftermath($email);
    }
}
