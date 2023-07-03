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
        if ($this->shouldFilter()) {
            $method = $this->option['clause'];

            $this->query->{$method}(
                $this->option['relationship'],
                $this->callback()
            );
        }
        return $this->query;
    }

    public function shouldFilter(): bool
    {
        return isset($this->option['clause']) && $this->option['clause'] == 'whereHas';
    }

    private function callback()
    {
        return function (Builder $query) {
            $arg = [$this->parser->column, $this->parser->operator, $this->parser->value];
            return $query->where(...$arg);
        };
    }
}
