<?php

namespace Lexxsoft\Odata\Services\Parsers;

class CountParser
{
    public function __invoke(array $queryParameters): bool
    {
        $keys = ['$count'];
        $value = '';
        foreach ($keys as $key) {
            if (isset($queryParameters[$key])) {
                $value = $queryParameters[$key];
                break;
            }
        }

        return !empty(trim($value)) && Str($value)->trim()->lower()->value() === 'true';
    }
}
