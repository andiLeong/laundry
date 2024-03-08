<?php

return [
    //list of free places that we offer free delivery
    'free' => !empty(env('FREE_DELIVERY_PLACE', '')) ? explode(',', env('FREE_DELIVERY_PLACE')) : [],

    //pickup/delivery fee look up
    'price' => [
        1000 => 60,
        700 => 30,
    ],

    //min meter threshold
    'min_meter' => 700,
];
