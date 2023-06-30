<?php

namespace App\Models\Sms\Contract;

interface Sms
{
    public function send($number, $message) :bool;
}
