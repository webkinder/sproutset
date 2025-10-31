<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Console;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Roots\Acorn\Console\Commands\Command;
use SplFileInfo;
use Symfony\Component\Console\Helper\ProgressBar;
use Webkinder\SproutsetPackage\Services\ImageOptimizer;

final class Optimize extends Command
{
    protected $signature = 'sproutset:optimize {--force : Force reoptimization of all images}';

    protected $description = 'Optimize all images in the media library.';

    private ImageOptimizer $imageOptimizer;

    private array $uploadDirectoryInfo;

    private string $uploadsBasePath;

    public function handle(): int
    {
        $this->initializeOptimizer();
        $this->displayStartMessage();

        if (! $this->verifyOptimizersAvailable()) {
            return self::FAILURE;
        }

        $allImagePaths = $this->discoverAllImages();
        if ($allImagePaths === []) {
            $this->warn('No images found to optimize.');

            return self::SUCCESS;
        }

        $imagesToProcess = $this->determineImagesToProcess($allImagePaths);
        if ($imagesToProcess === []) {
            $this->info('All images are already optimized.');

            return self::SUCCESS;
        }

        $this->displayProcessingInfo($allImagePaths, $imagesToProcess);
        $this->processImageOptimization($imagesToProcess);
        $this->displayCompletionMessage();

        return self::SUCCESS;
    }

    private function initializeOptimizer(): void
    {
        $this->imageOptimizer = ImageOptimizer::getInstance();
        $this->uploadDirectoryInfo = wp_upload_dir();
        $this->uploadsBasePath = $this->uploadDirectoryInfo['basedir'];
    }

    private function displayStartMessage(): void
    {
        $this->newLine();
        $this->info('Starting image optimization process...');
        $this->newLine();
        $this->line("Uploads directory: <comment>{$this->uploadsBasePath}</comment>");
        $this->newLine();
    }

    private function displayProcessingInfo(array $allImages, array $imagesToProcess): void
    {
        $isForceMode = (bool) $this->option('force');

        if ($isForceMode) {
            $this->line('<comment>Force mode enabled:</comment> Reoptimizing all images');
            $this->newLine();
        } else {
            $skippedCount = count($allImages) - count($imagesToProcess);
            if ($skippedCount > 0) {
                $this->line(sprintf('Skipping <comment>%d</comment> already optimized images', $skippedCount));
            }
        }

        $this->info(sprintf('Optimizing <comment>%d</comment> images', count($imagesToProcess)));
        $this->newLine();
    }

    private function displayCompletionMessage(): void
    {
        $this->newLine();
        $this->info('Image optimization completed successfully!');
    }

    private function determineImagesToProcess(array $allImagePaths): array
    {
        $isForceMode = (bool) $this->option('force');

        if ($isForceMode) {
            return $allImagePaths;
        }

        return $this->filterOutAlreadyOptimizedImages($allImagePaths);
    }

    private function discoverAllImages(): array
    {
        $this->line('Scanning for images...');

        $supportedExtensions = $this->getSupportedImageExtensions();
        $discoveredImages = $this->recursivelyFindImages($this->uploadsBasePath, $supportedExtensions);

        $this->line(sprintf('Found: <info>%d</info> images', count($discoveredImages)));
        $this->newLine();

        return $discoveredImages;
    }

    private function getSupportedImageExtensions(): array
    {
        return ['png', 'jpg', 'jpeg', 'webp', 'avif', 'svg', 'gif'];
    }

    private function recursivelyFindImages(string $directory, array $extensions): array
    {
        $imagePaths = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($this->isImageFile($file, $extensions)) {
                    $imagePaths[] = $file->getRealPath();
                }
            }
        } catch (Exception $e) {
            $this->error("Error scanning directory: {$e->getMessage()}");

            return [];
        }

        return $imagePaths;
    }

    private function isImageFile(SplFileInfo $file, array $allowedExtensions): bool
    {
        if (! $file->isFile()) {
            return false;
        }

        $fileExtension = mb_strtolower($file->getExtension());

        return in_array($fileExtension, $allowedExtensions, true);
    }

    private function processImageOptimization(array $imagePaths): void
    {
        $this->line('Optimizing images...');

        $progressBar = $this->createOptimizationProgressBar(count($imagePaths));
        $progressBar->start();

        $statistics = $this->performBatchOptimization($imagePaths, $progressBar);

        $progressBar->setMessage('Complete!');
        $progressBar->finish();

        $this->displayOptimizationStatistics($statistics);
    }

    private function createOptimizationProgressBar(int $totalImages): ProgressBar
    {
        $progressBar = $this->output->createProgressBar($totalImages);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $progressBar->setMessage('Starting...');

        return $progressBar;
    }

    private function performBatchOptimization(array $imagePaths, ProgressBar $progressBar): array
    {
        $statistics = ['optimized' => 0, 'failed' => 0];

        foreach ($imagePaths as $imagePath) {
            $fileName = basename((string) $imagePath);
            $progressBar->setMessage("Optimizing: {$fileName}");

            if ($this->optimizeSingleImage($imagePath)) {
                $statistics['optimized']++;
            } else {
                $this->reportOptimizationFailure($fileName);
                $statistics['failed']++;
            }

            $progressBar->advance();
        }

        return $statistics;
    }

    private function optimizeSingleImage(string $imagePath): bool
    {
        $attachmentId = $this->imageOptimizer->findAttachmentIdByFilePath($imagePath);

        if ($attachmentId === null) {
            return false;
        }

        return $this->imageOptimizer->optimizeAndRecordInMetadata($imagePath, $attachmentId);
    }

    private function reportOptimizationFailure(string $fileName): void
    {
        $this->newLine();
        $this->warn("Failed to optimize: {$fileName}");
        $this->newLine();
    }

    private function displayOptimizationStatistics(array $statistics): void
    {
        $this->newLine();
        $this->newLine();
        $this->line(sprintf('Successfully optimized: <info>%d</info>', $statistics['optimized']));

        if ($statistics['failed'] > 0) {
            $this->line(sprintf('Failed: <fg=red>%d</fg=red>', $statistics['failed']));
        }
    }

    private function filterOutAlreadyOptimizedImages(array $imagePaths): array
    {
        $this->line('Checking optimization status...');

        $unoptimizedImages = array_filter(
            $imagePaths,
            $this->requiresOptimization(...)
        );

        $this->newLine();

        return array_values($unoptimizedImages);
    }

    private function requiresOptimization(string $imagePath): bool
    {
        $attachmentId = $this->imageOptimizer->findAttachmentIdByFilePath($imagePath);

        if ($attachmentId === null || $attachmentId === 0) {
            return true;
        }

        return ! $this->imageOptimizer->hasBeenOptimized($imagePath, $attachmentId);
    }

    private function verifyOptimizersAvailable(): bool
    {
        $this->line('Checking optimizer binaries...');
        $this->newLine();

        $availabilityStatus = $this->checkInstalledOptimizers();
        $this->displayOptimizerStatus($availabilityStatus);

        if ($availabilityStatus['available'] === 0) {
            $this->error('No optimizer binaries found. Please install at least one optimizer.');

            return false;
        }

        $this->displayOptimizationTips($availabilityStatus);
        $this->newLine();

        return true;
    }

    private function checkInstalledOptimizers(): array
    {
        $optimizers = ImageOptimizer::getAvailableOptimizers();
        $status = ['available' => 0, 'missing' => 0, 'details' => []];

        foreach ($optimizers as $binaryName => $config) {
            $isAvailable = $this->imageOptimizer->checkBinaryAvailability($binaryName);

            $status['details'][$binaryName] = [
                'available' => $isAvailable,
                'config' => $config,
            ];

            if ($isAvailable) {
                $status['available']++;
            } else {
                $status['missing']++;
            }
        }

        return $status;
    }

    private function displayOptimizerStatus(array $status): void
    {
        foreach ($status['details'] as $binaryName => $details) {
            $icon = $details['available'] ? '<info>✓</info>' : '<fg=red>✗</fg=red>';
            $formatColor = $details['available'] ? '<comment>(%s)</comment>' : '<fg=gray>(%s)</fg=gray>';

            $this->line(sprintf(
                '%s %-12s '.$formatColor,
                $icon,
                $binaryName,
                $details['config']['format']
            ));
        }
    }

    private function displayOptimizationTips(array $status): void
    {
        if ($status['available'] > 0) {
            $this->newLine();
            $totalOptimizers = $status['available'] + $status['missing'];
            $this->line(sprintf('<info>%d/%d</info> optimizers available', $status['available'], $totalOptimizers));
        }

        if ($status['missing'] > 0) {
            $this->newLine();
            $this->line('<comment>Tip:</comment> Install missing optimizers for better results.');
            $this->line('See <fg=blue>https://github.com/spatie/image-optimizer?tab=readme-ov-file#optimization-tools</fg=blue>');
        }
    }
}
