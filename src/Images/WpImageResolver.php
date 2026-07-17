<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

use Webkinder\Sproutset\Attachments\Attachment;
use Webkinder\Sproutset\Attachments\AttachmentRepository;

final readonly class WpImageResolver implements ImageResolver
{
    private const string SVG_MIME = 'image/svg+xml';

    public function __construct(
        private AttachmentRepository $attachments,
        private OnDemandSizeGenerator $sizeGenerator,
    ) {}

    public function resolve(ImageRequest $request): ?ResolvedImage
    {
        $mime = get_post_mime_type($request->attachmentId);

        if ($mime === false) {
            return null;
        }

        if ($mime === self::SVG_MIME) {
            return $this->resolveSvg($request);
        }

        return $this->resolveRaster($request);
    }

    private function resolveSvg(ImageRequest $request): ?ResolvedImage
    {
        $url = wp_get_attachment_url($request->attachmentId);

        if ($url === false) {
            return null;
        }

        return new ResolvedImage(
            src: $url,
            srcset: null,
            sizes: null,
            width: null,
            height: null,
            alt: $this->alt($request->attachmentId),
            style: FocalPointPosition::forRequest($request),
            isSvg: true,
        );
    }

    private function resolveRaster(ImageRequest $request): ?ResolvedImage
    {
        $attachment = $this->attachments->find($request->attachmentId);

        if (! $attachment instanceof Attachment) {
            return null;
        }

        $this->sizeGenerator->ensure($attachment->id, $request->sizeName);

        [$src, $width, $height] = $this->sizedSource($attachment, $request->sizeName);

        return new ResolvedImage(
            src: $src,
            srcset: $this->srcset($attachment->id, $request->sizeName),
            sizes: ResponsiveSizes::forRequest($request),
            width: $width,
            height: $height,
            alt: $this->alt($attachment->id),
            style: FocalPointPosition::forRequest($request),
            isSvg: false,
        );
    }

    private function srcset(int $id, string $sizeName): ?string
    {
        $srcset = wp_get_attachment_image_srcset($id, $sizeName);

        return $srcset === false ? null : $srcset;
    }

    /**
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
