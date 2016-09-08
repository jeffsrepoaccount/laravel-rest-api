<?php namespace Jnet\Api\Filters;

use Illuminate\Database\Eloquent\Builder;

class Sieve implements FilterInterface
{
    protected $filters = [];

    public function addFilter($filter)
    {
        $this->filters[] = $filter;
        return $this;
    }

    public function filter(Builder $query)
    {
        foreach($this->filters as $filter) {
            $query = $filter->filter($query);
        }

        return $query;
    }    
}
