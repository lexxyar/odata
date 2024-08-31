<?php

namespace Lexxsoft\Odata\Contracts\Traits;

trait HasBeforeAndAfterSave
{
    protected \Closure|null $fnBeforeSave = null;
    protected \Closure|null $fnAfterSave = null;

    public function beforeSave(\Closure|null $fn = null): self
    {
        $this->fnBeforeSave = $fn;
        return $this;
    }

    public function afterSave(\Closure|null $fn = null): self
    {
        $this->fnAfterSave = $fn;
        return $this;
    }
}
