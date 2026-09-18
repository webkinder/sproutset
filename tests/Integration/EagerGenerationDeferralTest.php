<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Integration;

use Webkinder\Sproutset\Images\EagerGenerationDeferral;
use Webkinder\Sproutset\Images\ImageSizeConfigNormalizer;
use Webkinder\Sproutset\Images\ImageSizeRegistrar;

final class EagerGenerationDeferralTest extends IntegrationTestCase
{
    /** @var array<string, array{width: int, height: int, crop: bool}> */
    private array $originalSizes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalSizes = $GLOBALS['_wp_additional_image_sizes'] ?? [];
        remove_all_filters('intermediate_image_sizes_advanced');
    }

    protected function tearDown(): void
    {
        remove_all_filters('intermediate_image_sizes_advanced');
        $GLOBALS['_wp_additional_image_sizes'] = $this->originalSizes;
        parent::tearDown();
    }

    /**
     * @return array<string, array{width: int, height: int, crop: bool, srcset?: list<float>}>
     */
    private function roster(): array
    {
        return [
            'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
            'medium' => ['width' => 400, 'height' => 400, 'crop' => false],
            'medium_large' => ['width' => 768, 'height' => 0, 'crop' => false, 'srcset' => [2]],
            'large' => ['width' => 1024, 'height' => 1024, 'crop' => false],
            'hero' => ['width' => 1312, 'height' => 500, 'crop' => false, 'srcset' => [1.5]],
        ];
    }

    /**
     * @return array<string, array{width: int, height: int, crop: bool}>
     */
    private function eagerSet(): array
    {
        return [
            'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
            'medium' => ['width' => 400, 'height' => 400, 'crop' => false],
            'medium_large' => ['width' => 768, 'height' => 0, 'crop' => false],
            'medium_large@2x' => ['width' => 1536, 'height' => 0, 'crop' => false],
            'large' => ['width' => 1024, 'height' => 1024, 'crop' => false],
            'hero' => ['width' => 1312, 'height' => 500, 'crop' => false],
            'hero@1.5x' => ['width' => 1968, 'height' => 750, 'crop' => false],
            'woocommerce_thumbnail' => ['width' => 300, 'height' => 300, 'crop' => true],
        ];
    }

    private function deferral(): EagerGenerationDeferral
    {
        return new EagerGenerationDeferral(new ImageSizeConfigNormalizer);
    }

    public function test_enabled_defers_variant_sizes_from_eager_generation(): void
    {
        $this->deferral()->register(true, $this->roster());

        $this->assertNotFalse(has_filter('intermediate_image_sizes_advanced'));

        $eager = apply_filters('intermediate_image_sizes_advanced', $this->eagerSet(), [], 0);

        $this->assertSame([
            'thumbnail',
            'medium',
            'medium_large',
            'large',
            'hero',
            'woocommerce_thumbnail',
        ], array_keys($eager));
    }

    public function test_disabled_registers_no_filter(): void
    {
        $this->deferral()->register(false, $this->roster());

        $this->assertFalse(has_filter('intermediate_image_sizes_advanced'));
    }

    public function test_enabled_registers_no_filter_when_roster_has_no_variants(): void
    {
        $this->deferral()->register(true, [
            'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
            'large' => ['width' => 1024, 'height' => 1024, 'crop' => false],
        ]);

        $this->assertFalse(has_filter('intermediate_image_sizes_advanced'));
    }

    public function test_deferred_variants_remain_registered_for_on_demand(): void
    {
        (new ImageSizeRegistrar(new ImageSizeConfigNormalizer))->register($this->roster());
        $this->deferral()->register(true, $this->roster());

        $registered = wp_get_registered_image_subsizes();
        $this->assertArrayHasKey('hero', $registered);
        $this->assertArrayHasKey('hero@1.5x', $registered);

        $eager = apply_filters('intermediate_image_sizes_advanced', $this->eagerSet(), [], 0);
        $this->assertArrayHasKey('hero', $eager);
        $this->assertArrayNotHasKey('hero@1.5x', $eager);
    }
}
