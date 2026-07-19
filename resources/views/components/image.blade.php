@if($src)
    @if($avifSrcset)
        <picture>
            <source type="image/avif" srcset="{{ $avifSrcset }}"@if($avifSizes) sizes="{{ $avifSizes }}"@endif>
            <img {{ $attributes->class($class)->merge($htmlAttributes) }}>
        </picture>
    @else
        <img {{ $attributes->class($class)->merge($htmlAttributes) }}>
    @endif
@endif
