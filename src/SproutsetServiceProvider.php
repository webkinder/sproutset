<?php

declare(strict_types=1);

namespace Webkinder\Sproutset;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Webkinder\Sproutset\Attachments\AttachmentRepository;
use Webkinder\Sproutset\Attachments\WpAttachmentRepository;
use Webkinder\Sproutset\Images\ImageResolver;
use Webkinder\Sproutset\Images\NullImageResolver;
use Webkinder\Sproutset\View\Components\Image;

class SproutsetServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('sproutset')
            ->hasConfigFile()
            ->hasViews()
            ->hasViewComponents('sproutset', Image::class);
    }

    public function packageRegistered(): void
    {
        $this->app->bind(AttachmentRepository::class, WpAttachmentRepository::class);
        $this->app->bind(ImageResolver::class, NullImageResolver::class);
    }
}
