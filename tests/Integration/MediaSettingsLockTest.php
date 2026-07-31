<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Integration;

use Webkinder\Sproutset\Images\MediaSettingsLock;

final class MediaSettingsLockTest extends IntegrationTestCase
{
    public function test_disables_the_managed_media_settings_fields(): void
    {
        (new MediaSettingsLock)->register([
            'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
            'medium' => ['width' => 400, 'height' => 400, 'crop' => false],
        ]);

        ob_start();
        do_action('admin_footer-options-media.php');
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('thumbnail_size_w', $html);
        $this->assertStringContainsString('medium_size_w', $html);
        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('managed by Sproutset configuration', $html);
        $this->assertStringNotContainsString('large_size_w', $html);
    }
}
