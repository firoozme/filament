<?php

namespace Filament\Tables\DataProviders;

use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CollectionDataProvider implements DataProvider
{
    protected Collection $records;

    protected Table $table;

    final public function __construct(Table $table)
    {
        $this->table = $table;
        $this->records = $table->getData();
    }

    public static function make(Table $table): static
    {
        return app(static::class, ['table' => $table]);
    }

    public function getRecords(): Collection
    {
        return $this->records;
    }

    public function hydratePivotRelation(Collection | Paginator $records): Collection
    {
        return $records;
    }

    public function getEloquentQuery(): ?Builder
    {
        return null;
    }

    /**
     * @param  array<BaseFilter>  $filters
     * @param  array<string, mixed>  $filtersData
     */
    public function applyFilters(array $filters, array $filtersData): static
    {
        foreach ($filters as $filter) {
            $filter->apply(
                $this,
                $filtersData[$filter->getName()] ?? [],
            );
        }

        return $this;
    }

    /**
     * @param  array<Column>  $columns
     */
    public function applyGlobalSearchConstraint(array $columns, string $search): static
    {
        $this->records = $this->records->filter(function (mixed $record) use ($columns, $search): bool {
            foreach ($columns as $column) {
                if ($searchUsing = $column->getSearchUsing()) {
                    $records = $column->evaluate(
                        $searchUsing,
                        namedInjections: [
                            ...$this->getDefaultClosureDependenciesForEvaluationByName(),
                            'dataProvider' => $this,
                            'search' => $search,
                            'searchQuery' => $search,
                        ],
                        typedInjections: [
                            ...$this->getDefaultClosureDependenciesForEvaluationByName(),
                            DataProvider::class => $this,
                        ],
                    );

                    if ($records->containsStrict($record)) {
                        return true;
                    }

                    continue;
                }

                foreach ($column->getSearchColumns() as $searchColumn) {
                    if (str(data_get($record, $searchColumn))->lower()->contains(Str::lower($search))) {
                        return true;
                    }
                }
            }

            return false;
        });

        return $this;
    }

    public function applyIndividualColumnSearchConstraint(Column $column, string $search): static
    {
        if ($searchUsing = $column->getSearchUsing()) {
            $this->records = $column->evaluate(
                $searchUsing,
                namedInjections: [
                    ...$this->getDefaultClosureDependenciesForEvaluationByName(),
                    'dataProvider' => $this,
                    'search' => $search,
                    'searchQuery' => $search,
                ],
                typedInjections: [
                    ...$this->getDefaultClosureDependenciesForEvaluationByName(),
                    DataProvider::class => $this,
                ],
            );

            return $this;
        }

        $this->records = $this->records->filter(function (mixed $record) use ($column, $search): bool {
            foreach ($column->getSearchColumns() as $searchColumn) {
                if (str(data_get($record, $searchColumn))->lower()->contains(Str::lower($search))) {
                    return true;
                }
            }

            return false;
        });

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultClosureDependenciesForEvaluationByName(): array
    {
        return [
            'records' => $this->records,
        ];
    }

    /**
     * @return array<class-string, mixed>
     */
    public function getDefaultClosureDependenciesForEvaluationByType(): array
    {
        return [
            Collection::class => $this->records,
        ];
    }

    public function withTrashed(): static
    {
        return $this;
    }

    public function onlyTrashed(): static
    {
        $this->records = $this->records->whereNotNull('deleted_at');

        return $this;
    }

    public function withoutTrashed(): static
    {
        $this->records = $this->records->whereNull('deleted_at');

        return $this;
    }

    public function order(mixed $attribute, string $direction = 'asc'): static
    {
        $this->records = $this->records->sortBy($attribute, descending: $direction === 'desc');

        return $this;
    }

    public function where(string $attribute, mixed $value): static
    {
        if (is_array($value)) {
            $this->records = $this->records->whereIn($attribute, $value);

            return $this;
        }

        $this->records = $this->records->where($attribute, $value);

        return $this;
    }

    public function whereNotNull(string $attribute): static
    {
        $this->records = $this->records->whereNotNull($attribute);

        return $this;
    }

    public function whereNull(string $attribute): static
    {
        $this->records = $this->records->whereNull($attribute);

        return $this;
    }
}
