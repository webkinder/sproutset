<?php

declare(strict_types=1);

namespace Webkinder\Sproutset;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Webkinder\Sproutset\Attachments\AttachmentRepository;
use Webkinder\Sproutset\Attachments\WpAttachmentRepository;
use Webkinder\Sproutset\Images\CoreImageSizeOptions;
use Webkinder\Sproutset\Images\ImageResolver;
use Webkinder\Sproutset\Images\ImageSizeRegistrar;
use Webkinder\Sproutset\Images\MediaSettingsLock;
use Webkinder\Sproutset\Images\OnDemandSizeGenerator;
use Webkinder\Sproutset\Images\WpImageResolver;
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
        $this->app->singleton(OnDemandSizeGenerator::class);
        $this->app->bind(ImageResolver::class, WpImageResolver::class);
    }

    public function packageBooted(): void
    {
        if (! function_exists('add_action')) {
            return;
        }

        add_action('after_setup_theme', function (): void {
            $this->app->make(ImageSizeRegistrar::class)->register($this->imageSizesConfig());
        }, 10);

        $this->app->make(CoreImageSizeOptions::class)->register($this->imageSizesConfig());
        $this->app->make(MediaSettingsLock::class)->register($this->imageSizesConfig());
    }

    /**
     * @return array<array-key, mixed>
     */
    private function imageSizesConfig(): array
    {
        $rawConfig = config('sproutset.image_sizes', []);

        return is_array($rawConfig) ? $rawConfig : [];
    }
}
