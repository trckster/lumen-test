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

        Cache::shouldReceive('get')
            ->once()
            ->with(EmailVerificationService::PER_HOUR_KEY . $email, 0)
            ->andReturn(0);

        Cache::shouldReceive('put')
            ->once()
            ->with(
                EmailVerificationService::CODE_SENT_KEY . $email,
                true,
                EmailVerificationService::CODE_LIFE_TIME
            );

        Cache::shouldReceive('has')
            ->once()
            ->with(EmailVerificationService::PER_HOUR_KEY . $email)
            ->andReturn(false);

        Cache::shouldReceive('put')
            ->once()
            ->with(
                EmailVerificationService::PER_HOUR_KEY . $email,
                1,
                3600
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
        $email = 'my@mail.ru';

        Cache::shouldReceive('has')
            ->once()
            ->with(EmailVerificationService::CODE_SENT_KEY . $email)
            ->andReturn(true);

        $this->json('GET', 'sendCode', ['email' => $email])
            ->seeStatusCode(429)
            ->seeJson(['code_has_been_already_sent']);
    }

    /**
     * @test
     */
    public function cant_send_code_more_than_five_times_per_hour()
    {
        $email = 'my@mail.ru';

        Cache::shouldReceive('has')
            ->once()
            ->with(EmailVerificationService::CODE_SENT_KEY . $email)
            ->andReturn(false);

        Cache::shouldReceive('get')
            ->once()
            ->with(EmailVerificationService::PER_HOUR_KEY . $email, 0)
            ->andReturn(EmailVerificationService::PER_HOUR_ATTEMPTS_LIMIT);

        $this->json('GET', 'sendCode', ['email' => $email])
            ->seeStatusCode(429)
            ->seeJson(['too_many_attempt_per_hour']);
    }

    /**
     * @test
     */
    public function can_check_code()
    {
        $email = 'my@mail.ru';
        $code = '1234';

        Cache::shouldReceive('get')
            ->once()
            ->with(EmailVerificationService::CODE_SENT_KEY . $email)
            ->andReturn($code);

        $this->json('GET', 'checkCode', ['email' => $email, 'code' => $code])
            ->seeStatusCode(200)
            ->seeJson(['status' => 'success']);
    }

    /**
     * @test
     */
    public function can_check_code_is_invalid()
    {
        $email = 'my@mail.ru';
        $code = '1234';

        Cache::shouldReceive('get')
            ->once()
            ->with(EmailVerificationService::CODE_SENT_KEY . $email)
            ->andReturn($code);

        $this->json('GET', 'checkCode', ['email' => $email, 'code' => '8753'])
            ->seeStatusCode(412)
            ->seeJson(['bad_code']);
    }
}
