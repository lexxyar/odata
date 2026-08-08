<?php

namespace Lexxsoft\Odata\Parsers;

class PageParser
{
    public function __invoke(array $queryParameters): int
    {
        $keys = ['page'];
        $value = '';
        foreach ($keys as $key) {
            if (isset($queryParameters[$key])) {
                $value = $queryParameters[$key];
                break;
            }
        }

        return empty(trim($value)) ? 1 : Str($value)->trim()->toInteger();
    }
}
