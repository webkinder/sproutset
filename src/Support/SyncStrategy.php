<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Support;

enum SyncStrategy: string
{
    case REQUEST = 'request';
    case ADMIN_REQUEST = 'admin_request';
    case CRON = 'cron';
    case MANUAL = 'manual';
}
