<?php

declare(strict_types=1);
use Webkinder\Sproutset\Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests boot a Laravel/Acorn container via Testbench and register the
| package's service provider through the base TestCase. Unit tests stay plain.
|
*/

pest()->extend(TestCase::class)->in('Feature');
