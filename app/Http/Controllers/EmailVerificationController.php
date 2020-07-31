<?php

namespace App\Http\Controllers;

use App\Services\EmailVerificationService;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function sendCode(Request $request, EmailVerificationService $service)
    {
        $data = $this->validate($request, [
            'email' => 'required|email'
        ]);

        $service->sendVerificationEmail($data['email']);

        return $this->success();
    }

    public function checkCode()
    {
        return $this->success();
    }
}
