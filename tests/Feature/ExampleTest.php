<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');
//        $arr = [0.29*100];
//        $arr = [0.29];
//        dump(json_encode($arr));
//        dump(json_encode([0.29*100]));
//        dd(0.29*100);

        $response->assertStatus(200);
    }
}
