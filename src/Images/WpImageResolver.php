<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

use Webkinder\Sproutset\Attachments\Attachment;
use Webkinder\Sproutset\Attachments\AttachmentRepository;
use Webkinder\Sproutset\Images\Avif\AvifConfig;
use Webkinder\Sproutset\Images\Avif\AvifSrcsetBuilder;
use Webkinder\Sproutset\Images\Avif\AvifSupport;
use Webkinder\Sproutset\Images\Avif\AvifVariantGenerator;

final readonly class WpImageResolver implements ImageResolver
{
    private const string SVG_MIME = 'image/svg+xml';

    public function __construct(
        private AttachmentRepository $attachments,
        private OnDemandSizeGenerator $sizeGenerator,
        private AvifSupport $avifSupport,
        private AvifVariantGenerator $avifGenerator,
        private AvifConfig $avifConfig,
        private FocalPointCropper $cropper,
        private FocalPointConfig $focalConfig,
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
            alt: $this->alt($request),
            style: FocalPointPosition::forCover($this->cssFocal($request), $request->focalPoint),
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

        if ($this->focalConfig->enabled) {
            $metaFocal = FocalPointMeta::read($attachment->id);

            if ($metaFocal instanceof FocalPoint) {
                $this->cropper->ensureForAttachment($attachment->id, $metaFocal);
            }
        }

        [$src, $width, $height] = $this->sizedSource($attachment, $request->sizeName);

        $box = $this->presentedBox($request->sizeName, $attachment);
        $cover = false;

        if ($box !== null) {
            $width = $box['width'];
            $height = $box['height'];
            $cover = $box['cover'];
        }

        $srcset = $this->srcset($attachment->id, $request->sizeName);

        return new ResolvedImage(
            src: $src,
            srcset: $srcset,
            sizes: ResponsiveSizes::forRequest($request),
            width: $width,
            height: $height,
            alt: $this->alt($request),
            style: FocalPointPosition::forCover($this->cssFocal($request), $request->focalPoint || $cover),
            isSvg: false,
            avifSrcset: $this->avifSrcset($attachment->id, $src, $srcset),
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

    /**
     * @return array{width: int, height: int, cover: bool}|null
     */
    private function presentedBox(string $sizeName, Attachment $attachment): ?array
    {
        $sizes = wp_get_registered_image_subsizes();
        $spec = $sizes[$sizeName] ?? null;

        if (! is_array($spec)) {
            return null;
        }

        $targetWidth = is_numeric($spec['width'] ?? null) ? (int) $spec['width'] : 0;
        $targetHeight = is_numeric($spec['height'] ?? null) ? (int) $spec['height'] : 0;
        $crop = (bool) ($spec['crop'] ?? false);

        return PresentedDimensions::forSource(
            $targetWidth,
            $targetHeight,
            $crop,
            $attachment->width,
            $attachment->height,
        );
    }

    private function cssFocal(ImageRequest $request): ?FocalPoint
    {
        if ($request->focalPoint && $request->focalPointX !== null && $request->focalPointY !== null) {
            return new FocalPoint($request->focalPointX, $request->focalPointY);
        }

        if ($this->focalConfig->enabled) {
            return FocalPointMeta::read($request->attachmentId);
        }

        return null;
    }

    private function alt(ImageRequest $request): string
    {
        if ($request->alt !== null) {
            return $request->alt;
        }

        $alt = get_post_meta($request->attachmentId, '_wp_attachment_image_alt', true);

        return is_string($alt) ? $alt : '';
    }

    private function avifSrcset(int $attachmentId, string $src, ?string $originalSrcset): ?string
    {
        if (! $this->avifConfig->enabled) {
            return null;
        }

        if (! $this->avifSupport->isSupported()) {
            return null;
        }

        return AvifSrcsetBuilder::build(
            $originalSrcset ?? $src,
            fn (string $url): ?string => $this->avifSiblingUrl($attachmentId, $url),
        );
    }

    private function avifSiblingUrl(int $attachmentId, string $candidateUrl): ?string
    {
        $file = $this->urlToPath($candidateUrl);

        if ($file === null) {
            return null;
        }

        $avifFile = $this->avifGenerator->ensure($attachmentId, $file);

        return $avifFile === null ? null : $this->pathToUrl($avifFile);
    }

    private function urlToPath(string $url): ?string
    {
        $uploads = wp_get_upload_dir();
        $baseUrl = is_string($uploads['baseurl'] ?? null) ? $uploads['baseurl'] : '';
        $baseDir = is_string($uploads['basedir'] ?? null) ? $uploads['basedir'] : '';

        if ($baseUrl === '' || ! str_starts_with($url, $baseUrl)) {
            return null;
        }

        return $baseDir.substr($url, strlen($baseUrl));
    }

    private function pathToUrl(string $path): ?string
    {
        $uploads = wp_get_upload_dir();
        $baseDir = is_string($uploads['basedir'] ?? null) ? $uploads['basedir'] : '';
        $baseUrl = is_string($uploads['baseurl'] ?? null) ? $uploads['baseurl'] : '';

        if ($baseDir === '' || ! str_starts_with($path, $baseDir)) {
            return null;
        }

        return $baseUrl.substr($path, strlen($baseDir));
    }
}
