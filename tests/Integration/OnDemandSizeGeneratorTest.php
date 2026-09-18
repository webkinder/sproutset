<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Integration;

use Webkinder\Sproutset\Images\OnDemandSizeGenerator;

final class OnDemandSizeGeneratorTest extends IntegrationTestCase
{
    public function test_ensure_family_generates_base_and_variants(): void
    {
        $id = $this->seedAttachment();

        add_image_size('sproutset_fam', 300, 200, true);
        add_image_size('sproutset_fam@1.5x', 450, 300, true);
        add_image_size('sproutset_fam@2x', 600, 400, true);

        $before = wp_get_attachment_metadata($id)['sizes'] ?? [];
        $this->assertArrayNotHasKey('sproutset_fam', $before);
        $this->assertArrayNotHasKey('sproutset_fam@2x', $before);

        (new OnDemandSizeGenerator)->ensureFamily($id, 'sproutset_fam');

        $after = wp_get_attachment_metadata($id)['sizes'] ?? [];
        $this->assertArrayHasKey('sproutset_fam', $after);
        $this->assertArrayHasKey('sproutset_fam@1.5x', $after);
        $this->assertArrayHasKey('sproutset_fam@2x', $after);
    }

    public function test_ensure_family_leaves_unrelated_sizes_alone(): void
    {
        $id = $this->seedAttachment();

        add_image_size('sproutset_fam', 300, 200, true);
        add_image_size('sproutset_other', 320, 240, true);

        (new OnDemandSizeGenerator)->ensureFamily($id, 'sproutset_fam');

        $after = wp_get_attachment_metadata($id)['sizes'] ?? [];
        $this->assertArrayHasKey('sproutset_fam', $after);
        $this->assertArrayNotHasKey('sproutset_other', $after);
    }
}
