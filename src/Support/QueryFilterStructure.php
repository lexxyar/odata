<?php

namespace Lexxsoft\Odata\Support;

class QueryFilterStructure
{
    public string $condition = 'and';
    public string $field;
    public int $group = 0;
    public string $operator = 'eq';
    public string $value = '';
}
