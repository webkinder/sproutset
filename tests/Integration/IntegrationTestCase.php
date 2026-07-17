<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Integration;

use WP_UnitTestCase;

/**
 * Base case for the WordPress drift lane.
 *
 * Extends WordPress' own {@see WP_UnitTestCase}, so every test runs inside a
 * rolled-back database transaction against a real WordPress. Provides a helper
 * to seed a real attachment from the fixtures directory.
 */
abstract class IntegrationTestCase extends WP_UnitTestCase
{
    /**
     * Insert a real attachment from a fixture file and return its ID.
     */
    protected function seedAttachment(string $fixture = 'example.jpg'): int
    {
        return self::factory()->attachment->create_upload_object(__DIR__.'/fixtures/'.$fixture);
    }
}
