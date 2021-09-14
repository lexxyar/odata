<?php


namespace LexxSoft\odata\Http;

/**
 * Class OdataFilterStructure
 *
 * Структура фильтра
 *
 * @package LexxSoft\odata\Http
 */
class OdataFilterStructure
{
  public $condition = 'and';
  public $field;
  public $group = 0;
  public $operator = 'eq';
  public $value = '';
}
