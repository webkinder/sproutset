<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Integration;

/**
 * Proves the drift lane boots: real WordPress loads and can seed an image.
 */
final class SmokeTest extends IntegrationTestCase
{
    public function test_wordpress_boots_and_seeds_an_attachment(): void
    {
        $id = $this->seedAttachment();

        $this->assertIsInt($id);
        $this->assertTrue(wp_attachment_is_image($id));
    }
}
