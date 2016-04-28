<?php namespace Jnet\Api;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Input;
use Jnet\Api\Filters\Sieve;

class ApiServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $kernel = $this->app->make('Illuminate\Contracts\Http\Kernel');

        $kernel->pushMiddleware('Jnet\Api\Http\ApiMiddleware');
    }

    public function register()
    {
        $app = $this->app;

        $sieve = new Sieve;
        
        $app->singleton('Jnet\Api\Filters\FilterInterface', function() use($sieve) {
            return $sieve;
        });
    }
}
