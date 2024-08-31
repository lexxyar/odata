<?php

namespace Lexxsoft\Odata\Contracts\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Lexxsoft\Odata\OdataRelationship;

trait CanSyncRelations
{
    /**
     * Извлечение связей из данных
     * @param $data
     * @param Model $model
     * @return array
     */
    final protected function extractRelationsFromInputData(&$data, Model $model): array
    {
        if (!self::modelIsRestable($model)) {
            return [];
        }

        // check relation fields
        $relationships = $model->relationships();
        $related_array = [];

        /** @var OdataRelationship $relationship */
        foreach ($relationships as $relationship) {
            $snakeName = Str::snake($relationship->name);
            if (array_key_exists($snakeName, $data)) {
                $related = new \stdClass();
                $related->field = $relationship->name;
                $related->values = [];
                foreach ($data[$snakeName] as $datum) {
                    $idSync = 0;
                    $pivotSync = [];
                    if (is_array($datum)) {
                        foreach ($datum as $k => $v) {
                            if ($k == $model->getKeyName()) {
                                $idSync = $v;
                            } else {
                                $pivotSync[$k] = $v;
                            }
                        }
                    } else {
                        $idSync = $datum;
                    }
                    $related->values[$idSync] = $pivotSync;
                }
                unset($data[$snakeName]);
                $related_array[$relationship->name] = $related;
            }
        }
        return $related_array;
    }

    public static function modelIsRestable(Model $model): bool
    {
        return in_array("Lexxsoft\\Odata\\Contracts\\Traits\\Restable", class_uses($model));
    }

    /**
     * Синхронизация данных для связей ManyToMany
     */
    protected function syncRelations(Model &$find, array $related): void
    {
        // Sync pivot table
        foreach ($related as $key => $relation) {
            $syncField = $relation->field;
            $find->$syncField()->sync($relation->values);
        }
    }
}
