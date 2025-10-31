<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage;

use Webkinder\SproutsetPackage\Managers\AdminNotificationManager;
use Webkinder\SproutsetPackage\Managers\ConfigurationValidator;
use Webkinder\SproutsetPackage\Managers\ImageSizeManager;
use Webkinder\SproutsetPackage\Managers\OptimizationManager;
use Webkinder\SproutsetPackage\Managers\TextDomainManager;

final readonly class Sproutset
{
    private ConfigurationValidator $configurationValidator;

    private TextDomainManager $textDomainManager;

    private ImageSizeManager $imageSizeManager;

    private OptimizationManager $optimizationManager;

    private AdminNotificationManager $adminNotificationManager;

    public function __construct()
    {
        $this->initializeManagers();
        $this->bootstrapPackage();
    }

    private function initializeManagers(): void
    {
        $this->configurationValidator = new ConfigurationValidator();
        $this->textDomainManager = new TextDomainManager();
        $this->imageSizeManager = new ImageSizeManager();
        $this->optimizationManager = new OptimizationManager();
        $this->adminNotificationManager = new AdminNotificationManager();
    }

    private function bootstrapPackage(): void
    {
        $this->configurationValidator->validateRequiredImageSizes();
        $this->textDomainManager->registerTextDomain();
        $this->imageSizeManager->initializeImageSizes();
        $this->optimizationManager->initializeOptimizationFeatures();
        $this->adminNotificationManager->initializeAdminNotifications();
    }
}
