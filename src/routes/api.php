<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
//    Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/{any}', function (Request $request) {
        $odata = new \LexxSoft\odata\Odata($request);
        return $odata->response();
    })->where('any', '^(?!api).*$');
    Route::post('/{any}', function (Request $request) {
        $odata = new \LexxSoft\odata\Odata($request);
        return $odata->response();
    })->where('any', '^(?!api).*$');
    Route::put('/{any}', function (Request $request) {
        $odata = new \LexxSoft\odata\Odata($request);
        return $odata->response();
    })->where('any', '^(?!api).*$');
    Route::delete('/{any}', function (Request $request) {
        $odata = new \LexxSoft\odata\Odata($request);
        return $odata->response();
    })->where('any', '^(?!api).*$');
});
//});
