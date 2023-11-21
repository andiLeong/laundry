<?php

namespace App\QueryFilter\Filters;

use App\QueryFilter\QueryArgumentPhaser;

class WhereBetweenFilter implements Filters
{

    /**
     * WhereFilter constructor.
     * @param $query
     * @param QueryArgumentPhaser $parser
     */
    public function __construct(private $query, private QueryArgumentPhaser $parser)
    {
        //
    }

    /**
     * apply filter to the query
     *
     * @return mixed
     */
    public function filter()
    {
        $this->query->whereBetween($this->parser->column, $this->parser->value);
        return $this->query;
    }
}
