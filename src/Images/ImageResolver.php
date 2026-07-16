<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

/**
 * Boundary between the image component and image resolution.
 *
 * The component renders whatever a resolver returns. The WordPress-backed
 * implementation (dimensions, srcset, focal point, on-demand generation) lives
 * behind this contract and is added in a later step; tests bind a fake.
 */
interface ImageResolver
{
    /**
     * Resolve a request into a renderable view-model, or null to render nothing.
     */
    public function resolve(ImageRequest $request): ?ResolvedImage;
}
