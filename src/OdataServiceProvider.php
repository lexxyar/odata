<?php

namespace LexxSoft\odata;

use Illuminate\Support\ServiceProvider;

class OdataServiceProvider extends ServiceProvider
{
  public function boot()
  {
    $source = __DIR__ . '/config/odata.php';
    $this->publishes([
      $source => config_path('odata.php'),
    ]);
    $this->mergeConfigFrom($source, 'odata');
    $this->loadRoutesFrom(__DIR__ . '/routes/api.php');

  }

  public function register()
  {
    $this->mergeConfigFrom(__DIR__ . '/config/odata.php', 'odata');
    parent::register();
  }

}
