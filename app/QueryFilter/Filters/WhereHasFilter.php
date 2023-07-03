<?php

namespace App\QueryFilter\Filters;

use App\QueryFilter\QueryArgumentPhaser;
use Illuminate\Database\Eloquent\Builder;

class WhereHasFilter implements Filters
{
    private array $option;

    /**
     * @param $query
     * @param QueryArgumentPhaser $parser
     */
    public function __construct(private $query, private QueryArgumentPhaser $parser)
    {
        $this->option = $this->parser->getOption();
    }

    public function filter()
    {
        $method = $this->option['clause'];

        $this->query->{$method}(
            $this->option['relationship'],
            $this->callback()
        );
        return $this->query;
    }


    private function callback()
    {
        return function (Builder $query) {
            $arg = [$this->parser->column, $this->parser->operator, $this->parser->value];
            return $query->where(...$arg);
        };
    }
}
