<?php

namespace Lexxsoft\Odata;

use Error;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Lexxsoft\Odata\Exceptions\NotOdataRequestException;
use Lexxsoft\Odata\Exceptions\OdataIdentifierIsUndefined;

class Odata
{
    protected OdataRequest $_request;
    protected string $_entity;
    protected string|null $_id;
    protected array|null $_pathParts = [];
    protected OdataEntity $_odataEntity;

    public static function make(...$parameters): static
    {
        return new static(...$parameters);
    }

    public function __construct()
    {
        $this->_request = OdataRequest::getInstance();
        $this->parseUrlPath();
        $this->_odataEntity = new OdataEntity($this->_entity);
        $this->_odataEntity->setId($this->hasId() ? $this->_id : null);
    }

    public function getId(): string|null
    {
        return $this->_id;
    }

    public function hasId(): bool
    {
        return $this->_id !== null;
    }

    public function makeResponse(): mixed
    {
        if ($this->_odataEntity->hasController()) {
            return $this->_odataEntity->callController();
        }
        return match (request()->method()) {
            'GET' => $this->get(),
            'POST' => $this->post(),
            'PUT' => $this->put(),
            'PATCH' => $this->patch(),
            'DELETE' => $this->delete(),
            default => response()->noContent(),
        };
    }

    private function parseUrlPath(): void
    {
        $this->_id = null;
        $this->_entity = '';

        $path = urldecode(request()->getPathInfo());

        try {
            // remove /odata/
            $entityPath = explode('/odata/', $path, 2)[1];
        } catch (\Exception $ex) {
            throw new NotOdataRequestException();
        }

        // split by "/"
        $this->_pathParts = explode("/", $entityPath);

        if (!isset ($this->_pathParts[0])) {
            throw new Error('Entity not found');
        }

        $this->_entity = array_shift($this->_pathParts);

        if ($this->_entity[-1] == ')') {
            $re = '/(?<entity>\S*[^(])\((?<quote>[\'"])(?<id>.*)(?&quote)\)/m';
            preg_match_all($re, $this->_entity, $matches, PREG_SET_ORDER, 0);
            if (isset($matches[0]['entity'])) {
                $this->_entity = $matches[0]['entity'];
            } else {
                throw new Error('Entity not detected while identifier is using');
            }
            if (isset($matches[0]['id'])) {
                $this->_id = $matches[0]['id'];
            } else {
                throw new Error('Identifier is not detected');
            }
        }
    }

    public function prepareGetQueryBuilder(): \Illuminate\Database\Eloquent\Builder
    {
        $model = $this->_odataEntity->getModel();
        $queryBuilder = $model->newModelQuery();

        // Filter
        if (sizeOf($this->_request->filter) > 0) {
            $aFilterParts = [];
            foreach ($this->_request->filter as $oFilter) {
                if ($oFilter instanceof OdataFilter) {
                    $aFilterParts[] = $oFilter->toArray();
                }
            }
            $queryBuilder->where($aFilterParts);
        }

        // Order
        if (sizeof($this->_request->order) > 0) {
            foreach ($this->_request->order as $order) {
                if ($order instanceof OdataOrder) {
                    $queryBuilder->orderBy($order->field, $order->direction);
                }
            }
        }

        // select
        if (sizeof($this->_request->select) > 0) {
            $queryBuilder->select($this->_request->select);
        }

        // expand
        if (sizeof($this->_request->expand) > 0) {
            /** @var OdataExpand $expand */
            foreach ($this->_request->expand as $expand) {
                if ($expand->withCount()) {
                    $queryBuilder->withCount($expand->entity() . ' as ' . $expand->entity() . '__odata_count');

                    if ($expand->top() > 0) {
                        $queryBuilder->with($expand->entity());
                    }
                } else {
                    $queryBuilder->with($expand->entity());
                }
            }
        }

        // limit
        if ($this->_request->limit > 0) {
            $queryBuilder->limit($this->_request->limit);
        }

        // offset
        if ($this->_request->offset > 0) {
            $queryBuilder->offset($this->_request->offset);
        }

        if ($this->_id !== null) {
            $queryBuilder->where([$this->_odataEntity->getModel()->getKeyName() => $this->_id]);
        }

        return $queryBuilder;
    }

    private function replaceDataTechnicalKeySuffix(array $replacements, &$array): void
    {
        // If false its a single dimension array if true its a multi dimension array
        $isMultydimension = is_array(current($array));
        if ($isMultydimension) {
            $firstItem = current($array);
        } else {
            $firstItem = $array;
        }
        $keys = array_keys($firstItem);
        $substitutions = [];
        foreach ($replacements as $suffix => $replaceWith) {
            $filtered = array_filter($keys, fn($v) => str_ends_with($v, $suffix));
            foreach ($filtered as $originalKey) {
                $substitutions[$originalKey] = str_replace($suffix, $replaceWith, $originalKey);
            }
        }

        foreach ($substitutions as $originalKey => $replacer) {
            $array = $this->arrayKeyReplace($originalKey, $replacer, $array, $isMultydimension);
        }
    }

    public function toGetResponse(mixed $data, int $count = 0): \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
    {
        $asRawValue = false;

        if (sizeof($this->_pathParts) > 0) {
//            while (sizeof($this->_pathParts) > 0) {
            $part = $this->_pathParts[-1];
            if ($part == '$value') {
                $asRawValue = true;
            } elseif ($part == '$count') {
                if (is_array($data) || $data instanceof \Illuminate\Database\Eloquent\Collection) {
                    return response(sizeof($data), 200)->header('Content-Type', 'text/plain');
                } else {
                    throw new Error('$count is not applicable');
                }
            } else {
                $data = $data->$part ?? null;
            }
//            }
        }

        if (is_string($data)) {
            if (!$asRawValue) {
                $res = new \stdClass();
                $res->value = $data;
                $data = $res;
            } else {
                return response($data, 200)->header('Content-Type', 'text/plain');
            }
        } else {
            $res = new \stdClass();
            if ($this->_request->count) {
                $annotation = "@odata.count";
                $res->$annotation = $count;
            }

            // Replace technical suffix to OData standards
            $suffixReplacements = [
                '__odata_count' => '@odata.count'
            ];

            $array = $data->toArray();
            $this->replaceDataTechnicalKeySuffix($suffixReplacements, $array);

            $res->value = $array;

            $data = $res;
        }

//        dd($data, [$data]);
        return response()->json($data);
//        return response()->json(['test'=>'me']);
    }

    public function get(): \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
    {
        $queryBuilder = $this->prepareGetQueryBuilder();

        $coutnBuilder = null;
        $count = 0;
        if ($this->_request->count) {
            $coutnBuilder = clone $queryBuilder;
            $coutnBuilder->getQuery()->limit = null;
            $coutnBuilder->getQuery()->offset = null;
        }

        if ($this->_id !== null) {
            $data = $queryBuilder->first();
        } else {
            $data = $queryBuilder->get();
            if ($this->_request->count) {
                $count = $coutnBuilder->count();
            }
        }

        return $this->toGetResponse($data, $count);
    }

    public static function ResponsePostSuccess(\Illuminate\Database\Eloquent\Model $model): \Illuminate\Http\JsonResponse
    {
        return response()->json($model)->setStatusCode(201);
    }

    public function post(): \Illuminate\Http\JsonResponse
    {
//        $data = request()->all();
        $model = $this->_odataEntity->getModel();

        $validationRules = ValidationRulesGenerator::generate($model);
        $data = request()->validate($validationRules);

        // ToDo Password
//        if (isset($data->password)) {
//            $data->password = Hash::make($data->password);
//        }

        $model->fill($data);

        // sync data
        $aRelated = $this->extractRelationsFromInputData($data, $model);

        $model->save();

        // Sync pivot table
        $this->syncRelations($model, $aRelated);

        return self::ResponsePostSuccess($model);
    }

    /**
     * @throws OdataIdentifierIsUndefined
     */
    public function delete(): \Illuminate\Http\Response
    {
        $model = $this->_odataEntity->getModel();
        if (!$this->_id) {
            throw new OdataIdentifierIsUndefined();
        }
        $queryBuilder = $model->newModelQuery();
        $queryBuilder->where([$model->getKeyName() => $this->_id])->delete();
        return response()->noContent();
    }

    public function patch(): \Illuminate\Http\Response
    {
        return $this->update(true);
    }

    public function put(): \Illuminate\Http\Response
    {
        return $this->update(false);
    }

    private function update(bool $isPatchRequest = false): \Illuminate\Http\Response
    {
        $model = $this->_odataEntity->getModel();
        if (!$this->_id) {
            throw new Error('Identifier is undefined');
        }
        $queryBuilder = $model->newModelQuery();
        $item = $queryBuilder->find($this->_id);

        $validationRules = ValidationRulesGenerator::generate($model, sometimes: $isPatchRequest, ignoreId: $this->_id);
        $data = request()->validate($validationRules);

//        $data = request()->all();
        $item->fill($data);
        $item->save();
        return response()->noContent();
    }

    public function metadata(): \Illuminate\Http\Response
    {
        $xmlString = $this->createMetadataXml();
        return response($xmlString, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Генерация $metadata xml ответа
     */
    private function createMetadataXml(): string
    {
        $serviceName = Str::slug(config('app.name'));

        $xml = new \DOMDocument(); // creates an instance of DOMDocument class.
        $xml->encoding = 'utf-8'; // sets the document encoding to utf-8.
        $xml->xmlVersion = '1.0'; // specifies the version number 1.0.
        $xml->formatOutput = true; // ensures that the output is well formatted.

        $Edmx = $xml->createElementNS('http://docs.oasis-open.org/odata/ns/edmx', 'edmx:Edmx');
        $Edmx->setAttribute('Version', '4.0');
        $xml->appendChild($Edmx);

        $DataServices = $xml->createElement('edmx:DataServices');
        $Edmx->appendChild($DataServices);

        $Schema = $xml->createElementNS('http://docs.oasis-open.org/odata/ns/edm', 'Schema');
        $Schema->setAttribute('Namespace', $serviceName);
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
                    if (!$field->isNullable()) {
                        $Property->setAttribute('Nullable', OdataHelper::toValue($field->isNullable()));
                    }

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
            $allRelations = $oEntity->getModel()->relationships();
//            dd($allRelations);
            foreach ($allRelations as $relation) {
                $NavigationProperty = $xml->createElement('NavigationProperty');
                $NavigationProperty->setAttribute('Name', $relation->name);

                $aModelPathPart = explode('\\', $relation->model);
                $modelName = array_pop($aModelPathPart);

                $link = implode('.', [$serviceName, strtolower($modelName)]);
                $wrapper = [0 => '', 1 => ''];
                if (in_array($relation->type, ['BelongsToMany'])) {
                    $wrapper[0] = 'Collection(';
                    $wrapper[1] = ')';
                }
                $NavigationProperty->setAttribute('Type', $wrapper[0] . $link . $wrapper[1]);
                $NavigationProperty->setAttribute('up:foreign', $relation->foreignKey);
                $NavigationProperty->setAttribute('up:primary', $relation->ownerKey);

                $EntityType->appendChild($NavigationProperty);
            }
        }

        // Генерация раздела наборов
        $EntityContainer = $xml->createElement('EntityContainer');
        $EntityContainer->setAttribute('Name', 'Container');
        $DataServices->appendChild($EntityContainer);

        foreach ($aModel as $oEntity) {
            if (!$oEntity instanceof OdataEntityDescription) continue;
            $EntitySet = $xml->createElement('EntitySet');
            $EntitySet->setAttribute('Name', Str::plural($oEntity->getEntityName()));
            $EntitySet->setAttribute('EntityType', $serviceName . '.' . $oEntity->getEntityName());


            $EntityContainer->appendChild($EntitySet);

            $allRelations = $oEntity->getModel()->relationships();
            foreach ($allRelations as $relation) {
                if (!$relation instanceof OdataRelationship) continue;

                $aModelPathPart = explode('\\', $relation->model);
                $modelName = array_pop($aModelPathPart);

                $NavigationPropertyBinding = $xml->createElement('NavigationPropertyBinding');
                $NavigationPropertyBinding->setAttribute('Path', $relation->name);
                $NavigationPropertyBinding->setAttribute('Target', Str::plural(strtolower($modelName)));

                $EntitySet->appendChild($NavigationPropertyBinding);
            }

        }

        return $xml->saveXML();
    }

    /**
     * Возвращает массив сущностей
     * @param string|null $scanPath ! Используется только для рекурсии
     */
    private static function getEntityModels(string|null $scanPath = null): array
    {
        $aFolders = [];
        $aFolders[''] = $scanPath ? $scanPath : app_path() . DIRECTORY_SEPARATOR . 'Models';

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

    private function arrayKeyReplace(mixed $originalName, mixed $replaceWith, array $array, bool $recursive = true): array
    {
        $return = array();
        foreach ($array as $key => $value) {
            if ($key === $originalName)
                $key = $replaceWith;

            if (is_array($value) && $recursive)
                $value = $this->arrayKeyReplace($originalName, $replaceWith, $value, $recursive);

            $return[$key] = $value;
        }
        return $return;
    }

    /**
     * Синхронизация данных для связей ManyToMany
     */
    private function syncRelations(Model &$find, array $aRelated): void
    {
        // Sync pivot table
        foreach ($aRelated as $key => $relation) {
            $syncField = ucfirst($relation->field);
            call_user_func([$find, $syncField])->sync($relation->values);
        }
    }

    /**
     * Извлечение связей из данных
     * @param $data
     * @param Model $model
     * @return array
     */
    private function extractRelationsFromInputData(&$data, Model $oModel): array
    {
        if (!self::modelIsRestable($oModel)) {
            return [];
        }

        // check relation fields
        $aRelationship = $oModel->relationships();

        $aRelated = [];
        /** @var OdataRelationship $relationship */
        foreach ($aRelationship as $relationship) {
            if (array_key_exists($relationship->snakeName, $data)) {
                $related = new \stdClass();
                $related->field = $relationship->name;
                $related->values = [];
                foreach ($data[$relationship->snakeName] as $datum) {
                    $idSync = 0;
                    $pivotSync = [];
                    if (is_array($datum)) {
                        foreach ($datum as $k => $v) {
                            if ($k == $oModel->getKeyName()) {
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
                unset($data[$relationship->snakeName]);
                $aRelated[$relationship->name] = $related;
            }
        }
        return $aRelated;
    }

    public static function modelIsRestable(Model $model): bool
    {
        return in_array("\\Lexxsoft\\Odata\\Traits\\Restable", class_uses($model));
    }
}
