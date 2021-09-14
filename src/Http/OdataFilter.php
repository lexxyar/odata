<?php


namespace LexxSoft\odata\Http;

/**
 * Class OdataFilter
 * @package LexxSoft\odata\Http
 */
class OdataFilter
{
  /**
   * @var string
   */
  public $sFilter;

  /**
   * @var string
   */
  public $sField;

  /**
   * @var string
   */
  public $sSign;

  /**
   * @var string
   */
  public $sValue;

  /**
   * @var boolean
   */
  public $bGroup;

  /**
   * @var string
   */
  public $sCondition;

  const _EQ_ = 'EQ';
  const _GT_ = 'GT';
  const _GE_ = 'GE';
  const _LT_ = 'LT';
  const _LE_ = 'LE';
  const _CP_ = 'LIKE';
  const _NE_ = 'NE';
  const _LIKE_ = 'LIKE';

  /**
   * @var string
   */
  private $sTable = '';

  /**
   * Устанавливает имя таблицы
   * @param string $sTableName
   */
  public function setTable(string $sTableName)
  {
    $this->sTable = $sTableName;
  }

  /**
   * OdataFilter constructor.
   * @param OdataFilterStructure $oMatch
   */
  public function __construct($oMatch)
  {
    $this->sField = $oMatch->field;

    if (str_starts_with($this->sField, 'substringof') ||
      str_starts_with($this->sField, 'endswith') ||
      str_starts_with($this->sField, 'startswith')) {

      $sPattern = "{value}";
      if (str_starts_with($this->sField, 'substringof')) {
        $sPattern = "%" . $sPattern . "%";
      } elseif (str_starts_with($this->sField, 'endswith')) {
        $sPattern = "%" . $sPattern;
      } elseif (str_starts_with($this->sField, 'startswith')) {
        $sPattern = $sPattern . "%";
      }

      $re = '/.+\((?<Field>.+),\s*\'?(?<Value>.+[^\'])\'?\)/m';
      $reReplace = '/\{\S+\}/m';

      preg_match_all($re, $this->sField, $matches, PREG_SET_ORDER, 0);
      foreach ($matches as $match) {
        $this->sField = $match['Field'];
        $this->sValue = preg_replace($reReplace, $match['Value'], $sPattern);
        $this->sSign = self::_LIKE_;
      }
    } else {
      $this->sSign = strtoupper($oMatch->operator);
      $this->sValue = $oMatch->value;
      $this->bGroup = $oMatch->group;
      if (str_starts_with($this->sValue, "'")) {
        $this->sValue = substr(substr($this->sValue, 1), 0, -1);
      }
    }

    $this->sCondition = strtolower($oMatch->condition);
  }

  /**
   * Конвертирование в массив
   * @return string[]
   */
  public function toArray(): array
  {
    $aParts = [0 => '', 1 => '', 2 => '', 3 => 'and'];

    if ($this->sTable !== '') {
      $aParts[0] = $this->sTable . '.';
    }
    $aParts[0] .= $this->sField;
    $aParts[1] = self::toSqlSign($this->sSign);
    $aParts[2] = $this->sValue;
    $aParts[3] = $this->sCondition;
    return $aParts;
  }

  /**
   * Конвертирование знака сравнения в SQL понятный
   * @param string $sSign
   * @return string
   */
  public static function toSqlSign($sSign = self::EQ): string
  {
    $a = [
      self::_EQ_ => '=',
      self::_GT_ => '>',
      self::_GE_ => '>=',
      self::_LT_ => '<',
      self::_LE_ => '<=',
      self::_CP_ => 'LIKE',
      self::_NE_ => '<>',
      self::_LIKE_ => 'LIKE',
//            self::IS => 'IS',
//            self::ISNOT => 'IS NOT',
    ];
    return $a[$sSign];
  }
}
