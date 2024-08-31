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
}
