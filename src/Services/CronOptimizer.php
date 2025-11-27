<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Services;

use Webkinder\SproutsetPackage\Support\CronScheduler;

final class CronOptimizer
{
    private const ATTACHMENT_OPTIMIZATION_HOOK = 'sproutset_optimize_attachment';

    private const SINGLE_IMAGE_OPTIMIZATION_HOOK = 'sproutset_optimize_image';

    public static function initializeHooks(): void
    {
        add_action(self::ATTACHMENT_OPTIMIZATION_HOOK, self::executeAttachmentOptimization(...), 10, 1);
        add_action(self::SINGLE_IMAGE_OPTIMIZATION_HOOK, self::executeSingleImageOptimization(...), 10, 2);
    }

    public static function scheduleAttachmentOptimization(int $attachmentId): void
    {
        $eventArguments = [$attachmentId];

        CronScheduler::scheduleSingleEventIfNotScheduled(
            self::ATTACHMENT_OPTIMIZATION_HOOK,
            $eventArguments,
            30
        );
    }

    public static function scheduleImageOptimization(string $imagePath, int $attachmentId): void
    {
        $eventArguments = [$imagePath, $attachmentId];

        CronScheduler::scheduleSingleEventIfNotScheduled(
            self::SINGLE_IMAGE_OPTIMIZATION_HOOK,
            $eventArguments,
            30
        );
    }

    public static function executeAttachmentOptimization(int $attachmentId): void
    {
        $metadata = wp_get_attachment_metadata($attachmentId);

        if (! is_array($metadata) || $metadata === []) {
            return;
        }

        $optimizer = ImageOptimizer::getInstance();
        $optimizedMetadata = $optimizer->optimizeAllAttachmentVariants($attachmentId, $metadata);

        wp_update_attachment_metadata($attachmentId, $optimizedMetadata);
    }

    public static function executeSingleImageOptimization(string $imagePath, int $attachmentId): void
    {
        if (! file_exists($imagePath)) {
            return;
        }

        $optimizer = ImageOptimizer::getInstance();
        $optimizer->optimizeAndRecordInMetadata($imagePath, $attachmentId);
    }

    public static function clearAllScheduledOptimizationJobs(): void
    {
        wp_clear_scheduled_hook(self::ATTACHMENT_OPTIMIZATION_HOOK);
        wp_clear_scheduled_hook(self::SINGLE_IMAGE_OPTIMIZATION_HOOK);
    }
}
