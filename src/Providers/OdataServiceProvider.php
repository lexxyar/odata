<?php

namespace Lexxsoft\Odata\Providers;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Lexxsoft\Odata\Console\MakeOdataControllerCommand;
use Lexxsoft\Odata\Http\Middleware\OdataRequestParser;

//use Lexxsoft\Odata\Middleware\OdataRequestParser;


class OdataServiceProvider extends ServiceProvider
{
    public function boot(Kernel $kernel): void
    {
        // Add middleware to app
        $kernel->pushMiddleware(OdataRequestParser::class);

        $this->publishes([
            __DIR__ . '/../config/config.php' => config_path('odata.php'),
        ], 'odata');

        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'odata');

        if (config('odata.routes.register', false)) {
            $this->loadRoutesFrom(__DIR__ . '/../Routes/odata.php');
        }

        if ($this->app->runningInConsole()) {
            $this->commands(
                commands: [
                    MakeOdataControllerCommand::class,
                ]);
        }
    }

}
