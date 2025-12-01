<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Support;

final class FocalPointConfig
{
    private const STRATEGY_IMMEDIATE = 'immediate';

    private const STRATEGY_CRON = 'cron';

    private const DEFAULT_STRATEGY = self::STRATEGY_IMMEDIATE;

    private const DEFAULT_DELAY_SECONDS = 30;

    public static function isEnabled(): bool
    {
        $config = config('sproutset-config.focal_point_cropping', null);

        return is_array($config) || $config === true;
    }

    public static function getStrategy(): string
    {
        $config = config('sproutset-config.focal_point_cropping', null);

        if ($config === true) {
            return self::STRATEGY_IMMEDIATE;
        }

        if (is_array($config) && isset($config['strategy']) && is_string($config['strategy'])) {
            $strategy = mb_strtolower($config['strategy']);

            if (in_array($strategy, [self::STRATEGY_IMMEDIATE, self::STRATEGY_CRON], true)) {
                return $strategy;
            }
        }

        if (self::isEnabled()) {
            return self::STRATEGY_IMMEDIATE;
        }

        return self::DEFAULT_STRATEGY;
    }

    public static function getDelayInSeconds(): int
    {
        $config = config('sproutset-config.focal_point_cropping', null);

        $delay = self::DEFAULT_DELAY_SECONDS;

        if (is_array($config) && array_key_exists('delay_seconds', $config) && is_numeric($config['delay_seconds'])) {
            $candidate = (int) $config['delay_seconds'];

            if ($candidate >= 0) {
                $delay = $candidate;
            }
        }

        if ($delay < 0) {
            return 0;
        }

        return $delay;
    }
}
