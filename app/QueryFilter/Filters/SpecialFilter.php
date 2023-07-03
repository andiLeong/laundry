<?php

namespace App\QueryFilter\Filters;

use App\QueryFilter\QueryArgumentPhaser;

class SpecialFilter implements Filters
{

    public $clauses = ['whereIn','whereNotIn', 'whereBetween','whereNotBetween','whereYear','whereDay','whereMonth'];

    /**
     * @var array
     */
    private $option;

    /**
     * WhereFilter constructor.
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
        if ($this->shouldFilter()) {
            $clause = $this->clause();
            $this->query->$clause(
                $this->parser->column, $this->parser->value
            );
        }
        return $this->query;
    }

    /**
     * decide if we should filter based on the option
     *
     * @return bool
     */
    public function shouldFilter() :bool
    {
        return isset($this->option['clause']) && in_array($this->option['clause'], $this->clauses);
    }

    public function clause()
    {
        return $this->option['clause'];
    }
}
