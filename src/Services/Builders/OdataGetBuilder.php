<?php

namespace Lexxsoft\Odata\Services\Builders;

use Illuminate\Database\Eloquent\Scope;
use Lexxsoft\Odata\Contracts\Traits\HasId;
use Lexxsoft\Odata\Support\OdataExpand;
use Lexxsoft\Odata\Support\OdataFilter;
use Lexxsoft\Odata\Support\OdataOrder;

class OdataGetBuilder extends OdataBuilder
{
    use HasId;

    protected \Closure|null $fnAfterDataGet = null;
    protected \Closure|null $fnExtendQueryBuilder = null;

    public function afterDataGet(\Closure|null $fn = null): self
    {
        $this->fnAfterDataGet = $fn;
        return $this;
    }

    public function extendQueryBuilder(\Closure|null $fn = null): self
    {
        $this->fnExtendQueryBuilder = $fn;
        return $this;
    }


    public function withCount(bool $value = true): self
    {
        $this->request->count = $value;
        return $this;
    }

    public function noLimit(): self
    {
        $this->reset('limit');
        return $this;
    }

    public function noOffset(): self
    {
        $this->reset('offset');
        return $this;
    }

    public function reset(string|array $items = '*'): self
    {
        $this->request->reset($items);
        return $this;
    }

    public function prepare(): \Illuminate\Database\Eloquent\Builder
    {
        $model = $this->getModel();
        $queryBuilder = $model->newModelQuery();

        /** @var Scope $scope */
        foreach ($this->getModel()->getGlobalScopes() as $scope) {
            $scope->apply($queryBuilder, $this->getModel());
        }

        $modelTable = $this->getModel()->getTable();

        // Filter
        if ($this->request->hasFilter()) {
            $filterParts = [];
            foreach ($this->request->filter as $oFilter) {
                if ($oFilter instanceof OdataFilter) {
                    $filterParts[] = $oFilter->toArray($modelTable);
                }
            }
            $queryBuilder->where($filterParts);
        }

        // Order
        if ($this->request->hasOrder()) {
            foreach ($this->request->order as $order) {
                if ($order instanceof OdataOrder) {
                    $queryBuilder->orderBy($order->field, $order->direction->value);
                }
            }
        }

        // select
        if ($this->request->hasSelect()) {
            $queryBuilder->select($this->request->select);
        }

        // expand
        if ($this->request->hasExpand()) {
            /** @var OdataExpand $expand */
            foreach ($this->request->expand as $expand) {
                if ($expand->withCount()) {
                    $queryBuilder->withCount($expand->entity() . ' as ' . $expand->entity() . '__odata_count');

                    if ($expand->top() >= 0) {
                        $queryBuilder->with($expand->entity());
                    }
                } else {
                    $queryBuilder->with($expand->entity());
                }
            }
        }

        // limit
        if ($this->request->hasLimit()) {
            $queryBuilder->limit($this->request->limit);
        }

        // offset
        if ($this->request->hasOffset()) {
            $queryBuilder->offset($this->request->offset);
        }

        // if ID is applied
        if (!in_array($this->id, [null, '$count', '$value'])) {
            $queryBuilder->where([$this->getModel()->getKeyName() => $this->id]);
        }

        return $queryBuilder;
    }

    /**
     * @param ...$parameters
     */
    protected function toResponse(...$parameters)
    {
        $data = $parameters[0];
        $count = $parameters[1];

        $asRawValue = false;

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
            if ($this->request->count || $this->id == '$count') {
                $annotation = "@odata.count";
                $res->$annotation = $count;
            }

            // Replace technical suffix to OData standards
            $suffixReplacements = [
                '__odata_count' => '@odata.count'
            ];

            $array = $data->toArray();
            $this->replaceDataTechnicalKeySuffix($suffixReplacements, $array);

            if (!in_array($this->id, [null, '$value', '$count'])) {
                $res = $array;
            } else {
                $res->value = $array;
            }

            $data = $res;
        }

        return response()->json($data);
    }

    protected function replaceDataTechnicalKeySuffix(array $replacements, &$array): void
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

    protected function arrayKeyReplace(mixed $originalName, mixed $replaceWith, array $array, bool $recursive = true): array
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

    public function get()
    {
        $continue = true;
        if ($this->fnBeforeStart !== null) {
            $continue = ($this->fnBeforeStart)();
        }
        if ($continue !== true) {
            return $continue;
        }

        $dataQueryBuilder = $this->prepare();
        if ($this->fnExtendQueryBuilder !== null) {
            ($this->fnExtendQueryBuilder)($dataQueryBuilder);
        }
        $count = 0;
        if ($this->request->count || $this->id == '$count') {
            $this->noLimit()->noOffset();
            $countQueryBuilder = $this->prepare();
            if ($this->fnExtendQueryBuilder !== null) {
                ($this->fnExtendQueryBuilder)($countQueryBuilder);
            }
            $count = $countQueryBuilder->count();
        }


        if (!in_array($this->id, [null, '$count', '$value'])) {
            $data = $dataQueryBuilder->first();
        } else {
            $data = $dataQueryBuilder->get();
        }

        if ($this->fnAfterDataGet !== null) {
            ($this->fnAfterDataGet)($data);
        }

        return $this->toResponse($data, $count);
    }

}
