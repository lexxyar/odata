<?php

namespace Lexxsoft\Odata\Contracts\Traits;

trait HasId
{
    protected string|int|null $id = null;

    public function id(string|int|null $value): self
    {
        $this->id = $value;
        return $this;
    }
}
