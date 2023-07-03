<?php

namespace App\QueryFilter\Filters;

use App\QueryFilter\QueryArgumentPhaser;

class CallBackFilter implements Filters
{
    private array $option;

    public function __construct(private $query, private QueryArgumentPhaser $parser)
    {
        $this->option = $this->parser->getOption();
    }

    public function filter()
    {
        $fn = $this->option['callback'];
        $fn($this->query, $this->parser->request);
        return $this->query;
    }
}
