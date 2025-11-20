<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Components;

use Illuminate\View\Component;
use Webkinder\SproutsetPackage\Services\CronOptimizer;
use Webkinder\SproutsetPackage\Support\ImageSizeConfigNormalizer;

final class Image extends Component
{
    public ?string $sourcePath = null;

    public ?string $responsiveSourceSet = null;

    public ?string $inlineStyle = null;

    private static array $cache = [];

    private bool $isSvg = false;

    public function __construct(
        public readonly int $id,
        public readonly string $sizeName = 'large',
        public ?string $sizes = null,
        public ?string $alt = null,
        public ?int $width = null,
        public ?int $height = null,
        public readonly ?string $class = null,
        public readonly bool $useLazyLoading = true,
        public readonly string $decodingMode = 'async',
    ) {
        $cacheKey = $this->generateCacheKey();

        if (isset(self::$cache[$cacheKey])) {
            $this->loadDataFromCache(self::$cache[$cacheKey]);

            return;
        }

        $this->isSvg = $this->isSvgAttachment();

        $this->initializeImageData();

        if ($this->sizes === null || ! str_starts_with($this->sizes, 'auto')) {
            $this->sizes = $this->normalizeResponsiveSizesAttribute();
        }

        self::$cache[$cacheKey] = $this->getCacheableData();
    }

    public function render(): string
    {
        return $this->isSvg ? <<<'blade'
            @if($sourcePath)
                <img
                    src="{{ $sourcePath }}"
                    @if($alt) alt="{{ $alt }}" @endif
                    @if($class) class="{{ $class }}" @endif
                    @if($inlineStyle) style="{{ $inlineStyle }}" @endif
                >
            @endif
        blade
        : <<<'blade'
            @if($sourcePath)
                <img
                    src="{{ $sourcePath }}"
                    @if($width) width="{{ $width }}" @endif
                    @if($height) height="{{ $height }}" @endif
                    @if($responsiveSourceSet) srcset="{{ $responsiveSourceSet }}" @endif
                    @if($sizes) sizes="{{ $sizes }}" @endif
                    @if($alt) alt="{{ $alt }}" @endif
                    @if($class) class="{{ $class }}" @endif
                    @if($inlineStyle) style="{{ $inlineStyle }}" @endif
                    @if($useLazyLoading) loading="lazy" @endif
                    @if($decodingMode) decoding="{{ $decodingMode }}" @endif
                >
            @endif
        blade;
    }

    private function initializeImageData(): void
    {
        if (! $this->isValidAttachment()) {
            return;
        }

        $this->loadAlternativeTextIfNeeded();

        if ($this->isSvg) {
            $this->sourcePath = wp_get_attachment_url($this->id);

            return;
        }

        $this->ensureRequestedSizeIsAvailable();
        $this->loadImageDimensions();
        $this->responsiveSourceSet = $this->buildResponsiveSourceSet();
    }

    private function isValidAttachment(): bool
    {
        $attachment = get_post($this->id);

        return $attachment && $attachment->post_type === 'attachment';
    }

    private function loadAlternativeTextIfNeeded(): void
    {
        if ($this->alt !== null) {
            return;
        }

        $this->alt = get_post_meta($this->id, '_wp_attachment_image_alt', true) ?: '';
    }

    private function ensureRequestedSizeIsAvailable(): void
    {
        $metadata = wp_get_attachment_metadata($this->id);

        if (! $metadata || ! is_array($metadata)) {
            return;
        }

        if (isset($metadata['sizes'][$this->sizeName])) {
            return;
        }

        $this->generateMissingImageSize($metadata);
    }

    private function loadImageDimensions(): void
    {
        $imageData = $this->normalizeImageSourceData(wp_get_attachment_image_src($this->id, $this->sizeName));

        if ($imageData === null) {
            return;
        }

        $this->sourcePath = $imageData[0];

        $this->calculateAndSetDimensions($imageData);
    }

    private function calculateAndSetDimensions(array $imageData): void
    {
        $fullImageData = $this->normalizeImageSourceData(wp_get_attachment_image_src($this->id, 'full'));

        if ($fullImageData === null) {
            $this->width ??= $imageData[1];
            $this->height ??= $imageData[2];

            return;
        }

        [$fullWidth, $fullHeight] = [$fullImageData[1], $fullImageData[2]];

        $sizeConfiguration = ImageSizeConfigNormalizer::get($this->sizeName);
        $targetWidth = $sizeConfiguration['width'] ?? $imageData[1];
        $targetHeight = $sizeConfiguration['height'] ?? 0;
        $crop = $sizeConfiguration['crop'] ?? false;

        $calculated = $this->calculateAspectRatioDimensions(
            $fullWidth,
            $fullHeight,
            $targetWidth,
            $targetHeight,
            $crop
        );

        $this->width ??= $calculated['width'];
        $this->height ??= $calculated['height'];

        $actualWidth = $imageData[1];
        $actualHeight = $imageData[2];

        if ($crop && ($actualWidth < $targetWidth || $actualHeight < $targetHeight)) {
            $this->width = $targetWidth;
            $this->height = $targetHeight > 0 ? $targetHeight : $actualHeight;
            $this->inlineStyle = 'object-fit: cover;';
        } else {
            $this->width = min($this->width, $fullWidth);
            $this->height = min($this->height, $fullHeight);
        }
    }

    private function calculateAspectRatioDimensions(int $fullWidth, int $fullHeight, int $targetWidth, int $targetHeight, bool $crop): array
    {
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

    private function buildResponsiveSourceSet(): string
    {
        $sizeConfiguration = ImageSizeConfigNormalizer::get($this->sizeName);

        if (! $this->hasConfiguredSourceSetVariants($sizeConfiguration)) {
            return wp_get_attachment_image_srcset($this->id, $this->sizeName) ?: '';
        }

        return $this->assembleSourceSetFromVariants($sizeConfiguration['srcset']);
    }

    private function hasConfiguredSourceSetVariants(array $sizeConfiguration): bool
    {
        return isset($sizeConfiguration['srcset']) && $sizeConfiguration['srcset'] !== [];
    }

    private function assembleSourceSetFromVariants(array $multipliers): string
    {
        $sourceSetParts = $this->collectBaseImageSource();

        foreach ($multipliers as $multiplier) {
            $variantSource = $this->collectVariantSource($multiplier);
            if ($variantSource !== null) {
                $sourceSetParts[] = $variantSource;
            }
        }

        return implode(', ', $sourceSetParts);
    }

    private function collectBaseImageSource(): array
    {
        $baseImage = $this->normalizeImageSourceData(wp_get_attachment_image_src($this->id, $this->sizeName));

        return $baseImage ? [$baseImage[0].' '.$baseImage[1].'w'] : [];
    }

    private function collectVariantSource(float $multiplier): ?string
    {
        $variantSizeName = "{$this->sizeName}@{$multiplier}x";

        $this->ensureVariantSizeExists($variantSizeName);

        $variantImage = $this->normalizeImageSourceData(wp_get_attachment_image_src($this->id, $variantSizeName));

        return $variantImage ? $variantImage[0].' '.$variantImage[1].'w' : null;
    }

    private function ensureVariantSizeExists(string $variantSizeName): void
    {
        $metadata = wp_get_attachment_metadata($this->id);

        if (! $metadata || ! is_array($metadata)) {
            return;
        }

        if (isset($metadata['sizes'][$variantSizeName])) {
            return;
        }

        $this->generateMissingImageSize($metadata, $variantSizeName);
    }

    private function generateMissingImageSize(array $metadata, ?string $sizeName = null): void
    {
        $targetSizeName = $sizeName ?? $this->sizeName;
        $attachmentFilePath = get_attached_file($this->id);

        if (! $attachmentFilePath || ! file_exists($attachmentFilePath)) {
            return;
        }

        $sourceFilePath = $this->determineSourceFilePath($attachmentFilePath, $metadata);
        $sizeConfiguration = $this->loadSizeConfiguration($targetSizeName);

        if ($sizeConfiguration === null || $sizeConfiguration === []) {
            return;
        }

        $this->processSizeGeneration($sourceFilePath, $targetSizeName, $sizeConfiguration, $metadata);
    }

    private function determineSourceFilePath(string $attachmentFilePath, array $metadata): string
    {
        if (empty($metadata['original_image'])) {
            return $attachmentFilePath;
        }

        $pathinfo = pathinfo($attachmentFilePath);
        $originalFilePath = $pathinfo['dirname'].'/'.$metadata['original_image'];

        return file_exists($originalFilePath) ? $originalFilePath : $attachmentFilePath;
    }

    private function processSizeGeneration(string $sourceFilePath, string $sizeName, array $sizeConfiguration, array $metadata): void
    {
        $generatedImage = image_make_intermediate_size(
            $sourceFilePath,
            $sizeConfiguration['width'],
            $sizeConfiguration['height'],
            $sizeConfiguration['crop']
        );

        if (! $generatedImage || is_wp_error($generatedImage)) {
            return;
        }

        $this->saveGeneratedSizeMetadata($sizeName, $generatedImage, $metadata);
        $this->scheduleOptimizationIfEnabled($sourceFilePath, $generatedImage);
    }

    private function saveGeneratedSizeMetadata(string $sizeName, array $generatedImage, array $metadata): void
    {
        $metadata['sizes'][$sizeName] = $generatedImage;

        wp_update_attachment_metadata($this->id, $metadata);
    }

    private function scheduleOptimizationIfEnabled(string $sourceFilePath, array $generatedImage): void
    {
        if (! config('sproutset-config.auto_optimize_images', false)) {
            return;
        }

        $pathinfo = pathinfo($sourceFilePath);
        $generatedImagePath = $pathinfo['dirname'].'/'.$generatedImage['file'];

        if (file_exists($generatedImagePath)) {
            CronOptimizer::scheduleImageOptimization($generatedImagePath, $this->id);
        }
    }

    private function loadSizeConfiguration(string $sizeName): ?array
    {
        global $_wp_additional_image_sizes;

        if (! isset($_wp_additional_image_sizes[$sizeName])) {
            return null;
        }

        $size = $_wp_additional_image_sizes[$sizeName];

        return [
            'width' => isset($size['width']) ? (int) $size['width'] : 0,
            'height' => isset($size['height']) ? (int) $size['height'] : 0,
            'crop' => $size['crop'] ?? false,
        ];
    }

    private function normalizeResponsiveSizesAttribute(): string
    {
        if ($this->sizes && str_starts_with(mb_trim($this->sizes), 'auto')) {
            return $this->sizes;
        }

        $autoPrefix = ['auto'];
        $sizesValue = $this->determineResponsiveSizesValue();
        $autoPrefix[] = $sizesValue;

        return implode(', ', $autoPrefix);
    }

    private function determineResponsiveSizesValue(): string
    {
        $hasCustomSizes = ! in_array($this->sizes, [null, '', '0'], true);

        return $hasCustomSizes
            ? mb_trim($this->sizes)
            : $this->generateDefaultResponsiveSizes();
    }

    private function generateDefaultResponsiveSizes(): string
    {
        if ($this->width === null || $this->width === 0) {
            return '100vw';
        }

        return "(max-width: {$this->width}px) 100vw, {$this->width}px";
    }

    private function generateCacheKey(): string
    {
        return "{$this->id}-{$this->sizeName}-{$this->width}-{$this->height}";
    }

    private function normalizeImageSourceData(?array $imageData): ?array
    {
        if (! is_array($imageData) || ! isset($imageData[0], $imageData[1], $imageData[2])) {
            return null;
        }

        $imageData[1] = (int) $imageData[1];
        $imageData[2] = (int) $imageData[2];

        return $imageData;
    }

    private function loadDataFromCache(array $cachedData): void
    {
        $this->sourcePath = $cachedData['sourcePath'];
        $this->responsiveSourceSet = $cachedData['responsiveSourceSet'];
        $this->inlineStyle = $cachedData['inlineStyle'];
        $this->width = $cachedData['width'];
        $this->height = $cachedData['height'];
        $this->alt = $cachedData['alt'];
        $this->sizes = $cachedData['sizes'];
    }

    private function getCacheableData(): array
    {
        return [
            'sourcePath' => $this->sourcePath,
            'responsiveSourceSet' => $this->responsiveSourceSet,
            'inlineStyle' => $this->inlineStyle,
            'width' => $this->width,
            'height' => $this->height,
            'alt' => $this->alt,
            'sizes' => $this->sizes,
        ];
    }

    private function isSvgAttachment(): bool
    {
        $mimeType = get_post_mime_type($this->id);

        if ($mimeType === 'image/svg+xml') {
            return true;
        }

        $filePath = get_attached_file($this->id);

        return $filePath && mb_strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'svg';
    }
}
