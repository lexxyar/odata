<?php

namespace Lexxsoft\Odata\Services\Parsers;

use Lexxsoft\Odata\OdataOrder;

class SearchParser
{
    public function __invoke(array $queryParameters): string
    {
        $keys = ['$search'];
        $value = '';
        foreach ($keys as $key) {
            if (isset($queryParameters[$key])) {
                $value = $queryParameters[$key];
                break;
            }
        }

        return Str($value)->trim()->value();
    }
}
