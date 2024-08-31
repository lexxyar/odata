<?php


namespace Lexxsoft\Odata\Support;

use Lexxsoft\Odata\Contracts\OdataOrderDirection;

class OdataOrder
{
    public string $field = '';
    public OdataOrderDirection $direction = OdataOrderDirection::ASC;

    /**
     * OdataOrder constructor
     */
    public function __construct(string $string)
    {
        $value = Str($string)->trim()->replaceMatches(pattern: '/[\s\t]{2,}/', replace: ' ');

        $parts = explode(' ', $value);
        if (sizeof($parts) === 2) {
            $this->field = Str($parts[0])->trim()->lower();
            $this->direction = OdataOrder::convert($parts[1]);
        }
        if (sizeof($parts) === 1) {
            $this->field = Str($parts[0])->trim()->lower();
            $this->direction = OdataOrderDirection::ASC;
        }
    }

    /**
     * Конвертирует значение константы в текстовое значение
     */
    private static function convert(string $value): OdataOrderDirection
    {
        return OdataOrderDirection::tryFrom(strtoupper(trim($value))) ?? OdataOrderDirection::ASC;
    }

}
