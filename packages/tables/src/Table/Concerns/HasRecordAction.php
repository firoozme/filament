<?php

namespace Filament\Tables\Table\Concerns;

use Closure;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;

trait HasRecordAction
{
    protected string | Closure | null $recordAction = null;

    public function recordAction(string | Closure | null $action): static
    {
        $this->recordAction = $action;

        return $this;
    }

    public function getRecordAction(mixed $record): ?string
    {
        $action = $this->evaluate(
            $this->recordAction,
            namedInjections: [
                'record' => $record,
            ],
            typedInjections: [
                Model::class => $record,
                is_object($record) ? $record::class : null => $record,
            ],
        );

        if (! class_exists($action)) {
            return $action;
        }

        if (! is_subclass_of($action, Action::class)) {
            return $action;
        }

        return $action::getDefaultName() ?? $action;
    }
}
