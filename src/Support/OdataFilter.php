<?php

namespace Lexxsoft\Odata\Support;

use Illuminate\Contracts\Support\Arrayable;
use Lexxsoft\Odata\Contracts\OdataFilterOperator;

/**
 * Class OdataFilter
 * @package LexxSoft\odata\Http
 */
class OdataFilter implements Arrayable
{

    public string $field;
    public OdataFilterOperator $sign = OdataFilterOperator::EQ;
    public string $value = '';
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

        if (str_starts_with($this->field, OdataFilterOperator::SUBSTRINGOF->value) ||
            str_starts_with($this->field, OdataFilterOperator::CONTAINS->value) ||
            str_starts_with($this->field, OdataFilterOperator::ENDSWITH->value) ||
            str_starts_with($this->field, OdataFilterOperator::STARTSWITH->value)) {

            $sPattern = "{value}";
            if (str_starts_with($this->field, OdataFilterOperator::SUBSTRINGOF->value)) {
                $sPattern = "%" . $sPattern . "%";
            } elseif (str_starts_with($this->field, OdataFilterOperator::CONTAINS->value)) {
                $sPattern = "%" . $sPattern . "%";
            } elseif (str_starts_with($this->field, OdataFilterOperator::ENDSWITH->value)) {
                $sPattern = "%" . $sPattern;
            } elseif (str_starts_with($this->field, OdataFilterOperator::STARTSWITH->value)) {
                $sPattern = $sPattern . "%";
            }

            $re = '/.+\((?<Field>.+),\s*\'(?<Value>.+)\'\)/m';
            $reReplace = '/\{\S+\}/m';

            preg_match_all($re, $this->field, $matches, PREG_SET_ORDER, 0);
            foreach ($matches as $match) {
                $this->field = $match['Field'];
                $this->value = preg_replace($reReplace, $match['Value'], $sPattern);
                $this->sign = OdataFilterOperator::CP;
            }
        } else {
            $this->sign = OdataFilterOperator::from(strtoupper($oMatch->operator));
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
    public function toArray(string $tableAlias = ''): array
    {
        $aParts = [0 => '', 1 => '', 2 => '', 3 => 'and'];

        if ($this->table !== '') {
            $aParts[0] = $this->table . '.';
        }
        if ($tableAlias !== '') {
            $aParts[0] = $tableAlias . '.';
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
    public static function toSqlSign(OdataFilterOperator $operator = OdataFilterOperator::EQ): string
    {
        $a = [
            OdataFilterOperator::EQ->value => '=',
            OdataFilterOperator::GT->value => '>',
            OdataFilterOperator::GE->value => '>=',
            OdataFilterOperator::LT->value => '<',
            OdataFilterOperator::LE->value => '<=',
            OdataFilterOperator::CP->value => 'LIKE',
            OdataFilterOperator::NE->value => '<>',
//            self::IS => 'IS',
//            self::ISNOT => 'IS NOT',
        ];
        return $a[$operator->value];
    }

    public function toWhere(): array
    {
        $parts = $this->toArray();
        return [[$parts[0], $parts[1], $parts[2]]];
    }

}
