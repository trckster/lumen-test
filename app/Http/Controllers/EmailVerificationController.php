<?php

namespace App\Http\Controllers;

use App\Services\EmailVerificationService;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * @var EmailVerificationService
     */
    protected $service;

    public function __construct(EmailVerificationService $service)
    {
        $this->service = $service;
    }

    public function sendCode(Request $request)
    {
        $data = $this->validate($request, [
            'email' => 'required|email'
        ]);

        $this->service->sendVerificationEmail($data['email']);

        return $this->success();
    }

    public function checkCode(Request $request)
    {
        $data = $this->validate($request, [
            'email' => 'required|email',
            'code' => 'required|string|size:4'
        ]);

        $this->service->checkCode($data['email'], $data['code']);

        return $this->success();
    }
}
