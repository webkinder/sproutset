<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Managers;

final class AutoSizesManager
{
    private static bool $hasImagesWithAutoSizesDisabled = false;

    public static function registerImageWithAutoSizesDisabled(): void
    {
        self::$hasImagesWithAutoSizesDisabled = true;
    }

    public static function hasImagesWithAutoSizesDisabled(): bool
    {
        return self::$hasImagesWithAutoSizesDisabled;
    }

    public function initializeAutoSizesFilters(): void
    {
        add_filter('wp_content_img_tag', $this->createContentImageTagFilter(), 10, 1);
        add_filter('wp_get_attachment_image_attributes', $this->createAttachmentImageAttributesFilter(), 10, 1);
    }

    private function createContentImageTagFilter(): callable
    {

        return static function (string $image): string {
            if (! self::$hasImagesWithAutoSizesDisabled) {
                return $image;
            }

            return str_replace(' sizes="auto, ', ' sizes="', $image);
        };
    }

    private function createAttachmentImageAttributesFilter(): callable
    {

        return static function (array $attr): array {
            if (! self::$hasImagesWithAutoSizesDisabled) {
                return $attr;
            }

            if (isset($attr['sizes'])) {
                $attr['sizes'] = preg_replace('/^auto, /', '', (string) $attr['sizes']);
            }

            return $attr;
        };
    }
}
