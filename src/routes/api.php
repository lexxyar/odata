<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use LexxSoft\odata\Odata;

$config = Config::get('odata');

Route::middleware($config['routes_middleware'])->prefix('odata')->group(function () {
  Route::get('/{any}/_file', function () {
    $odata = new \LexxSoft\odata\OdataFile();
    return $odata->response();
  })->where('any', '^(?!(api|odata)).*$');
  Route::get('/{any}/_file64', function () {
    $odata = new \LexxSoft\odata\OdataFile();
    return $odata->response64();
  })->where('any', '^(?!(api|odata)).*$');
  Route::get('/{any}', function () {
    $odata = new Odata();
    return $odata->response();
  })->where('any', '^(?!(api|odata)).*$');
  Route::post('/{any}', function () {
    $odata = new Odata();
    return $odata->response();
  })->where('any', '^(?!(api|odata)).*$');
  Route::put('/{any}', function () {
    $odata = new Odata();
    return $odata->response();
  })->where('any', '^(?!(api|odata)).*$');
  Route::delete('/{any}', function () {
    $odata = new Odata();
    return $odata->response();
  })->where('any', '^(?!(api|odata)).*$');
});
