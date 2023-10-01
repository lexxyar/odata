<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/$metadata', function (Request $request) {
    return (new \Lexxsoft\Odata\Odata())->metadata();
});
Route::any('/{any}', function (Request $request) {
    return (new \Lexxsoft\Odata\Odata())->makeResponse();
})->where('any', '.*');
