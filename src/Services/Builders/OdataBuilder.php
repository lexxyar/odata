<?php

namespace Lexxsoft\Odata\Services\Builders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Lexxsoft\Odata\OdataRequest;

abstract class OdataBuilder
{
    protected OdataRequest $request;
    protected Model|null $modelInstance = null;
    protected \Closure|null $fnBeforeStart = null;

    public static function make(...$parameters): static
    {
        return new static(...$parameters);
    }

    public function __construct(protected string|null $modelClass = null, bool $useRequest = true)
    {
        $this->request = clone OdataRequest::getInstance();
        if (!$useRequest) {
            $this->request->reset();
        }
    }

    public function beforeStart(\Closure|null $fn = null): self
    {
        $this->fnBeforeStart = $fn;
        return $this;
    }

    public function applyRequest(): self
    {
        $this->request = clone OdataRequest::getInstance();
        return $this;
    }

    public function model(string $value): self
    {
        $this->modelClass = $value;
        return $this;
    }

    protected function getModel(): Model
    {
        if ($this->modelInstance !== null) return $this->modelInstance;

        if (!class_exists($this->modelClass)) {
            throw new \Error("Model class $this->modelClass not found");
        }
        $this->modelInstance = App::make($this->modelClass);
        return $this->modelInstance;
    }
}
