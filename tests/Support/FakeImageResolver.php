<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Support;

use Webkinder\Sproutset\Images\ImageRequest;
use Webkinder\Sproutset\Images\ImageResolver;
use Webkinder\Sproutset\Images\ResolvedImage;

/**
 * Returns a preset {@see ResolvedImage} (or null) for any request, so the
 * component's rendering can be exercised with no WordPress runtime.
 */
final readonly class FakeImageResolver implements ImageResolver
{
    public function __construct(private ?ResolvedImage $resolved) {}

    public function resolve(ImageRequest $request): ?ResolvedImage
    {
        return $this->resolved;
    }
}
