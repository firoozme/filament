<?php

namespace Filament\Tables\Columns\Concerns;

use Closure;
use Filament\Support\Color;
use Filament\Support\Contracts\HasColor as ColorInterface;
use Filament\Tables\Columns\Column;

trait HasColor
{
    protected string | bool | Closure | array | null $color = null;

    public function color(string | bool | Closure | array | null $color): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @param  array<mixed> | Closure  $colors
     */
    public function colors(array | Closure $colors): static
    {
        $this->color(function (Column $column, $state) use ($colors) {
            $colors = $column->evaluate($colors);

            $color = null;

            foreach ($colors as $conditionalColor => $condition) {
                if (is_numeric($conditionalColor)) {
                    $color = $condition;
                } elseif ($condition instanceof Closure && $column->evaluate($condition)) {
                    $color = $conditionalColor;
                } elseif ($condition === $state) {
                    $color = $conditionalColor;
                }
            }

            return $color;
        });

        return $this;
    }

    public function getColor(mixed $state): string | array | null
    {
        $color = $this->evaluate($this->color, [
            'state' => $state,
        ]);

        if ($color === false) {
            return null;
        }

        if (filled($color)) {
            if ($color instanceof Color) {
                return $color->asCustomColors();
            }

            return $color;
        }

        if (! $state instanceof ColorInterface) {
            return null;
        }

        return $state->getColor();
    }
}
