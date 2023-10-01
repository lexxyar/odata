<?php

namespace Lexxsoft\Odata;

/**
 * Class OdataFilter
 * @package LexxSoft\odata\Http
 */
class OdataFilter
{
    const _EQ_ = 'EQ';
    const _GT_ = 'GT';
    const _GE_ = 'GE';
    const _LT_ = 'LT';
    const _LE_ = 'LE';
    const _CP_ = 'LIKE';
    const _NE_ = 'NE';

    const __SUBSTRINGOF__ = 'substringof';
    const __CONTAINS__ = 'contains';
    const __ENDSWITH__ = 'endswith';
    const __STARTSWITH__ = 'startswith';

    public string $field;
    public string $sign;
    public string $value;
    public bool $group;
    public string $condition;
    private string $table = '';

    /**
     * Устанавливает имя таблицы
     */
    public function setTable(string $sTableName): void
    {
        $this->table = $sTableName;
    }

    /**
     * OdataFilter constructor.
     */
    public function __construct(OdataFilterStructure $oMatch)
    {
        $this->field = $oMatch->field;

        if (str_starts_with($this->field, self::__SUBSTRINGOF__) ||
            str_starts_with($this->field, self::__CONTAINS__) ||
            str_starts_with($this->field, self::__ENDSWITH__) ||
            str_starts_with($this->field, self::__STARTSWITH__)) {

            $sPattern = "{value}";
            if (str_starts_with($this->field, self::__SUBSTRINGOF__)) {
                $sPattern = "%" . $sPattern . "%";
            } elseif (str_starts_with($this->field, self::__CONTAINS__)) {
                $sPattern = "%" . $sPattern . "%";
            } elseif (str_starts_with($this->field, self::__ENDSWITH__)) {
                $sPattern = "%" . $sPattern;
            } elseif (str_starts_with($this->field, self::__STARTSWITH__)) {
                $sPattern = $sPattern . "%";
            }

            $re = '/.+\((?<Field>.+),\s*\'(?<Value>.+)\'\)/m';
            $reReplace = '/\{\S+\}/m';

            preg_match_all($re, $this->field, $matches, PREG_SET_ORDER, 0);
            foreach ($matches as $match) {
                $this->field = $match['Field'];
                $this->value = preg_replace($reReplace, $match['Value'], $sPattern);
                $this->sign = self::_CP_;
            }
        } else {
            $this->sign = strtoupper($oMatch->operator);
            $this->value = $oMatch->value;
            $this->group = $oMatch->group;
            if (str_starts_with($this->value, "'")) {
                $this->value = substr(substr($this->value, 1), 0, -1);
            }
        }

        $this->condition = strtolower($oMatch->condition);
    }

    /**
     * Конвертирование в массив
     */
    public function toArray(): array
    {
        $aParts = [0 => '', 1 => '', 2 => '', 3 => 'and'];

        if ($this->table !== '') {
            $aParts[0] = $this->table . '.';
        }
        $aParts[0] .= $this->field;
        $aParts[1] = self::toSqlSign($this->sign);
        $aParts[2] = $this->adoptValueType($this->value);
        $aParts[3] = $this->condition;
        return $aParts;
    }

    /**
     * Адаптирует значение переменной к подходящему типу
     */
    private function adoptValueType($sValue): mixed
    {
        if (strtolower($sValue) == 'true') return true;
        if (strtolower($sValue) == 'false') return false;
        if (strtolower($sValue) == 'null') return null;
        return $sValue;
    }

    /**
     * Конвертирование знака сравнения в SQL понятный
     */
    public static function toSqlSign(string $sSign = self::_EQ_): string
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

    public function toWhere(): array
    {
        $parts = $this->toArray();
        return [[$parts[0], $parts[1], $parts[2]]];
    }

}
