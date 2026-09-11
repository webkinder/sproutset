<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Integration;

use Webkinder\Sproutset\Images\ImageSizeConfigNormalizer;
use Webkinder\Sproutset\Images\SrcsetWidthLimit;

final class SrcsetWidthLimitTest extends IntegrationTestCase
{
    private function limit(): SrcsetWidthLimit
    {
        return new SrcsetWidthLimit(new ImageSizeConfigNormalizer);
    }

    public function test_raises_the_srcset_ceiling_to_the_largest_configured_width(): void
    {
        $this->limit()->register([
            'large' => ['width' => 1024, 'height' => 1024, 'crop' => false, 'srcset' => [2, 3]],
        ]);

        $this->assertSame(3072, apply_filters('max_srcset_image_width', 2048, [1024, 1024]));
    }

    public function test_never_lowers_a_ceiling_another_plugin_raised_higher(): void
    {
        $this->limit()->register([
            'large' => ['width' => 1024, 'height' => 1024, 'crop' => false, 'srcset' => [3]],
        ]);

        $this->assertSame(4000, apply_filters('max_srcset_image_width', 4000, [1024, 1024]));
    }

    public function test_leaves_the_default_untouched_when_no_width_exceeds_it(): void
    {
        $this->limit()->register([
            'large' => ['width' => 1024, 'height' => 1024, 'crop' => false, 'srcset' => [2]],
        ]);

        $this->assertSame(2048, apply_filters('max_srcset_image_width', 2048, [1024, 1024]));
    }
}
