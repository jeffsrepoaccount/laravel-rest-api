<?php namespace Jnet\Api\Http;

use Closure;
use Illuminate\Http\Request;

class ApiMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }    
}
