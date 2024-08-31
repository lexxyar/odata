<?php

namespace Lexxsoft\Odata\Services\Builders;

use Illuminate\Support\Facades\DB;
use Lexxsoft\Odata\Contracts\Traits\HasId;
use Lexxsoft\Odata\Resources\OdataMessageResource;

class OdataDeleteBuilder extends OdataBuilder
{
    use HasId;

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

        if (!$this->id) {
            return OdataMessageResource::make(__('ID is missing'))
                ->response()
                ->setStatusCode(400);
        }

        try {
            DB::transaction(function () {
                $model = $this->getModel();
                $model = $model->newModelQuery()->where($model->getKeyName(), $this->id)->first();
                $continue = true;
                if ($this->fnBeforeDelete !== null) {
                    $continue = ($this->fnBeforeDelete)($model);
                }
                if ($continue !== true) {
                    throw new \Exception('Not continued operation returned', 422);
                }
                $exists = $model->delete();
                if (!$exists) {
                    throw new \Exception('Internal exception', 404);
                }

                if ($this->fnAfterDelete !== null) {
                    ($this->fnAfterDelete)($model->toArray());
                }
            });
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
