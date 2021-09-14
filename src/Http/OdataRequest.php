<?php


namespace LexxSoft\odata\Http;

use stdClass;

/**
 * Class OdataRequest
 * @package LexxSoft\odata\Http
 */
class OdataRequest
{
  /**
   * @var array
   */
  public $filter = [];

  /**
   * @var array
   */
  public $order = [];

  /**
   * @var array
   */
  public $expand = [];

  /**
   * @var int
   */
  public $limit = -1;

  /**
   * @var int
   */
  public $offset = -1;

  /**
   * @var array|false|string[]
   */
  public $requestPathParts = [];

  /**
   * @var null
   */
  private static $instance = null;

  /**
   * @var bool
   */
  private $countRequested = false;

  /**
   * @var bool
   */
  public $force = false;

  /**
   * @var array
   */
  public $select = [];

  /**
   * Возвращает инстанцию класса
   * @return OdataRequest
   */
  public static function getInstance(): OdataRequest
  {
    if (self::$instance === null) {
      self::$instance = new OdataRequest();
    }
    return self::$instance;
  }

  /**
   * OdataRequest constructor.
   */
  public function __construct()
  {
    $this->requestPathParts = explode('/', request()->getPathInfo());
    array_shift($this->requestPathParts); // Убираем пустой
    array_shift($this->requestPathParts); // убираем 'odata'
    $this->countRequested = in_array(urlencode('$count'), $this->requestPathParts)
      || in_array('$count', $this->requestPathParts);

    $queryParams = request()->all();

    if (isset($queryParams['$filter'])) {
      $this->parseFilter($queryParams['$filter']);
    }
    if (isset($queryParams['$orderby'])) {
      $this->parseOrder($queryParams['$orderby']);
    }
    if (isset($queryParams['$expand'])) {
      $this->parseExpand($queryParams['$expand']);
    }
    if (isset($queryParams['$top'])) {
      $this->parseLimit($queryParams['$top']);
    }
    if (isset($queryParams['$skip'])) {
      $this->parseOffset($queryParams['$skip']);
    }
    if (isset($queryParams['$force'])) {
      $this->force = true;
    }
    if (isset($queryParams['$select'])) {
      $this->parseSelect($queryParams['$select']);
    }
  }

  public function isCountRequested()
  {
    return $this->countRequested;
  }

  /**
   * Парсинг строки фильтра
   * @param string $sFilterString
   */
  private function parseFilter(string $sFilterString)
  {
//    $re = '/(?<Filter>(?<Field>[^ ]+?)\s(?<Operator>eq|ne|gt|ge|lt|le|)\s\'?(?<Value>.+?))\'?\s*(?:[^\']*$|\s+(?<Condition>:|or|and|not))/m';
//    preg_match_all($re, $sFilterString, $matches, PREG_SET_ORDER, 0);
//    dd($sFilterString, $matches);
    $words = explode(' ', $sFilterString);

    $quote = 0;
    $text = '';
    $stage = 0;
    $matches = [];
    $o = new stdClass();
    $group = 0;
    foreach ($words as $word) {
      $text = trim(implode(' ', [$text, $word]));
      $quoteCount = substr_count($word, "'");
      $quote += $quoteCount;
      if ($quote % 2 != 0) continue;

      switch ($stage) {
        case 0: // Field
          if (str_starts_with($text, '(')) {
            $group++;
            $text = substr($text, 1);
          }
          $o = new stdClass();
          $o->Field = $text;
          $o->Group = $group;
          $stage++;
          $matches[] = $o;
          break;
        case 1: // Sign
          if (in_array($text, explode(',', 'eq,ne,lt,le,gt,ge'))) {
            $o->Operator = $text;
            $stage++;
          } else {
            $o->Field .= $text;
          }
          break;
        case 2: // Value
          if (str_ends_with($text, ')')) {
            $group--;
            $text = substr($text, 0, -1);
          }
          $o->Value = $text;
          $stage++;
          break;
        case 3: // Binary operation
          $o->Condition = $text;
          $stage = 0;
          break;
      }
      $text = '';
    }
    foreach ($matches as $match) {
      $oFilter = new OdataFilter((array)$match);
      $this->filter[] = $oFilter;
    }
  }

  /**
   * Парсинг строки сортировки
   * @param string $sString
   */
  private function parseOrder(string $sString): void
  {
    if (!$sString) return;

    $aOrderParts = explode(',', $sString);
    foreach ($aOrderParts as $sOrderPart) {
      $oOrder = new OdataOrder(trim($sOrderPart));
      $this->order[] = $oOrder;
    }
  }

  /**
   * Парсинг строки $expand
   * @param string $sExpand
   */
  private function parseExpand(string $sExpand)
  {
    $this->expand = [];
    $a = explode(',', $sExpand);
    foreach ($a as $item) {
      $this->expand[] = trim($item);
    }
  }

  /**
   * Парсинг $top
   * @param $sLimit
   */
  private function parseLimit($sLimit)
  {
    $this->limit = $sLimit;
  }

  /**
   * Парсинг $skip
   * @param $sOffset
   */
  private function parseOffset($sOffset)
  {
    $this->offset = $sOffset;
  }

  /**
   * Парсинг $select
   * @param $sSelect
   */
  private function parseSelect($sSelect){
    $this->select = [];
    $a = explode(',', $sSelect);
    foreach ($a as $item) {
      $this->select[] = trim($item);
    }
  }
}
