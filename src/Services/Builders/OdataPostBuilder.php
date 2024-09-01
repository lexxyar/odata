<?php

namespace Lexxsoft\Odata\Services\Builders;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;
use Lexxsoft\Odata\Contracts\Traits\CanSyncRelations;
use Lexxsoft\Odata\Contracts\Traits\HasBeforeAndAfterSave;
use Lexxsoft\Odata\Contracts\Traits\HasValidationRules;
use Lexxsoft\Odata\Resources\OdataMessageResource;

class OdataPostBuilder extends OdataBuilder
{
    use HasValidationRules, CanSyncRelations, HasBeforeAndAfterSave;

    public function post(): \Illuminate\Http\JsonResponse
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

        try {
            DB::transaction(function () use (&$validData) {
//                if ($this->fnBeforeSave !== null) {
//                    ($this->fnBeforeSave)($validData);
//                    if ($validData instanceof MessageBag) {
//                        throw new \Exception('Internal exception', 422);
//                    }
//                }

                $model = $this->getModel();
                $model->fill($validData);

                if ($this->fnBeforeSave !== null) {
                    ($this->fnBeforeSave)($model);
                    if ($validData instanceof MessageBag) {
                        throw new \Exception('Internal exception', 422);
                    }
                }

                // sync relations
                $related = $this->extractRelationsFromInputData($validData, $model);

                $model->save();

                // Sync pivot table
                $this->syncRelations($model, $related);

                if ($this->fnAfterSave !== null) {
                    ($this->fnAfterSave)($model);
                }
            });
        } catch (\Exception $ex) {
            if ($ex->getCode() == 422) {
                return OdataMessageResource::make()
                    ->fromValidation($validData->toArray())
                    ->message($validData->first())
                    ->response()
                    ->setStatusCode(422);
            } else {
                return OdataMessageResource::make()
                    ->fromException($ex)
                    ->response()
                    ->setStatusCode(500);
            }
        }

        return OdataMessageResource::make(__('Created successfully'))
            ->asSuccess()
            ->response()
            ->setStatusCode(201);
    }
}
