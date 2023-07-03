<?php

namespace App\QueryFilter\Filters;


use App\QueryFilter\QueryArgumentPhaser;

class WhereNotNullFilter implements Filters
{
    private $option;

    /**
     * WhereNullFilter constructor.
     * @param $query
     * @param QueryArgumentPhaser $parser
     */
    public function __construct(private $query, private QueryArgumentPhaser $parser)
    {
        $this->option = $this->parser->getOption();
    }

    /**
     * apply filter to the query
     *
     * @return mixed
     */
    public function filter()
    {
        $method = $this->option['clause'];
        $this->query->{$method}($this->parser->column);
        return $this->query;
    }
}
