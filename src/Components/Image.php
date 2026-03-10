<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Components;

use Illuminate\View\Component;
use Webkinder\SproutsetPackage\Managers\AutoSizesManager;
use Webkinder\SproutsetPackage\Services\CronOptimizer;
use Webkinder\SproutsetPackage\Services\FocalPointCropper;
use Webkinder\SproutsetPackage\Support\FocalPointConfig;
use Webkinder\SproutsetPackage\Support\FocalPointMetadata;
use Webkinder\SproutsetPackage\Support\ImageComponentInputNormalizer;
use Webkinder\SproutsetPackage\Support\ImageEditDetector;
use Webkinder\SproutsetPackage\Support\ImageSizeConfigNormalizer;

final class Image extends Component
{
    public ?string $sourcePath = null;

    public ?string $responsiveSourceSet = null;

    public ?string $inlineStyle = null;

    public int $attachmentId;

    public string $sizeName;

    public ?string $sizes;

    public ?string $alt;

    public ?int $width;

    public ?int $height;

    public ?string $class;

    public string $loading;

    public string $decoding;

    public bool $useAutoSizes;

    public bool $focalPoint;

    public ?float $focalPointX;

    public ?float $focalPointY;

    public array $htmlAttributes = [];

    private static array $cache = [];

    private bool $isSvg = false;

    private static int $generatedSizesInCurrentRequest = 0;

    public function __construct(
        mixed $attachmentId,
        mixed $sizeName = 'large',
        mixed $sizes = null,
        mixed $alt = null,
        mixed $width = null,
        mixed $height = null,
        mixed $class = null,
        mixed $loading = 'lazy',
        mixed $decoding = 'async',
        mixed $useAutoSizes = true,
        mixed $focalPoint = false,
        mixed $focalPointX = null,
        mixed $focalPointY = null,
    ) {
        $input = ImageComponentInputNormalizer::normalize(
            $attachmentId,
            $sizeName,
            $sizes,
            $alt,
            $width,
            $height,
            $class,
            $loading,
            $decoding,
            $useAutoSizes,
            $focalPoint,
            $focalPointX,
            $focalPointY,
        );

        $this->attachmentId = $input->attachmentId;
        $this->sizeName = $input->sizeName;
        $this->sizes = $input->sizes;
        $this->alt = $input->alt;
        $this->width = $input->width;
        $this->height = $input->height;
        $this->class = $input->class;
        $this->loading = $input->loading;
        $this->decoding = $input->decoding;
        $this->useAutoSizes = $input->useAutoSizes;
        $this->focalPoint = $input->focalPoint;
        $this->focalPointX = $input->focalPointX;
        $this->focalPointY = $input->focalPointY;

        $cacheKey = $this->generateCacheKey();

        if (isset(self::$cache[$cacheKey])) {
            $this->loadDataFromCache(self::$cache[$cacheKey]);
        } else {
            $this->isSvg = $this->isSvgAttachment();

            $this->initializeImageData();

            $this->applyFocalPointIfEnabled();

            self::$cache[$cacheKey] = $this->getCacheableData();
        }

        $this->loadAlternativeTextIfNeeded();

        if (! $this->useAutoSizes) {
            AutoSizesManager::registerImageWithAutoSizesDisabled();
        }

        if (! $this->useAutoSizes || $this->sizes === null || ! str_starts_with($this->sizes, 'auto')) {
            $this->sizes = $this->normalizeResponsiveSizesAttribute();
        }

        $this->htmlAttributes = $this->isSvg
            ? $this->buildSvgAttributes()
            : $this->buildImageAttributes();
    }

    public function render(): string
    {
        return <<<'blade'
            @if($sourcePath)
                <img {{ $attributes->class($class)->merge($htmlAttributes) }}>
            @endif
        blade;
    }

    public function buildSvgAttributes(): array
    {
        return array_filter([
            'src' => $this->sourcePath,
            'alt' => $this->alt,
            'style' => $this->inlineStyle,
        ]);
    }

    public function buildImageAttributes(): array
    {
        return array_filter([
            'src' => $this->sourcePath,
            'width' => $this->width,
            'height' => $this->height,
            'srcset' => $this->responsiveSourceSet,
            'sizes' => $this->sizes,
            'alt' => $this->alt,
            'style' => $this->inlineStyle,
            'loading' => $this->loading,
            'decoding' => $this->decoding,
        ]);
    }

    private function initializeImageData(): void
    {
        if (! $this->isValidAttachment() || ! $this->isRenderableImageAttachment()) {
            return;
        }

        if ($this->isSvg) {
            $this->sourcePath = wp_get_attachment_url($this->attachmentId);

            return;
        }

        $this->ensureRequestedSizeIsAvailable();
        $this->loadImageDimensions();
        $this->buildResponsiveSourceSet();
    }

    private function isValidAttachment(): bool
    {
        $attachment = get_post($this->attachmentId);

        return $attachment && $attachment->post_type === 'attachment';
    }

    private function isRenderableImageAttachment(): bool
    {
        return $this->isSvg || wp_attachment_is_image($this->attachmentId);
    }

    private function loadAlternativeTextIfNeeded(): void
    {
        if ($this->alt !== null) {
            return;
        }

        $this->alt = get_post_meta($this->attachmentId, '_wp_attachment_image_alt', true) ?: '';
    }

    private function ensureRequestedSizeIsAvailable(): void
    {
        $metadata = wp_get_attachment_metadata($this->attachmentId);

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
        $imageData = $this->normalizeImageSourceData(wp_get_attachment_image_src($this->attachmentId, $this->sizeName));

        if ($imageData === null) {
            return;
        }

        $this->sourcePath = $imageData[0];

        $this->calculateAndSetDimensions($imageData);
    }

    private function calculateAndSetDimensions(array $imageData): void
    {
        $fullImageData = $this->normalizeImageSourceData(wp_get_attachment_image_src($this->attachmentId, 'full'));

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

        $needsObjectFit = false;

        if ($crop && ($actualWidth < $targetWidth || $actualHeight < $targetHeight)) {
            $needsObjectFit = true;
        }

        if (! $needsObjectFit && $crop && isset($sizeConfiguration['srcset']) && is_array($sizeConfiguration['srcset'])) {
            foreach ($sizeConfiguration['srcset'] as $multiplier) {
                $variantTargetWidth = (int) round($targetWidth * $multiplier);
                $variantTargetHeight = $targetHeight > 0 ? (int) round($targetHeight * $multiplier) : 0;

                if ($fullWidth < $variantTargetWidth || ($variantTargetHeight > 0 && $fullHeight < $variantTargetHeight)) {
                    $needsObjectFit = true;
                    break;
                }
            }
        }

        if ($needsObjectFit) {
            $this->width = $targetWidth;
            $this->height = $targetHeight > 0 ? $targetHeight : $actualHeight;
            $this->inlineStyle = 'object-fit: cover;';
        } else {
            $this->width = min($this->width, $fullWidth);
            $this->height = min($this->height, $fullHeight);
        }
    }

    private function applyFocalPointIfEnabled(): void
    {
        if (! $this->focalPoint || $this->isSvg) {
            return;
        }

        $metadata = wp_get_attachment_metadata($this->attachmentId);

        if (! is_array($metadata)) {
            $metadata = [];
        }

        [$metaX, $metaY] = FocalPointMetadata::readCoordinatesFromMetadataArray($metadata);

        $x = $this->focalPointX ?? $metaX;
        $y = $this->focalPointY ?? $metaY;

        $x = max(FocalPointMetadata::MIN_PERCENT, min(FocalPointMetadata::MAX_PERCENT, $x));
        $y = max(FocalPointMetadata::MIN_PERCENT, min(FocalPointMetadata::MAX_PERCENT, $y));

        $focalStyle = sprintf('object-fit: cover; object-position: %s%% %s%%;', $x, $y);

        if ($this->inlineStyle === null || $this->inlineStyle === '') {
            $this->inlineStyle = $focalStyle;

            return;
        }

        $this->inlineStyle = mb_rtrim($this->inlineStyle).' '.$focalStyle;
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

    private function buildResponsiveSourceSet(): void
    {
        $sizeConfiguration = ImageSizeConfigNormalizer::get($this->sizeName);

        if (! $this->hasConfiguredSourceSetVariants($sizeConfiguration)) {
            $this->responsiveSourceSet = wp_get_attachment_image_srcset($this->attachmentId, $this->sizeName) ?: '';

            return;
        }

        $this->responsiveSourceSet = $this->assembleSourceSetFromVariants($sizeConfiguration['srcset']);
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
        $baseImage = $this->normalizeImageSourceData(wp_get_attachment_image_src($this->attachmentId, $this->sizeName));

        return $baseImage ? [$baseImage[0].' '.$baseImage[1].'w'] : [];
    }

    private function collectVariantSource(float $multiplier): ?string
    {
        $variantSizeName = "{$this->sizeName}@{$multiplier}x";

        $this->ensureVariantSizeExists($variantSizeName);

        $variantImage = $this->normalizeImageSourceData(wp_get_attachment_image_src($this->attachmentId, $variantSizeName));

        return $variantImage ? $variantImage[0].' '.$variantImage[1].'w' : null;
    }

    private function ensureVariantSizeExists(string $variantSizeName): void
    {
        $metadata = wp_get_attachment_metadata($this->attachmentId);

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
        if (! $this->canGenerateAnotherSizeInCurrentRequest()) {
            return;
        }

        $targetSizeName = $sizeName ?? $this->sizeName;
        $attachmentFilePath = get_attached_file($this->attachmentId);

        if (! $attachmentFilePath || ! file_exists($attachmentFilePath)) {
            return;
        }

        $sourceFilePath = $this->determineSourceFilePath($attachmentFilePath, $metadata);
        $sizeConfiguration = $this->loadSizeConfiguration($targetSizeName);

        if ($sizeConfiguration === null || $sizeConfiguration === []) {
            return;
        }

        self::$generatedSizesInCurrentRequest++;

        $this->processSizeGeneration($sourceFilePath, $targetSizeName, $sizeConfiguration, $metadata);

        if (! FocalPointConfig::isEnabled()) {
            return;
        }

        $currentMetadata = wp_get_attachment_metadata($this->attachmentId);

        if (! is_array($currentMetadata)) {
            return;
        }

        $targetWidth = isset($sizeConfiguration['width']) ? (int) $sizeConfiguration['width'] : 0;
        $targetHeight = isset($sizeConfiguration['height']) ? (int) $sizeConfiguration['height'] : 0;
        $crop = (bool) ($sizeConfiguration['crop'] ?? false);

        $updatedMetadata = FocalPointCropper::applyFocalCropToSingleSizeWithConfiguration(
            $this->attachmentId,
            $targetSizeName,
            $currentMetadata,
            $targetWidth,
            $targetHeight,
            $crop
        );

        wp_update_attachment_metadata($this->attachmentId, $updatedMetadata);
    }

    private function canGenerateAnotherSizeInCurrentRequest(): bool
    {
        $limit = (int) (config('sproutset-config.max_on_demand_generations_per_request', 0) ?? 0);

        if ($limit <= 0) {
            return true;
        }

        return self::$generatedSizesInCurrentRequest < $limit;
    }

    private function determineSourceFilePath(string $attachmentFilePath, array $metadata): string
    {
        if (empty($metadata['original_image'])) {
            return $attachmentFilePath;
        }

        if (ImageEditDetector::isEditedImage($metadata)) {
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

        wp_update_attachment_metadata($this->attachmentId, $metadata);
    }

    private function scheduleOptimizationIfEnabled(string $sourceFilePath, array $generatedImage): void
    {
        if (! config('sproutset-config.auto_optimize_images', false)) {
            return;
        }

        $pathinfo = pathinfo($sourceFilePath);
        $generatedImagePath = $pathinfo['dirname'].'/'.$generatedImage['file'];

        if (file_exists($generatedImagePath)) {
            CronOptimizer::scheduleImageOptimization($generatedImagePath, $this->attachmentId);
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
        $trimmedSizes = $this->sizes !== null ? mb_trim($this->sizes) : null;

        if (! $this->useAutoSizes) {
            if ($trimmedSizes !== null && $trimmedSizes !== '') {
                return $trimmedSizes;
            }

            return $this->determineResponsiveSizesValue();
        }

        if ($trimmedSizes && str_starts_with($trimmedSizes, 'auto')) {
            return $trimmedSizes;
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
        $focalSuffix = $this->focalPoint ? 'fp1' : 'fp0';
        $overrideXSuffix = $this->focalPointX !== null ? (string) $this->focalPointX : 'xnull';
        $overrideYSuffix = $this->focalPointY !== null ? (string) $this->focalPointY : 'ynull';

        return "{$this->attachmentId}-{$this->sizeName}-{$this->width}-{$this->height}-{$focalSuffix}-{$overrideXSuffix}-{$overrideYSuffix}";
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
        $this->isSvg = $cachedData['isSvg'] ?? false;
    }

    private function getCacheableData(): array
    {
        return [
            'sourcePath' => $this->sourcePath,
            'responsiveSourceSet' => $this->responsiveSourceSet,
            'inlineStyle' => $this->inlineStyle,
            'width' => $this->width,
            'height' => $this->height,
            'isSvg' => $this->isSvg,
        ];
    }

    private function isSvgAttachment(): bool
    {
        $mimeType = get_post_mime_type($this->attachmentId);

        if ($mimeType === 'image/svg+xml') {
            return true;
        }

        $filePath = get_attached_file($this->attachmentId);

        return $filePath && mb_strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'svg';
    }
}
