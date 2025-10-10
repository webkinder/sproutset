<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Components;

use Illuminate\View\Component;

final class Image extends Component
{
    public ?string $src = null;

    public ?string $srcset = null;

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
        $this->generateImageData();

        if ($this->sizes === null || ! str_starts_with($this->sizes, 'auto')) {
            $this->sizes = $this->normalizeSizes();
        }
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
        $attachment = get_post($this->id);

        if (! $attachment || $attachment->post_type !== 'attachment') {
            return;
        }

        if ($this->alt === null) {
            $this->alt = get_post_meta($this->id, '_wp_attachment_image_alt', true) ?: '';
        }

        $this->ensureImageSizeExists($this->size);

        $imageData = wp_get_attachment_image_src($this->id, $this->size);

        if (! $imageData) {
            return;
        }

        $this->src = $imageData[0];

        if ($this->width === null) {
            $this->width = $imageData[1];
        }

        if ($this->height === null) {
            $this->height = $imageData[2];
        }

        $this->srcset = $this->generateSrcset();
    }

    private function generateSrcset(): string
    {
        $config = config('sproutset-image-sizes', []);

        if (! isset($config[$this->size])) {
            return wp_get_attachment_image_srcset($this->id, $this->size) ?: '';
        }

        $sizeConfig = $config[$this->size];

        if (! isset($sizeConfig['srcset']) || ! is_array($sizeConfig['srcset'])) {
            return wp_get_attachment_image_srcset($this->id, $this->size) ?: '';
        }

        $srcsetParts = [];

        $baseImage = wp_get_attachment_image_src($this->id, $this->size);
        if ($baseImage) {
            $srcsetParts[] = $baseImage[0].' '.$baseImage[1].'w';
        }

        foreach ($sizeConfig['srcset'] as $multiplier) {
            $variantSize = "{$this->size}@{$multiplier}x";

            $this->ensureImageSizeExists($variantSize);

            $variantImage = wp_get_attachment_image_src($this->id, $variantSize);

            if ($variantImage) {
                $srcsetParts[] = $variantImage[0].' '.$variantImage[1].'w';
            }
        }

        return implode(', ', $srcsetParts);
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

        $resized = image_make_intermediate_size(
            $file,
            $sizeData['width'],
            $sizeData['height'],
            $sizeData['crop']
        );

        if (! $resized || is_wp_error($resized)) {
            return;
        }

        $metadata['sizes'][$sizeName] = $resized;
        wp_update_attachment_metadata($this->id, $metadata);
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

        $parts[] = $this->sizes !== null && $this->sizes !== '' && $this->sizes !== '0' ? mb_trim($this->sizes) : $this->generateDefaultSizes();

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
}
