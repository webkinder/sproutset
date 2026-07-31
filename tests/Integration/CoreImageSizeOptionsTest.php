<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Integration;

use Webkinder\Sproutset\Images\CoreImageSizeOptions;
use Webkinder\Sproutset\Images\ImageSizeConfigNormalizer;

final class CoreImageSizeOptionsTest extends IntegrationTestCase
{
    private function coreImageSizeOptions(): CoreImageSizeOptions
    {
        return new CoreImageSizeOptions(new ImageSizeConfigNormalizer);
    }

    public function test_overrides_get_option_for_a_managed_core_size(): void
    {
        update_option('medium_size_w', 999);

        $this->coreImageSizeOptions()->register([
            'medium' => ['width' => 400, 'height' => 400, 'crop' => false],
        ]);

        $this->assertSame(400, (int) get_option('medium_size_w'));

        remove_all_filters('pre_option_medium_size_w');
        $this->assertSame(999, (int) get_option('medium_size_w'));
    }

    public function test_leaves_an_unmanaged_core_size_at_the_wordpress_value(): void
    {
        update_option('large_size_w', 1234);

        $this->coreImageSizeOptions()->register([
            'medium' => ['width' => 400, 'height' => 400, 'crop' => false],
        ]);

        $this->assertSame(1234, (int) get_option('large_size_w'));
    }
}
