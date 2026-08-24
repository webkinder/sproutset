<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\Avif\AvifSiblingPath;

it('derives a sibling path scoped by attachment id', function (): void {
    expect(AvifSiblingPath::for('/uploads/2026/08/image-300x200.jpg', 42))
        ->toBe('/uploads/2026/08/image-300x200.42.avif');
});

it('returns null when the file has no extension', function (): void {
    expect(AvifSiblingPath::for('/uploads/2026/08/image', 42))->toBeNull();
});

it('derives distinct sibling paths for attachments that share a basename', function (): void {
    $png = AvifSiblingPath::for('/uploads/2026/08/siegel.png', 10);
    $jpg = AvifSiblingPath::for('/uploads/2026/08/siegel.jpg', 11);

    expect($png)->not->toBe($jpg);
});
