<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Support;

enum SyncStrategy: string
{
    case REQUEST = 'request';
    case ADMIN_REQUEST = 'admin_request';
    case CRON = 'cron';
    case MANUAL = 'manual';

    public static function fromString(mixed $value): ?self
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = mb_strtolower($value);

        foreach (self::cases() as $case) {
            if ($case->value === $normalized) {
                return $case;
            }
        }

        return null;
    }
}
