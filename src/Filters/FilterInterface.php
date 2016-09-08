<?php namespace Jnet\Api\Filters;

use Illuminate\Database\Eloquent\Builder;

interface FilterInterface
{
    public function filter(Builder $query);
}
