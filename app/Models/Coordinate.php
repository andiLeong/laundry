<?php

namespace App\Models;

class Coordinate
{
    public function __construct(public readonly string $longitude, public readonly string $latitude)
    {
        //
    }
}
