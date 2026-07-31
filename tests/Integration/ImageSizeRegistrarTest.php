<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Integration;

use Webkinder\Sproutset\Images\ImageSizeConfigNormalizer;
use Webkinder\Sproutset\Images\ImageSizeRegistrar;

final class ImageSizeRegistrarTest extends IntegrationTestCase
{
    /** @var array<string, array{width: int, height: int, crop: bool}> */
    private array $originalSizes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalSizes = $GLOBALS['_wp_additional_image_sizes'] ?? [];
    }

    protected function tearDown(): void
    {
        $GLOBALS['_wp_additional_image_sizes'] = $this->originalSizes;
        parent::tearDown();
    }

    private function registrar(): ImageSizeRegistrar
    {
        return new ImageSizeRegistrar(new ImageSizeConfigNormalizer);
    }

    public function test_registers_configured_sizes_and_their_variants(): void
    {
        $this->registrar()->register([
            'sproutset_card' => ['width' => 600, 'height' => 400, 'crop' => true, 'srcset' => [2]],
        ]);

        $sizes = wp_get_registered_image_subsizes();

        $this->assertArrayHasKey('sproutset_card', $sizes);
        $this->assertSame(600, $sizes['sproutset_card']['width']);
        $this->assertSame(400, $sizes['sproutset_card']['height']);
        $this->assertTrue($sizes['sproutset_card']['crop']);

        $this->assertArrayHasKey('sproutset_card@2x', $sizes);
        $this->assertSame(1200, $sizes['sproutset_card@2x']['width']);
        $this->assertSame(800, $sizes['sproutset_card@2x']['height']);
    }

    public function test_strips_previously_registered_sizes(): void
    {
        add_image_size('sproutset_stray', 111, 222, false);
        $this->assertArrayHasKey('sproutset_stray', wp_get_registered_image_subsizes());

        $this->registrar()->register([
            'sproutset_keep' => ['width' => 300, 'height' => 300, 'crop' => false],
        ]);

        $sizes = wp_get_registered_image_subsizes();
        $this->assertArrayNotHasKey('sproutset_stray', $sizes);
        $this->assertArrayHasKey('sproutset_keep', $sizes);
    }
}
