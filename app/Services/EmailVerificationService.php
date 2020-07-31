<?php

namespace App\Services;

use App\Mail\VerificationCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class EmailVerificationService
{
    const CODE_LIFE_TIME = 5 * 60;
    const PER_HOUR_ATTEMPTS_LIMIT = 5;

    const CODE_SENT_KEY = 'code-';
    const PER_HOUR_KEY = 'hour-attempts-';

    private function generateCode(): string
    {
        return strval(rand(1000, 9999));
    }

    private function codeAlreadySent(string $email): bool
    {
        return Cache::has(self::CODE_SENT_KEY . $email);
    }

    private function perHourLimitExceeded(string $email): bool
    {
        $attempts = Cache::get(self::PER_HOUR_KEY . $email, 0);

        return $attempts >= self::PER_HOUR_ATTEMPTS_LIMIT;
    }

    private function validateCodeCanBeSent(string $email): void
    {
        if ($this->codeAlreadySent($email)) {
            abort(429,'code_has_been_already_sent');
        }

        if ($this->perHourLimitExceeded($email)) {
            abort(429, 'too_many_attempt_per_hour');
        }
    }

    private function processCodeSendingAftermath(string $email, string $code): void
    {
        Cache::put(self::CODE_SENT_KEY . $email, $code, self::CODE_LIFE_TIME);

        $perHourKey = self::PER_HOUR_KEY . $email;

        Cache::has($perHourKey) ? Cache::increment($perHourKey) : Cache::put($perHourKey, 1, 60 * 60);
    }

    public function sendVerificationEmail(string $email): void
    {
        $this->validateCodeCanBeSent($email);

        $code = $this->generateCode();

        Mail::to($email)->queue(new VerificationCode($code));

        $this->processCodeSendingAftermath($email, $code);
    }

    public function checkCode(string $email, string $code): void
    {
        $realCode = Cache::get(self::CODE_SENT_KEY . $email);

        if ($realCode !== $code) {
            abort(412, 'bad_code');
        }
    }
}
