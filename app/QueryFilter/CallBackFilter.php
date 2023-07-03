<?php

namespace App\QueryFilter;

use App\QueryFilter\Filters\Filters;

class CallBackFilter implements Filters
{
    private array $option;

    public function __construct(private $query, private QueryArgumentPhaser $parser)
    {
        $this->option = $this->parser->getOption();
    }

    public function filter()
    {
        if ($this->shouldFilter()) {
            $fn = $this->option['callback'];
            $fn($this->query, $this->parser->request);
        }
        return $this->query;
    }

    public function shouldFilter(): bool
    {
        return isset($this->option['clause']) && $this->option['clause'] == 'callBack';
    }
}
