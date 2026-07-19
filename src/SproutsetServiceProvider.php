<?php

declare(strict_types=1);

namespace Webkinder\Sproutset;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Webkinder\Sproutset\Attachments\AttachmentRepository;
use Webkinder\Sproutset\Attachments\WpAttachmentRepository;
use Webkinder\Sproutset\Images\Avif\AvifCleanup;
use Webkinder\Sproutset\Images\Avif\AvifConfig;
use Webkinder\Sproutset\Images\Avif\AvifSupport;
use Webkinder\Sproutset\Images\Avif\AvifVariantGenerator;
use Webkinder\Sproutset\Images\Avif\WpAvifSupport;
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
        $this->app->singleton(AvifConfig::class, fn (): AvifConfig => $this->avifConfig());
        $this->app->singleton(AvifSupport::class, WpAvifSupport::class);
        $this->app->singleton(AvifVariantGenerator::class);
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

        add_action('delete_attachment', function (int $attachmentId): void {
            $this->app->make(AvifCleanup::class)->forget($attachmentId);
        }, 10, 1);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function imageSizesConfig(): array
    {
        $rawConfig = config('sproutset.image_sizes', []);

        return is_array($rawConfig) ? $rawConfig : [];
    }

    private function avifConfig(): AvifConfig
    {
        $raw = config('sproutset.avif', []);
        $raw = is_array($raw) ? $raw : [];

        $enabled = (bool) ($raw['enabled'] ?? false);
        $quality = is_numeric($raw['quality'] ?? null) ? max(0, min(100, (int) $raw['quality'])) : 50;

        return new AvifConfig($enabled, $quality);
    }
}
