<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Admin;

use Webkinder\Sproutset\Images\FocalPoint;
use Webkinder\Sproutset\Images\FocalPointMeta;
use WP_Post;

final class FocalPointMediaField
{
    public function register(): void
    {
        add_filter('attachment_fields_to_edit', $this->addField(...), 10, 2);
        add_filter('attachment_fields_to_save', $this->saveField(...), 10, 2);
        add_action('admin_print_footer_scripts', $this->printAssets(...));
    }

    /**
     * @param  array<string, mixed>  $formFields
     * @return array<string, mixed>
     */
    public function addField(array $formFields, WP_Post $attachment): array
    {
        if (! wp_attachment_is_image($attachment->ID)) {
            return $formFields;
        }

        $focal = FocalPointMeta::read($attachment->ID);
        $x = $focal instanceof FocalPoint ? $focal->x : FocalPointMeta::DEFAULT_PERCENT;
        $y = $focal instanceof FocalPoint ? $focal->y : FocalPointMeta::DEFAULT_PERCENT;
        $preview = wp_get_attachment_image_url($attachment->ID, 'medium') ?: wp_get_attachment_url($attachment->ID);

        $formFields['sproutset_focal_point'] = [
            'label' => __('Focal point', 'sproutset'),
            'input' => 'html',
            'html' => $this->markup((string) $preview, $x, $y),
        ];

        return $formFields;
    }

    /**
     * @param  array<string, mixed>  $post
     * @param  array<string, mixed>  $attachment
     * @return array<string, mixed>
     */
    public function saveField(array $post, array $attachment): array
    {
        $id = is_numeric($post['ID'] ?? null) ? (int) $post['ID'] : 0;

        if ($id <= 0 || ! isset($attachment['sproutset_focal_x'], $attachment['sproutset_focal_y'])) {
            return $post;
        }

        if (! is_numeric($attachment['sproutset_focal_x']) || ! is_numeric($attachment['sproutset_focal_y'])) {
            return $post;
        }

        FocalPointMeta::write($id, (float) $attachment['sproutset_focal_x'], (float) $attachment['sproutset_focal_y']);
        FocalPointMeta::clearApplied($id);

        return $post;
    }

    public function printAssets(): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        if ($screen === null || ! in_array($screen->base, ['post', 'upload', 'media'], true)) {
            return;
        }

        echo $this->styles();
        echo $this->script();
    }

    private function markup(string $preview, float $x, float $y): string
    {
        return sprintf(
            '<div class="sproutset-focal" data-sproutset-focal>'
            .'<div class="sproutset-focal__stage"><img src="%1$s" alt="">'
            .'<span class="sproutset-focal__dot" style="left:%2$s%%;top:%3$s%%"></span></div>'
            .'<input type="hidden" class="sproutset-focal__x" name="attachments[%4$s][sproutset_focal_x]" value="%2$s">'
            .'<input type="hidden" class="sproutset-focal__y" name="attachments[%4$s][sproutset_focal_y]" value="%3$s">'
            .'<p class="description">%5$s</p></div>',
            esc_url($preview),
            esc_attr((string) round($x, 4)),
            esc_attr((string) round($y, 4)),
            '{{ID}}',
            esc_html__('Click or drag to set the focal point used for cropping and positioning.', 'sproutset'),
        );
    }

    private function styles(): string
    {
        return '<style>'
            .'.sproutset-focal__stage{position:relative;display:inline-block;max-width:100%;cursor:crosshair}'
            .'.sproutset-focal__stage img{display:block;max-width:100%;height:auto}'
            .'.sproutset-focal__dot{position:absolute;width:18px;height:18px;margin:-9px 0 0 -9px;border:2px solid #fff;'
            .'border-radius:50%;box-shadow:0 0 0 2px rgba(0,0,0,.5);pointer-events:none}'
            .'</style>';
    }

    private function script(): string
    {
        return <<<'HTML'
<script>
(function () {
    function clamp(value) { return Math.max(0, Math.min(100, value)); }

    function init(wrapper) {
        var stage = wrapper.querySelector('.sproutset-focal__stage');
        var dot = wrapper.querySelector('.sproutset-focal__dot');
        var inputX = wrapper.querySelector('.sproutset-focal__x');
        var inputY = wrapper.querySelector('.sproutset-focal__y');
        if (!stage || !dot || !inputX || !inputY) { return; }

        function setFromEvent(event) {
            var rect = stage.getBoundingClientRect();
            var x = clamp(((event.clientX - rect.left) / rect.width) * 100);
            var y = clamp(((event.clientY - rect.top) / rect.height) * 100);
            dot.style.left = x + '%';
            dot.style.top = y + '%';
            inputX.value = Math.round(x * 10000) / 10000;
            inputY.value = Math.round(y * 10000) / 10000;
            inputX.dispatchEvent(new Event('change', { bubbles: true }));
        }

        var dragging = false;
        stage.addEventListener('pointerdown', function (e) { dragging = true; setFromEvent(e); });
        stage.addEventListener('pointermove', function (e) { if (dragging) { setFromEvent(e); } });
        window.addEventListener('pointerup', function () { dragging = false; });
    }

    document.querySelectorAll('[data-sproutset-focal]').forEach(init);
})();
</script>
HTML;
    }
}
