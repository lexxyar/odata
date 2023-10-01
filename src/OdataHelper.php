<?php
namespace Lexxsoft\Odata;

class OdataHelper
{
    public static function toValue($val): string
    {
        if (gettype($val) == 'boolean') {
            return $val ? 'true' : 'false';
        }
        return $val;
    }

    public static function toSnakeCase($input): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    public static function mapMysqlTypeToEdm($mysqlType): string
    {
        $mysqlType = strtoupper($mysqlType);

        $type = match ($mysqlType) {
            'VARCHAR', 'TINYTEXT', 'TEXT', 'BLOB', 'MEDIUMTEXT', 'MEDIUMBLOB', 'LONGTEXT', 'LONGBLOB', 'ENUM', 'SET', 'CHAR' => 'String',
            'SMALLINT', 'MEDIUMINT', 'INT' => 'Int32',
            'BIGINT' => 'Int64',
            'DECIMAL', 'FLOAT' => 'Decimal',
            'DOUBLE' => 'Double',
            'DATETIME', 'TIMESTAMP', 'DATE' => 'DateTime',
            'TIME' => 'Time',
            'BOOLEAN', 'TINYINT' => 'Boolean',
            'VARBINARY', 'BINARY' => 'Binary',
            default => 'Null',
        };
        return "Edm.{$type}";
    }

    /**
     * Проверяет на нэймспэйс модели
     */
    public static function isModelNamespace(string $sNamespace): bool
    {
        return str_contains($sNamespace, '\\Models\\');
    }
}
