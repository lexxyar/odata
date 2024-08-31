<?php

namespace Lexxsoft\Odata\Services\Builders;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lexxsoft\Odata\Contracts\Traits\HasValidationRules;
use Lexxsoft\Odata\Resources\OdataMessageResource;

class OdataDeleteBatchBuilder extends OdataBuilder
{
    use HasValidationRules;

    protected \Closure|null $fnAfterDelete = null;
    protected \Closure|null $fnBeforeDelete = null;

    public function afterDelete(\Closure|null $fn = null): self
    {
        $this->fnAfterDelete = $fn;
        return $this;
    }

    public function beforeDelete(\Closure|null $fn = null): self
    {
        $this->fnBeforeDelete = $fn;
        return $this;
    }

    public function delete(): \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
    {
        $continue = true;
        if ($this->fnBeforeStart !== null) {
            $continue = ($this->fnBeforeStart)();
        }
        if ($continue !== true) {
            return $continue;
        }

        try {
            $validData = request()->validate($this->rules);
        } catch (ValidationException $ex) {
            return OdataMessageResource::make($ex->getMessage())
                ->code($ex->getCode())
                ->fromValidation($ex->errors())
                ->response()
                ->setStatusCode($ex->status);
        }

        reset($validData);
        $key = key($validData);
        $ids = $validData[$key];
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        if (sizeof($ids) === 0) {
            return OdataMessageResource::make()
                ->message(__("ID's are not provided"))
                ->response()
                ->setStatusCode(400);
        }

        try {
            $data = [];
            DB::transaction(function () use ($ids, &$data) {
                $model = $this->getModel();
                $builder = $model->newModelQuery()->whereIn($model->getKeyName(), $ids);
                $continue = true;
                if ($this->fnBeforeDelete !== null) {
                    $continue = ($this->fnBeforeDelete)($model);
                }
                if ($continue !== true) {
                    throw new \Exception('Not continued operation returned', 422);
                }
                $data = $builder->get();
                $builder->delete();
            });
            if ($this->fnAfterDelete !== null) {
                ($this->fnAfterDelete)($data);
            }
        } catch (\Exception $ex) {
            if ($ex->getCode() == 404) {
                return OdataMessageResource::make()
                    ->message(__('Item not found'))
                    ->response()
                    ->setStatusCode(404);
            } else {
                return OdataMessageResource::make()
                    ->fromException($ex)
                    ->response()
                    ->setStatusCode(500);
            }
        }

        return response()->noContent();
    }
}
