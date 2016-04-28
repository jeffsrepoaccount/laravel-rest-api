<?php namespace Jnet\Api\Filters;

class Sieve implements FilterInterface
{
    protected $filters = [];

    public function addFilter($filter)
    {
        $this->filters[] = $filter;
        return $this;
    }

    public function filter($query)
    {
        foreach($this->filters as $filter) {
            $query = $filter->filter($query);
        }

        return $query;
    }    
}
