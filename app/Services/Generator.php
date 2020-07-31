<?php

namespace App\Services;

class Generator
{
    public function generateCode(): string
    {
        return strval(rand(1000, 9999));
    }
}
