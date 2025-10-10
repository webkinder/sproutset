<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage;

use Illuminate\Support\ServiceProvider;

final class SproutsetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        dump('SproutsetServiceProvider boot');
        $this->app->singleton('Sproutset', fn (): Sproutset => new Sproutset);
    }

    public function boot(): void
    {
        $this->app->make('Sproutset');
    }
}
