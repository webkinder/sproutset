<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

use Webkinder\Sproutset\Attachments\Attachment;
use Webkinder\Sproutset\Attachments\AttachmentRepository;

/**
 * WordPress-backed {@see ImageResolver}.
 *
 * Runs on the front-end boot path: it gathers render primitives from the
 * WordPress media API for the requested attachment and size, delegates pure
 * presentation logic to {@see ResponsiveSizes} and {@see FocalPointPosition},
 * and never fatals — a missing or broken attachment resolves to `null`.
 */
final class WpImageResolver implements ImageResolver
{
    public function __construct(private readonly AttachmentRepository $attachments) {}

    public function resolve(ImageRequest $request): ?ResolvedImage
    {
        $attachment = $this->attachments->find($request->attachmentId);

        if (! $attachment instanceof Attachment) {
            return null;
        }

        [$src, $width, $height] = $this->sizedSource($attachment, $request->sizeName);

        return new ResolvedImage(
            src: $src,
            srcset: null,
            sizes: null,
            width: $width,
            height: $height,
            alt: $this->alt($attachment->id),
            style: null,
            isSvg: false,
        );
    }

    /**
     * The URL and intrinsic dimensions for a named size, falling back to the
     * attachment's full-size identity when WordPress reports no sized source.
     *
     * @return array{0: string, 1: int, 2: int}
     */
    private function sizedSource(Attachment $attachment, string $sizeName): array
    {
        $source = wp_get_attachment_image_src($attachment->id, $sizeName);

        if ($source === false) {
            return [$attachment->url, $attachment->width, $attachment->height];
        }

        return [$source[0], $source[1], $source[2]];
    }

    private function alt(int $id): string
    {
        $alt = get_post_meta($id, '_wp_attachment_image_alt', true);

        return is_string($alt) ? $alt : '';
    }
}
