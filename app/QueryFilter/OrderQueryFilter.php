<?php


namespace App\QueryFilter;


use Illuminate\Database\Eloquent\Builder;

class OrderQueryFilter
{
    /**
     * @var array
     */
    private $orderFilter;

    /**
     * @var mixed
     */
    private $orderBy;

    /**
     * @var mixed
     */
    private $direction;

    /**
     * @var Builder
     */
    private $query;

    /**
     * @var \Illuminate\Support\Collection
     */
    private $request;


    /**
     * OrderQueryFilter constructor.
     * @param Builder $query
     * @param array $orderFilter
     */
    public function __construct(Builder $query,array $orderFilter)
    {
        $this->orderFilter = $orderFilter;

        $this->orderBy = $orderFilter['key'];
        $this->direction = $orderFilter['direction'];
        $this->query = $query;

        $this->request = collect(request()->all());
    }

    /**
     * apply each order by query to builder
     *
     * @return Builder
     */
    public function apply()
    {
        $this->request->filter(fn($value, $key) => $key === $this->orderBy || $key === $this->direction);

        if ($this->notArray()) {
            return $this->query;
        }

        foreach ($this->orderByArray() as $index => $value)
        {
            $this->query->orderBy($value, $this->directionArray()[$index]);
        }

        return $this->query;
    }

    /**
     * check order by aad direction is array
     *
     * @return bool
     */
    public function notArray()
    {
        return !is_array($this->orderByArray()) || !is_array($this->directionArray()) ;
    }

    /**
     * get the order by inside the request collection
     *
     * @return mixed
     */
    public function orderByArray()
    {
        return $this->request->get($this->orderBy);
    }

    /**
     * get the order by direction from the request collection
     *
     * @return mixed
     */
    public function directionArray()
    {
        return $this->request->get($this->direction);
    }
}
