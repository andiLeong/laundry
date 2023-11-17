<?php

namespace App\QueryFilter;

use App\QueryFilter\Filters\WhereFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class QueryFilterManager
{

    /**
     * @var Builder
     */
    private $query;

    /**
     * @var array|null
     */
    private $request;

    /**
     * @var array
     */
    private $filterOption;


    /**
     * QueryFilterManager constructor.
     * @param Builder $query
     * @param array $filterOption
     * @param Request|null $request
     */
    public function __construct(Builder $query, array $filterOption, Request $request = null)
    {
        $this->query = $query;
        $this->filterOption = $filterOption;
        if (is_null($request)) {
            $request = app(Request::class);
        }
        $this->request = $request;
    }

    /**
     * apply each query cause to builder
     *
     * @return Builder
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function apply()
    {
        foreach ($this->filterOption as $key => $option) {
            if (is_null($this->request->get($key)) || !is_array($option)) {
                continue;
            }

            $this->attachQuery($option, $key);
        }

        return $this->query;
    }

    /**
     * attach query to builder
     *
     * @param $option
     * @param $key
     * @return Builder
     */
    public function attachQuery($option, $key)
    {
        $parser = new QueryArgumentPhaser($option, $key, $this->request);
        if (empty($option)) {
            return (new WhereFilter($this->query, $parser))->filter();
        }

        $class = "App\\QueryFilter\\Filters\\" . ucfirst($option['clause']) . 'Filter';
        if (!class_exists($class)) {
            throw new \RuntimeException('query filter class not found ' . $class);
        }

        $instance = new $class($this->query, $parser);
        return $instance->filter();
    }
}
