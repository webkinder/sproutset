<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Services;

use Exception;
use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\OptimizerChainFactory;

final class ImageOptimizer
{
    private static ?self $instance = null;

    private readonly OptimizerChain $optimizerChain;

    private readonly array $uploadDirectoryInfo;

    private readonly string $uploadsBasePath;

    private function __construct()
    {
        $this->optimizerChain = OptimizerChainFactory::create();
        $this->uploadDirectoryInfo = wp_upload_dir();
        $this->uploadsBasePath = trailingslashit($this->uploadDirectoryInfo['basedir']);
    }

    public static function getInstance(): self
    {
        if (! self::$instance instanceof self) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function getAvailableOptimizers(): array
    {
        return [
            'jpegoptim' => ['name' => 'JpegOptim', 'format' => 'JPEG'],
            'optipng' => ['name' => 'Optipng', 'format' => 'PNG'],
            'pngquant' => ['name' => 'Pngquant 2', 'format' => 'PNG'],
            'svgo' => ['name' => 'SVGO 1', 'format' => 'SVG'],
            'gifsicle' => ['name' => 'Gifsicle', 'format' => 'GIF'],
            'cwebp' => ['name' => 'cwebp', 'format' => 'WebP'],
            'avifenc' => ['name' => 'avifenc', 'format' => 'AVIF'],
        ];
    }

    public function optimizeImageFile(string $imagePath): bool
    {
        if (! file_exists($imagePath)) {
            return false;
        }

        try {
            $this->optimizerChain->optimize($imagePath);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    public function optimizeAndRecordInMetadata(string $imagePath, int $attachmentId): bool
    {
        if (! $this->optimizeImageFile($imagePath)) {
            return false;
        }

        $this->recordOptimizationInMetadata($imagePath, $attachmentId);

        return true;
    }

    public function hasBeenOptimized(string $imagePath, int $attachmentId): bool
    {
        $metadata = wp_get_attachment_metadata($attachmentId);

        if (! $metadata) {
            return false;
        }

        $fileName = basename($imagePath);

        if (isset($metadata['original_image']) && $metadata['original_image'] === $fileName) {
            return isset($metadata['original_image_sproutset_optimized']['hash'])
                && $metadata['original_image_sproutset_optimized']['hash'] === md5_file($imagePath);
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

    public function recordOptimizationInMetadata(string $imagePath, int $attachmentId): void
    {
        $metadata = wp_get_attachment_metadata($attachmentId);

        if (! $metadata) {
            return;
        }

        $fileName = basename($imagePath);
        $optimizationData = [
            'hash' => md5_file($imagePath),
            'timestamp' => time(),
        ];

        if (isset($metadata['original_image']) && $metadata['original_image'] === $fileName) {
            $metadata['original_image_sproutset_optimized'] = $optimizationData;
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

    public function findAttachmentIdByFilePath(string $imagePath): ?int
    {
        $relativePath = $this->extractRelativePath($imagePath);
        $directMatchId = $this->findAttachmentByExactPath($relativePath);

        if ($directMatchId !== null) {
            return $directMatchId;
        }

        return $this->findAttachmentByBaseFilename($relativePath);
    }

    public function optimizeAllAttachmentVariants(int $attachmentId, array $metadata): array
    {
        if ($metadata === []) {
            return $metadata;
        }

        $this->optimizeOriginalImage($metadata);
        $this->optimizeMainImage($metadata);
        $this->optimizeGeneratedSizes($metadata);

        return $metadata;
    }

    public function checkBinaryAvailability(string $binaryName): bool
    {
        $checkCommand = sprintf('command -v %s > /dev/null 2>&1', escapeshellarg($binaryName));

        exec($checkCommand, $output, $returnCode);

        return $returnCode === 0;
    }

    private function extractRelativePath(string $fullPath): string
    {
        return mb_ltrim(str_replace($this->uploadsBasePath, '', $fullPath), '/');
    }

    private function findAttachmentByExactPath(string $relativePath): ?int
    {
        global $wpdb;

        $attachmentId = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
                $relativePath
            )
        );

        return $attachmentId ? (int) $attachmentId : null;
    }

    private function findAttachmentByBaseFilename(string $relativePath): ?int
    {
        global $wpdb;

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

    private function optimizeOriginalImage(array &$metadata): void
    {
        if (! isset($metadata['original_image'])) {
            return;
        }

        $originalPath = $this->buildOriginalImagePath($metadata);

        if (file_exists($originalPath) && $this->optimizeImageFile($originalPath)) {
            $metadata['original_image_sproutset_optimized'] = $this->createOptimizationRecord($originalPath);
        }
    }

    private function buildOriginalImagePath(array $metadata): string
    {
        $pathInfo = pathinfo((string) $metadata['file']);

        return $this->uploadsBasePath.$pathInfo['dirname'].'/'.$metadata['original_image'];
    }

    private function optimizeMainImage(array &$metadata): void
    {
        $mainFilePath = $this->uploadsBasePath.$metadata['file'];

        if (file_exists($mainFilePath) && $this->optimizeImageFile($mainFilePath)) {
            $metadata['sproutset_optimized'] = $this->createOptimizationRecord($mainFilePath);
            $metadata['filesize'] = filesize($mainFilePath);
        }
    }

    private function optimizeGeneratedSizes(array &$metadata): void
    {
        if (! isset($metadata['sizes']) || ! is_array($metadata['sizes'])) {
            return;
        }

        $pathInfo = pathinfo((string) $metadata['file']);
        $directoryPath = $pathInfo['dirname'];

        foreach ($metadata['sizes'] as $sizeName => $sizeData) {
            $this->optimizeSingleSize($metadata, $sizeName, $sizeData, $directoryPath);
        }
    }

    private function optimizeSingleSize(array &$metadata, string $sizeName, array $sizeData, string $directoryPath): void
    {
        $sizePath = $this->uploadsBasePath.$directoryPath.'/'.$sizeData['file'];

        if (file_exists($sizePath) && $this->optimizeImageFile($sizePath)) {
            $metadata['sizes'][$sizeName]['sproutset_optimized'] = $this->createOptimizationRecord($sizePath);
            $metadata['sizes'][$sizeName]['filesize'] = filesize($sizePath);
        }
    }

    private function createOptimizationRecord(string $filePath): array
    {
        return [
            'hash' => md5_file($filePath),
            'timestamp' => time(),
        ];
    }
}
