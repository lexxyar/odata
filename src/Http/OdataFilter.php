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
     * @var string
     */
    public $sCondition;

    const _EQ_ = 'EQ';
    const _GT_ = 'GT';
    const _GE_ = 'GE';
    const _LT_ = 'LT';
    const _LE_ = 'LE';
    const _CP_ = 'CP';
    const _NE_ = 'NE';

    /**
     * @var string
     */
    private $sTable = '';

    /**
     * Устанавливает имя таблицы
     * @param string $sTableName
     */
    public function setTable(string $sTableName){
        $this->sTable = $sTableName;
    }

    /**
     * OdataFilter constructor.
     * @param $aMatch
     */
    public function __construct($aMatch)
    {
        $this->sFilter = $aMatch['Filter'];
        $this->sField = $aMatch['Resource'];
        $this->sSign = strtoupper($aMatch['Operator']);
        $this->sValue = $aMatch['Value'];
        if (isset($aMatch['Condition'])) {
            $this->sCondition = strtolower($aMatch['Condition']);
        } else {
            $this->sCondition = strtolower('and');
        }
    }

    /**
     * Конвертирование в массив
     * @return string[]
     */
    public function toArray():array
    {
        $aParts = [0=>'',1=>'',2=>''];

        if($this->sTable !== ''){
            $aParts[0] = $this->sTable.'.';
        }
        $aParts[0] .= $this->sField;
        if ($this->sValue === 'null') {
            $aParts[2] = $this->sValue;
        } else {
            if ($this->sSign === self::_CP_) {
                $aParts[2] = "%{$this->sValue}%";
            } else {
                $aParts[2] = $this->sValue;
            }
        }
        $aParts[1] = self::toSqlSign($this->sSign);
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
//            self::IS => 'IS',
//            self::ISNOT => 'IS NOT',
        ];
        return $a[$sSign];
    }
}
