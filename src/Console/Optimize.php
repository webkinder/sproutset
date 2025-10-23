<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Console;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Roots\Acorn\Console\Commands\Command;
use Webkinder\SproutsetPackage\Services\ImageOptimizer;

final class Optimize extends Command
{
    protected $signature = 'sproutset:optimize {--force : Force reoptimization of all images}';

    protected $description = 'Optimize all images in the media library.';

    private ImageOptimizer $optimizer;

    public function handle(): int
    {
        $this->newLine();
        $this->info('Starting image optimization process...');
        $this->newLine();

        $this->optimizer = ImageOptimizer::getInstance();
        $uploadDir = wp_upload_dir();
        $this->line("Uploads directory: <comment>{$uploadDir['basedir']}</comment>");
        $this->newLine();

        if (! $this->checkBinaryAvailability()) {
            $this->error('No optimizer binaries found. Please install at least one optimizer.');

            return self::FAILURE;
        }

        $imagePaths = $this->scanForImages($uploadDir['basedir']);

        if ($imagePaths === []) {
            $this->warn('No images found to optimize.');

            return self::SUCCESS;
        }

        $force = (bool) $this->option('force');

        if ($force) {
            $this->line('<comment>Force mode enabled:</comment> Reoptimizing all images');
            $this->newLine();
            $filteredPaths = $imagePaths;
        } else {
            $filteredPaths = $this->filterOptimizedImages($imagePaths);

            if ($filteredPaths === []) {
                $this->info('All images are already optimized.');

                return self::SUCCESS;
            }

            $skippedCount = count($imagePaths) - count($filteredPaths);
            if ($skippedCount > 0) {
                $this->line(sprintf('Skipping <comment>%d</comment> already optimized images', $skippedCount));
            }
        }

        $this->info(sprintf('Optimizing <comment>%d</comment> images', count($filteredPaths)));
        $this->newLine();

        $this->optimizeImages($filteredPaths);

        $this->newLine();
        $this->info('Image optimization completed successfully!');

        return self::SUCCESS;
    }

    private function scanForImages(string $uploadsPath): array
    {
        $this->line('Scanning for images...');

        $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp', 'avif', 'svg', 'gif'];
        $imagePaths = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($uploadsPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $extension = mb_strtolower((string) $file->getExtension());
                    if (in_array($extension, $allowedExtensions, true)) {
                        $imagePaths[] = $file->getRealPath();
                    }
                }
            }
        } catch (Exception $e) {
            $this->error("Error scanning directory: {$e->getMessage()}");

            return [];
        }

        $this->line(sprintf('Found: <info>%d</info> images', count($imagePaths)));
        $this->newLine();

        return $imagePaths;
    }

    private function optimizeImages(array $imagePaths): void
    {
        $this->line('Optimizing images...');

        $totalImages = count($imagePaths);
        $progressBar = $this->output->createProgressBar($totalImages);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $progressBar->setMessage('Starting...');

        $progressBar->start();

        $optimizedCount = 0;
        $failedCount = 0;

        foreach ($imagePaths as $imagePath) {
            $fileName = basename((string) $imagePath);
            $progressBar->setMessage("Optimizing: {$fileName}");

            $attachmentId = $this->optimizer->getAttachmentIdByPath($imagePath);

            if ($attachmentId && $this->optimizer->optimizeAndMark($imagePath, $attachmentId)) {
                $optimizedCount++;
            } else {
                $this->newLine();
                $this->warn("Failed to optimize: {$fileName}");
                $this->newLine();
                $failedCount++;
            }

            $progressBar->advance();
        }

        $progressBar->setMessage('Complete!');
        $progressBar->finish();
        $this->newLine();
        $this->newLine();

        $this->line(sprintf('Successfully optimized: <info>%d</info>', $optimizedCount));
        if ($failedCount > 0) {
            $this->line(sprintf('Failed: <fg=red>%d</fg=red>', $failedCount));
        }
    }

    private function filterOptimizedImages(array $imagePaths): array
    {
        $this->line('Checking optimization status...');

        $filtered = [];
        foreach ($imagePaths as $imagePath) {
            $attachmentId = $this->optimizer->getAttachmentIdByPath($imagePath);
            if ($attachmentId && ! $this->optimizer->isOptimized($imagePath, $attachmentId)) {
                $filtered[] = $imagePath;
            } elseif ($attachmentId === null || $attachmentId === 0) {
                $filtered[] = $imagePath;
            }
        }

        $this->newLine();

        return $filtered;
    }

    private function checkBinaryAvailability(): bool
    {
        $optimizers = [
            'jpegoptim' => 'JPEG',
            'pngquant' => 'PNG',
            'optipng' => 'PNG',
            'svgo' => 'SVG',
            'gifsicle' => 'GIF',
            'cwebp' => 'WebP',
            'avifenc' => 'AVIF',
        ];

        $this->line('Checking optimizer binaries...');
        $this->newLine();

        $availableCount = 0;

        foreach ($optimizers as $binaryName => $format) {
            if ($this->optimizer->isBinaryAvailable($binaryName)) {
                $availableCount++;
                $this->line(sprintf('<info>✓</info> %-12s <comment>(%s)</comment>', $binaryName, $format));
            } else {
                $this->line(sprintf('<fg=red>✗</fg=red> %-12s <fg=gray>(%s)</fg=gray>', $binaryName, $format));
            }
        }

        if ($availableCount > 0) {
            $this->newLine();
            $this->line(sprintf('<info>%d/%d</info> optimizers available', $availableCount, count($optimizers)));
        }

        if ($availableCount < count($optimizers)) {
            $this->newLine();
            $this->line('<comment>Tip:</comment> Install missing optimizers for better results. See <fg=blue>https://github.com/spatie/image-optimizer?tab=readme-ov-file#optimization-tools</fg=blue> for more information.');
        }

        $this->newLine();

        return $availableCount > 0;
    }
}
