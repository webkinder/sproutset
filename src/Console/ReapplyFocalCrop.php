<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Console;

use Roots\Acorn\Console\Commands\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Webkinder\SproutsetPackage\Services\CronOptimizer;
use Webkinder\SproutsetPackage\Services\FocalPointCropper;
use Webkinder\SproutsetPackage\Support\FocalPointConfig;

final class ReapplyFocalCrop extends Command
{
    protected $signature = 'sproutset:reapply-focal-crop {--optimize : Schedule optimization for recropped attachments}';

    protected $description = 'Reapply Sproutset focal cropping to all image attachments.';

    public function handle(): int
    {
        if (! FocalPointConfig::isEnabled()) {
            $this->info('Focal point cropping is disabled. Nothing to do.');

            return self::SUCCESS;
        }

        $attachmentIds = $this->discoverImageAttachments();

        if ($attachmentIds === []) {
            $this->info('No image attachments found.');

            return self::SUCCESS;
        }

        $this->line('Reapplying focal cropping to image sizes...');
        $this->newLine();

        $progressBar = $this->createProgressBar(count($attachmentIds));
        $progressBar->start();

        $shouldOptimize = (bool) $this->option('optimize');

        foreach ($attachmentIds as $attachmentId) {
            $metadata = wp_get_attachment_metadata($attachmentId);

            if (! is_array($metadata) || $metadata === [] || ! isset($metadata['sizes']) || ! is_array($metadata['sizes'])) {
                $progressBar->advance();

                continue;
            }

            $updatedMetadata = FocalPointCropper::applyFocalCropToAllSizes(
                $attachmentId,
                $metadata
            );

            wp_update_attachment_metadata($attachmentId, $updatedMetadata);

            if ($shouldOptimize) {
                CronOptimizer::executeAttachmentOptimization($attachmentId);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->newLine();
        $this->info('Focal cropping reapplication completed.');

        return self::SUCCESS;
    }

    private function discoverImageAttachments(): array
    {
        $ids = get_posts([
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
        ]);

        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_map(static fn ($id): int => (int) $id, $ids));
    }

    private function createProgressBar(int $total): ProgressBar
    {
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $progressBar->setMessage('Processing attachments...');

        return $progressBar;
    }
}
