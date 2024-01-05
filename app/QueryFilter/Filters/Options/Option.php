<?php

namespace App\QueryFilter\Filters\Options;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class Option
{
    protected array $keys = [];

    public function __construct(protected Request $request, protected Builder $query)
    {
        //
    }

    public function get(): array
    {
        $result = [];

        foreach ($this->keys as $key) {
            $method = Str::camel($key);
            $result[$key] = $this->{$method}();
        }

        return $result;
    }

    public function __call(string $name, array $arguments): array
    {
        return [];
    }
}
