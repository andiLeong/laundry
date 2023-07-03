<?php

namespace App\QueryFilter\Filters;


interface Filters
{

    public function filter();

    public function shouldFilter() :bool;


}
