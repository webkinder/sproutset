<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Integration;

use Webkinder\Sproutset\Images\Avif\WpAvifSupport;

/**
 * Drift-lane coverage for the transient-backed verdict cache against a real
 * WordPress, where {@see get_transient()} exists and returns boolean false for
 * a missing transient — the ambiguity the fast lane cannot reach.
 */
final class WpAvifSupportTest extends IntegrationTestCase
{
    private const TRANSIENT_KEY = 'sproutset_avif_writable_v1';

    private function avifBytes(): string
    {
        $body = 'ftypavif'."\x00\x00\x00\x00";

        return pack('N', 4 + strlen($body)).$body;
    }

    public function test_an_absent_transient_re_runs_the_probe_instead_of_reading_false_as_a_verdict(): void
    {
        delete_transient(self::TRANSIENT_KEY);

        $support = new WpAvifSupport(fn (): string => $this->avifBytes());

        // A missing transient makes get_transient() return boolean false; the
        // support must treat that as "no verdict cached" and run the probe, not
        // as a cached unsupported verdict that permanently disables AVIF.
        $this->assertTrue($support->isSupported());
    }

    public function test_the_verdict_is_cached_and_reused_across_instances(): void
    {
        delete_transient(self::TRANSIENT_KEY);

        $firstCalls = 0;
        $first = new WpAvifSupport(function () use (&$firstCalls): string {
            $firstCalls++;

            return $this->avifBytes();
        });
        $this->assertTrue($first->isSupported());
        $this->assertSame(1, $firstCalls);

        $secondCalls = 0;
        $second = new WpAvifSupport(function () use (&$secondCalls): string {
            $secondCalls++;

            return 'not-avif';
        });
        $this->assertTrue($second->isSupported());
        $this->assertSame(0, $secondCalls, 'the cached verdict must be reused without re-probing');
    }
}
