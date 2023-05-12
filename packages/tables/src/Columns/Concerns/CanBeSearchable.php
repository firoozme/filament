<?php

namespace Filament\Tables\Columns\Concerns;

use Closure;
use Illuminate\Support\Arr;

trait CanBeSearchable
{
    protected bool $isGloballySearchable = false;

    protected bool $isIndividuallySearchable = false;

    protected bool $isSearchable = false;

    /**
     * @var array<string> | null
     */
    protected ?array $searchColumns = null;

    protected ?Closure $searchUsing = null;

    /**
     * @param  bool | array<string> | string  $condition
     */
    public function searchable(
        bool | array | string $condition = true,
        ?Closure $using = null,
        bool $isIndividual = false,
        bool $isGlobal = true,
    ): static {
        if (is_bool($condition)) {
            $this->isSearchable = $condition;
            $this->searchColumns = null;
        } else {
            $this->isSearchable = true;
            $this->searchColumns = Arr::wrap($condition);
        }

        $this->isGloballySearchable = $isGlobal;
        $this->isIndividuallySearchable = $isIndividual;
        $this->searchUsing = $using;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getSearchColumns(): array
    {
        return $this->searchColumns ?? $this->getDefaultSearchColumns();
    }

    public function isSearchable(): bool
    {
        return $this->isSearchable;
    }

    public function isGloballySearchable(): bool
    {
        return $this->isSearchable() && $this->isGloballySearchable;
    }

    public function isIndividuallySearchable(): bool
    {
        return $this->isSearchable() && $this->isIndividuallySearchable;
    }

    /**
     * @return array{0: string}
     */
    public function getDefaultSearchColumns(): array
    {
        return [str($this->getName())->afterLast('.')];
    }
}
