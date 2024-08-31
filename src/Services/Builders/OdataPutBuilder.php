<?php

namespace Lexxsoft\Odata\Services\Builders;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;
use Lexxsoft\Odata\Contracts\Traits\CanSyncRelations;
use Lexxsoft\Odata\Contracts\Traits\HasBeforeAndAfterSave;
use Lexxsoft\Odata\Contracts\Traits\HasId;
use Lexxsoft\Odata\Contracts\Traits\HasValidationRules;
use Lexxsoft\Odata\Resources\OdataMessageResource;

class OdataPutBuilder extends OdataBuilder
{
    use HasId, HasValidationRules, CanSyncRelations, HasBeforeAndAfterSave;

    public function put()
    {
        $continue = true;
        if ($this->fnBeforeStart !== null) {
            $continue = ($this->fnBeforeStart)();
        }
        if ($continue !== true) {
            return $continue;
        }

        if (!$this->id) {
            return OdataMessageResource::make(__('ID is missing'))
                ->response()
                ->setStatusCode(400);
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

        $model = $this->getModel();
        $item = $model->newModelQuery()->find($this->id);
        if (!$item) {
            return OdataMessageResource::make()
                ->message(__('Item not found'))
                ->response()
                ->setStatusCode(404);
        }
        try {
            DB::transaction(function () use (&$validData, $item, $model) {
                if ($this->fnBeforeSave !== null) {
                    ($this->fnBeforeSave)($validData);
                    if ($validData instanceof MessageBag) {
                        throw new \Exception('Internal exception', 422);
                    }
                }

                // Extract relations
                $related = $this->extractRelationsFromInputData($validData, $model);

                $item->fill($validData)->save();

                // Sync pivot table
                $this->syncRelations($item, $related);

                if ($this->fnAfterSave !== null) {
                    ($this->fnAfterSave)($item);
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

        return response()->noContent();
    }
}
