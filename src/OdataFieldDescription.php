<?php


namespace Lexxsoft\Odata;

/**
 * Class OdataFieldDescription
 */
class OdataFieldDescription
{
    private string $table;
    private string $name;
    private bool $isNullable;
    private bool $isPrimary;
    private bool $isUnique;
    private string|null $default;
    private string $dbType;
    private string $type;
    private string $edmType;
    private int $size;
    private int $float;
    private int $int;

    public function getTable(): string
    {
        return $this->table;
    }
public function getName(): string
    {
        return $this->name;
    }

    public function getEdmType(): string
    {
        return $this->edmType;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function isUnique(): bool
    {
        return $this->isUnique;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getFloat(): int
    {
        return $this->float;
    }

    public function getInt(): int
    {
        return $this->int;
    }

    public function __construct(object $dbDescription, string $tableName='')
    {
        $this->table = $tableName;
        $this->name = $dbDescription->Field;
        $this->isNullable = !($dbDescription->Null === 'NO');
        $this->isPrimary = $dbDescription->Key === 'PRI';
        $this->isUnique = $dbDescription->Key === 'UNI';
        $this->default = $dbDescription->Default;
        $this->dbType = $dbDescription->Type;

        $this->mysqlToEdmType($this->dbType);
    }

    /**
     * Конвертирование типа MySQL в Edm
     */
    private function mysqlToEdmType(string $mysqlType): void
    {
        $re = '/^(?<type>\w+)(\(?(?<size>(?<int>\d+)(,(?<float>\d+))?)\)?)?(.*)$/m';
        preg_match_all($re, $mysqlType, $matches, PREG_SET_ORDER, 0);
        $matches = $matches[0];
        $this->type = $matches['type'];
        $this->edmType = OdataHelper::mapMysqlTypeToEdm($this->type);
        $this->size = intval(isset($matches['size']) && $matches['size'] ? $matches['size'] : 0);
        $this->float = intval($matches['float'] ?? 0);
        $this->int = intval($matches['int'] ?? 0);
    }

    /**
     * Проверка ограничения длины строки
     * @return false|string - yes,no. Return false if not a string type
     */
    public function isLimitString(): false|string
    {
        return match (strtoupper($this->type)) {
            'VARCHAR', 'ENUM', 'SET', 'CHAR' => 'yes',
            'TEXT', 'BLOB', 'LONGTEXT', 'LONGBLOB', 'MEDIUMTEXT', 'MEDIUMBLOB', 'TINYTEXT' => 'no',
            default => false,
        };
    }

    /**
     * Проверка наличия показателя дробной части
     */
    public function hasPrecision(): bool
    {
        return match (strtoupper($this->type)) {
            'DECIMAL', 'FLOAT', 'DOUBLE' => true,
            default => false,
        };
    }
}
