<?php

namespace Lexxsoft\Odata\Contracts\Traits;

trait HasValidationRules
{
    protected $rules = [];

    public function validationRules(array $rules): self
    {
        $this->rules = $rules;
        return $this;
    }
}
