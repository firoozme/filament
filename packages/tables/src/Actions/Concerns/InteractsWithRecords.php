<?php

namespace Filament\Tables\Actions\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Collection;

trait InteractsWithRecords
{
    protected string | Closure | null $recordLabel = null;

    protected string | Closure | null $pluralRecordLabel = null;

    protected Collection | Closure | null $records = null;

    public function records(Collection | Closure | null $records): static
    {
        $this->records = $records;

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

    public function getModel(): string
    {
        return $this->getTable()->getModel();
    }

    public function getRecordLabel(): string
    {
        $label = $this->evaluate($this->recordLabel);

        if (filled($label)) {
            return $label;
        }

        return $this->getTable()->getRecordLabel();
    }

    public function getPluralRecordLabel(): string
    {
        $label = $this->evaluate($this->pluralRecordLabel);

        if (filled($label)) {
            return $label;
        }

        return $this->getTable()->getPluralRecordLabel();
    }

    public function getRecords(): ?Collection
    {
        return $this->evaluate($this->records);
    }
}
