<?php


namespace LexxSoft\odata;


use Illuminate\Support\Collection;
use LexxSoft\odata\Http\OdataFilter;
use LexxSoft\odata\Http\OdataRequest;

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

  /**
   * Применяет OdataFilter к коллекции
   * @use OdataRequest filter
   *
   * @param Collection $oCollection
   * @return Collection
   *
   * @since 0.6.0
   */
  public static function filterCollection(Collection &$oCollection)
  {
    $oRequest = OdataRequest::getInstance();
    if (sizeof($oRequest->filter) > 0) {
      $oCollection = $oCollection->filter(function ($value, $key) use ($oRequest) {
        foreach ($oRequest->filter as $oFilter) {
          if ($oFilter instanceof OdataFilter) {
            switch ($oFilter->sSign) {
//              case OdataFilter::_EQ_:
//                return $value[$oFilter->sField] == $oFilter->sValue;
              case OdataFilter::_GE_:
                return $value[$oFilter->sField] >= $oFilter->sValue;
              case OdataFilter::_GT_:
                return $value[$oFilter->sField] > $oFilter->sValue;
              case OdataFilter::_LE_:
                return $value[$oFilter->sField] <= $oFilter->sValue;
              case OdataFilter::_LT_:
                return $value[$oFilter->sField] < $oFilter->sValue;
              default:
                return $value[$oFilter->sField] == $oFilter->sValue;
            }
          }
        }
      });
    }
    return $oCollection;
  }

  /**
   * Сдвигает/выкидывает первые записи коллекции
   * @use OdataRequest offset
   *
   * @param Collection $oCollection Коллекция
   * @return Collection Измененная коллекция
   *
   * @since 0.6.0
   */
  public static function skipCollection(Collection &$oCollection)
  {
    if($oCollection->count() == 0) return $oCollection;
    $oRequest = OdataRequest::getInstance();
    if ($oRequest->offset > 0){
      $oCollection = $oCollection->skip($oRequest->offset);
    }
    return $oCollection;
  }

  /**
   * Сдвигает/выкидывает первые записи коллекции
   * @alias skipCollection
   * @use OdataRequest offset
   *
   * @param Collection $oCollection Коллекция
   * @return Collection Измененная коллекция
   *
   * @since 0.6.0
   */
  public static function offsetCollection(Collection &$oCollection)
  {
    return self::skipCollection($oCollection);
  }

  /**
   * Выбирает определенное количество значений коллекции
   * @use OdataRequest limit
   *
   * @param Collection $oCollection Коллекция
   * @return Collection Измененная коллекция
   *
   * @since 0.6.0
   */
  public static function limitCollection(Collection &$oCollection)
  {
    if($oCollection->count() == 0) return $oCollection;
    $oRequest = OdataRequest::getInstance();
    if ($oRequest->limit > 0){
      $oCollection = $oCollection->slice(0, $oRequest->limit);
    }
    return $oCollection;
  }

  /**
   * Выбирает определенное количество значений коллекции
   * @alias limitCollection
   * @use OdataRequest limit
   *
   * @param Collection $oCollection Коллекция
   * @return Collection Измененная коллекция
   *
   * @since 0.6.0
   */
  public static function topCollection(Collection &$oCollection)
  {
    return self::limitCollection($oCollection);
  }

  /**
   * Проверяет на нэймспэйс модели
   *
   * @param $sNamespace Нэймспэйс
   * @return bool
   *
   * @since 0.7.4
   */
  public static function isModelNamespace(string $sNamespace):bool
  {
    return str_contains($sNamespace, '\\Models\\');
  }
}
