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
//        $promotionCount = 21;
//        $orderCount = 73;
//        $res = (int) round(($promotionCount/$orderCount) * 100);
////        dump(is_int($res));
//        dump(json_encode([$res]));
//        dd('done');
//
////        dd($res);
//        $value =  intval(0.29 * 100);
//        dump($value);
//        dump(0.29 * 100);
//        dump(is_float($value));
//        dump(is_int($value));
//        dd($value);
//        $arr = [$value];
////        $arr = [0.29];
//        dump(json_encode($arr, JSON_NUMERIC_CHECK));
//        dump(json_encode([0.29*100]));
//        dd(0.29*100);

        $response->assertStatus(200);
    }
}
