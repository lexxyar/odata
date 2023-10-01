# Laravel OData REST

![GitHub](https://img.shields.io/github/license/lexxyar/odata)
![GitHub package.json version](https://img.shields.io/github/package-json/v/lexxyar/odata)

# Contents

- [Installation](#installation)
    * [Requirements](#requirements)
    * [Setup](#setup)
    * [Update model requirements](#update-model-requirements)
- [OData features](#odata-features)
- [Data manipulations](#data-manipulations)
    * [Reading data](#reading-data)
    * [Updating data](#updating-data)
        + [Updating relations](#updating-relations)
        + [Updating relations with pivot](#updating-relations-with-pivot)
    * [Creating data](#creating-data)
    * [Deleting data](#deleting-data)
- [Spatie laravel permissions](#spatie-laravel-permissions)

# Installation

## Requirements

| Component | Version |
|-----------|---------|
| PHP       | 8.1     |
| Laravel   | 10.5    |

## Setup

```shell script
composer require lexxsoft/odata
```

After installation all routes as `/odata/*` will be accessible

```shell script
php artisan vendor:publish --provider="LexxSoft\odata\OdataServiceProvider"
``` 

After that `config/odata.php` file will appear.

## Update model requirements

To make model as OData entity, you must use `Restable` trait.

```php
use LexxSoft\odata\Traits\Restable;

class Log extends Model
{
    use HasFactory, Restable;
}
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
        - [X] Single field value request (i.e. `/odata/category(1)/name`)
        - [X] Value request (i.e. `/odata/category(1)/name/$value`)
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
        - [X] `$expand`
            - [x] Simple expand (i.e.`$expand=products`)
            - [x] Deep expand (i.e. `$expand=products/supplier`)
            - [X] Expand with count (i.e. `$expand=products($count=true)`)
        - [X] `$select`
        - [X] `$count=true` (ex. `$inlinecount`)
    - [X] Custom query options (i.e. `/odata/products?x=y`)

# Data manipulations

## Reading data

To read data, use `GET` request. Also, you can add parameters to your query from `OData features` section

```http request
GET /odata/role?$top=5
```

Reading user with ID = 1

```http request
GET /odata/user(1)
```

## Updating data

To update data you should use `PUT` method. Then, fill request body by new data.

Request example:

```http request
PUT /odata/role(2)
```

```json
{
  "name": "User role"
}
``` 

### Updating relations

To update Many-To-Many relationship, you need pass array of ID's for relation field name

```json
{
  "id": 2,
  "permissions": [
    5,
    6,
    7
  ]
}
```

### Updating relations with pivot

Sometimes Many-To-Many table has additional fields. To update them, pass array of objects for relation field.
> Note, **key** field is required.

```json
 {
  "id": 2,
  "permissions": [
    {
      "id": 5,
      "author": "Larry"
    },
    {
      "id": 8,
      "author": "John"
    }
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
DELETE /odata/role(2)
```

# Spatie laravel permissions

If you use [laravel-permission](https://spatie.be/docs/laravel-permission/v5/introduction) from Spatie, then `Role`model
and `Permission` model not use `Restable` trait by default. To make them RESTable, you should create yore own models
(for example, via `php artisam make:model` command) and extends yore new models from
`\Spatie\Permission\Models\*` models

```php
/** Extended Role model */
namespace App\Models;

use Lexxsoft\Odata\Primitives\Restable;

class Role extends \Spatie\Permission\Models\Role
{
  use Restable;
}
```

```php
/** Extended Permission model */
namespace App\Models;

use Lexxsoft\Odata\Traits\Restable;

class Permission extends \Spatie\Permission\Models\Permission
{
    use Restable;
}
```
