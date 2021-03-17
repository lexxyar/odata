<?php


namespace LexxSoft\odata\Db;

use LexxSoft\odata\OdataHelper;

/**
 * Class OdataFieldDescription
 * @package LexxSoft\odata\Db
 */
class OdataFieldDescription
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $isNullable;

    /**
     * @var bool
     */
    private $isPrimary;

    /**
     * @var string
     */
    private $default;

    /**
     * @var string
     */
    private $dbType;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $edmType;

    /**
     * @var int
     */
    private $size;

    /**
     * @var int
     */
    private $float;

    /**
     * @var int
     */
    private $int;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEdmType(): string
    {
        return $this->edmType;
    }

    /**
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return mixed
     */
    public function getFloat()
    {
        return $this->float;
    }

    /**
     * @return mixed
     */
    public function getInt()
    {
        return $this->int;
    }

    /**
     * OdataFieldDescription constructor.
     * @param object $dbDescription
     */
    public function __construct(object $dbDescription)
    {
        $this->name = $dbDescription->Field;
        $this->isNullable = $dbDescription->Null === 'NO' ? false : true;
        $this->isPrimary = $dbDescription->Key === 'PRI' ? true : false;
        $this->default = $dbDescription->Default;
        $this->dbType = $dbDescription->Type;

        $this->mysqlToEdmType($this->dbType);
    }

    /**
     * Конвертирование типа MySQL в Edm
     * @param $mysqlType
     */
    private function mysqlToEdmType($mysqlType): void
    {
        $re = '/^(?<type>\w+)(\(?(?<size>(?<int>\d+)(,(?<float>\d+))?)\)?)?(.*)$/m';
        preg_match_all($re, $mysqlType, $matches, PREG_SET_ORDER, 0);
        $matches = $matches[0];
        $this->type = $matches['type'];
        $this->edmType = OdataHelper::mapMysqlTypeToEdm($this->type);
        $this->size = isset($matches['size']) && $matches['size'] ? $matches['size'] : 0;
        $this->float = isset($matches['float']) ? $matches['float'] : 0;
        $this->int = isset($matches['int']) ? $matches['int'] : 0;
    }

    /**
     * Проверка ограничения длины строки
     * @return false|string - yes,no. Return false if not a string type
     */
    public function isLimitString()
    {
        switch (strtoupper($this->type)) {
            case 'VARCHAR':
            case 'ENUM':
            case 'SET':
            case 'CHAR':
                return 'yes';
            case 'TEXT':
            case 'BLOB':
            case 'LONGTEXT':
            case 'LONGBLOB':
            case 'MEDIUMTEXT':
            case 'MEDIUMBLOB':
            case 'TINYTEXT':
                return 'no';
        }
        return false;
    }

    /**
     * Проверка наличия показателя дробной части
     * @return bool
     */
    public function hasPrecision(): bool
    {
        switch (strtoupper($this->type)) {
            case 'DECIMAL':
            case 'FLOAT':
            case 'DOUBLE':
                return true;
        }
        return false;
    }
}
