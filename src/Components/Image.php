<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Components;

use Illuminate\View\Component;
use Webkinder\SproutsetPackage\Services\CronOptimizer;

final class Image extends Component
{
    public ?string $src = null;

    public ?string $srcset = null;

    private static array $cache = [];

    public function __construct(
        public int $id,
        public string $size = 'large',
        public ?string $sizes = null,
        public ?string $alt = null,
        public ?int $width = null,
        public ?int $height = null,
        public ?string $class = null,
        public bool $lazy = true,
        public string $decoding = 'async',
    ) {
        $cacheKey = "{$this->id}-{$this->size}-{$this->width}-{$this->height}";

        if (isset(self::$cache[$cacheKey])) {
            $this->loadFromCache(self::$cache[$cacheKey]);

            return;
        }

        $this->generateImageData();
        $this->sizes = $this->normalizeSizes();

        self::$cache[$cacheKey] = $this->getCacheableData();
    }

    public function render(): string
    {
        return <<<'blade'
            @if($src)
                <img
                    src="{{ $src }}"
                    @if($width) width="{{ $width }}" @endif
                    @if($height) height="{{ $height }}" @endif
                    @if($srcset) srcset="{{ $srcset }}" @endif
                    @if($sizes) sizes="{{ $sizes }}" @endif
                    @if($alt) alt="{{ $alt }}" @endif
                    @if($class) class="{{ $class }}" @endif
                    @if($lazy) loading="lazy" @endif
                    @if($decoding) decoding="{{ $decoding }}" @endif
                >
            @endif
        blade;
    }

    private function generateImageData(): void
    {
        $imageData = $this->initializeProperties();

        if (! $imageData) {
            return;
        }

        $this->setDimensions($imageData);
        $this->srcset = $this->generateSrcset();
    }

    private function initializeProperties(): ?array
    {
        $attachment = get_post($this->id);

        if (! $attachment || $attachment->post_type !== 'attachment') {
            return null;
        }

        if ($this->alt === null) {
            $this->alt = get_post_meta($this->id, '_wp_attachment_image_alt', true) ?: '';
        }

        $this->ensureImageSizeExists($this->size);

        $imageData = wp_get_attachment_image_src($this->id, $this->size);

        if (! $imageData) {
            return null;
        }

        $this->src = $imageData[0];

        return $imageData;
    }

    private function setDimensions(array $imageData): void
    {
        $fullImageData = wp_get_attachment_image_src($this->id, 'full');

        if (! $fullImageData) {
            $this->width = $imageData[1];
            $this->height = $imageData[2];

            return;
        }

        [$fullWidth, $fullHeight] = [$fullImageData[1], $fullImageData[2]];

        $sizeData = $this->getSizeData($this->size);
        $targetWidth = $sizeData['width'] ?? $imageData[1];
        $targetHeight = $sizeData['height'] ?? 0;
        $crop = $sizeData['crop'] ?? false;

        $calculated = $this->calculateAspectDimensions(
            $fullWidth,
            $fullHeight,
            $targetWidth,
            $targetHeight,
            $crop
        );

        $this->width ??= $calculated['width'];
        $this->height ??= $calculated['height'];

        $this->width = min($this->width, $fullWidth);
        $this->height = min($this->height, $fullHeight);
    }

    private function calculateAspectDimensions(
        int $fullWidth,
        int $fullHeight,
        int $targetWidth,
        int $targetHeight,
        bool $crop
    ): array {
        if ($fullWidth === 0) {
            return ['width' => 0, 'height' => 0];
        }

        $aspectRatio = $fullHeight / $fullWidth;

        if ($crop) {
            return [
                'width' => min($targetWidth, $fullWidth),
                'height' => $targetHeight > 0 ? min($targetHeight, $fullHeight) : $fullHeight,
            ];
        }

        $calculatedWidth = min($targetWidth, $fullWidth);

        if ($targetHeight === 0) {
            return [
                'width' => $calculatedWidth,
                'height' => (int) round($calculatedWidth * $aspectRatio),
            ];
        }

        $maxAllowedHeight = min($targetHeight, $fullHeight);
        $maxAllowedWidth = min($targetWidth, $fullWidth);

        if ($aspectRatio === 0.0) {
            return ['width' => $maxAllowedWidth, 'height' => 0];
        }

        $heightFromWidth = (int) round($maxAllowedWidth * $aspectRatio);
        $widthFromHeight = (int) round($maxAllowedHeight / $aspectRatio);

        return $heightFromWidth <= $maxAllowedHeight
            ? ['width' => $maxAllowedWidth, 'height' => $heightFromWidth]
            : ['width' => $widthFromHeight, 'height' => $maxAllowedHeight];
    }

    private function generateSrcset(): string
    {
        $imageSizes = config('sproutset-config.image_sizes', []);

        if (! isset($imageSizes[$this->size])) {
            return wp_get_attachment_image_srcset($this->id, $this->size) ?: '';
        }

        $sizeConfig = $imageSizes[$this->size];

        if (! isset($sizeConfig['srcset']) || ! is_array($sizeConfig['srcset'])) {
            return wp_get_attachment_image_srcset($this->id, $this->size) ?: '';
        }

        $srcsetParts = [];

        $baseImage = wp_get_attachment_image_src($this->id, $this->size);

        if ($baseImage) {
            $srcsetParts[$baseImage[0]] = $baseImage[0].' '.$baseImage[1].'w';
        }

        foreach ($sizeConfig['srcset'] as $multiplier) {
            $variantSize = "{$this->size}@{$multiplier}x";

            $this->ensureImageSizeExists($variantSize);

            $variantImage = wp_get_attachment_image_src($this->id, $variantSize);

            if ($variantImage) {
                $srcsetParts[$variantImage[0]] = $variantImage[0].' '.$variantImage[1].'w';
            }
        }

        return implode(', ', array_values($srcsetParts));
    }

    private function ensureImageSizeExists(string $sizeName): void
    {
        $metadata = wp_get_attachment_metadata($this->id);

        if (! $metadata || ! is_array($metadata)) {
            return;
        }

        if (isset($metadata['sizes'][$sizeName])) {
            return;
        }

        $this->generateImageSize($sizeName, $metadata);
    }

    private function generateImageSize(string $sizeName, array $metadata): void
    {
        $file = get_attached_file($this->id);

        if (! $file || ! file_exists($file)) {
            return;
        }

        if (! empty($metadata['original_image'])) {
            $pathinfo = pathinfo($file);
            $originalFile = $pathinfo['dirname'].'/'.$metadata['original_image'];

            if (file_exists($originalFile)) {
                $file = $originalFile;
            }
        }

        $sizeData = $this->getSizeData($sizeName);

        if ($sizeData === null || $sizeData === []) {
            return;
        }

        $originalWidth = $metadata['width'] ?? 0;
        $originalHeight = $metadata['height'] ?? 0;

        if ($originalWidth === 0 || $originalHeight === 0) {
            $imageInfo = getimagesize($file);

            if ($imageInfo) {
                [$originalWidth, $originalHeight] = $imageInfo;
            } else {
                return;
            }
        }

        $targetWidth = min($sizeData['width'], $originalWidth);
        $targetHeight = $sizeData['height'] > 0 ? min($sizeData['height'], $originalHeight) : $sizeData['height'];

        $resized = image_make_intermediate_size(
            $file,
            $targetWidth,
            $targetHeight,
            $sizeData['crop']
        );

        if (! $resized || is_wp_error($resized)) {
            return;
        }

        $metadata['sizes'][$sizeName] = $resized;

        wp_update_attachment_metadata($this->id, $metadata);

        if (config('sproutset-config.auto_optimize_images', false)) {
            $pathinfo = pathinfo($file);
            $generatedImagePath = $pathinfo['dirname'].'/'.$resized['file'];

            if (file_exists($generatedImagePath)) {
                CronOptimizer::scheduleImageOptimization($generatedImagePath, $this->id);
            }
        }
    }

    private function getSizeData(string $sizeName): ?array
    {
        global $_wp_additional_image_sizes;

        if (! isset($_wp_additional_image_sizes[$sizeName])) {
            return null;
        }

        return [
            'width' => $_wp_additional_image_sizes[$sizeName]['width'],
            'height' => $_wp_additional_image_sizes[$sizeName]['height'],
            'crop' => $_wp_additional_image_sizes[$sizeName]['crop'],
        ];
    }

    private function normalizeSizes(): string
    {
        if ($this->sizes && str_starts_with(mb_trim($this->sizes), 'auto')) {
            return $this->sizes;
        }

        $parts = ['auto'];

        $parts[] = in_array($this->sizes, [null, '', '0'], true) ? $this->generateDefaultSizes() : mb_trim($this->sizes);

        return implode(', ', $parts);
    }

    private function generateDefaultSizes(): string
    {
        $sizeData = $this->getSizeData($this->size);

        if ($sizeData === null || $sizeData === [] || $sizeData['width'] === 0) {
            return '100vw';
        }

        $width = $sizeData['width'];

        return "(max-width: {$width}px) 100vw, {$width}px";
    }

    private function loadFromCache(array $data): void
    {
        $this->src = $data['src'];
        $this->srcset = $data['srcset'];
        $this->width = $data['width'];
        $this->height = $data['height'];
        $this->alt = $data['alt'];
        $this->sizes = $data['sizes'];
    }

    private function getCacheableData(): array
    {
        return [
            'src' => $this->src,
            'srcset' => $this->srcset,
            'width' => $this->width,
            'height' => $this->height,
            'alt' => $this->alt,
            'sizes' => $this->sizes,
        ];
    }
}
