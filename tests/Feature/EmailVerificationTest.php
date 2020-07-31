<?php

namespace Feature;

use App\Mail\VerificationCode;
use Illuminate\Support\Facades\Mail;
use TestCase;

class EmailVerificationTest extends TestCase
{
    /**
     * @test
     */
    public function can_send_verification_email()
    {
        Mail::fake();

        $email = 'my@mail.ru';

        $this->json('GET','sendCode', ['email' => $email])
            ->seeJson([
                'status' => 'success'
            ]);

        Mail::assertQueued(VerificationCode::class, function ($mail) use ($email) {
            return $mail->hasTo($email);
        });
    }
}
