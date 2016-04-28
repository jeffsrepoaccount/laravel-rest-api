<?php namespace Jnet\Api\Filters;

abstract class FilterAbstract
{
    protected $value;
    
    public function __construct($value)
    {
        $this->value = $value;
    }
}
