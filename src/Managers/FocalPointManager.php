<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Managers;

use Webkinder\SproutsetPackage\Services\CronOptimizer;
use Webkinder\SproutsetPackage\Services\FocalPointCropper;
use Webkinder\SproutsetPackage\Support\CronScheduler;
use Webkinder\SproutsetPackage\Support\FocalPointConfig;
use Webkinder\SproutsetPackage\Support\FocalPointMetadata;

final class FocalPointManager
{
    private const FOCAL_CROP_CRON_HOOK = 'sproutset_reapply_focal_crop';

    public function initializeFocalPointFeatures(): void
    {
        add_filter('attachment_fields_to_edit', $this->addFocalPointFieldToAttachmentEditForm(...), 10, 2);
        add_filter('attachment_fields_to_save', $this->saveFocalPointFieldFromAttachmentForm(...), 10, 2);
        add_action('admin_head', $this->printFocalPointAssetsInAdminHead(...));
        add_filter('wp_generate_attachment_metadata', $this->maybeApplyFocalCroppingOnUpload(...), 9, 2);
        add_filter('wp_update_attachment_metadata', $this->preserveFocalPointMetadataOnUpdate(...), 10, 2);
        add_action(self::FOCAL_CROP_CRON_HOOK, $this->executeFocalCropCron(...), 10, 1);
    }

    public function executeFocalCropCron(int $attachmentId): void
    {
        if (! wp_attachment_is_image($attachmentId) || ! FocalPointConfig::isEnabled()) {
            return;
        }

        $metadata = wp_get_attachment_metadata($attachmentId);

        if (! is_array($metadata) || $metadata === []) {
            return;
        }

        $updatedMetadata = FocalPointCropper::applyFocalCropToAllSizes(
            $attachmentId,
            $metadata
        );

        wp_update_attachment_metadata($attachmentId, $updatedMetadata);

        if (config('sproutset-config.auto_optimize_images', false)) {
            CronOptimizer::scheduleAttachmentOptimization($attachmentId);
        }
    }

    private function maybeApplyFocalCroppingOnUpload(array $metadata, int $attachmentId): array
    {
        if (! wp_attachment_is_image($attachmentId)) {
            return $metadata;
        }

        if (! FocalPointConfig::isEnabled()) {
            return $metadata;
        }

        $strategy = FocalPointConfig::getStrategy();

        if ($strategy === 'immediate') {
            return FocalPointCropper::applyFocalCropToAllSizes(
                $attachmentId,
                $metadata
            );
        }

        if ($strategy === 'cron') {
            $this->scheduleFocalCropJob($attachmentId);

            return $metadata;
        }

        return $metadata;
    }

    private function addFocalPointFieldToAttachmentEditForm(array $formFields, \WP_Post $attachment): array
    {
        if (! wp_attachment_is_image($attachment->ID)) {
            return $formFields;
        }

        if (! FocalPointConfig::isEnabled()) {
            return $formFields;
        }

        $focalPoint = $this->loadFocalPointMetadata((int) $attachment->ID);
        $fieldIds = $this->buildFocalPointFieldIdentifiers((int) $attachment->ID);

        $previewMarkup = $this->renderFocalPointPreview(
            $attachment,
            $fieldIds['id_x'],
            $fieldIds['name_x'],
            $fieldIds['id_y'],
            $fieldIds['name_y'],
            $focalPoint['x'],
            $focalPoint['y']
        );

        if ($previewMarkup === '') {
            return $formFields;
        }

        $formFields['sproutset_focal_point_preview'] = [
            'label' => __('Focal point', 'webkinder-sproutset'),
            'input' => 'html',
            'html' => $previewMarkup,
        ];

        return $formFields;
    }

    private function saveFocalPointFieldFromAttachmentForm(array $post, array $attachment): array
    {
        $attachmentId = isset($post['ID']) ? (int) $post['ID'] : 0;

        if ($attachmentId <= 0) {
            return $post;
        }

        if (! FocalPointConfig::isEnabled()) {
            return $post;
        }

        $sanitizedValues = $this->sanitizeFocalPointInput($attachment);

        if ($sanitizedValues === []) {
            return $post;
        }

        $this->updateFocalPointMetadata($attachmentId, $sanitizedValues);

        return $post;
    }

    private function loadFocalPointMetadata(int $attachmentId): array
    {
        $metadata = wp_get_attachment_metadata($attachmentId);

        if (! is_array($metadata)) {
            return [
                'x' => FocalPointMetadata::getDefaultPercentAsString(),
                'y' => FocalPointMetadata::getDefaultPercentAsString(),
            ];
        }

        [$xFloat, $yFloat] = FocalPointMetadata::readCoordinatesFromMetadataArray($metadata);

        $x = (string) $xFloat;
        $y = (string) $yFloat;

        return [
            'x' => $x,
            'y' => $y,
        ];
    }

    private function buildFocalPointFieldIdentifiers(int $attachmentId): array
    {
        $idPrefix = 'attachments-'.$attachmentId.'-';
        $namePrefix = "attachments[$attachmentId]";

        return [
            'id_x' => $idPrefix.FocalPointMetadata::META_KEY_X,
            'name_x' => $namePrefix.'['.FocalPointMetadata::META_KEY_X.']',
            'id_y' => $idPrefix.FocalPointMetadata::META_KEY_Y,
            'name_y' => $namePrefix.'['.FocalPointMetadata::META_KEY_Y.']',
        ];
    }

    private function sanitizeFocalPointInput(array $attachment): array
    {
        $keys = [FocalPointMetadata::META_KEY_X, FocalPointMetadata::META_KEY_Y];
        $sanitized = [];

        foreach ($keys as $key) {
            if (! array_key_exists($key, $attachment)) {
                continue;
            }

            $rawValue = $attachment[$key];

            if ($rawValue === '') {
                $sanitized[$key] = FocalPointMetadata::getDefaultPercentAsString();

                continue;
            }

            if (! is_numeric($rawValue)) {
                continue;
            }

            $floatValue = (float) $rawValue;
            $clampedValue = max((int) FocalPointMetadata::MIN_PERCENT, min((int) FocalPointMetadata::MAX_PERCENT, $floatValue));
            $sanitized[$key] = (string) $clampedValue;
        }

        return $sanitized;
    }

    private function preserveFocalPointMetadataOnUpdate(array $newMetadata, int $attachmentId): array
    {

        if (! wp_attachment_is_image($attachmentId) || ! FocalPointConfig::isEnabled()) {
            return $newMetadata;
        }

        $existingMetadata = wp_get_attachment_metadata($attachmentId);

        if (! is_array($existingMetadata)) {
            return $newMetadata;
        }

        foreach ([FocalPointMetadata::META_KEY_X, FocalPointMetadata::META_KEY_Y] as $key) {
            if (isset($existingMetadata[$key]) && ! isset($newMetadata[$key])) {
                $newMetadata[$key] = $existingMetadata[$key];
            }
        }

        return $newMetadata;
    }

    private function updateFocalPointMetadata(int $attachmentId, array $sanitizedValues): void
    {
        $metadata = wp_get_attachment_metadata($attachmentId);

        if (! is_array($metadata)) {
            $metadata = [];
        }

        foreach ($sanitizedValues as $key => $value) {
            $metadata[$key] = $value;
        }

        if (! wp_attachment_is_image($attachmentId) || ! FocalPointConfig::isEnabled()) {
            wp_update_attachment_metadata($attachmentId, $metadata);

            return;
        }

        $strategy = FocalPointConfig::getStrategy();

        if ($strategy === 'immediate') {
            $metadata = FocalPointCropper::applyFocalCropToAllSizes(
                $attachmentId,
                $metadata
            );

            wp_update_attachment_metadata($attachmentId, $metadata);

            if (config('sproutset-config.auto_optimize_images', false)) {
                CronOptimizer::scheduleAttachmentOptimization($attachmentId);
            }

            return;
        }

        if ($strategy === 'cron') {
            $this->scheduleFocalCropJob($attachmentId);

            wp_update_attachment_metadata($attachmentId, $metadata);

            return;
        }

        wp_update_attachment_metadata($attachmentId, $metadata);
    }

    private function scheduleFocalCropJob(int $attachmentId): void
    {
        CronScheduler::scheduleSingleEventIfNotScheduled(
            self::FOCAL_CROP_CRON_HOOK,
            [$attachmentId],
            FocalPointConfig::getDelayInSeconds()
        );
    }

    private function renderFocalPointPreview(\WP_Post $attachment, string $inputIdX, string $inputNameX, string $inputIdY, string $inputNameY, string $focalPointX, string $focalPointY): string
    {
        $previewUrl = wp_get_attachment_image_url($attachment->ID, 'large');

        if (! is_string($previewUrl)) {
            $previewUrl = wp_get_attachment_url($attachment->ID);
        }

        if (! is_string($previewUrl) || $previewUrl === '') {
            return '';
        }

        ob_start();

        ?>
        <div class="sproutset-focal-preview">
            <div
                class="sproutset-focal-wrapper"
                data-field-x-id="<?php echo esc_attr($inputIdX); ?>"
                data-field-y-id="<?php echo esc_attr($inputIdY); ?>"
                data-initial-x="<?php echo esc_attr($focalPointX); ?>"
                data-initial-y="<?php echo esc_attr($focalPointY); ?>"
            >
                <img
                    class="sproutset-focal-image"
                    src="<?php echo esc_url($previewUrl); ?>"
                />
                <button
                    type="button"
                    class="sproutset-focal-handle"
                    aria-label="<?php echo esc_attr__('Drag the handle to adjust the focal point.', 'webkinder-sproutset'); ?>"
                ></button>
            </div>
            <p class="description"><?php echo esc_html__('Drag the handle to adjust the focal point.', 'webkinder-sproutset'); ?></p>
            <div class="sproutset-focal-coordinates">
                <label class="sproutset-focal-coordinate">
                    <span><?php echo esc_html__('X (%)', 'webkinder-sproutset'); ?></span>
                    <input
                        type="number"
                        id="<?php echo esc_attr($inputIdX); ?>"
                        name="<?php echo esc_attr($inputNameX); ?>"
                        value="<?php echo esc_attr($focalPointX); ?>"
                        min="0"
                        max="100"
                        step="0.1"
                        readonly
                    />
                </label>
                <label class="sproutset-focal-coordinate">
                    <span><?php echo esc_html__('Y (%)', 'webkinder-sproutset'); ?></span>
                    <input
                        type="number"
                        id="<?php echo esc_attr($inputIdY); ?>"
                        name="<?php echo esc_attr($inputNameY); ?>"
                        value="<?php echo esc_attr($focalPointY); ?>"
                        min="0"
                        max="100"
                        step="0.1"
                        readonly
                    />
                </label>
            </div>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    private function getFocalPointStylesMarkup(): string
    {
        return <<< 'STYLE'
        <style id="sproutset-focal-point-styles">
            .sproutset-focal-preview {
                margin: 12px 0;
            }

            .sproutset-focal-wrapper {
                position: relative;
                display: inline-block;
                max-width: 100%;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                overflow: hidden;
                background: #f8f9f9;
            }

            .sproutset-focal-image {
                display: block;
                max-width: 100%;
                height: auto;
                user-select: none;
            }

            .sproutset-focal-handle {
                position: absolute;
                width: 20px;
                height: 20px;
                border-radius: 50%;
                border: 2px solid #ffffff;
                background: rgba(0, 0, 0, 0.7);
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.35);
                transform: translate(-50%, -50%);
                cursor: grab;
                padding: 0;
            }

            .sproutset-focal-handle:active {
                cursor: grabbing;
            }

            .sproutset-focal-coordinates {
                display: flex;
                justify-content: flex-end;
                gap: 8px;
                margin-top: 8px;
            }

            .sproutset-focal-coordinate {
                display: flex;
                flex-direction: column;
                justify-content: start;
                gap: 2px;
                font-size: 11px;
                color: #555d66;
                width: 50%;

                > span {
                    padding-top: 0 !important;
                    text-align: left !important;
                }
            }

            .sproutset-focal-preview .description {
                margin-top: 2px;
            }
        </style>
        STYLE;
    }

    private function getFocalPointScriptMarkup(): string
    {
        return <<< 'SCRIPT'
        <script id="sproutset-focal-point-script">
            (function() {
                const initializedWrappers = new WeakSet();

                function clamp(value) {
                    if (Number.isNaN(value)) {
                        return 50;
                    }

                    return Math.min(100, Math.max(0, value));
                }

                function initWrapper(wrapper) {
                    if (initializedWrappers.has(wrapper)) {
                        return;
                    }

                    const fieldX = document.getElementById(wrapper.dataset.fieldXId);
                    const fieldY = document.getElementById(wrapper.dataset.fieldYId);
                    const handle = wrapper.querySelector('.sproutset-focal-handle');

                    if (!fieldX || !fieldY || !handle || !window.PointerEvent) {
                        return;
                    }

                    const rectCache = { width: 0, height: 0, left: 0, top: 0 };

                    function updateRectCache() {
                        const rect = wrapper.getBoundingClientRect();

                        rectCache.width = rect.width;
                        rectCache.height = rect.height;
                        rectCache.left = rect.left;
                        rectCache.top = rect.top;
                    }

                    function setHandlePosition(x, y) {
                        handle.style.left = x + '%';
                        handle.style.top = y + '%';
                    }

                    function syncHandleWithInputs() {
                        const x = clamp(parseFloat(fieldX.value));
                        const y = clamp(parseFloat(fieldY.value));

                        setHandlePosition(x, y);
                    }

                    function updateInputsFromPosition(x, y) {
                        const roundedX = Math.round(x * 10) / 10;
                        const roundedY = Math.round(y * 10) / 10;

                        fieldX.value = roundedX.toString();
                        fieldY.value = roundedY.toString();
                    }

                    function setPositionFromClient(clientX, clientY) {
                        updateRectCache();

                        const percentX = ((clientX - rectCache.left) / rectCache.width) * 100;
                        const percentY = ((clientY - rectCache.top) / rectCache.height) * 100;
                        const clampedX = clamp(percentX);
                        const clampedY = clamp(percentY);

                        setHandlePosition(clampedX, clampedY);
                        updateInputsFromPosition(clampedX, clampedY);
                    }

                    let activePointerId = null;
                    let changeTimeoutId = null;

                    function dispatchFieldChanges() {
                        fieldX.dispatchEvent(new Event('change', { bubbles: true }));
                        fieldY.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    function scheduleChangeDispatch() {
                        if (changeTimeoutId !== null) {
                            window.clearTimeout(changeTimeoutId);
                        }

                        changeTimeoutId = window.setTimeout(function() {
                            changeTimeoutId = null;
                            dispatchFieldChanges();
                        }, 250);
                    }

                    function handlePointerDown(event) {
                        if (event.button !== undefined && event.button !== 0) {
                            return;
                        }

                        event.preventDefault();
                        activePointerId = event.pointerId ?? null;

                        if (handle.setPointerCapture && event.pointerId !== undefined) {
                            handle.setPointerCapture(event.pointerId);
                        }

                        setPositionFromClient(event.clientX, event.clientY);

                        window.addEventListener('pointermove', handlePointerMove);
                        window.addEventListener('pointerup', handlePointerUp);
                    }

                    function handlePointerMove(event) {
                        if (activePointerId !== null && event.pointerId !== activePointerId) {
                            return;
                        }

                        event.preventDefault();
                        setPositionFromClient(event.clientX, event.clientY);
                    }

                    function handlePointerUp(event) {
                        if (activePointerId !== null && event.pointerId !== activePointerId) {
                            return;
                        }

                        if (handle.releasePointerCapture && event.pointerId !== undefined) {
                            handle.releasePointerCapture(event.pointerId);
                        }

                        activePointerId = null;
                        window.removeEventListener('pointermove', handlePointerMove);
                        window.removeEventListener('pointerup', handlePointerUp);
                        scheduleChangeDispatch();
                    }

                    wrapper.addEventListener('pointerdown', handlePointerDown);
                    handle.addEventListener('pointerdown', handlePointerDown);

                    ['input', 'change'].forEach(function(eventName) {
                        fieldX.addEventListener(eventName, syncHandleWithInputs);
                        fieldY.addEventListener(eventName, syncHandleWithInputs);
                    });

                    const initialX = clamp(parseFloat(wrapper.dataset.initialX));
                    const initialY = clamp(parseFloat(wrapper.dataset.initialY));

                    fieldX.value = initialX.toString();
                    fieldY.value = initialY.toString();

                    setHandlePosition(initialX, initialY);

                    initializedWrappers.add(wrapper);
                }

                function initAllWrappers(root) {
                    const scope = root || document;
                    scope.querySelectorAll('.sproutset-focal-wrapper').forEach(initWrapper);
                }

                document.addEventListener('DOMContentLoaded', function() {
                    initAllWrappers(document);

                    const observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            mutation.addedNodes.forEach(function(node) {
                                if (node.nodeType !== Node.ELEMENT_NODE) {
                                    return;
                                }

                                initAllWrappers(node);
                            });
                        });
                    });

                    observer.observe(document.body, {
                        childList: true,
                        subtree: true
                    });
                });
            })();
        </script>
        SCRIPT;
    }

    private function printFocalPointAssetsInAdminHead(): void
    {
        if (! FocalPointConfig::isEnabled()) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        if ($screen !== null && isset($screen->base)) {
            $allowedBases = ['upload', 'media', 'post', 'post-new', 'customize', 'site-editor'];

            if (! in_array($screen->base, $allowedBases, true)) {
                return;
            }
        }

        echo $this->getFocalPointStylesMarkup().$this->getFocalPointScriptMarkup();
    }
}
