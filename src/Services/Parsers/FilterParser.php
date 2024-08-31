<?php

namespace Lexxsoft\Odata\Services\Parsers;

use Lexxsoft\Odata\Support\OdataFilter;
use Lexxsoft\Odata\Support\OdataFilterStructure;

class FilterParser
{
    public function __invoke(array $queryParameters): array
    {
        $keys = ['$filter'];
        $value = '';
        foreach ($keys as $key) {
            if (isset($queryParameters[$key])) {
                $value = $queryParameters[$key];
                break;
            }
        }

        if (empty(trim(($value)))) return [];

        $filterString = Str($value)->trim()->replaceMatches(pattern: '/[\s\t]{2,}/', replace: ' ')->value();

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

        $res = [];
        foreach ($matches as $match) {
            $res[] = new OdataFilter($match);
        }
        return $res;
    }
}
