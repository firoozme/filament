<?php

namespace Filament\Tables\Filters;

use Filament\Tables\DataProviders\DataProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TrashedFilter extends TernaryFilter
{
    public static function getDefaultName(): ?string
    {
        return 'trashed';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-tables::table.filters.trashed.label'));

        $this->placeholder(__('filament-tables::table.filters.trashed.without_trashed'));

        $this->trueLabel(__('filament-tables::table.filters.trashed.with_trashed'));

        $this->falseLabel(__('filament-tables::table.filters.trashed.only_trashed'));

        $this->queries(
            true: fn (DataProvider $dataProvider) => $dataProvider->withTrashed(),
            false: fn (DataProvider $dataProvider) => $dataProvider->onlyTrashed(),
            blank: fn (DataProvider $dataProvider) => $dataProvider->withoutTrashed(),
        );

        $this->indicateUsing(function (array $state): array {
            if ($state['value'] ?? null) {
                return [$this->getTrueLabel()];
            }

            if (blank($state['value'] ?? null)) {
                return [];
            }

            return [$this->getFalseLabel()];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function applyToBaseEloquentQuery(Builder $query, array $data = []): Builder
    {
        $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);

        return $query;
    }
}
