<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Components;

use Illuminate\View\Component;
use Webkinder\SproutsetPackage\Services\CronOptimizer;

final class Image extends Component
{
    public ?string $sourcePath = null;

    public ?string $responsiveSourceSet = null;

    public ?string $inlineStyle = null;

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
        $this->initializeImageData();

        if ($this->sizes === null || ! str_starts_with($this->sizes, 'auto')) {
            $this->sizes = $this->normalizeResponsiveSizesAttribute();
        }
    }

    public function render(): string
    {
        return <<<'blade'
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
        $imageData = wp_get_attachment_image_src($this->id, $this->sizeName);

        if (! $imageData) {
            return;
        }

        $this->sourcePath = $imageData[0];
        $actualWidth = $imageData[1];
        $actualHeight = $imageData[2];

        if ($this->width === null) {
            $this->width = $this->getConfiguredOrActualWidth($actualWidth);
        }

        if ($this->height === null) {
            $this->height = $this->getConfiguredOrActualHeight($actualHeight);
        }

        $this->applyObjectFitIfNeeded($actualWidth, $actualHeight);
    }

    private function getConfiguredOrActualWidth(int $actualWidth): int
    {
        $sizeConfiguration = $this->getSizeConfiguration();

        if ($sizeConfiguration['width'] === 0) {
            return $actualWidth;
        }

        return $sizeConfiguration['width'];
    }

    private function getConfiguredOrActualHeight(int $actualHeight): int
    {
        $sizeConfiguration = $this->getSizeConfiguration();

        if ($sizeConfiguration['height'] === 0) {
            return $actualHeight;
        }

        return $sizeConfiguration['height'];
    }

    private function applyObjectFitIfNeeded(int $actualWidth, int $actualHeight): void
    {
        $sizeConfiguration = $this->getSizeConfiguration();

        if ($sizeConfiguration === null) {
            return;
        }

        $configuredWidth = $sizeConfiguration['width'] ?? 0;
        $configuredHeight = $sizeConfiguration['height'] ?? 0;

        if ($configuredWidth === 0 || $configuredHeight === 0) {
            return;
        }

        $needsObjectFit = $actualWidth < $configuredWidth || $actualHeight < $configuredHeight;

        if ($needsObjectFit) {
            $this->inlineStyle = 'object-fit: cover;';
        }
    }

    private function buildResponsiveSourceSet(): string
    {
        $sizeConfiguration = $this->getSizeConfiguration();

        if (! $this->hasConfiguredSourceSetVariants($sizeConfiguration)) {
            return wp_get_attachment_image_srcset($this->id, $this->sizeName) ?: '';
        }

        return $this->assembleSourceSetFromVariants($sizeConfiguration['srcset']);
    }

    private function getSizeConfiguration(): array
    {
        $imageSizes = config('sproutset-config.image_sizes', []);

        return $imageSizes[$this->sizeName] ?? [];
    }

    private function hasConfiguredSourceSetVariants(array $sizeConfiguration): bool
    {
        return isset($sizeConfiguration['srcset']) && is_array($sizeConfiguration['srcset']);
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
        $baseImage = wp_get_attachment_image_src($this->id, $this->sizeName);

        return $baseImage ? [$baseImage[0].' '.$baseImage[1].'w'] : [];
    }

    private function collectVariantSource(float $multiplier): ?string
    {
        $variantSizeName = "{$this->sizeName}@{$multiplier}x";

        $this->ensureVariantSizeExists($variantSizeName);

        $variantImage = wp_get_attachment_image_src($this->id, $variantSizeName);

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

        return [
            'width' => $_wp_additional_image_sizes[$sizeName]['width'],
            'height' => $_wp_additional_image_sizes[$sizeName]['height'],
            'crop' => $_wp_additional_image_sizes[$sizeName]['crop'],
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
}
