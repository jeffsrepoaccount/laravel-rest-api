<?php namespace Jnet\Api\Filters;

use Illuminate\Database\Eloquent\Builder;

abstract class FilterAbstract
{
    protected $key;
    protected $value;
    
    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    abstract public function filter(Builder $query);
}
