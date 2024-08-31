<?php

namespace Lexxsoft\Odata\Services\Parsers;

class SelectParser
{
    public function __invoke(array $queryParameters): array
    {
        $keys = ['$select'];
        $value = '';
        foreach ($keys as $key) {
            if (isset($queryParameters[$key])) {
                $value = $queryParameters[$key];
                break;
            }
        }

        if (empty(trim($value))) return [];

        $res = [];
        $parts = explode(',', $value);
        foreach ($parts as $part) {
            $res[] = Str($part)->trim()->lower();
        }
        return $res;
    }
}
