<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Support;

final class CronScheduler
{
    public static function canScheduleEvents(): bool
    {
        return function_exists('wp_schedule_single_event');
    }

    public static function isEventAlreadyScheduled(string $hook, array $arguments): bool
    {
        if (! function_exists('wp_next_scheduled')) {
            return false;
        }

        return wp_next_scheduled($hook, $arguments) !== false;
    }

    public static function scheduleSingleEvent(string $hook, array $arguments, int $delayInSeconds): void
    {
        if (! self::canScheduleEvents()) {
            return;
        }

        $delay = max(0, $delayInSeconds);

        wp_schedule_single_event(
            time() + $delay,
            $hook,
            $arguments
        );
    }

    public static function scheduleSingleEventIfNotScheduled(string $hook, array $arguments, int $delayInSeconds): void
    {
        if (! self::canScheduleEvents()) {
            return;
        }

        if (self::isEventAlreadyScheduled($hook, $arguments)) {
            return;
        }

        self::scheduleSingleEvent($hook, $arguments, $delayInSeconds);
    }
}
