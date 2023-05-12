<?php

namespace Filament\Tables\Filters\Concerns;

use Closure;
use Filament\Tables\DataProviders\DataProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait InteractsWithTableQuery
{
    protected ?Closure $modifyDataUsing = null;

    /**
     * @param  array<string, mixed>  $state
     */
    public function apply(DataProvider $data, array $state = []): DataProvider
    {
        if ($this->isHidden()) {
            return $data;
        }

        if (! $this->hasDataModificationCallback()) {
            return $data;
        }

        if (! ($state['isActive'] ?? true)) {
            return $data;
        }

        $this->modifyData(
            $this->modifyDataUsing,
            $data,
            $state,
        );

        return $data;
    }

    /**
     * @param array<string, mixed> $state
     */
    public function modifyData(Closure $using, DataProvider $data, array $state): void
    {
        $this->evaluate(
            $using,
            namedInjections: [
                ...$data->getDefaultClosureDependenciesForEvaluationByName(),
                'data' => $state,
                'dataProvider' => $data,
                'state' => $state,
            ],
            typedInjections: [
                ...$data->getDefaultClosureDependenciesForEvaluationByType(),
                DataProvider::class => $data,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function applyToBaseEloquentQuery(Builder $query, array $data = []): Builder
    {
        return $query;
    }

    public function using(?Closure $callback): static
    {
        $this->modifyDataUsing = $callback;

        return $this;
    }

    public function query(?Closure $callback): static
    {
        $this->using($callback);

        return $this;
    }

    protected function hasDataModificationCallback(): bool
    {
        return $this->modifyDataUsing instanceof Closure;
    }
}
