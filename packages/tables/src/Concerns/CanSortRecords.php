<?php

namespace Filament\Tables\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait CanSortRecords
{
    public ?string $tableSortColumn = null;

    public ?string $tableSortDirection = null;

    public function sortTable(?string $column = null, ?string $direction = null): void
    {
        if ($column === $this->tableSortColumn) {
            $direction ??= match ($this->tableSortDirection) {
                'asc' => 'desc',
                'desc' => null,
                default => 'asc',
            };
        } else {
            $direction ??= 'asc';
        }

        $this->tableSortColumn = $direction ? $column : null;
        $this->tableSortDirection = $direction;

        $this->updatedTableSortColumn();
    }

    public function getTableSortColumn(): ?string
    {
        return $this->tableSortColumn;
    }

    public function getTableSortDirection(): ?string
    {
        return $this->tableSortDirection;
    }

    public function updatedTableSortColumn(): void
    {
        if ($this->getTable()->persistsSortInSession()) {
            session()->put(
                $this->getTableSortSessionKey(),
                [
                    'column' => $this->tableSortColumn,
                    'direction' => $this->tableSortDirection,
                ],
            );
        }

        $this->resetPage();
    }

    public function updatedTableSortDirection(): void
    {
        if ($this->getTable()->persistsSortInSession()) {
            session()->put(
                $this->getTableSortSessionKey(),
                [
                    'column' => $this->tableSortColumn,
                    'direction' => $this->tableSortDirection,
                ],
            );
        }

        $this->resetPage();
    }

    protected function applySortingToTableQuery(Builder $query): Builder
    {
        if ($this->getTable()->isGroupsOnly()) {
            return $query;
        }

        if ($this->isTableReordering()) {
            return $query->orderBy($this->getTable()->getReorderColumn());
        }

        if (! $this->tableSortColumn) {
            return $this->applyDefaultSortingToTableQuery($query);
        }

        $column = $this->getTable()->getSortableVisibleColumn($this->tableSortColumn);

        if (! $column) {
            return $this->applyDefaultSortingToTableQuery($query);
        }

        $sortDirection = $this->tableSortDirection === 'desc' ? 'desc' : 'asc';

        $column->applySort($query, $sortDirection);

        return $query;
    }

    protected function applyDefaultSortingToTableQuery(Builder $query): Builder
    {
        $sortColumnName = $this->getTable()->getDefaultSortColumn();
        $sortDirection = ($this->getTable()->getDefaultSortDirection() ?? $this->tableSortDirection) === 'desc' ? 'desc' : 'asc';

        if (
            $sortColumnName &&
            ($sortColumn = $this->getTable()->getSortableVisibleColumn($sortColumnName))
        ) {
            $sortColumn->applySort($query, $sortDirection);

            return $query;
        }

        if ($sortColumnName) {
            return $query->orderBy($sortColumnName, $sortDirection);
        }

        if ($sortQueryUsing = $this->getTable()->getDefaultSortQuery()) {
            app()->call($sortQueryUsing, [
                'direction' => $sortDirection,
                'query' => $query,
            ]);

            return $query;
        }

        return $query->orderBy($query->getModel()->getQualifiedKeyName());
    }

    protected function applySortingToTableData(Collection $data): Collection
    {
        if ($this->getTable()->isGroupsOnly()) {
            return $data;
        }

        if ($this->isTableReordering()) {
            return $data->sortBy($this->getTable()->getReorderColumn());
        }

        if (! $this->tableSortColumn) {
            return $this->applyDefaultSortingToTableData($data);
        }

        $column = $this->getTable()->getSortableVisibleColumn($this->tableSortColumn);

        if (! $column) {
            return $this->applyDefaultSortingToTableData($data);
        }

        $sortDirection = $this->tableSortDirection === 'desc' ? 'desc' : 'asc';

        return $column->applySort($data, $sortDirection);
    }

    protected function applyDefaultSortingToTableData(Collection $data): Collection
    {
        $sortColumnName = $this->getTable()->getDefaultSortColumn();
        $sortDirection = ($this->getTable()->getDefaultSortDirection() ?? $this->tableSortDirection) === 'desc' ? 'desc' : 'asc';

        if (
            $sortColumnName &&
            ($sortColumn = $this->getTable()->getSortableVisibleColumn($sortColumnName))
        ) {
            $sortColumn->applySort($data, $sortDirection);

            return $data;
        }

        if ($sortColumnName) {
            return $data->sortBy($sortColumnName, descending: $sortDirection === 'desc');
        }

        $sortQueryUsing = $this->getTable()->getDefaultSortQuery();

        if (! $sortQueryUsing) {
            return $data;
        }

        return app()->call($sortQueryUsing, [
            'direction' => $sortDirection,
            'query' => $data,
        ]);
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected function getDefaultTableSortColumn(): ?string
    {
        return null;
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected function getDefaultTableSortDirection(): ?string
    {
        return null;
    }

    public function getTableSortSessionKey(): string
    {
        $table = class_basename($this::class);

        return "tables.{$table}_sort";
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected function shouldPersistTableSortInSession(): bool
    {
        return false;
    }
}
