<?php

namespace LexxSoft\odata\Primitives;

use LexxSoft\odata\Db\OdataFieldDescription;
use LexxSoft\odata\OdataHelper;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use ReflectionMethod;

/**
 * Trait IsRestable
 *
 * Применяется к модели и определяет ее как REST сущность
 *
 * @package LexxSoft\odata\Primitives
 */
trait IsRestable
{
  /**
   * @var bool
   */
  public $isRestModel = true;

  /**
   * @var array
   */
  private $fields = [];

  /**
   * @return array
   */
  public function getFields(): array
  {
    $this->describeFields();
    return $this->fields;
  }

  /**
   * Валидирование данных
   * @return bool
   * @throws \Exception
   *
   * @deprecated sins v. 0.3.5
   */
  public function validateObject()
  {
    // ToDo Validate maximum length
    $keyField = $this->getKeyName();

    // Проверка на обязательные поля
    if (isset($this->requiredFields) && is_array($this->requiredFields)) {
      foreach ($this->requiredFields as $field) {
        if (!isset($this->$field)) {
          throw new \Exception('Fill `' . $field . '` field');
        }
        if (trim($this->$field) == '') {
          throw new \Exception('Field `' . $field . '` cannot be empty');
        }
      }
    }

    // Проверка уникальности значений
    if (isset($this->uniqueFields) && is_array($this->uniqueFields)) {
      foreach ($this->uniqueFields as $field) {
        $value = null;
        if (isset($this->$field)) {
          $value = $this->$field;
        }
        if ($this->where($field, $value)->where($keyField, '<>', $this->$keyField)->exists()) {
          throw new \Exception('Value `' . $value . '` of `' . $field . '` is not unique');
        }
      }
    }
    return true;
  }

  /**
   * Выбор связей, описанных в модели
   * @return array
   * @throws \ReflectionException
   */
  public function relationships()
  {
    $model = new static;
    $reflector = new ReflectionClass($model);

    $relations = [];
    foreach ($reflector->getMethods() as $reflectionMethod) {

      if ($reflectionMethod instanceof ReflectionMethod
        && $reflectionMethod->isPublic()
        && empty($reflectionMethod->getParameters())
        && $reflectionMethod->getName() !== __FUNCTION__
      ) {

        /**
         * Проверяем на нэймспейс
         * @since 0.7.4
         */
//        $modelStartsWith = substr($reflectionMethod->getDeclaringClass()->getName(), 0, strlen('App\\Models\\'));
//        if ($modelStartsWith == 'App\\Models\\') {
        if (OdataHelper::isModelNamespace($reflectionMethod->getDeclaringClass()->getName())) {
          try {
            $model = $model->firstOrFail();
          } catch (\Exception $ex) {
            continue;
          }

          if (in_array($reflectionMethod->name, ['forceDelete', 'getFields'])) {
            continue;
          }
          $return = $reflectionMethod->invoke($model);

          if ($return instanceof Relation) {

            /**
             * Проверяем на нэймспэйс модели
             * @since 0.7.4
             */
//            $modelStartsWith = substr((new ReflectionClass($return->getRelated()))->getName(), 0, strlen('App\\Models\\'));
//            if ($modelStartsWith !== 'App\\Models\\') {
            if (OdataHelper::isModelNamespace((new ReflectionClass($return->getRelated()))->getName())) {
              continue;
            }

            $ownerKey = null;
            if ((new ReflectionClass($return))->hasMethod('getOwnerKey'))
              $ownerKey = $return->getOwnerKey();
            else {
              $segments = explode('.', $return->getQualifiedParentKeyName());
              $ownerKey = $segments[count($segments) - 1];
            }

            $rel = new OdataRelationship([
              'name' => $reflectionMethod->getName(),
              'type' => (new ReflectionClass($return))->getShortName(),
              'model' => (new ReflectionClass($return->getRelated()))->getName(),
              'foreignKey' => (new ReflectionClass($return))->hasMethod('getForeignKeyName')
                ? $return->getForeignKeyName()
                : $return->getForeignPivotKeyName(),
              'ownerKey' => $ownerKey,
              'fkName' => (new ReflectionClass($return->getRawBindings()))->hasMethod('getTable')
                ? $return->getRawBindings()->getTable()
                : '',
              'snakeName' => OdataHelper::toSnakeCase($reflectionMethod->getName()),
            ]);
            $relations[] = $rel;
          }
        }
      }
    }
    return $relations;
  }

  /**
   * Составляет описание полей на основе БД
   */
  private function describeFields()
  {
    if (sizeof($this->fields) > 0) return;

    $raw = DB::select(DB::raw("DESCRIBE " . $this->getTable()));
    $this->fields = [];
    foreach ($raw as $field) {
      $this->fields[] = new OdataFieldDescription($field);
    }

    /*
    // Добавим кастомные атрибуты (Аксессоры и мутаторы)
    // Добавление по принципу наличия и Аксессора и Мутатора
    // Если объявлен только один, то его в метаданные не выводим
    $aAccessors = [];
    $aMutators = [];
    $re = '/(?<access>get|set)(?<name>\w+)Attribute/m';
    foreach (get_class_methods($this) as $methodName) {
      if (str_ends_with($methodName, 'Attribute')) {
        preg_match_all($re, $methodName, $matches, PREG_SET_ORDER, 0);
        if (sizeof($matches) > 0) {
          if (str_starts_with($methodName, 'get')) {
            $aAccessors[] = $matches[0]['name'];
          } elseif (str_starts_with($methodName, 'set')) {
            $aMutators[] = $matches[0]['name'];
          }
        }
      }
    }

    $aIntersec = array_intersect($aAccessors, $aMutators);
    foreach ($aIntersec as $item) {
      $dbDescription = new \stdClass();
      $dbDescription->Field = $item;
      $dbDescription->Null = 'YES';
      $dbDescription->Key = '';
      $dbDescription->Default = '';
      $dbDescription->Type = 'TEXT';
      $this->fields[] = new OdataFieldDescription($dbDescription);
    }
*/
  }

  /**
   * Проверяет наличие поля
   *
   * @param string $sField
   * @return bool
   */
  public function hasField(string $sField): bool
  {
    $res = false;
    foreach ($this->getFields() as $field) {
      if ($field instanceof OdataFieldDescription) {
        if ($field->getName() == $sField) return true;
      }
    }
    return $res;
  }
}
