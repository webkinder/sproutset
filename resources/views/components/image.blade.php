@if($src)
    <img {{ $attributes->class($class)->merge($htmlAttributes) }}>
@endif
