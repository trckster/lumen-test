<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    protected function success(): array
    {
        return [
            'status' => 'success'
        ];
    }
}
