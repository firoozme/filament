<?php

namespace Filament\Tables\DataProviders;

use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface DataProvider
{
    public function getRecords(): Collection | Paginator;

    public function hydratePivotRelation(Collection | Paginator $records): Collection | Paginator;

    public function getEloquentQuery(): ?Builder;

    /**
     * @param array<BaseFilter> $filters
     * @param array<string, mixed> $filtersData
     */
    public function applyFilters(array $filters, array $filtersData): static;

    /**
     * @param array<Column> $columns
     */
    public function applyGlobalSearchConstraint(array $columns, string $search): static;

    public function applyIndividualColumnSearchConstraint(Column $column, string $search): static;

    /**
     * @return array<string, mixed>
     */
    public function getDefaultClosureDependenciesForEvaluationByName(): array;

    /**
     * @return array<class-string, mixed>
     */
    public function getDefaultClosureDependenciesForEvaluationByType(): array;

    public function withTrashed(): static;

    public function onlyTrashed(): static;

    public function withoutTrashed(): static;

    public function order(mixed $attribute, string $direction = 'asc'): static;

    public function where(string $attribute, mixed $value): static;

    public function whereNotNull(string $attribute): static;

    public function whereNull(string $attribute): static;
}
