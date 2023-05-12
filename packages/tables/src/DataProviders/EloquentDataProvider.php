<?php

namespace Filament\Tables\DataProviders;

use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use function Livewire\invade;

class EloquentDataProvider implements DataProvider
{
    protected Builder $query;

    protected Collection | Paginator | null $records = null;

    protected Table $table;

    final public function __construct(Table $table, ?Builder $query = null)
    {
        $this->table = $table;
        $this->query = $query ?? $table->getQuery();
    }

    public static function make(Table $table, ?Builder $query = null): static
    {
        return app(static::class, ['table' => $table, 'query' => $query]);
    }

    public function getRecords(): Collection | Paginator
    {
        if ($this->records) {
            return $this->records;
        }

        if (
            (! $this->table->isPaginated()) ||
            ($this->table->isReordering() && (! $this->table->isPaginatedWhileReordering()))
        ) {
            return $this->records = $this->hydratePivotRelation($this->query->get());
        }

        return $this->records = $this->hydratePivotRelation($this->paginateTableQuery($this->query));
    }

    protected function paginateTableQuery(Builder $query): Paginator
    {
        $perPage = $this->table->getRecordsPerPage();

        /** @var LengthAwarePaginator $records */
        $records = $query->paginate(
            $perPage === 'all' ? $query->count() : $perPage,
            ['*'],
            $this->table->getLivewire()->getTablePaginationPageName(),
        );

        return $records->onEachSide(1);
    }

    public function hydratePivotRelation(Collection | Paginator $records): Collection | Paginator
    {
        $relationship = $this->table->getRelationship();

        if ($relationship instanceof BelongsToMany && ! $this->table->allowsDuplicates()) {
            invade($relationship)->hydratePivotRelation($records->all());
        }

        return $records;
    }

    public function getEloquentQuery(): ?Builder
    {
        return $this->query;
    }

    /**
     * @param array<BaseFilter> $filters
     * @param array<string, mixed> $filtersData
     */
    public function applyFilters(array $filters, array $filtersData): static
    {
        $this->query->where(function (Builder $query) use ($filters, $filtersData) {
            foreach ($filters as $filter) {
                $filter->apply(
                    static::make($this->table, $query),
                    $filtersData[$filter->getName()] ?? [],
                );
            }
        });

        return $this;
    }

    /**
     * @param array<Column> $columns
     */
    public function applyGlobalSearchConstraint(array $columns, string $search): static
    {
        $this->query->where(function (Builder $query) use ($columns, $search) {
            $isFirst = true;

            foreach ($columns as $column) {
                $this->applySearchConstraint(
                    $query,
                    $column,
                    $search,
                    $isFirst,
                );
            }
        });

        return $this;
    }

    public function applyIndividualColumnSearchConstraint(Column $column, string $search): static
    {
        $this->query->where(function (Builder $query) use ($column, $search) {
            $isFirst = true;

            $this->applySearchConstraint(
                $query,
                $column,
                $search,
                $isFirst,
            );
        });

        return $this;
    }

    public function applySearchConstraint(Builder $query, Column $column, string $search, bool &$isFirst): static
    {
        if ($searchUsing = $column->getSearchUsing()) {
            $whereClause = $isFirst ? 'where' : 'orWhere';

            $query->{$whereClause}(
                fn (Builder $query) => $column->evaluate(
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
                ),
            );

            $isFirst = false;

            return $this;
        }

        $translatableContentDriver = $this->table->getLivewire()->makeTableTranslatableContentDriver();

        foreach ($column->getSearchColumns() as $searchColumn) {
            $whereClause = $isFirst ? 'where' : 'orWhere';

            $this->query->when(
                $translatableContentDriver?->isAttributeTranslatable($this->query->getModel()::class, attribute: $searchColumn),
                fn (EloquentBuilder $query): EloquentBuilder => $translatableContentDriver->applySearchConstraintToQuery($this->query, $searchColumn, $search, $whereClause),
                function (EloquentBuilder $query) use ($column, $search, $searchColumn, $whereClause): EloquentBuilder {
                    /** @var Connection $databaseConnection */
                    $databaseConnection = $query->getConnection();

                    $searchOperator = match ($databaseConnection->getDriverName()) {
                        'pgsql' => 'ilike',
                        default => 'like',
                    };

                    return $query->when(
                        $column->queriesRelationships($query->getModel()),
                        fn (EloquentBuilder $query): EloquentBuilder => $query->{"{$whereClause}Relation"}(
                            $column->getRelationshipName(),
                            $searchColumn,
                            $searchOperator,
                            "%{$search}%",
                        ),
                        fn (EloquentBuilder $query): EloquentBuilder => $query->{$whereClause}(
                            $searchColumn,
                            $searchOperator,
                            "%{$search}%",
                        ),
                    );
                },
            );

            $isFirst = false;
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultClosureDependenciesForEvaluationByName(): array
    {
        return [
            'query' => $this->query,
        ];
    }

    /**
     * @return array<class-string, mixed>
     */
    public function getDefaultClosureDependenciesForEvaluationByType(): array
    {
        return [
            Builder::class => $this->query,
        ];
    }

    public function withTrashed(): static
    {
        $this->query->withTrashed();

        return $this;
    }

    public function onlyTrashed(): static
    {
        $this->query->onlyTrashed();

        return $this;
    }

    public function withoutTrashed(): static
    {
        $this->query->withoutTrashed();

        return $this;
    }

    public function order(mixed $attribute, string $direction = 'asc'): static
    {
        $this->query->orderBy($attribute, $direction);

        return $this;
    }

    public function where(string $attribute, mixed $value): static
    {
        $isMultiple = is_array($value);

        if (! str($attribute)->contains('.')) {
            $this->query->{$isMultiple ? 'whereIn' : 'where'}(
                $attribute,
                $value,
            );

            return $this;
        }

        $relationshipName = (string) str($attribute)->beforeLast('.');
        $relationshipKey = (string) str($attribute)->afterLast('.');

        if ($isMultiple) {
            $this->query->whereHas(
                $relationshipName,
                fn (Builder $query) => $query->whereIn(
                    $relationshipKey,
                    $value,
                ),
            );

            return $this;
        }

        $this->query->whereRelation(
            $relationshipName,
            $relationshipKey,
            $value,
        );

        return $this;
    }

    public function whereNotNull(string $attribute): static
    {
        if (str($attribute)->contains('.')) {
            $this->query->whereRelation(
                (string) str($attribute)->beforeLast('.'),
                (string) str($attribute)->afterLast('.'),
                '!=',
                null,
            );

            return $this;
        }

        $this->query->whereNotNull($attribute);

        return $this;
    }

    public function whereNull(string $attribute): static
    {
        if (str($attribute)->contains('.')) {
            $this->query->whereRelation(
                (string) str($attribute)->beforeLast('.'),
                (string) str($attribute)->afterLast('.'),
                null,
            );

            return $this;
        }

        $this->query->whereNull($attribute);

        return $this;
    }
}
