<?php

use Illuminate\Support\Facades\Route;
use LexxSoft\odata\Odata;

Route::prefix('odata')->group(function () {
  Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/{any}', function () {
//      $odata = new Odata($request);
      $odata = new Odata();
      return $odata->response();
    })->where('any', '^(?!(api|odata)).*$');
    Route::post('/{any}', function () {
//      $odata = new Odata($request);
      $odata = new Odata();
      return $odata->response();
    })->where('any', '^(?!(api|odata)).*$');
    Route::put('/{any}', function () {
//      $odata = new Odata($request);
      $odata = new Odata();
      return $odata->response();
    })->where('any', '^(?!(api|odata)).*$');
    Route::delete('/{any}', function () {
//      $odata = new Odata($request);
      $odata = new Odata();
      return $odata->response();
    })->where('any', '^(?!(api|odata)).*$');
  });
});
