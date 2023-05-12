<?php

namespace Filament\Tables\Filters;

use Closure;
use Filament\Forms\Components\Select;
use Filament\Tables\DataProviders\DataProvider;

class TernaryFilter extends SelectFilter
{
    protected string | Closure | null $trueLabel = null;

    protected string | Closure | null $falseLabel = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trueLabel(__('filament-forms::components.select.boolean.true'));
        $this->falseLabel(__('filament-forms::components.select.boolean.false'));
        $this->placeholder('-');

        $this->boolean();

        $this->indicateUsing(function (array $state): array {
            if (blank($state['value'] ?? null)) {
                return [];
            }

            $stateLabel = $state['value'] ?
                $this->getTrueLabel() :
                $this->getFalseLabel();

            return ["{$this->getIndicator()}: {$stateLabel}"];
        });
    }

    public function trueLabel(string | Closure | null $trueLabel): static
    {
        $this->trueLabel = $trueLabel;

        return $this;
    }

    public function falseLabel(string | Closure | null $falseLabel): static
    {
        $this->falseLabel = $falseLabel;

        return $this;
    }

    public function getTrueLabel(): ?string
    {
        return $this->trueLabel;
    }

    public function getFalseLabel(): ?string
    {
        return $this->falseLabel;
    }

    public function getFormField(): Select
    {
        return parent::getFormField()
            ->boolean(
                trueLabel: $this->getTrueLabel(),
                falseLabel: $this->getFalseLabel(),
                placeholder: $this->getPlaceholder(),
            );
    }

    public function nullable(): static
    {
        $this->queries(
            true: fn (DataProvider $data) => $data->whereNotNull($this->getAttribute()),
            false: fn (DataProvider $data) => $data->whereNull($this->getAttribute()),
        );

        return $this;
    }

    public function boolean(): static
    {
        $this->queries(
            true: fn (DataProvider $data) => $data->where($this->getAttribute(), true),
            false: fn (DataProvider $data) => $data->where($this->getAttribute(), false),
        );

        return $this;
    }

    public function queries(Closure $true, Closure $false, Closure $blank = null): static
    {
        $this->query(function (DataProvider $dataProvider, array $state) use ($blank, $false, $true) {
            if (blank($state['value'] ?? null)) {
                if ($blank instanceof Closure) {
                    $this->modifyData($blank, $dataProvider, $state);
                }

                return;
            }

            if ($state['value']) {
                $this->modifyData($true, $dataProvider, $state);

                return;
            }

            $this->modifyData($false, $dataProvider, $state);
        });

        return $this;
    }
}
