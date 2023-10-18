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
            __DIR__.'/../config/config.php' => config_path('odata.php'),
//            __DIR__.'/../Routes/odata.php' => base_path('routes/odata.php'),
        ], 'odata');
//        $kernel->pushMiddleware(OdataRequestParser::class);

//        app()->register(OdataRouteServiceProvider::class);
        $this->mergeConfigFrom(__DIR__ . '/../config/odata.php', 'odata');
        $this->loadRoutesFrom(__DIR__.'/../Routes/odata.php');
    }

}
