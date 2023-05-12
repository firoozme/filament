<?php

namespace Filament\Tables\Concerns;

use Filament\Tables\DataProviders\DataProvider;

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

    protected function applySortingToTableData(DataProvider $data): DataProvider
    {
        if ($this->getTable()->isGroupsOnly()) {
            return $data;
        }

        if ($this->isTableReordering()) {
            return $data->order($this->getTable()->getReorderColumn());
        }

        if (! $this->tableSortColumn) {
            return $this->applyDefaultSortingToTableData($data);
        }

        $column = $this->getTable()->getSortableVisibleColumn($this->tableSortColumn);

        if (! $column) {
            return $this->applyDefaultSortingToTableData($data);
        }

        $sortDirection = $this->tableSortDirection === 'desc' ? 'desc' : 'asc';

        $column->applySort($data, $sortDirection);

        return $data;
    }

    protected function applyDefaultSortingToTableData(DataProvider $data): DataProvider
    {
        $sortColumnName = $this->getTable()->getDefaultSortColumn();
        $sortDirection = ($this->getTable()->getDefaultSortDirection() ?? $this->tableSortDirection) === 'desc' ? 'desc' : 'asc';

        if (
            $sortColumnName &&
            ($sortColumn = $this->getTable()->getSortableVisibleColumn($sortColumnName))
        ) {
            return $sortColumn->applySort($data, $sortDirection);
        }

        if ($sortColumnName) {
            return $data->order($sortColumnName, $sortDirection);
        }

        if ($sortDataUsing = $this->getTable()->getDefaultSortUsing()) {
            app()->call($sortDataUsing, [
                ...$data->getDefaultClosureDependenciesForEvaluationByName(),
                'dataProvider' => $data,
                'direction' => $sortDirection,
            ]);

            return $data;
        }

        $query = $data->getEloquentQuery();

        if (! $query) {
            return $data;
        }

        return $data->order($query->getModel()->getQualifiedKeyName());
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
