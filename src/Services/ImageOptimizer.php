<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Services;

use Exception;
use Spatie\ImageOptimizer\OptimizerChainFactory;

final class ImageOptimizer
{
    private static ?self $instance = null;

    private readonly \Spatie\ImageOptimizer\OptimizerChain $optimizerChain;

    private array $uploadDir;

    private readonly string $baseDir;

    private function __construct()
    {
        $this->optimizerChain = OptimizerChainFactory::create();
        $this->uploadDir = wp_upload_dir();
        $this->baseDir = trailingslashit($this->uploadDir['basedir']);
    }

    public static function getInstance(): self
    {
        if (! self::$instance instanceof self) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get optimizer binaries configuration.
     *
     * @return array<string, array{name: string, format: string}>
     */
    public static function getOptimizerBinaries(): array
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

    public function optimize(string $imagePath): bool
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

    public function optimizeAndMark(string $imagePath, int $attachmentId): bool
    {
        if (! $this->optimize($imagePath)) {
            return false;
        }

        $this->markAsOptimized($imagePath, $attachmentId);

        return true;
    }

    public function isOptimized(string $imagePath, int $attachmentId): bool
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

    public function markAsOptimized(string $imagePath, int $attachmentId): void
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

    public function getAttachmentIdByPath(string $imagePath): ?int
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

    public function optimizeAttachmentSizes(int $attachmentId, array $metadata): array
    {
        if ($metadata === []) {
            return $metadata;
        }

        $uploadDir = wp_upload_dir();
        $baseDir = trailingslashit($uploadDir['basedir']);

        if (isset($metadata['original_image'])) {
            $pathInfo = pathinfo((string) $metadata['file']);
            $originalPath = $baseDir.$pathInfo['dirname'].'/'.$metadata['original_image'];
            if (file_exists($originalPath) && $this->optimize($originalPath)) {
                $metadata['original_image_sproutset_optimized'] = [
                    'hash' => md5_file($originalPath),
                    'timestamp' => time(),
                ];
            }
        }

        $mainFile = $baseDir.$metadata['file'];
        if (file_exists($mainFile) && $this->optimize($mainFile)) {
            $metadata['sproutset_optimized'] = [
                'hash' => md5_file($mainFile),
                'timestamp' => time(),
            ];
            $metadata['filesize'] = filesize($mainFile);
        }

        if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            $pathInfo = pathinfo((string) $metadata['file']);
            $dirname = $pathInfo['dirname'];

            foreach ($metadata['sizes'] as $sizeName => $sizeData) {
                $sizePath = $baseDir.$dirname.'/'.$sizeData['file'];
                if (file_exists($sizePath) && $this->optimize($sizePath)) {
                    $metadata['sizes'][$sizeName]['sproutset_optimized'] = [
                        'hash' => md5_file($sizePath),
                        'timestamp' => time(),
                    ];
                    $metadata['sizes'][$sizeName]['filesize'] = filesize($sizePath);
                }
            }
        }

        return $metadata;
    }

    public function isBinaryAvailable(string $binaryName): bool
    {
        $command = sprintf('command -v %s > /dev/null 2>&1', escapeshellarg($binaryName));
        exec($command, $output, $returnCode);

        return $returnCode === 0;
    }
}
