<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Services;

final class CronOptimizer
{
    private const ATTACHMENT_OPTIMIZATION_HOOK = 'sproutset_optimize_attachment';

    private const SINGLE_IMAGE_OPTIMIZATION_HOOK = 'sproutset_optimize_image';

    public static function initializeHooks(): void
    {
        add_action(self::ATTACHMENT_OPTIMIZATION_HOOK, self::executeAttachmentOptimization(...), 10, 2);
        add_action(self::SINGLE_IMAGE_OPTIMIZATION_HOOK, self::executeSingleImageOptimization(...), 10, 2);
    }

    public static function scheduleAttachmentOptimization(int $attachmentId, array $metadata): void
    {
        if (! self::canScheduleEvents()) {
            return;
        }

        $eventArguments = [$attachmentId, $metadata];

        if (self::isEventAlreadyScheduled(self::ATTACHMENT_OPTIMIZATION_HOOK, $eventArguments)) {
            return;
        }

        self::scheduleOptimizationEvent(self::ATTACHMENT_OPTIMIZATION_HOOK, $eventArguments);
    }

    public static function scheduleImageOptimization(string $imagePath, int $attachmentId): void
    {
        if (! self::canScheduleEvents()) {
            return;
        }

        $eventArguments = [$imagePath, $attachmentId];

        if (self::isEventAlreadyScheduled(self::SINGLE_IMAGE_OPTIMIZATION_HOOK, $eventArguments)) {
            return;
        }

        self::scheduleOptimizationEvent(self::SINGLE_IMAGE_OPTIMIZATION_HOOK, $eventArguments);
    }

    public static function executeAttachmentOptimization(int $attachmentId, array $metadata): void
    {
        if ($metadata === []) {
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

    private static function canScheduleEvents(): bool
    {
        return function_exists('wp_schedule_single_event');
    }

    private static function isEventAlreadyScheduled(string $hook, array $arguments): bool
    {
        return wp_next_scheduled($hook, $arguments) !== false;
    }

    private static function scheduleOptimizationEvent(string $hook, array $arguments): void
    {
        $delayInSeconds = 30;

        wp_schedule_single_event(
            time() + $delayInSeconds,
            $hook,
            $arguments
        );
    }
}
