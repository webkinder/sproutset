<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Services;

final class CronOptimizer
{
    private const string HOOK_OPTIMIZE_ATTACHMENT = 'sproutset_optimize_attachment';

    private const string HOOK_OPTIMIZE_IMAGE = 'sproutset_optimize_image';

    public static function init(): void
    {
        add_action(self::HOOK_OPTIMIZE_ATTACHMENT, self::processAttachmentOptimization(...), 10, 2);
        add_action(self::HOOK_OPTIMIZE_IMAGE, self::processImageOptimization(...), 10, 2);
    }

    public static function scheduleAttachmentOptimization(int $attachmentId, array $metadata): void
    {
        if (! function_exists('wp_schedule_single_event')) {
            return;
        }

        $args = [$attachmentId, $metadata];

        if (wp_next_scheduled(self::HOOK_OPTIMIZE_ATTACHMENT, $args)) {
            return;
        }

        wp_schedule_single_event(
            time() + 30,
            self::HOOK_OPTIMIZE_ATTACHMENT,
            $args
        );
    }

    public static function scheduleImageOptimization(string $imagePath, int $attachmentId): void
    {
        if (! function_exists('wp_schedule_single_event')) {
            return;
        }

        $args = [$imagePath, $attachmentId];

        if (wp_next_scheduled(self::HOOK_OPTIMIZE_IMAGE, $args)) {
            return;
        }

        wp_schedule_single_event(
            time() + 30,
            self::HOOK_OPTIMIZE_IMAGE,
            $args
        );
    }

    public static function processAttachmentOptimization(int $attachmentId, array $metadata): void
    {
        if ($metadata === []) {
            return;
        }

        $optimizer = ImageOptimizer::getInstance();
        $optimizedMetadata = $optimizer->optimizeAttachmentSizes($attachmentId, $metadata);

        wp_update_attachment_metadata($attachmentId, $optimizedMetadata);
    }

    public static function processImageOptimization(string $imagePath, int $attachmentId): void
    {
        if (! file_exists($imagePath)) {
            return;
        }

        $optimizer = ImageOptimizer::getInstance();
        $optimizer->optimizeAndMark($imagePath, $attachmentId);
    }

    public static function clearScheduledJobs(): void
    {
        wp_clear_scheduled_hook(self::HOOK_OPTIMIZE_ATTACHMENT);
        wp_clear_scheduled_hook(self::HOOK_OPTIMIZE_IMAGE);
    }
}
