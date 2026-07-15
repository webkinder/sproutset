<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Console;

use Roots\Acorn\Console\Commands\Command;
use Webkinder\SproutsetPackage\Services\AvifSupportDetector;

final class RegenerateMissingSizes extends Command
{
    protected $signature = 'sproutset:regenerate-missing-sizes {--dry-run : List affected attachments without regenerating} {--force : Regenerate all raster image attachments}';

    protected $description = 'Regenerate sub-sizes for image attachments that have none.';

    public function handle(): int
    {
        AvifSupportDetector::clearCache();

        $candidates = $this->findCandidates();

        if ($candidates === []) {
            $this->info('No image attachments need regeneration.');

            return self::SUCCESS;
        }

        if ((bool) $this->option('dry-run')) {
            $this->reportDryRun($candidates);

            return self::SUCCESS;
        }

        $this->regenerate($candidates);

        return self::SUCCESS;
    }

    private function findCandidates(): array
    {
        $force = (bool) $this->option('force');

        $ids = get_posts([
            'post_type' => 'attachment',
            'post_mime_type' => ['image/jpeg', 'image/png', 'image/webp', 'image/avif', 'image/gif'],
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);

        $candidates = [];

        foreach ($ids as $id) {
            $attachmentId = (int) $id;

            if ($force || $this->needsRegeneration($attachmentId)) {
                $candidates[] = $attachmentId;
            }
        }

        return $candidates;
    }

    private function needsRegeneration(int $attachmentId): bool
    {
        $metadata = wp_get_attachment_metadata($attachmentId);

        if (! is_array($metadata)) {
            return true;
        }

        if (! empty($metadata['sizes'])) {
            return false;
        }

        $width = isset($metadata['width']) ? (int) $metadata['width'] : 0;
        $height = isset($metadata['height']) ? (int) $metadata['height'] : 0;

        if ($width === 0 || $height === 0) {
            return false;
        }

        [$minWidth, $minHeight] = $this->smallestConfiguredSize();

        return $width > $minWidth || $height > $minHeight;
    }

    private function smallestConfiguredSize(): array
    {
        $sizes = config('sproutset-config.image_sizes', []);
        $minWidth = PHP_INT_MAX;
        $minHeight = PHP_INT_MAX;

        foreach ($sizes as $size) {
            $width = isset($size['width']) ? (int) $size['width'] : 0;
            $height = isset($size['height']) ? (int) $size['height'] : 0;

            if ($width > 0) {
                $minWidth = min($minWidth, $width);
            }

            if ($height > 0) {
                $minHeight = min($minHeight, $height);
            }
        }

        return [
            $minWidth === PHP_INT_MAX ? 0 : $minWidth,
            $minHeight === PHP_INT_MAX ? 0 : $minHeight,
        ];
    }

    private function reportDryRun(array $candidates): void
    {
        $this->line(sprintf('Found <info>%d</info> attachment(s) that would be regenerated:', count($candidates)));

        foreach ($candidates as $attachmentId) {
            $file = get_attached_file($attachmentId);
            $this->line(sprintf('  #%d %s', $attachmentId, $file !== false ? $file : '(missing file)'));
        }
    }

    private function regenerate(array $candidates): void
    {
        require_once ABSPATH.'wp-admin/includes/image.php';

        $progressBar = $this->output->createProgressBar(count($candidates));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $progressBar->start();

        $statistics = ['regenerated' => 0, 'skipped' => 0];

        foreach ($candidates as $attachmentId) {
            $file = get_attached_file($attachmentId);
            $progressBar->setMessage($file !== false ? basename($file) : "#{$attachmentId}");

            if ($file === false || ! file_exists($file)) {
                $statistics['skipped']++;
                $progressBar->advance();

                continue;
            }

            $metadata = wp_generate_attachment_metadata($attachmentId, $file);

            if (is_array($metadata) && ! empty($metadata['sizes'])) {
                wp_update_attachment_metadata($attachmentId, $metadata);
                $statistics['regenerated']++;
            } else {
                $statistics['skipped']++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
        $this->line(sprintf('Regenerated: <info>%d</info>', $statistics['regenerated']));

        if ($statistics['skipped'] > 0) {
            $this->line(sprintf('Skipped: <comment>%d</comment>', $statistics['skipped']));
        }
    }
}
