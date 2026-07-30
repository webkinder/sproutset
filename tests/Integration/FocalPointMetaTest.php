<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Integration;

use Webkinder\Sproutset\Images\FocalPoint;
use Webkinder\Sproutset\Images\FocalPointMeta;

final class FocalPointMetaTest extends IntegrationTestCase
{
    public function test_reads_a_stored_focal_point(): void
    {
        $id = $this->seedAttachment();
        FocalPointMeta::write($id, 25.0, 75.0);

        $focal = FocalPointMeta::read($id);

        $this->assertInstanceOf(FocalPoint::class, $focal);
        $this->assertSame(25.0, $focal->x);
        $this->assertSame(75.0, $focal->y);
    }

    public function test_returns_null_when_no_focal_point_is_stored(): void
    {
        $id = $this->seedAttachment();

        $this->assertNull(FocalPointMeta::read($id));
    }

    public function test_returns_null_for_a_stored_center_point(): void
    {
        $id = $this->seedAttachment();
        FocalPointMeta::write($id, 50.0, 50.0);

        $this->assertNull(FocalPointMeta::read($id));
    }

    public function test_clamps_out_of_range_coordinates_on_write(): void
    {
        $id = $this->seedAttachment();
        FocalPointMeta::write($id, -10.0, 250.0);

        $focal = FocalPointMeta::read($id);

        $this->assertSame(0.0, $focal->x);
        $this->assertSame(100.0, $focal->y);
    }

    public function test_marks_and_reads_and_clears_applied_sizes(): void
    {
        $id = $this->seedAttachment();

        FocalPointMeta::markApplied($id, 'thumbnail', '25,75');
        $this->assertSame(['thumbnail' => '25,75'], FocalPointMeta::appliedAt($id));

        FocalPointMeta::clearApplied($id);
        $this->assertSame([], FocalPointMeta::appliedAt($id));
    }
}
