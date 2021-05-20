<?php

namespace LexxSoft\odata;

use Illuminate\Support\ServiceProvider;

class OdataServiceProvider extends ServiceProvider
{
  public function boot()
  {
    $this->publishes([
      __DIR__ . '/../config/odata.php' => config_path('odata.php'),
    ]);
    $this->loadRoutesFrom(__DIR__ . '/routes/api.php');

  }

  public function register()
  {
    $this->mergeConfigFrom(__DIR__ . '/../config/odata.php', 'odata');
    parent::register();
  }

}
