<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\ImageInputNormalizer;
use Webkinder\Sproutset\Images\NullImageResolver;

it('resolves every request to null', function (): void {
    $resolver = new NullImageResolver;
    $request = ImageInputNormalizer::normalize(attachmentId: 42);

    expect($resolver->resolve($request))->toBeNull();
});
