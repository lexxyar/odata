<?php

namespace Lexxsoft\Odata\Providers;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class OdataRouteServiceProvider extends ServiceProvider
{
    public function boot(Kernel $kernel): void
    {
        Route::prefix('odata')
            ->group(base_path('routes/odata.php'));
    }
}
