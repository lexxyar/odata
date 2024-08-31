<?php

namespace Lexxsoft\Odata\Services\Parsers;

use Lexxsoft\Odata\Support\OdataOrder;

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
            $res[] = new OdataOrder($part);
        }
        return $res;
    }
}
