<?php

namespace Webkinder\SproutsetPackage;

use Illuminate\Support\ServiceProvider;

class SproutsetServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('Sproutset', function () {
            return new Sproutset;
        });
    }

    public function boot()
    {
        $this->app->make('Sproutset');
    }
}
