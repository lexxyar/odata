<?php

namespace Lexxsoft\Odata;

class OdataRequest
{
    public string $cacheKey = '';
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
        $this->cacheKey = $this->cacheKey();
        $queryParams = request()->all();
        if (isset($queryParams['$top'])) {
            $this->parseLimit($queryParams['$top']);
        }
        if (isset($queryParams['$limit'])) {
            $this->parseLimit($queryParams['$limit']);
        }
        if (isset($queryParams['$skip'])) {
            $this->parseOffset($queryParams['$skip']);
        }
        if (isset($queryParams['$orderby'])) {
            $this->parseOrder($queryParams['$orderby']);
        }
        if (isset($queryParams['$order'])) {
            $this->parseOrder($queryParams['$order']);
        }
        if (isset($queryParams['$expand'])) {
            $this->parseExpand($queryParams['$expand']);
        }
        if (isset($queryParams['$select'])) {
            $this->parseSelect($queryParams['$select']);
        }
        if (isset($queryParams['$filter'])) {
            $this->parseFilter($queryParams['$filter']);
        }
        if (isset($queryParams['$count'])) {
            $this->parseCount($queryParams['$count']);
        }
        if (isset($queryParams['$search'])) {
            $this->parseSearch($queryParams['$search']);
        }
    }

    private function cacheKey(): string
    {
        $aKeys = ['limit', 'select', 'offset', 'filter', 'order', 'expand', 'search'];
        sort($aKeys);
        $aValues = [];
        foreach ($aKeys as $sKey) {
            $aValues[] = json_encode($this->$sKey);
        }
        $str = \request()->method() . json_encode($aValues);
        return md5($str);
    }

    public function getFilterByField(string $fieldName): OdataFilter|bool
    {
        $filterIndex = array_search($fieldName, array_column($this->filter, 'field'));
        if ($filterIndex !== false) {
            return $this->filter[$filterIndex];
        }
        return false;
    }

    /**
     * Парсинг $top
     */
    private function parseLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * Парсинг $skip
     */
    private function parseOffset(int $offset): void
    {
        $this->offset = $offset;
    }

    /**
     * Парсинг $select
     */
    private function parseSelect(string $select): void
    {
        $this->select = [];
        $a = explode(',', $select);
        foreach ($a as $item) {
            $this->select[] = trim($item);
        }
    }

    /**
     * Парсинг $search
     */
    private function parseSearch(string $value): void
    {
        $this->search = $value;
    }

    /**
     * Парсинг строки $expand
     */
    private function parseExpand(string $value): void
    {
        $this->expand = [];
        $a = explode(',', $value);
        foreach ($a as $item) {
            $exp = new OdataExpand(trim($item));
            $this->expand[] = $exp;
//            $this->expand[] = trim($item);
        }


    }

    /**
     * Парсинг строки сортировки
     */
    private function parseOrder(string $string): void
    {
        if (!$string) return;

        $aOrderParts = explode(',', $string);
        foreach ($aOrderParts as $sOrderPart) {
            $oOrder = new OdataOrder(trim($sOrderPart));
            $this->order[] = $oOrder;
        }
    }

    /**
     * Парсинг строки фильтра
     */
    private function parseFilter(string $filterString): void
    {
        $words = explode(' ', $filterString);

        $quote = 0;
        $text = '';
        $stage = 0;
        $matches = [];
        /** @var OdataFilterStructure $o */
        $o = null;
        $group = 0;
        $isFirst = true;
        foreach ($words as $word) {
            $text = trim(implode(' ', [$text, $word]));
            $quoteCount = substr_count($word, "'");
            $quote += $quoteCount;
            if ($quote % 2 != 0) continue;

            if ($isFirst) {
                $o = new OdataFilterStructure();
                $matches[] = $o;
                $stage++;
            }

            switch ($stage) {
                case 0: // Binary operation
                    $o = new OdataFilterStructure();
                    $matches[] = $o;
                    $o->condition = $text;
                    $stage++;
                    break;
                case 1: // Field
                    if (str_starts_with($text, '(')) {
                        $group++;
                        $text = substr($text, 1);
                    }
                    $o->field = $text;
                    $o->group = $group;
                    $stage++;
                    break;
                case 2: // Sign
                    if (in_array($text, explode(',', 'eq,ne,lt,le,gt,ge'))) {
                        $o->operator = $text;
                        $stage++;
                    } else {
                        $o->field .= $text;
                    }
                    break;
                case 3: // Value
                    if (str_ends_with($text, ')')) {
                        $group--;
                        $text = substr($text, 0, -1);
                    }
                    $o->value = $text;
                    $stage = 0;
                    break;

            }
            $text = '';
            if ($isFirst) {
                $isFirst = false;
            }
        }


        foreach ($matches as $match) {
            $oFilter = new OdataFilter($match);
            $this->filter[] = $oFilter;
        }
    }

    /**
     * Парсинг параметра $count
     */
    private function parseCount(string $value): void
    {
        if (!$value) return;

        $this->count = strtolower($value) === 'true';
    }
}
