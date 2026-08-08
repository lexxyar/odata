# Laravel OData REST

![Latest Stable Version](http://poser.pugx.org/lexxsoft/odata/v)
![GitHub](https://img.shields.io/github/license/lexxyar/odata)
![Package validation status](https://github.com/lexxyar/odata/actions/workflows/validating.yml/badge.svg)
![PHP Version Require](http://poser.pugx.org/lexxsoft/odata/require/php)
![License](http://poser.pugx.org/lexxsoft/odata/license)
![Total Downloads](http://poser.pugx.org/lexxsoft/odata/downloads)

# Contents

- [Installation](#installation)
    * [Requirements](#requirements)
    * [Setup](#setup)

# Installation

## Requirements

| Component | Version |
|-----------|--------|
| PHP       | 8.1    |
| Laravel   | 13    |

## Setup

```shell script
composer require lexxsoft/odata
```

## Controller example
```php
...
use Lexxsoft\Odata\RequestQuery;
...

public function index(Request $request): AnonymousResourceCollection
    {
        $requestQuery = RequestQuery::make();
        $perPage = $requestQuery->limit > 0 ? $requestQuery->limit : 10;
        $builder = $requestQuery->noLimit()->builder(App::make(Document::class));
        if (!$requestQuery->hasOrder()) {
            $builder = $builder->orderBy('created_at', 'desc');
        }
        if ($requestQuery->search !== '') {
            $builder = $builder->where('name', 'like', '%' . $requestQuery->search . '%');
        }
        if (isset($request->period)) {
            $startFrom = now()->subtract($request->period . ' days');
            $builder = $builder->where('created_at', '>=', $startFrom->format('Y-m-d'));
        }
        $docs = $builder->where('created_by', Auth::id())
            ->paginate($perPage)
            ->withQueryString();

        return DocumentResource::collection($docs);
    }
```
