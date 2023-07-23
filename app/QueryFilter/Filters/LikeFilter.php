<?php

namespace App\QueryFilter\Filters;

use App\QueryFilter\QueryArgumentPhaser;

class LikeFilter implements Filters
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
        $this->query->where($this->parser->column, 'like', '%'. $this->parser->value .'%');
        return $this->query;
    }
}