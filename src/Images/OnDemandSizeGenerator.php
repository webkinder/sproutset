<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

use Throwable;

/**
 * Generates a missing registered image size on demand, on the front-end path.
 *
 * WordPress does not create registered sizes retroactively, so a template that
 * requests a size added after upload would otherwise fall back to the closest
 * existing one. This generates the size once, caps the number of generations
 * per request, and swallows every failure so on-demand image editing can never
 * fatal a request. It uses only front-end-safe media APIs — never the
 * admin-only intermediate-size helpers.
 */
final class OnDemandSizeGenerator
{
    private const MAX_GENERATIONS_PER_REQUEST = 10;

    private int $generations = 0;

    public function ensure(int $attachmentId, string $sizeName): void
    {
        if ($this->generations >= self::MAX_GENERATIONS_PER_REQUEST) {
            return;
        }

        if (image_get_intermediate_size($attachmentId, $sizeName) !== false) {
            return;
        }

        $sizes = wp_get_registered_image_subsizes();

        if (! isset($sizes[$sizeName])) {
            return;
        }

        $this->generations++;

        try {
            $this->generate($attachmentId, $sizeName, $sizes[$sizeName]);
        } catch (Throwable) {
            // Boot-safety: never fatal a request over on-demand image editing.
        }
    }

    /**
     * @param  array<array-key, mixed>  $size
     */
    private function generate(int $attachmentId, string $sizeName, array $size): void
    {
        $rawWidth = $size['width'] ?? 0;
        $rawHeight = $size['height'] ?? 0;
        $width = is_numeric($rawWidth) ? (int) $rawWidth : 0;
        $height = is_numeric($rawHeight) ? (int) $rawHeight : 0;
        $crop = (bool) ($size['crop'] ?? false);

        $file = get_attached_file($attachmentId);

        if ($file === false || ! file_exists($file)) {
            return;
        }

        $editor = wp_get_image_editor($file);

        if (is_wp_error($editor)) {
            return;
        }

        if (is_wp_error($editor->resize($width, $height, $crop))) {
            return;
        }

        $saved = $editor->save();

        if (is_wp_error($saved)) {
            return;
        }

        $metadata = wp_get_attachment_metadata($attachmentId);

        if (! is_array($metadata)) {
            return;
        }

        $metadata['sizes'][$sizeName] = [
            'file' => $saved['file'],
            'width' => $saved['width'],
            'height' => $saved['height'],
            'mime-type' => $saved['mime-type'],
        ];

        wp_update_attachment_metadata($attachmentId, $metadata);
    }
}
