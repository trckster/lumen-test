<?php

namespace Feature;

use App\Mail\VerificationCode;
use App\Services\EmailVerificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use TestCase;

class EmailVerificationTest extends TestCase
{
    private function mockCacheForCodeSending(string $email): void
    {
        Cache::shouldReceive('has')
            ->once()
            ->with(EmailVerificationService::CODE_SENT_KEY . $email)
            ->andReturn(false);

        Cache::shouldReceive('put')
            ->once()
            ->with(
                EmailVerificationService::CODE_SENT_KEY . $email,
                true,
                EmailVerificationService::CODE_LIFE_TIME
            );

    }

    /**
     * @test
     */
    public function can_send_verification_email()
    {
        Mail::fake();

        $email = 'my@mail.ru';

        $this->mockCacheForCodeSending($email);

        $this->json('GET','sendCode', ['email' => $email])
            ->seeJson([
                'status' => 'success'
            ]);

        Mail::assertQueued(VerificationCode::class, function ($mail) use ($email) {
            return $mail->hasTo($email);
        });
    }

    /**
     * @test
     */
    public function cant_send_code_twice()
    {
        Mail::fake();

        $email = 'my@mail.ru';

        $this->mockCacheForCodeSending($email);

        $this->json('GET','sendCode', ['email' => 'my@mail.ru'])
            ->seeJson([
                'status' => 'success'
            ]);

        Cache::shouldReceive('has')
            ->once()
            ->with(EmailVerificationService::CODE_SENT_KEY . $email)
            ->andReturn(true);

        $this->json('GET', 'sendCode', ['email' => 'my@mail.ru'])
            ->seeStatusCode(429)
            ->seeJson(['code_has_been_already_sent']);
    }
}
