<?php

namespace App\Models;

use Illuminate\Support\Facades\Http;

class GoogleRecaptcha
{
    private $baseUrl = 'https://www.google.com/recaptcha/api/siteverify';

    public function __construct(private readonly string $secret)
    {
        //
    }

    public function pass($token)
    {
        $query = http_build_query([
            'secret' => $this->secret,
            'response' => $token
        ]);

        $response = Http::post($this->baseUrl. '?' .$query)->json();
        if($response['success'] && $response['score'] > 0.5){
            return true;
        }

        return false;
    }
}
