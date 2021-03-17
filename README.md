# Laravel REST API OData-like

![GitHub](https://img.shields.io/github/license/lexxyar/odata)
![GitHub all releases](https://img.shields.io/github/downloads/lexxyar/odata/total)

# Installation
## Setup
```shell script
composer require lexxsoft/odata
```
>After installation all routes as `/api/*` will be accessible via auth:api middlevare

## Update model requirements 
To make model as OData entity, you must use `IsRestable` trait.
```php
use LexxSoft\odata\Primitives\IsRestable;

class Log extends Model
{
    use HasFactory, IsRestable;
}
```  

## SPA routes
If you create single padge applicatio, you may need to  modify `/routes/web.php` file like this
```php
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*');
```
