<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use Webkinder\Sproutset\SproutsetServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            SproutsetServiceProvider::class,
        ];
    }
}
