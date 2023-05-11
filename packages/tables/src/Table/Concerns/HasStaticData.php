<?php

namespace Filament\Tables\Table\Concerns;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

trait HasStaticData
{
    protected array | Arrayable | Closure | null $data = null;

    public function data(array | Arrayable | Closure | null $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getData(): ?Collection
    {
        $data = $this->evaluate($this->data);

        if ($data === null) {
            return null;
        }

        if ($data instanceof Collection) {
            return $data;
        }

        return collect($data);
    }

    public function hasStaticData(): bool
    {
        return $this->data !== null;
    }
}
