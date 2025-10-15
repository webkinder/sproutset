<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Webkinder\SproutsetPackage\Components\Image;

final class SproutsetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('Sproutset', fn (): Sproutset => new Sproutset());

        $this->mergeConfigFrom(
            __DIR__.'/../config/sproutset-image-sizes.php',
            'sproutset-image-sizes'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/sproutset-image-sizes.php' => config_path('sproutset-image-sizes.php'),
        ], 'sproutset-image-sizes');

        $this->app->make('Sproutset');

        Blade::component('sproutset-image', Image::class);
    }
}
