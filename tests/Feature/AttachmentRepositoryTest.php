<?php

declare(strict_types=1);

use Webkinder\Sproutset\Attachments\Attachment;
use Webkinder\Sproutset\Tests\Support\FakeAttachmentRepository;

it('resolves a seeded attachment by id', function (): void {
    $repository = new FakeAttachmentRepository;
    $attachment = new Attachment(id: 42, url: 'https://example.com/cat.jpg', width: 1200, height: 800);
    $repository->add($attachment);

    expect($repository->find(42))->toEqual($attachment);
});

it('returns null for an unknown id', function (): void {
    $repository = new FakeAttachmentRepository;

    expect($repository->find(999))->toBeNull();
});
