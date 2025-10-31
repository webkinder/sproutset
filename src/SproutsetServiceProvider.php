<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Webkinder\SproutsetPackage\Components\Image;
use Webkinder\SproutsetPackage\Console\Optimize;

final class SproutsetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Sproutset::class, fn (): Sproutset => new Sproutset());

        $this->mergeConfigFrom(
            __DIR__.'/../config/sproutset-config.php',
            'sproutset-config'
        );
    }

    public function boot(): void
    {
        $this->publishConfiguration();
        $this->initializeSproutset();
        $this->registerBladeComponents();
        $this->registerConsoleCommands();
    }

    private function publishConfiguration(): void
    {
        $this->publishes([
            __DIR__.'/../config/sproutset-config.php' => config_path('sproutset-config.php'),
        ], 'sproutset-config');
    }

    private function initializeSproutset(): void
    {
        $this->app->make(Sproutset::class);
    }

    private function registerBladeComponents(): void
    {
        Blade::component('sproutset-image', Image::class);
    }

    private function registerConsoleCommands(): void
    {
        $this->commands([
            Optimize::class,
        ]);
    }
}
