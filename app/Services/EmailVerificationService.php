<?php

namespace App\Services;

use App\Mail\VerificationCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class EmailVerificationService
{
    const CODE_LIFE_TIME = 5 * 60;
    const PER_HOUR_ATTEMPTS_LIMIT = 5;
    const CHECK_ATTEMPTS_LIMIT = 3;

    const CODE_SENT_KEY = 'code-';
    const PER_HOUR_KEY = 'hour-attempts-';
    const INVALID_ATTEMPTS_KEY = 'invalid-';

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

    private function increaseInvalidAttemptsCount(string $email): void
    {
        $key = self::INVALID_ATTEMPTS_KEY . $email;

        $attempts = Cache::get($key, 0) + 1;

        if ($attempts >= self::CHECK_ATTEMPTS_LIMIT) {
            Cache::forget(self::CODE_SENT_KEY . $email);
        }

        Cache::put($key, $attempts);
    }

    private function invalidateCacheForEmail(string $email): void
    {
        Cache::forget(self::CODE_SENT_KEY . $email);

        Cache::forget(self::PER_HOUR_KEY . $email);

        Cache::forget(self::INVALID_ATTEMPTS_KEY . $email);
    }

    public function checkCode(string $email, string $code): void
    {
        $realCode = Cache::get(self::CODE_SENT_KEY . $email);

        if ($realCode === null) {
            abort(412, 'no_code');
        }

        if ($realCode !== $code) {
            $this->increaseInvalidAttemptsCount($email);

            abort(412, 'bad_code');
        }

        $this->invalidateCacheForEmail($email);

        // Email confirmed, do something about it
    }
}
