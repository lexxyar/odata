<?php

namespace Lexxsoft\Odata\Support;

class QueryOrder
{
    public string $field = '';
    public QueryOrderDirection $direction = QueryOrderDirection::ASC;

    /**
     * OdataOrder constructor
     */
    public function __construct(string $string)
    {
        $value = Str($string)->trim()->replaceMatches(pattern: '/[\s\t]{2,}/', replace: ' ');

        $parts = explode(' ', $value);
        if (sizeof($parts) === 2) {
            $this->field = Str($parts[0])->trim()->lower();
            $this->direction = QueryOrder::convert($parts[1]);
        }
        if (sizeof($parts) === 1) {
            $this->field = Str($parts[0])->trim()->lower();
            $this->direction = QueryOrderDirection::ASC;
        }
    }

    /**
     * Конвертирует значение константы в текстовое значение
     */
    private static function convert(string $value): QueryOrderDirection
    {
        return QueryOrderDirection::tryFrom(strtoupper(trim($value))) ?? QueryOrderDirection::ASC;
    }
}
