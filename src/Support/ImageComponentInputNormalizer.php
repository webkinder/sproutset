<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Support;

final class ImageComponentInputNormalizer
{
    private const DEFAULT_SIZE_NAME = 'large';

    private const DEFAULT_DECODING_MODE = 'async';

    private const ALLOWED_DECODING_MODES = ['async', 'sync', 'auto'];

    public static function normalize(
        mixed $attachmentId,
        mixed $sizeName = self::DEFAULT_SIZE_NAME,
        mixed $sizes = null,
        mixed $alt = null,
        mixed $width = null,
        mixed $height = null,
        mixed $class = null,
        mixed $useLazyLoading = true,
        mixed $decodingMode = self::DEFAULT_DECODING_MODE,
        mixed $useAutoSizes = true,
        mixed $focalPoint = false,
        mixed $focalPointX = null,
        mixed $focalPointY = null,
    ): NormalizedImageInput {
        return new NormalizedImageInput(
            attachmentId: self::normalizeAttachmentId($attachmentId),
            sizeName: self::normalizeSizeName($sizeName),
            sizes: self::normalizeNullableString($sizes),
            alt: self::normalizeNullableString($alt),
            width: self::normalizeNullableInt($width),
            height: self::normalizeNullableInt($height),
            class: self::normalizeNullableString($class),
            useLazyLoading: self::normalizeBool($useLazyLoading, true),
            decodingMode: self::normalizeDecodingMode($decodingMode),
            useAutoSizes: self::normalizeBool($useAutoSizes, true),
            focalPoint: self::normalizeBool($focalPoint, false),
            focalPointX: self::normalizeNullableFloat($focalPointX),
            focalPointY: self::normalizeNullableFloat($focalPointY),
        );
    }

    public static function normalizeAttachmentId(mixed $value): int
    {
        if ($value === null) {
            return 0;
        }

        if (is_int($value)) {
            return max(0, $value);
        }

        if (is_string($value) && is_numeric($value)) {
            return max(0, (int) $value);
        }

        if (is_float($value)) {
            return max(0, (int) $value);
        }

        return 0;
    }

    public static function normalizeSizeName(mixed $value): string
    {
        if (! is_string($value)) {
            return self::DEFAULT_SIZE_NAME;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : self::DEFAULT_SIZE_NAME;
    }

    public static function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            if (is_scalar($value)) {
                $value = (string) $value;
            } else {
                return null;
            }
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    public static function normalizeNullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) $value;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '' || ! is_numeric($trimmed)) {
                return null;
            }

            return (int) $trimmed;
        }

        return null;
    }

    public static function normalizeBool(mixed $value, bool $default): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null) {
            return $default;
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        if (is_string($value)) {
            $lower = strtolower(trim($value));

            if (in_array($lower, ['true', '1', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($lower, ['false', '0', 'no', 'off', ''], true)) {
                return false;
            }

            return $default;
        }

        return $default;
    }

    public static function normalizeDecodingMode(mixed $value): string
    {
        if (! is_string($value)) {
            return self::DEFAULT_DECODING_MODE;
        }

        $trimmed = strtolower(trim($value));

        return in_array($trimmed, self::ALLOWED_DECODING_MODES, true)
            ? $trimmed
            : self::DEFAULT_DECODING_MODE;
    }

    public static function normalizeNullableFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_float($value)) {
            return $value;
        }

        if (is_int($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '' || ! is_numeric($trimmed)) {
                return null;
            }

            return (float) $trimmed;
        }

        return null;
    }
}
