<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Integration;

use Webkinder\Sproutset\Admin\FocalPointMediaField;
use Webkinder\Sproutset\Images\FocalPointMeta;

final class FocalPointMediaFieldTest extends IntegrationTestCase
{
    public function test_saves_and_clamps_the_focal_point_from_the_form(): void
    {
        $id = $this->seedAttachment();

        (new FocalPointMediaField)->saveField(
            ['ID' => $id],
            ['sproutset_focal_x' => '250', 'sproutset_focal_y' => '-5'],
        );

        $focal = FocalPointMeta::read($id);
        $this->assertSame(100.0, $focal->x);
        $this->assertSame(0.0, $focal->y);
    }

    public function test_clears_the_applied_marker_when_the_focal_point_is_saved(): void
    {
        $id = $this->seedAttachment();
        FocalPointMeta::markApplied($id, 'thumbnail', '50,50');

        (new FocalPointMediaField)->saveField(
            ['ID' => $id],
            ['sproutset_focal_x' => '25', 'sproutset_focal_y' => '75'],
        );

        $this->assertSame([], FocalPointMeta::appliedAt($id));
    }

    public function test_injects_a_focal_point_field_with_the_current_values(): void
    {
        $id = $this->seedAttachment();
        FocalPointMeta::write($id, 25.0, 75.0);
        $attachment = get_post($id);

        $fields = (new FocalPointMediaField)->addField([], $attachment);

        $this->assertArrayHasKey('sproutset_focal_point', $fields);
        $html = $fields['sproutset_focal_point']['html'];
        $this->assertStringContainsString('sproutset_focal_x', $html);
        $this->assertStringContainsString('sproutset_focal_y', $html);
        $this->assertStringContainsString('value="25"', $html);
        $this->assertStringContainsString('value="75"', $html);
    }

    public function test_uses_the_real_attachment_id_in_the_input_names(): void
    {
        $id = $this->seedAttachment();
        $attachment = get_post($id);

        $html = (new FocalPointMediaField)->addField([], $attachment)['sproutset_focal_point']['html'];

        $this->assertStringContainsString('name="attachments['.$id.'][sproutset_focal_x]"', $html);
        $this->assertStringContainsString('name="attachments['.$id.'][sproutset_focal_y]"', $html);
        $this->assertStringNotContainsString('{{ID}}', $html);
        $this->assertStringContainsString('draggable="false"', $html);
    }

    public function test_does_not_offer_the_picker_for_an_svg_attachment(): void
    {
        $id = $this->seedAttachment('example.svg');
        $attachment = get_post($id);

        $fields = (new FocalPointMediaField)->addField([], $attachment);

        $this->assertArrayNotHasKey('sproutset_focal_point', $fields);
    }
}
