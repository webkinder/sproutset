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
     *
     * SVGs are inserted directly: WordPress rejects them from the upload
     * pipeline by default, so the raster factory helper cannot seed them.
     */
    protected function seedAttachment(string $fixture = 'example.jpg'): int
    {
        $file = __DIR__.'/fixtures/'.$fixture;

        if (str_ends_with($fixture, '.svg')) {
            return $this->seedSvgAttachment($file);
        }

        return self::factory()->attachment->create_upload_object($file);
    }

    private function seedSvgAttachment(string $file): int
    {
        $upload = wp_upload_dir();
        $destination = $upload['path'].'/'.basename($file);
        copy($file, $destination);

        return wp_insert_attachment([
            'post_mime_type' => 'image/svg+xml',
            'post_title' => 'example',
            'post_status' => 'inherit',
            'guid' => $upload['url'].'/'.basename($file),
        ], $destination);
    }
}
