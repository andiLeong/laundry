<?php

namespace App\Models;

use Illuminate\Support\Facades\Http;

class GooglePlaces
{
    private $baseUrl = 'https://places.googleapis.com/v1/places';

    /**
     * @var array|mixed
     */
    public mixed $body;

    public function __construct(private readonly array $config)
    {
    }

    public function get($id)
    {
        $query = urldecode(http_build_query([
            'key' => $this->config['secret'],
            'fields' => $this->config['fields']
        ]));

        $url = $this->baseUrl . '/' . $id . '?' . $query;
        $response = Http::get($url);
        if($response->status() !== 200){
            logger($response->body());
            return false;
        }

        $this->body = $response->json();
        return true;
    }
}
