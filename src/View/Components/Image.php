<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFactory;
use Illuminate\View\Component;
use Webkinder\Sproutset\Images\ImageInputNormalizer;
use Webkinder\Sproutset\Images\ImageRequest;
use Webkinder\Sproutset\Images\ImageResolver;
use Webkinder\Sproutset\Images\ResolvedImage;

/**
 * The `<x-sproutset-image>` Blade component: a thin shell over an
 * {@see ImageResolver}. It normalizes the public attributes, renders whatever
 * the resolver returns, and re-applies the declared `class` prop to the tag.
 */
final class Image extends Component
{
    private readonly ImageRequest $request;

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
        $this->request = ImageInputNormalizer::normalize(
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
    }

    public function render(): View
    {
        $resolved = resolve(ImageResolver::class)->resolve($this->request);

        return ViewFactory::make('sproutset::components.image', [
            'src' => $resolved?->src,
            'class' => $this->request->class,
            'htmlAttributes' => $resolved instanceof ResolvedImage
                ? $this->htmlAttributesFor($resolved)
                : [],
        ]);
    }

    /**
     * @return array<string, scalar>
     */
    private function htmlAttributesFor(ResolvedImage $resolved): array
    {
        if ($resolved->isSvg) {
            return array_filter([
                'src' => $resolved->src,
                'alt' => $resolved->alt,
                'style' => $resolved->style,
            ]);
        }

        return array_filter([
            'src' => $resolved->src,
            'width' => $resolved->width,
            'height' => $resolved->height,
            'srcset' => $resolved->srcset,
            'sizes' => $resolved->sizes,
            'alt' => $resolved->alt,
            'style' => $resolved->style,
            'loading' => $this->request->loading,
            'decoding' => $this->request->decoding,
        ]);
    }
}
