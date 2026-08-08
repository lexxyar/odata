<?php

namespace Lexxsoft\Odata;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Lexxsoft\Odata\Parsers\CountParser;
use Lexxsoft\Odata\Parsers\FilterParser;
use Lexxsoft\Odata\Parsers\LimitParser;
use Lexxsoft\Odata\Parsers\OffsetParser;
use Lexxsoft\Odata\Parsers\OrderParser;
use Lexxsoft\Odata\Parsers\PageParser;
use Lexxsoft\Odata\Parsers\SearchParser;
use Lexxsoft\Odata\Parsers\SelectParser;
use Lexxsoft\Odata\Support\QueryFilter;
use Lexxsoft\Odata\Support\QueryOrder;

class RequestQuery
{
    public int $limit = -1;
    public int $page = 1;
    public array $select = [];
    public int $offset = -1;
    public array $filter = [];
    public array $order = [];
    public bool $count = false;
    public string $search = '';

    public static function make(...$parameters): static
    {
        return new static(...$parameters);
    }

    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $queryParams = request()->all();

        // Парсинг top (limit)
        $this->limit = (new LimitParser())($queryParams);

        // Парсинг page
        $this->page = (new PageParser())($queryParams);

        // Парсинг skip (offset)
        $this->offset = (new OffsetParser())($queryParams);

        // Парсинг order
        $this->order = (new OrderParser())($queryParams);

        // Парсинг select
        $this->select = (new SelectParser())($queryParams);

        //Парсинг filter
        $this->filter = (new FilterParser())($queryParams);

        // Парсинг параметра $count
        $this->count = (new CountParser())($queryParams);

        // Парсинг $search
        $this->search = (new SearchParser())($queryParams);
    }

    public function hasFilter(): bool
    {
        return sizeOf($this->filter) > 0;
    }

    public function hasOrder(): bool
    {
        return sizeOf($this->order) > 0;
    }

    public function hasSelect(): bool
    {
        return sizeOf($this->select) > 0;
    }

    public function hasLimit(): bool
    {
        return $this->limit > 0;
    }

    public function hasOffset(): bool
    {
        return $this->offset > 0;
    }

    public function noLimit(): self
    {
        $this->limit = -1;
        return $this;
    }

    public function noOffset(): self
    {
        $this->offset = -1;
        return $this;
    }

    public function builder(Model $model, bool $applyGlobalScope = true): \Illuminate\Database\Eloquent\Builder
    {
        $queryBuilder = $model->newModelQuery();

        if ($applyGlobalScope) {
            /** @var Scope $scope */
            foreach ($model->getGlobalScopes() as $scope) {
                $scope->apply($queryBuilder, $model);
            }
        }

        $modelTable = $model->getTable();

        // Filter
        if ($this->hasFilter()) {
            foreach ($this->filter as $oFilter) {
                if ($oFilter instanceof QueryFilter) {
                    $parts = $oFilter->toArray($modelTable);
                    $queryBuilder->where($parts[0], $parts[1], $parts[2], $parts[3]);
                }
            }
        }

        // Order
        if ($this->hasOrder()) {
            foreach ($this->order as $order) {
                if ($order instanceof QueryOrder) {
                    $queryBuilder->orderBy($order->field, $order->direction->value);
                }
            }
        }

        // select
        if ($this->hasSelect()) {
            $queryBuilder->select($this->select);
        }

        // limit
        if ($this->hasLimit()) {
            $queryBuilder->limit($this->limit);
        }

        // offset
        if ($this->hasOffset()) {
            $queryBuilder->offset($this->offset);
        }

        return $queryBuilder;
    }
}
