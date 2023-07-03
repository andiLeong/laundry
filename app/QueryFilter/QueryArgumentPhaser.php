<?php

namespace App\QueryFilter;

use Illuminate\Http\Request;

class QueryArgumentPhaser
{
    private $option;
    private $defaultOperator = '=';
    private $defaultColumn;
    public $request;

    /**
     * QueryArgumentPhaser constructor.
     * @param array $option
     * @param string $defaultColumn
     * @param Request $request
     */
    public function __construct(array $option, string $defaultColumn, Request $request)
    {
        $this->option = $option;
        $this->defaultColumn = $defaultColumn;
        $this->request = $request;
    }

    /**
     * @return array
     */
    public function getOption(): array
    {
        return $this->option;
    }


    private function getColumn()
    {
        if (array_key_exists('column', $this->option)) {
            return $this->option['column'];
        }
        return $this->defaultColumn;
    }


    private function getOperator()
    {
        if (array_key_exists('operator', $this->option)) {
            $operator = $this->option['operator'];
        } else {
            $operator = $this->defaultOperator;
        }
        return $operator;
    }


    private function getValue()
    {
        if (array_key_exists('value', $this->option)) {
            $value = $this->option['value'];
        } else {
            $value = $this->request->get($this->defaultColumn);
        }

        if ($this->getOperator() == 'like') {
            return '%' . $value . '%';
        }
        return $value;
    }

    public function __get($name)
    {
        $method = "get" . ucfirst($name);
        if (!method_exists($this, $method)) {
            throw new \InvalidArgumentException("{$name} property not found.");
        }

        return $this->$method();
    }


}
