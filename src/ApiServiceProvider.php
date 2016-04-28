<?php namespace Jnet\Api;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Input;
use App\Jnet\Api\Filters\Sieve;

class ApiServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->middleware('api', 'Jnet\Api\Http\ApiMiddleware');
    }

    public function register()
    {
        $app = $this->app;

        $sieve = new Sieve;
        
        $app->singleton('App\Jnet\Api\Filters\Sieve', function() use($sieve) {
            return $sieve;
        });
    }
}
