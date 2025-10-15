<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Console;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Roots\Acorn\Console\Commands\Command;
use Spatie\ImageOptimizer\OptimizerChainFactory;

final class Optimize extends Command
{
    protected $signature = 'sproutset:optimize';

    protected $description = 'Optimize all images in the media library.';

    private array $uploadDir;

    private string $baseDir;

    public function handle(): int
    {
        $this->newLine();
        $this->info('Starting image optimization process...');
        $this->newLine();

        $this->uploadDir = wp_upload_dir();
        $this->baseDir = trailingslashit($this->uploadDir['basedir']);
        $this->line("Uploads directory: <comment>{$this->uploadDir['basedir']}</comment>");
        $this->newLine();

        if (! $this->checkBinaryAvailability()) {
            $this->error('No optimizer binaries found. Please install at least one optimizer.');

            return self::FAILURE;
        }

        $imagePaths = $this->scanForImages($this->uploadDir['basedir']);

        if ($imagePaths === []) {
            $this->warn('No images found to optimize.');

            return self::SUCCESS;
        }

        $filteredPaths = $this->filterOptimizedImages($imagePaths);

        if ($filteredPaths === []) {
            $this->info('All images are already optimized.');

            return self::SUCCESS;
        }

        $skippedCount = count($imagePaths) - count($filteredPaths);
        if ($skippedCount > 0) {
            $this->line(sprintf('Skipping <comment>%d</comment> already optimized images', $skippedCount));
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

        $optimizerChain = OptimizerChainFactory::create();
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

            try {
                $optimizerChain->optimize($imagePath);
                $this->markAsOptimized($imagePath);
                $optimizedCount++;
            } catch (Exception) {
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
            if ($this->isBinaryAvailable($binaryName)) {
                $availableCount++;
                $this->line(sprintf('<info>✓</info> %-12s <comment>(%s)</comment>', $binaryName, $format));
            } else {
                $this->line(sprintf('<fg=red>✗</fg=red> %-12s <fg=gray>(%s)</fg=gray>', $binaryName, $format));
            }
        }

        $this->newLine();

        if ($availableCount > 0) {
            $this->line(sprintf('<info>%d/%d</info> optimizers available', $availableCount, count($optimizers)));
        }

        if ($availableCount < count($optimizers)) {
            $this->line('<comment>Tip:</comment> Install missing optimizers for better results. See <fg=blue>https://github.com/spatie/image-optimizer?tab=readme-ov-file#optimization-tools</fg=blue> for more information.');
        }

        $this->newLine();

        return $availableCount > 0;
    }

    private function isBinaryAvailable(string $binaryName): bool
    {
        $command = sprintf('command -v %s > /dev/null 2>&1', escapeshellarg($binaryName));
        exec($command, $output, $returnCode);

        return $returnCode === 0;
    }

    private function filterOptimizedImages(array $imagePaths): array
    {
        $this->line('Checking optimization status...');

        $filtered = [];
        foreach ($imagePaths as $imagePath) {
            if (! $this->isOptimized($imagePath)) {
                $filtered[] = $imagePath;
            }
        }

        $this->newLine();

        return $filtered;
    }

    private function isOptimized(string $imagePath): bool
    {
        $attachmentId = $this->getAttachmentIdByPath($imagePath);
        if ($attachmentId === null || $attachmentId === 0 || ! ($metadata = wp_get_attachment_metadata($attachmentId))) {
            return false;
        }

        $fileName = basename($imagePath);

        if (isset($metadata['original_image']) && $metadata['original_image'] === $fileName) {
            return isset($metadata['original_image_optimized']['hash'])
                && $metadata['original_image_optimized']['hash'] === md5_file($imagePath);
        }

        if (isset($metadata['file']) && basename($metadata['file']) === $fileName) {
            return isset($metadata['sproutset_optimized']['hash'])
                && $metadata['sproutset_optimized']['hash'] === md5_file($imagePath);
        }

        if (isset($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $sizeName => $sizeData) {
                if ($sizeData['file'] === $fileName) {
                    return isset($metadata['sizes'][$sizeName]['sproutset_optimized']['hash'])
                        && $metadata['sizes'][$sizeName]['sproutset_optimized']['hash'] === md5_file($imagePath);
                }
            }
        }

        return false;
    }

    private function markAsOptimized(string $imagePath): void
    {
        $attachmentId = $this->getAttachmentIdByPath($imagePath);
        if ($attachmentId === null || $attachmentId === 0 || ! ($metadata = wp_get_attachment_metadata($attachmentId))) {
            return;
        }

        $fileName = basename($imagePath);
        $optimizationData = [
            'hash' => md5_file($imagePath),
        ];

        if (isset($metadata['original_image']) && $metadata['original_image'] === $fileName) {
            $metadata['original_image_optimized'] = $optimizationData;
        } elseif (isset($metadata['file']) && basename($metadata['file']) === $fileName) {
            $metadata['sproutset_optimized'] = $optimizationData;
        } elseif (isset($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $sizeName => $sizeData) {
                if ($sizeData['file'] === $fileName) {
                    $metadata['sizes'][$sizeName]['sproutset_optimized'] = $optimizationData;
                    break;
                }
            }
        }

        wp_update_attachment_metadata($attachmentId, $metadata);
    }

    private function getAttachmentIdByPath(string $imagePath): ?int
    {
        $relativePath = mb_ltrim(str_replace($this->baseDir, '', $imagePath), '/');

        global $wpdb;

        $attachmentId = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
                $relativePath
            )
        );

        if ($attachmentId) {
            return (int) $attachmentId;
        }

        $pathInfo = pathinfo($relativePath);

        $baseFilename = preg_replace('/-\d+x\d+$|@\d+x$|-scaled$/', '', $pathInfo['filename']);

        $searchPattern = ($pathInfo['dirname'] !== '.' ? $pathInfo['dirname'].'/' : '').$baseFilename.'%';
        $attachmentId = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s LIMIT 1",
                $searchPattern
            )
        );

        return $attachmentId ? (int) $attachmentId : null;
    }
}
