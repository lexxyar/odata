<?php


namespace LexxSoft\odata\Http;

/**
 * Class OdataOrder
 * @package LexxSoft\odata\Http
 */
class OdataOrder
{
    /**
     * @var string
     */
    public $sFieldname = '';

    /**
     * @var string
     */
    public $sDirection = self::ASC;

    const ASC = 'ASC';
    const DESC = 'DESC';

    /**
     * OdataOrder constructor.
     * @param string $sString
     */
    public function __construct(string $sString)
    {
        $aParts = explode(' ', $sString);
        if (sizeof($aParts) === 2) {
            $this->sFieldname = trim($aParts[0]);
            $this->sDirection = OdataOrder::convert(trim($aParts[1]));
        }
        if (sizeof($aParts) === 1) {
            $this->sFieldname = trim($aParts[0]);
            $this->sDirection = OdataOrder::ASC;
        }
    }

    /**
     * Конвертирует значение константы в текстовое значение
     * @param string $sValue
     * @return string
     */
    private static function convert(string $sValue): string
    {
        $sUpperValue = strtoupper($sValue);
        if (defined(OdataOrder::class . $sUpperValue)) {
            return $sUpperValue;
        } else {
            return self::ASC;
        }
    }

}
