<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Managers;

final class FocalPointManager
{
    private const META_KEY_X = 'sproutset_focal_point_x';

    private const META_KEY_Y = 'sproutset_focal_point_y';

    private const DEFAULT_PERCENT = '50';

    private const MIN_PERCENT = 0;

    private const MAX_PERCENT = 100;

    public function initializeFocalPointFeatures(): void
    {
        add_filter('attachment_fields_to_edit', $this->handleAttachmentFieldsToEdit(...), 10, 2);
        add_filter('attachment_fields_to_save', $this->handleAttachmentFieldsToSave(...), 10, 2);
        add_action('admin_head', $this->printFocalPointAssetsInAdminHead(...));
    }

    private function handleAttachmentFieldsToEdit(array $formFields, \WP_Post $attachment): array
    {
        if (! wp_attachment_is_image($attachment->ID)) {
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

    private function handleAttachmentFieldsToSave(array $post, array $attachment): array
    {
        $attachmentId = isset($post['ID']) ? (int) $post['ID'] : 0;

        if ($attachmentId <= 0) {
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
                'x' => self::DEFAULT_PERCENT,
                'y' => self::DEFAULT_PERCENT,
            ];
        }

        $x = isset($metadata[self::META_KEY_X]) ? (string) $metadata[self::META_KEY_X] : self::DEFAULT_PERCENT;
        $y = isset($metadata[self::META_KEY_Y]) ? (string) $metadata[self::META_KEY_Y] : self::DEFAULT_PERCENT;

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
            'id_x' => $idPrefix.self::META_KEY_X,
            'name_x' => $namePrefix.'['.self::META_KEY_X.']',
            'id_y' => $idPrefix.self::META_KEY_Y,
            'name_y' => $namePrefix.'['.self::META_KEY_Y.']',
        ];
    }

    private function sanitizeFocalPointInput(array $attachment): array
    {
        $keys = [self::META_KEY_X, self::META_KEY_Y];
        $sanitized = [];

        foreach ($keys as $key) {
            if (! array_key_exists($key, $attachment)) {
                continue;
            }

            $rawValue = $attachment[$key];

            if ($rawValue === '') {
                $sanitized[$key] = self::DEFAULT_PERCENT;

                continue;
            }

            if (! is_numeric($rawValue)) {
                continue;
            }

            $floatValue = (float) $rawValue;
            $clampedValue = max(self::MIN_PERCENT, min(self::MAX_PERCENT, $floatValue));
            $sanitized[$key] = (string) $clampedValue;
        }

        return $sanitized;
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

        wp_update_attachment_metadata($attachmentId, $metadata);
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
                    aria-label="<?php echo esc_attr__('Drag to set focal point', 'webkinder-sproutset'); ?>"
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
        echo $this->getFocalPointStylesMarkup().$this->getFocalPointScriptMarkup();
    }
}
