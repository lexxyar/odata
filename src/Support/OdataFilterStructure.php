<?php


namespace Lexxsoft\Odata\Support;

class OdataFilterStructure
{
    public string $condition = 'and';
    public string $field;
    public int $group = 0;
    public string $operator = 'eq';
    public string $value = '';
}
