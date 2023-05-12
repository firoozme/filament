<?php

namespace Filament\Tables\Table\Concerns;

use Closure;
use Filament\Tables\DataProviders\CollectionDataProvider;
use Filament\Tables\DataProviders\DataProvider;
use Filament\Tables\DataProviders\EloquentDataProvider;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use function Filament\Support\get_model_label;
use function Filament\Support\locale_has_pluralization;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasRecords
{
    protected bool | Closure $allowsDuplicates = false;

    protected string | Closure | null $recordLabel = null;

    protected string | Closure | null $pluralRecordLabel = null;

    protected string | Closure | null $recordTitle = null;

    protected string | Closure | null $recordKeyAttribute = null;

    protected string | Closure | null $recordTitleAttribute = null;

    protected string | Closure | null $dataProvider = null;

    public function allowDuplicates(bool | Closure $condition = true): static
    {
        $this->allowsDuplicates = $condition;

        return $this;
    }

    public function modelLabel(string | Closure | null $label): static
    {
        $this->recordLabel($label);

        return $this;
    }

    public function pluralModelLabel(string | Closure | null $label): static
    {
        $this->pluralRecordLabel($label);

        return $this;
    }

    public function recordLabel(string | Closure | null $label): static
    {
        $this->recordLabel = $label;

        return $this;
    }

    public function pluralRecordLabel(string | Closure | null $label): static
    {
        $this->pluralRecordLabel = $label;

        return $this;
    }

    public function recordTitle(string | Closure | null $title): static
    {
        $this->recordTitle = $title;

        return $this;
    }

    public function recordTitleAttribute(string | Closure | null $attribute): static
    {
        $this->recordTitleAttribute = $attribute;

        return $this;
    }

    public function recordKeyAttribute(string | Closure | null $attribute): static
    {
        $this->recordKeyAttribute = $attribute;

        return $this;
    }

    public function getAllRecordsCount(): int
    {
        return $this->getLivewire()->getAllTableRecordsCount();
    }

    public function getDataProvider(): DataProvider
    {
        $dataProvider = $this->evaluate($this->dataProvider);

        if ($dataProvider) {
            return $dataProvider::make($this);
        }

        if ($this->hasStaticData()) {
            return CollectionDataProvider::make($this);
        }

        return EloquentDataProvider::make($this);
    }

    public function getRecords(): Collection | Paginator
    {
        return $this->getLivewire()->getTableRecords();
    }

    public function getRecordKey(mixed $record): string
    {
        if (! $record instanceof Model) {
            return data_get(
                $record,
                $this->evaluate($this->recordKeyAttribute) ?? 'id',
            );
        }

        if (! ($this->getRelationship() instanceof BelongsToMany && $this->allowsDuplicates())) {
            return $record->getKey();
        }

        /** @var BelongsToMany $relationship */
        $relationship = $this->getRelationship();

        $pivotClass = $relationship->getPivotClass();
        $pivotKeyName = app($pivotClass)->getKeyName();

        return $record->getAttributeValue($pivotKeyName);
    }

    public function getModel(): string
    {
        return $this->getQuery()->getModel()::class;
    }

    public function allowsDuplicates(): bool
    {
        return (bool) $this->evaluate($this->allowsDuplicates);
    }

    public function getRecordLabel(): string
    {
        if (filled($recordLabel = $this->evaluate($this->recordLabel))) {
            return $recordLabel;
        }

        if ($this->hasStaticData()) {
            return __('filament-tables::table.record');
        }

        return get_model_label($this->getModel());
    }

    public function getPluralRecordLabel(): string
    {
        $label = $this->evaluate($this->pluralRecordLabel);

        if (filled($label)) {
            return $label;
        }

        if (locale_has_pluralization()) {
            return Str::plural($this->getRecordLabel());
        }

        return $this->getRecordLabel();
    }

    public function getRecordTitle(Model $record): string
    {
        $title = $this->evaluate(
            $this->recordTitle,
            namedInjections: [
                'record' => $record,
            ],
            typedInjections: [
                Model::class => $record,
                is_object($record) ? $record::class : null => $record,
            ],
        );

        if (filled($title)) {
            return $title;
        }

        $titleAttribute = $this->evaluate(
            $this->recordTitleAttribute,
            namedInjections: [
                'record' => $record,
            ],
            typedInjections: [
                Model::class => $record,
                is_object($record) ? $record::class : null => $record,
            ],
        );

        return $record->getAttributeValue($titleAttribute) ?? $this->getRecordLabel();
    }
}
