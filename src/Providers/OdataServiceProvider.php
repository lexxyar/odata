<?php

namespace Lexxsoft\Odata\Providers;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Lexxsoft\Odata\Http\Middleware\OdataRequestParser;

//use Lexxsoft\Odata\Middleware\OdataRequestParser;


class OdataServiceProvider extends ServiceProvider
{
    public function boot(Kernel $kernel): void
    {
        $kernel->pushMiddleware(OdataRequestParser::class);

        $this->publishes([
            './config/config.php' => config_path('odata.php'),
            './Routes/odata.php' => base_path('routes/odata.php'),
        ], 'odata');
//        $kernel->pushMiddleware(OdataRequestParser::class);

//        app()->register(OdataRouteServiceProvider::class);
        $this->loadRoutesFrom(__DIR__.'/../Routes/odata.php');
    }

}
