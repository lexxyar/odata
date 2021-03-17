<?php

namespace LexxSoft\odata;

use Illuminate\Support\ServiceProvider;

class OdataServiceProvider extends ServiceProvider
{
    public function boot(){
        $this->loadRoutesFrom(__DIR__.'/routes/api.php');

    }
    public function register()
    {
        parent::register();
    }

}
