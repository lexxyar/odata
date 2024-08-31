<?php

namespace Lexxsoft\Odata\Contracts\Traits;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Lexxsoft\Odata\Support\OdataFieldDescription;
use ReflectionClass;
use ReflectionMethod;

/**
 * Trait Restable
 *
 * Применяется к модели и определяет ее как REST сущность
 *
 * @method getTable()
 */
trait Restable
{
    private array $fields = [];

    public function getFields(): array
    {
        $this->describeFields();
        return $this->fields;
    }

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
                && str_starts_with($reflectionMethod->getReturnType(), 'Illuminate\\Database\\Eloquent\\Relations\\')
            ) {

                // Проверяем на нэймспэйс
                if ($this->isModelNamespace($reflectionMethod->getDeclaringClass()->getName())) {

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
                        if (!$this->isModelNamespace((new ReflectionClass($return->getRelated()))->getName())) {
                            continue;
                        }

                        $reflectionClass = new ReflectionClass($return);
                        $ownerKey = null;
                        if ($reflectionClass->hasMethod('getOwnerKey'))
                            $ownerKey = $return->getOwnerKey();
                        else {
                            $segments = explode('.', $return->getQualifiedParentKeyName());
                            $ownerKey = $segments[count($segments) - 1];
                        }

                        $rel = new \stdClass();
                        $rel->name = $reflectionMethod->getName();
                        $rel->type = $reflectionClass->getShortName();
                        $rel->model = get_class($return->getRelated());
                        $rel->foreign = $reflectionClass->hasMethod('getForeignKeyName')
                            ? $return->getForeignKeyName()
                            : $return->getForeignPivotKeyName();
                        $rel->key = $ownerKey;
                        $relations[$rel->name] = $rel;
                    }
                }
            }
        }
        return $relations;
    }

    /**
     * Составляет описание полей на основе БД
     */
    private function describeFields(): void
    {
        if (sizeof($this->fields) > 0) return;

        $this->fields = [];

        try {
            $raw = DB::select(DB::raw("DESCRIBE " . $this->getTable())
                ->getValue(DB::getQueryGrammar()));
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == '42S02') return;
        }

        foreach ($raw as $field) {
            $this->fields[] = new OdataFieldDescription($field, $this->getTable());
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
     * Проверяет на нэймспэйс модели
     */
    protected function isModelNamespace(string $sNamespace): bool
    {
        return str_contains($sNamespace, '\\Models\\');
    }

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
