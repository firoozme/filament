<?php

namespace Filament;

use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class SpatieLaravelTranslatablePluginServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/filament-spatie-laravel-translatable-plugin.php' => config_path('filament-spatie-laravel-translatable-plugin.php'),
            ], 'filament-spatie-laravel-translatable-plugin-config');

            $this->publishes([
                __DIR__ . '/../resources/lang' => resource_path('lang/vendor/filament-spatie-laravel-translatable-plugin-translations'),
            ], 'filament-spatie-laravel-translatable-plugin-translations');
        }

        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'filament-spatie-laravel-translatable-plugin');
    }
}
