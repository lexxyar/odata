<?php

namespace Lexxsoft\Odata\Traits;

use Lexxsoft\Odata\OdataFieldDescription;
use Lexxsoft\Odata\OdataHelper;
use Lexxsoft\Odata\OdataRelationship;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

/**
 * Trait Restable
 *
 * Применяется к модели и определяет ее как REST сущность
 *
 */
trait Restable
{
    protected bool $_isRestModel = true;

    public function isRestable():bool
    {
        return $this->_isRestModel;
    }
//    private array $fields = [];
//
//
//    public function getFields(): array
//    {
//        $this->describeFields();
//        return $this->fields;
//    }

    /**
     * Выбор связей, описанных в модели
     * @throws \ReflectionException
     */
    public function relationships(): array
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

                // Проверяем на нэймспэйс
                if (OdataHelper::isModelNamespace($reflectionMethod->getDeclaringClass()->getName())) {
                    try {
                        $model = $model->firstOrFail();
                    } catch (\Exception $ex) {
                        continue;
                    }

                    /**
                     * Skip methods 'forceDelete', 'getFields' to avoid errors
                     */
                    if (in_array($reflectionMethod->name, ['forceDelete', 'getFields'])) {
                        continue;
                    }

                    /**
                     * Spatie laravel-permission fix
                     */
                    if (($reflectionMethod->class == 'Spatie\Permission\Models\Permission'
                            || $reflectionMethod->class == 'Spatie\Permission\Models\Role')
                        && in_array($reflectionMethod->name, ['users', 'getPermissionsViaRoles'])) {
                        continue;
                    }

                    $return = $reflectionMethod->invoke($model);

                    if ($return instanceof Relation) {

                        // Проверяем на нэймспэйс модели
                        if (!OdataHelper::isModelNamespace((new ReflectionClass($return->getRelated()))->getName())) {
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
//                            'snakeName' => OdataHelper::toSnakeCase($reflectionMethod->getName()),
                            'snakeName' => Str::snake($reflectionMethod->getName()),
                        ]);
                        $relations[] = $rel;
                    }
                }
            }
        }
        return $relations;
    }

//    /**
//     * Составляет описание полей на основе БД
//     */
//    private function describeFields(): void
//    {
//        if (sizeof($this->fields) > 0) return;
//
//        $this->fields = [];
//
//        try {
//            $raw = DB::select(DB::raw("DESCRIBE " . $this->getTable())
//                ->getValue(DB::getQueryGrammar()));
//        } catch (\Illuminate\Database\QueryException $e) {
//            if ($e->getCode() == '42S02') return;
//        }
//
//        foreach ($raw as $field) {
//            $this->fields[] = new OdataFieldDescription($field);
//        }
//
//        /*
//        // Добавим кастомные атрибуты (Аксессоры и мутаторы)
//        // Добавление по принципу наличия и Аксессора и Мутатора
//        // Если объявлен только один, то его в метаданные не выводим
//        $aAccessors = [];
//        $aMutators = [];
//        $re = '/(?<access>get|set)(?<name>\w+)Attribute/m';
//        foreach (get_class_methods($this) as $methodName) {
//          if (str_ends_with($methodName, 'Attribute')) {
//            preg_match_all($re, $methodName, $matches, PREG_SET_ORDER, 0);
//            if (sizeof($matches) > 0) {
//              if (str_starts_with($methodName, 'get')) {
//                $aAccessors[] = $matches[0]['name'];
//              } elseif (str_starts_with($methodName, 'set')) {
//                $aMutators[] = $matches[0]['name'];
//              }
//            }
//          }
//        }
//
//        $aIntersec = array_intersect($aAccessors, $aMutators);
//        foreach ($aIntersec as $item) {
//          $dbDescription = new \stdClass();
//          $dbDescription->Field = $item;
//          $dbDescription->Null = 'YES';
//          $dbDescription->Key = '';
//          $dbDescription->Default = '';
//          $dbDescription->Type = 'TEXT';
//          $this->fields[] = new OdataFieldDescription($dbDescription);
//        }
//    */
//    }
//
//    /**
//     * Проверяет наличие поля
//     */
//    public function hasField(string $sField): bool
//    {
//        foreach ($this->getFields() as $field) {
//            if ($field instanceof OdataFieldDescription) {
//                if ($field->getName() == $sField) return true;
//            }
//        }
//        return false;
//    }
}
