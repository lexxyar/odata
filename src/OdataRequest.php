<?php

namespace Lexxsoft\Odata;

use Lexxsoft\Odata\Services\Parsers\CountParser;
use Lexxsoft\Odata\Services\Parsers\ExpandParser;
use Lexxsoft\Odata\Services\Parsers\FilterParser;
use Lexxsoft\Odata\Services\Parsers\LimitParser;
use Lexxsoft\Odata\Services\Parsers\OffsetParser;
use Lexxsoft\Odata\Services\Parsers\OrderParser;
use Lexxsoft\Odata\Services\Parsers\SearchParser;
use Lexxsoft\Odata\Services\Parsers\SelectParser;
use Lexxsoft\Odata\Support\OdataFilter;

class OdataRequest
{
    public int $limit = -1;
    public array $select = [];
    public int $offset = -1;
    public array $filter = [];
    public array $order = [];
    public array $expand = [];
    public bool $count = false;
    public string $search = '';

    private static OdataRequest|null $instance = null;

    /**
     * Возвращает инстанцию класса
     * @return OdataRequest
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $queryParams = request()->all();

        // Парсинг $top
        $this->limit = (new LimitParser())($queryParams);

        // Парсинг $skip
        $this->offset = (new OffsetParser())($queryParams);

        // Парсинг строки сортировки
        $this->order = (new OrderParser())($queryParams);

        // Парсинг строки $expand
        $this->expand = (new ExpandParser())($queryParams);

        // Парсинг $select
        $this->select = (new SelectParser())($queryParams);

        //Парсинг строки фильтра
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

    public function hasExpand(): bool
    {
        return sizeOf($this->expand) > 0;
    }

    public function hasLimit(): bool
    {
        return $this->limit > 0;
    }

    public function hasOffset(): bool
    {
        return $this->limit > 0;
    }

    protected function defaults(): array
    {
        return get_class_vars(get_class($this));
    }

    public function reset(string|array $items = '*'): void
    {
        $aKeys = $this->defaults();

        if (is_array($items)) {
            if (in_array('*', $items)) {
                foreach ($aKeys as $sKey => $default) {
                    $this->$sKey = $default;
                }
            } else {
                foreach ($items as $item) {
                    if (in_array($item, array_keys($aKeys))) {
                        $this->$item = $aKeys[$item];
                    }
                }
            }
        } else {
            if ($items == '*') {
                foreach ($aKeys as $sKey => $default) {
                    $this->$sKey = $default;
                }
            } else {
                if (in_array($items, array_keys($aKeys))) {
                    $this->$items = $aKeys[$items];
                }
            }
        }
    }

    public function getFilterByField(string $fieldName): OdataFilter|bool
    {
        $filterIndex = array_search($fieldName, array_column($this->filter, 'field'));
        if ($filterIndex !== false) {
            return $this->filter[$filterIndex];
        }
        return false;
    }
}
