<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

final class MediaSettingsLock
{
    /**
     * @var array<string, list<string>>
     */
    private const array INPUT_MAP = [
        'thumbnail' => ['thumbnail_size_w', 'thumbnail_size_h', 'thumbnail_crop'],
        'medium' => ['medium_size_w', 'medium_size_h'],
        'large' => ['large_size_w', 'large_size_h'],
    ];

    /**
     * @param  array<array-key, mixed>  $rawConfig
     * @return list<string>
     */
    public function lockedInputIds(array $rawConfig): array
    {
        $ids = [];

        foreach (self::INPUT_MAP as $sizeName => $inputIds) {
            if (isset($rawConfig[$sizeName]) && is_array($rawConfig[$sizeName])) {
                $ids = array_merge($ids, $inputIds);
            }
        }

        return $ids;
    }

    /**
     * @param  array<array-key, mixed>  $rawConfig
     */
    public function register(array $rawConfig): void
    {
        $inputIds = $this->lockedInputIds($rawConfig);

        if ($inputIds === []) {
            return;
        }

        add_action('admin_footer-options-media.php', function () use ($inputIds): void {
            $this->printLockScript($inputIds);
        });
    }

    /**
     * @param  list<string>  $inputIds
     */
    private function printLockScript(array $inputIds): void
    {
        $ids = wp_json_encode($inputIds);
        $note = wp_json_encode(
            __('These sizes are managed by Sproutset configuration and cannot be edited here.', 'sproutset'),
        );
        ?>
        <script>
        (function () {
            var ids = <?php echo $ids; ?>;
            var note = <?php echo $note; ?>;
            ids.forEach(function (id) {
                var el = document.getElementById(id);
                if (el) {
                    el.setAttribute('disabled', 'disabled');
                }
            });
            var anchor = document.getElementById(ids[0]);
            var table = anchor ? anchor.closest('table.form-table') : null;
            if (table && table.parentNode) {
                var description = document.createElement('p');
                description.className = 'description';
                description.textContent = note;
                table.parentNode.insertBefore(description, table);
            }
        })();
        </script>
        <?php
    }
}
