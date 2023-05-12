<?php

namespace Filament\Tables\Concerns;

use Filament\Tables\DataProviders\DataProvider;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use function Livewire\invade;

trait HasRecords
{
    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected bool $allowsDuplicates = false;

    protected Collection | Paginator $records;

    public function getFilteredTableData(): DataProvider
    {
        $data = $this->getTable()->getDataProvider();

        $this->applyFiltersToTableData($data);

        $this->applySearchToTableData($data);

        if ($query = $data->getEloquentQuery()) {
            foreach ($this->getTable()->getColumns() as $column) {
                if ($column->isHidden()) {
                    continue;
                }

                $column->applyRelationshipAggregates($query);

                if ($this->getTable()->isGroupsOnly()) {
                    continue;
                }

                $column->applyEagerLoading($query);
            }
        }

        return $data;
    }

    public function getFilteredSortedTableData(): DataProvider
    {
        $data = $this->getFilteredTableData();

        $this->applyGroupingToTableData($data);

        $this->applySortingToTableData($data);

        return $data;
    }

    protected function hydratePivotRelationForTableRecords(EloquentCollection | Paginator $records): EloquentCollection| Paginator
    {
        $table = $this->getTable();
        $relationship = $table->getRelationship();

        if ($table->getRelationship() instanceof BelongsToMany && ! $table->allowsDuplicates()) {
            invade($relationship)->hydratePivotRelation($records->all());
        }

        return $records;
    }

    public function getTableRecords(): Collection | Paginator
    {
        return $this->records ??= $this->getFilteredSortedTableData()->getRecords();
    }

    protected function resolveTableRecord(?string $key): ?Model
    {
        if ($key === null) {
            return null;
        }

        if (! ($this->getTable()->getRelationship() instanceof BelongsToMany)) {
            return $this->getTable()->getQuery()->find($key);
        }

        /** @var BelongsToMany $relationship */
        $relationship = $this->getTable()->getRelationship();

        $pivotClass = $relationship->getPivotClass();
        $pivotKeyName = app($pivotClass)->getKeyName();

        $table = $this->getTable();

        $query = $table->allowsDuplicates() ?
            $relationship->wherePivot($pivotKeyName, $key) :
            $relationship->where($relationship->getQualifiedRelatedKeyName(), $key);

        $record = $table->selectPivotDataInQuery($query)->first();

        return $record?->setRawAttributes($record->getRawOriginal());
    }

    public function getTableRecord(?string $key): ?Model
    {
        return $this->resolveTableRecord($key);
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    public function allowsDuplicates(): bool
    {
        return $this->allowsDuplicates;
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    public function getTableRecordTitle(Model $record): ?string
    {
        return null;
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    public function getTableModelLabel(): ?string
    {
        return null;
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    public function getTablePluralModelLabel(): ?string
    {
        return null;
    }
}
