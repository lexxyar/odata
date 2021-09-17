# Laravel REST API OData-like

![GitHub](https://img.shields.io/github/license/lexxyar/odata)
![GitHub package.json version](https://img.shields.io/github/package-json/v/lexxyar/odata)

# Contents
- [Installation](#installation)
- [Customisation data selection](#customisation-data-selection)
    - [Controller methods](#controller-methods)
    - [Executing default data logic in controllers methods](#executing-default-data-logic-in-controllers-methods)
    - [Returning controller result](#returning-controller-result)
- [Working with files](#working-with-files)
    - [Upload files](#upload-files)
        - [Create new file](#create-new-file)
        - [Update file](#update-file)
    - [Delete file](#delete-file)
    - [Get file list](#get-file-list)
    - [Download file](#download-file)
    - [Database table obligatory fields](#database-table-obligatory-fields)
- [OData features](#odata-features)
- [Data manipulation](#data-manipulations)
    - [Reading data](#reading-data)
    - [Updating data](#updating-data)
    - [Creating data](#creating-data)
    - [Deleting data](#deleting-data)
- [Data validation](#data-validation)
    - [How to define rules](#how-to-define-rules)

# Installation
## Setup
```shell script
composer require lexxsoft/odata
```
>After installation all routes as `/odata/*` will be accessible

>__Note__: `/odata/*` routes use `auth:api` middleware. To override this, run command
>```shell script
>php artisan vendor:publish --provider="LexxSoft\odata\OdataServiceProvider"
>``` 
>After that `config/odata.php` file will appear, and you could change `routes_middleware` parameter as you want.

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

# Configuration
OData package contain configuration, witch help to make your application more flexible. 
To start, run command
```shell script
php artisan vendor:publish --provider=LexxSoft\odata\OdataServiceProvider
``` 
After that `config/odata.php` file will appear, and you could change default configuration.

Parameter|Type|Description|Default value
---|---|---|---
routes_middleware|Array|Additional middleware for OData routes|`['auth:api']`
upload_dir|String|Laravel Storage path for uploaded files|`uploads`

# Customisation data selection
## Controller methods
By default OData look for `GetEntity`, `GetEntitySet`, `CreateEntity`, `UpdateEntity` and `UploadFile` methods. It means, that you can simply create this method in your controller.    

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

# Working with files
## Upload files
OData process use `UploadFile` method of custom controller. Also, you can use dynamic package method to upload file for bind model.
Model mast use `IsFile` trait, to be able to make actions with files
>Important note! Your database table must have fields, described [below](#database-table-obligatory-fields).
>Also, you can pass additional data for table fields. Additional obligatory fields will **no effect**. 

In result filename will be like `cust_aab3238922bcc25a6f606eb525ffdc56`

### Create new file
To upload new file use HTTP `POST` method **without any keys**. In this case filename wil be generated as `cust_` prefix and `MD5` hash of database record ID.

### Update file
To update existing file, use HTTP `POST` method **with table key**

## Delete file
To delete record in database use HTTP `DELETE` method, as for simple REST model. As result, record will be deleted so as associated file in storage.

## Get file list
Because of using table for uploaded files, use HTTP `GET` request to get list of files, just like for simple OData entity.

## Download file
Downloading file content has own specific URL.
Use URL template
```http request
GET <REST API URL>/<entity name>(<key>)/_file
```
Another words: use simple [reading data](#reading-data) request with key to get single record and add `/_file` to end of the request.
```http request
GET /odata/files(1)/_file
```

## Database table obligatory fields
Field name|Laravel data type
---|---
id|id
name|string
ext|string
mime|string

Or just copy migration pattern bellow
```php
Schema::create('files', function (Blueprint $table) {
  $table->id();
  $table->string('name', 100);
  $table->string('ext', 10);
  $table->string('mime', 60);
  $table->timestamps();
});
```

# OData features
- [x] Metadata
- [x] CRUD
  - [x] **C**reate
  - [x] **R**ead
  - [x] **U**pdate
  - [x] **D**elete
- [x] OData Entity
- [ ] OData request
  - [ ] Resource path
    - [x] Simple request (i.e. `/odata/category`)
    - [x] Count request (i.e. `/odata/category/$count`)
    - [x] Request by key (i.e. `/odata/category(1)`)
    - [ ] Single field value request (i.e. `/odata/category(1)/name`)
    - [ ] Value request (i.e. `/odata/category(1)/name/$value`)
    - [ ] Nested entity request (i.e. `/odata/category(1)/products`)
    - [ ] Count nested entity (i.e. `/odata/category(1)/products/$count`)
    - [ ] Deep nested entity (i.e. `/odata/category(1)/products(2)/supplier/address/city/$value`)
  - [ ] System query options
    - [x] `$orderby`
    - [x] `$top`
    - [x] `skip`
    - [ ] `$filter`
      - [x] EQ
      - [x] NE
      - [x] GT
      - [x] GE
      - [x] LT
      - [x] LE
      - [ ] AND
      - [ ] OR
      - [ ] NOT
      - [X] substringof
      - [X] endswith
      - [X] startswith
    - [ ] `$expand`
      - [x] Simple expand (i.e.`$expand=products`)
      - [x] Deep expand (i.e. `$expand=products/supplier`)
    - [x] `$format` (JSON only supported)
    - [X] `$select`
    - [ ] `$inlinecount`
  - [ ] Custom query options (i.e. `/odata/products?x=y`)
- [X] Middleware routes (current is `auth:api`)

# Data manipulations
## Reading data
To read data, use `GET` request. Also you can add parameters to your query from `OData features` section
```http request
GET /odata/role?$top=5
```

Reading user with ID = 1 
```http request
GET /odata/user(1)
```

### Reading `SoftDelete` data
To get data with trashed records, use `$force` flag in query request
```http request
GET /odata/role?$force=true
```

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
```json
 {
     "id": 2,
     "permissions": [
        {"id": 5, "author": "Larry"},
        {"id": 8, "author": "John"}
     ]
 }
 ``` 

## Creating data
To create new record, use `POST` request type
```http request
POST /odata/role
```
```json
{
    "name": "New role"
}
```

## Deleting data
To delete data, use `DELETE` request with record key
```http request
DELETE /odata/role(2)?$force=true
```
### SoftDelete
To delete data permanently, use `$force` query parameter.
```http request
DELETE /odata/role(2)?$force=true
```

## Save passwords
To save password as Laravel Hash in database, you shoud send `password` field with password content to `POST` or `PUT` request.

# Data validation
Data validation use standard Laravel `Illuminate\Support\Facades\Validator` with its rules. 
There is one moment in **error** response - error response message will contain format and 
validation response, as shown in example below:
```json
{
  "error": {
    "code": 0,
    "message": "json:{\"email\":[\"validation.required\"]}"
  }
}
```  
In this case format is `json` then `:`(column) separator and JSON body

## How to define rules
When you use `IsRestable` trait, you can define `public` variable `$validationRules`, witch contain
validation rules like in [Laravel article](https://laravel.com/docs/8.x/validation). Available rules are 
[documented here](#https://laravel.com/docs/8.x/validation#available-validation-rules)
 
