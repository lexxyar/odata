<?php


namespace LexxSoft\odata;


class OdataHelper
{
    public static function toValue($val): string
    {
        if (gettype($val) == 'boolean') {
            return $val ? 'true' : 'false';
        }
        return $val;
    }

    public static function toCamelCase($str): string
    {
        $i = array("-", "_");
        $str = preg_replace('/([a-z])([A-Z])/', "\\1 \\2", $str);
        $str = preg_replace('@[^a-zA-Z0-9\-_ ]+@', '', $str);
        $str = str_replace($i, ' ', $str);
        $str = str_replace(' ', '', ucwords(strtolower($str)));
        $str = strtolower(substr($str, 0, 1)) . substr($str, 1);
        return $str;
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

        switch ($mysqlType) {
            case 'VARCHAR':
            case 'TINYTEXT':
            case 'TEXT':
            case 'BLOB':
            case 'MEDIUMTEXT':
            case 'MEDIUMBLOB':
            case 'LONGTEXT':
            case 'LONGBLOB':
            case 'ENUM':
            case 'SET':
            case 'CHAR':
                $type = 'String';
                break;
            case 'SMALLINT':
            case 'MEDIUMINT':
            case 'INT':
                $type = 'Int32';
                break;
            case 'BIGINT':
                $type = 'Int64';
                break;
            case 'DECIMAL':
            case 'FLOAT':
                $type = 'Decimal';
                break;
            case 'DOUBLE':
                $type = 'Double';
                break;
            case 'DATETIME':
            case 'TIMESTAMP':
            case 'DATE':
                $type = 'DateTime';
                break;
            case 'TIME':
                $type = 'Time';
                break;
            case 'BOOLEAN':
            case 'TINYINT':
                $type = 'Boolean';
                break;
            case 'VARBINARY':
            case 'BINARY':
                $type = 'Binary';
                break;
            default:
                $type = 'Null';
                break;
        }
        return "Edm.{$type}";
    }

//    public static function isAssoc(array $arr):bool
//    {
//        if (array() === $arr) return false;
//        return array_keys($arr) !== range(0, count($arr) - 1);
//    }
}
