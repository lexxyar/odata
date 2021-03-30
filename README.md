# Laravel REST API OData-like

![GitHub](https://img.shields.io/github/license/lexxyar/odata)
![GitHub all releases](https://img.shields.io/github/downloads/lexxyar/odata/total)

# Installation
## Setup
```shell script
composer require lexxsoft/odata
```
>After installation all routes as `/odata/*` will be accessible via auth:api middleware

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
})->where('any', '^(?!(api|odata)).*');
```

# Customisation data selection
## Controller methods
By default OData look for `GetEntity`, `GetEntitySet`, `CreateEntity` and `UpdateEntity` methods. It means, that you can simply create this method in according controller.    

## Executing default data logic in controllers methods
If you need make some changes in selected data before responding, you can execute default logic of ODataEntity. For example:
```php
class PermissionController extends Controller
{
  public function GetEntitySet()
  {
    $odata = new OdataEntity('permission');
    $res = $odata->callDynamic();
    $res = $res->groupBy('action');
    return $res;
  }
}
``` 
>Note! Using this method is not recommended, if you can get data via database query.

## Returning controller result
Method should return two kinds of result:
1. Data array
1. Throw error
### Data array
If you return some data, the result will be applied to `ODataDefaultResource`.
### Throw error
If some logic should return some error, you must `throw \Exception`. This will generate `ODataErrorResource` response.   

# OData features
-[x] Metadata
-[x] CRUD
  -[x] **C**reate
  -[x] **R**ead
  -[x] **U**pdate
  -[x] **D**elete
-[x] OData Entity
-[ ] OData request
  -[ ] Resource path
    -[x] Simple request (i.e. `/odata/category`)
    -[x] Count request (i.e. `/odata/category/$count`)
    -[x] Request by key (i.e. `/odata/category(1)`)
    -[ ] Single field value request (i.e. `/odata/category(1)/name`)
    -[ ] Value request (i.e. `/odata/category(1)/name/$value`)
    -[ ] Nested entity request (i.e. `/odata/category(1)/products`)
    -[ ] Count nested entity (i.e. `/odata/category(1)/products/$count`)
    -[ ] Deep nested entity (i.e. `/odata/category(1)/products(2)/supplier/address/city/$value`)
  -[ ] System query options
    -[x] `$orderby`
    -[x] `$top`
    -[x] `skip`
    -[ ] `$filter`
      -[x] EQ
      -[x] NE
      -[x] GT
      -[x] GE
      -[x] LT
      -[x] LE
      -[ ] AND
      -[ ] OR
      -[ ] NOT
    -[ ] `$expand`
      -[x] Simple expand (i.e.`$expand=products`)
      -[ ] Deep expand (i.e. `$expand=products/supplier`)
    -[x] `$format` (JSON only supported)
    -[ ] `$select`
    -[ ] `$inlinecount`
  -[ ] Custom query options (i.e. `/odata/products?x=y`)
-[ ] Middleware routes (current is `auth:api`)

# Data manipulations
## Reading data
## Updating data
To update data you should use `PUT` method. Then, fill request body by new data.
> Note, that system wil search record by key field, like `id`, which should be passed in request body with other data fields

Request example:
```http request
PUT /odata/role(2)
```
```json
{
    "id": 2,
	"name":"User role"
}
```   
### Updating relations
To update Many-To-Many relationship, you need pass array of ID's for relation field name
```json
{
    "id": 2,
    "permissions": [5,6,7]
}
```  
### Updating retations with pivot
Somtimes Many-To-Many table has additional fields. To update them, pass array of objects for relation field.
> Note, **key** field is required.

## Creating data
## Deleting data

