<?php


namespace App\QueryFilter\Filters;


use App\QueryFilter\QueryArgumentPhaser;

class WhereNullFilter implements Filters
{

    private $option;
    private $clauses = ['whereNull','whereNotNull'];

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
        if ($this->shouldFilter()) {
            $method = $this->option['clause'];
            $this->query->{$method}($this->parser->column);
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
}
