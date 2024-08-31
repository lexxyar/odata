<?php

namespace Lexxsoft\Odata\Services\Parsers;

class OffsetParser
{
    public function __invoke(array $queryParameters): int
    {
        $keys = ['$skip', '$offset'];
        $value = '';
        foreach ($keys as $key) {
            if (isset($queryParameters[$key])) {
                $value = $queryParameters[$key];
                break;
            }
        }

        return empty(trim($value)) ? -1 : Str($value)->trim()->toInteger();
    }
}
