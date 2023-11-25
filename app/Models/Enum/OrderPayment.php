<?php

namespace App\Models\Enum;

enum OrderPayment :int
{
    case cash = 1;
    case gcash = 2;

    public static function fromName(string $name): string
    {
        foreach (self::cases() as $payments) {
            if( $name === $payments->name ){
                return $payments->value;
            }
        }
    }
}
