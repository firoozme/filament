<?php

namespace Filament\Tables\Concerns;

use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait CanGroupRecords
{
    public ?string $tableGrouping = null;

    public ?string $tableGroupingDirection = null;

    public function getTableGrouping(): ?Group
    {
        if (
            filled($this->tableGrouping) &&
            ($group = $this->getTable()->getGroup($this->tableGrouping))
        ) {
            return $group;
        }

        if ($this->getTable()->isDefaultGroupSelectable()) {
            return null;
        }

        return $this->getTable()->getDefaultGroup();
    }

    public function updatedTableGroupColumn(): void
    {
        $this->resetPage();
    }

    protected function applyGroupingToTableQuery(Builder $query): Builder
    {
        if ($this->isTableReordering()) {
            return $query;
        }

        $group = $this->getTableGrouping();

        if (! $group) {
            return $query;
        }

        return $group->orderQuery($query, $this->tableGroupingDirection ?? 'asc');
    }

    protected function applyGroupingToTableData(Collection $data): Collection
    {
        if ($this->isTableReordering()) {
            return $data;
        }

        $group = $this->getTableGrouping();

        if (! $group) {
            return $data;
        }

        return $group->orderQuery($data, $this->tableGroupingDirection ?? 'asc');
    }
}
