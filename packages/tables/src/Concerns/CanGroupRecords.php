<?php

namespace Filament\Tables\Concerns;

use Filament\Tables\DataProviders\DataProvider;
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

    protected function applyGroupingToTableData(DataProvider $data): DataProvider
    {
        if ($this->isTableReordering()) {
            return $data;
        }

        $group = $this->getTableGrouping();

        if (! $group) {
            return $data;
        }

        $group->orderData($data, $this->tableGroupingDirection ?? 'asc');

        return $data;
    }
}
