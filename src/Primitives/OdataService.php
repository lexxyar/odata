<?php


namespace LexxSoft\odata\Primitives;


use DOMDocument;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Config;
use LexxSoft\odata\Db\OdataFieldDescription;
use LexxSoft\odata\OdataHelper;

/**
 * Class OdataService
 * @package LexxSoft\odata\Primitives
 */
class OdataService
{
  /**
   * Генерация $metadata xml ответа
   * @return string
   */
  public static function createMetadataXml(): string
  {
    $serviceName = 'LaraProd';

    $xml = new DOMDocument(); // creates an instance of DOMDocument class.
    $xml->encoding = 'utf-8'; // sets the document encoding to utf-8.
    $xml->xmlVersion = '1.0'; // specifies the version number 1.0.
    $xml->formatOutput = true; // ensures that the output is well formatted.

    $Edmx = $xml->createElementNS('http://schemas.microsoft.com/ado/2007/06/edmx', 'edmx:Edmx');
    $Edmx->setAttribute('Version', '1.0');
    $xml->appendChild($Edmx);

    $DataServices = $xml->createElement('edmx:DataServices');
    $DataServices->setAttribute('xmlns:m', 'http://schemas.microsoft.com/ado/2007/08/dataservices/metadata');
    $DataServices->setAttribute('m:DataServiceVersion', '1.0');
    $Edmx->appendChild($DataServices);

    $Schema = $xml->createElement('Schema');
    $Schema->setAttribute('Namespace', $serviceName);
    $Schema->setAttribute('xmlns:d', 'http://schemas.microsoft.com/ado/2007/08/dataservices');
    $Schema->setAttribute('xmlns:m', 'http://schemas.microsoft.com/ado/2007/08/dataservices/metadata');
    $Schema->setAttribute('xmlns', 'http://schemas.microsoft.com/ado/2008/09/edm');
    $DataServices->appendChild($Schema);

    // Берем все модели
    $aModel = self::getEntityModels();
    foreach ($aModel as $oEntity) {
      if (!$oEntity instanceof OdataEntityDescription) continue;

      // Создаем блок EntityType
      $EntityType = $xml->createElement('EntityType');
      $EntityType->setAttribute('Name', $oEntity->getEntityName());
      $Schema->appendChild($EntityType);

      $Key = $xml->createElement('Key');
      $EntityType->appendChild($Key);

      $PropertyRef = $xml->createElement('PropertyRef');
      $PropertyRef->setAttribute('Name', $oEntity->getKeyField());
      $Key->appendChild($PropertyRef);

      foreach ($oEntity->getFields() as $field) {
        if ($field instanceof OdataFieldDescription) {
          $Property = $xml->createElement('Property');
          $Property->setAttribute('Name', $field->getName());
          $Property->setAttribute('Type', $field->getEdmType());
          $Property->setAttribute('Nullable', OdataHelper::toValue($field->isNullable()));

          $isLimitString = $field->isLimitString();
          if ($isLimitString) {
            $val = $isLimitString == 'yes' ? $field->getSize() : 'Max';
            if ($val) {
              $Property->setAttribute('MaxLength', $val);
            }
          } else {
            if ($field->hasPrecision()) {
              if ($field->getInt() > 0) {
                $Property->setAttribute('Precision', $field->getInt());
              }
              if ($field->getFloat() > 0) {
                $Property->setAttribute('Scale', $field->getFloat());
              }
            }
          }


          $EntityType->appendChild($Property);
        }
      }

      // Генерация навигации по отношениям
      $aAssoc = [];
      $allRelations = $oEntity->getModel()->relationships();
      foreach ($allRelations as $relation) {
        $NavigationProperty = $xml->createElement('NavigationProperty');
        $NavigationProperty->setAttribute('Name', $relation->name);
        $NavigationProperty->setAttribute('FromRole', $oEntity->getEntityName());

        $sFkName = $relation->fkName;
        if (!$sFkName) {
          $sFkName = $oEntity->getEntityName() . '_' . $relation->name;
        }
        $aModelPathPart = explode('\\', $relation->model);
        $modelName = array_pop($aModelPathPart);
        $NavigationProperty->setAttribute('ToRole', strtolower($modelName));
        $NavigationProperty->setAttribute('Relationship', $sFkName);

        $EntityType->appendChild($NavigationProperty);

        $Association = $xml->createElement('Association');
        $Association->setAttribute('Name', $sFkName);
        $aAssoc[] = $Association;

        $End = $xml->createElement('End');
        $End->setAttribute('Role', $oEntity->getEntityName());
        $End->setAttribute('Type', $oEntity->getEntityName());
        $End->setAttribute('Multiplicity', '0..1');
        $Association->appendChild($End);

        $End = $xml->createElement('End');
        $End->setAttribute('Role', strtolower($modelName));
        $End->setAttribute('Type', strtolower($modelName));
        $End->setAttribute('Multiplicity', '*');
        $Association->appendChild($End);

        $ReferentialConstraint = $xml->createElement('ReferentialConstraint');
        $Association->appendChild($ReferentialConstraint);

        $Principal = $xml->createElement('Principal');
        $Principal->setAttribute('Role', $oEntity->getEntityName());
        $ReferentialConstraint->appendChild($Principal);

        $PropertyRef = $xml->createElement('PropertyRef');
        $PropertyRef->setAttribute('Name', $relation->foreignKey);
        $Principal->appendChild($PropertyRef);

        $Dependent = $xml->createElement('Dependent');
        $Dependent->setAttribute('Role', strtolower($modelName));
        $ReferentialConstraint->appendChild($Dependent);

        $PropertyRef = $xml->createElement('PropertyRef');
        $PropertyRef->setAttribute('Name', $relation->ownerKey);
        $Dependent->appendChild($PropertyRef);
      }
    }

    // Генерация ассоциаций
    foreach ($aAssoc as $Association) {
      $Schema->appendChild($Association);
    }

    // Генерация раздела наборов
    $EntityContainer = $xml->createElement('EntityContainer');
    $EntityContainer->setAttribute('Name', $serviceName . 'Entities');
    $EntityContainer->setAttribute('xmlns:p7', 'http://schemas.microsoft.com/ado/2009/02/edm/annotation');
    $DataServices->appendChild($EntityContainer);

    foreach ($aModel as $oEntity) {
      if (!$oEntity instanceof OdataEntityDescription) continue;
      $EntitySet = $xml->createElement('EntitySet');
      $EntitySet->setAttribute('Name', $oEntity->getEntityName());
      $EntitySet->setAttribute('EntityType', $oEntity->getEntityName());
      $EntityContainer->appendChild($EntitySet);

      $allRelations = $oEntity->getModel()->relationships();
      foreach ($allRelations as $relation) {
        if (!$relation instanceof OdataRelationship) continue;

        $aModelPathPart = explode('\\', $relation->model);
        $modelName = array_pop($aModelPathPart);

        $AssociationSet = $xml->createElement('AssociationSet');
        $AssociationSet->setAttribute('Name', $relation->fkName);
        $AssociationSet->setAttribute('Association', $relation->fkName);
        $EntityContainer->appendChild($AssociationSet);

        $End = $xml->createElement('End');
        $End->setAttribute('Role', $oEntity->getEntityName());
        $End->setAttribute('EntitySet', $oEntity->getEntityName());
        $AssociationSet->appendChild($End);

        $End = $xml->createElement('End');
        $End->setAttribute('Role', strtolower($modelName));
        $End->setAttribute('EntitySet', strtolower($modelName));
        $AssociationSet->appendChild($End);
      }

    }

    return $xml->saveXML();
  }

  /**
   * Возвращает массив сущностей
   * @param string|null $scanPath ! Используется только для рекурсии
   * @return array
   */
  private static function getEntityModels($scanPath = null): array
  {
    $config = Config::get('odata');

    $aFolders = [];

    $aFolders [''] = $scanPath ? $scanPath : app_path() . DIRECTORY_SEPARATOR . 'Models';
    foreach ($config['components'] as $sComponentNS) {
      if (Env::get('LEXXSOFT_DEBUG', false))
        $sPath = base_path('package') . DIRECTORY_SEPARATOR . 'blog' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Models';
      else
        $sPath = base_path('vendor') . DIRECTORY_SEPARATOR . $sComponentNS . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Models';

      if (!is_dir($sPath)) continue;
      $aFolders[$sComponentNS] = $sPath;
    }

    $out = [];
    foreach ($aFolders as $sNamespace => $sPath) {
      // Сканируем все файлы и подпапки директории моделей
      $files = scandir($sPath);

      foreach ($files as $file) {
        if ($file === '.' or $file === '..') continue;
        $filename = $sPath . DIRECTORY_SEPARATOR . $file;
        if (is_dir($filename)) {
          // если папка - замыкаем рекурсию
          $out = array_merge($out, self::getEntityModels($filename));
        } else {
          try {
            // Пытаемся создать описание сущности на основе файла
            $o = new OdataEntityDescription($filename, $sNamespace);
            $out[$o->getEntityName()] = $o;
          } catch (\Exception $e) {

          }
        }
      }
    }

    ksort($out, SORT_STRING);
    return $out;
  }

  /**
   * Возвращает описание модели по имени сущности
   *
   * @param string $sEntityName Имя сущности
   * @return OdataEntityDescription|null
   * @since 0.7.0
   */
  public static function getEntityModelByEntityName(string $sEntityName):?OdataEntityDescription{
    $aModels = self::getEntityModels();
    return isset($aModels[$sEntityName])?$aModels[$sEntityName]:null;
  }
}
