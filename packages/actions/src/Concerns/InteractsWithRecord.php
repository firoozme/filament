<?php

namespace Filament\Actions\Concerns;

use Closure;
use function Filament\Support\get_model_label;
use function Filament\Support\locale_has_pluralization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait InteractsWithRecord
{
    protected Model | Closure | null $record = null;

    protected string | Closure | null $model = null;

    protected string | Closure | null $recordLabel = null;

    protected string | Closure | null $pluralRecordLabel = null;

    protected string | Closure | null $recordTitle = null;

    public function record(Model | Closure | null $record): static
    {
        $this->record = $record;

        return $this;
    }

    public function model(string | Closure | null $model): static
    {
        $this->model = $model;

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

    public function recordTitle(string | Closure | null $title): static
    {
        $this->recordTitle = $title;

        return $this;
    }

    public function getRecord(): ?Model
    {
        return $this->evaluate($this->record);
    }

    public function getRecordTitle(?Model $record = null): ?string
    {
        return $this->getCustomRecordTitle($record) ?? $this->getRecordLabel();
    }

    public function getCustomRecordTitle(?Model $record = null): ?string
    {
        $record ??= $this->getRecord();

        return $this->evaluate(
            $this->recordTitle,
            namedInjections: [
                'record' => $record,
            ],
            typedInjections: [
                Model::class => $record,
                $record::class => $record,
            ],
        );
    }

    public function getModel(): ?string
    {
        $model = $this->getCustomModel();

        if (filled($model)) {
            return $model;
        }

        $record = $this->getRecord();

        if (! $record) {
            return null;
        }

        return $record::class;
    }

    public function getCustomModel(): ?string
    {
        return $this->evaluate($this->model);
    }

    public function getRecordLabel(): ?string
    {
        $label = $this->getCustomRecordLabel();

        if (filled($label)) {
            return $label;
        }

        $model = $this->getModel();

        if (! $model) {
            return null;
        }

        return get_model_label($model);
    }

    public function getCustomRecordLabel(): ?string
    {
        return $this->evaluate($this->recordLabel);
    }

    public function getPluralRecordLabel(): ?string
    {
        $label = $this->getCustomPluralRecordLabel();

        if (filled($label)) {
            return $label;
        }

        $singularLabel = $this->getRecordLabel();

        if (blank($singularLabel)) {
            return null;
        }

        if (locale_has_pluralization()) {
            return Str::plural($singularLabel);
        }

        return $singularLabel;
    }

    public function getCustomPluralRecordLabel(): ?string
    {
        return $this->evaluate($this->pluralRecordLabel);
    }

    /**
     * @param  array<mixed>  $arguments
     * @return array<mixed>
     */
    protected function parseAuthorizationArguments(array $arguments): array
    {
        if ($record = $this->getRecord()) {
            array_unshift($arguments, $record);
        }

        return $arguments;
    }
}
