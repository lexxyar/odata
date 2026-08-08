<?php

namespace Lexxsoft\Odata\Parsers;

use Lexxsoft\Odata\Support\QueryOrder;

class OrderParser
{
    public function __invoke(array $queryParameters): array
    {
        $keys = ['$orderby', '$order'];
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
            $res[] = new QueryOrder($part);
        }
        return $res;
    }
}
