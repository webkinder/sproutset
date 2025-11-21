<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Console;

use Roots\Acorn\Console\Commands\Command;
use Webkinder\SproutsetPackage\Managers\ImageSizeManager;

final class SyncImageSizes extends Command
{
    protected $signature = 'sproutset:sync-image-sizes {--force : Force synchronization even if no config changes are detected}';

    protected $description = 'Synchronize WordPress image size options with Sproutset configuration.';

    private readonly ImageSizeManager $imageSizeManager;

    public function __construct()
    {
        parent::__construct();

        $this->imageSizeManager = new ImageSizeManager();
    }

    public function handle(): int
    {
        $this->line('Synchronizing WordPress image size options...');

        $force = (bool) $this->option('force');
        $updated = $this->imageSizeManager->synchronizeImageSizeOptionsToDatabase($force);

        if ($updated) {
            $this->info('Image size options synchronized successfully.');

            return self::SUCCESS;
        }

        $this->info('Image size options are already up to date.');

        return self::SUCCESS;
    }
}
