<?php

/**
 * Bootstrap for the WordPress drift lane.
 *
 * Runs against this directory's own Composer project (PHPUnit + wp-phpunit +
 * polyfills, no Laravel), so WordPress core never collides with Illuminate's
 * global helpers. Points wp-phpunit at our test config, ensures a writable
 * content directory, then boots WordPress via the wp-phpunit test library.
 */

declare(strict_types=1);

$here = __DIR__;

require_once $here.'/vendor/autoload.php';
require_once $here.'/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

putenv('WP_PHPUNIT__TESTS_CONFIG='.$here.'/wp-tests-config.php');

$contentDir = getenv('WP_TESTS_CONTENT_DIR') ?: sys_get_temp_dir().'/sproutset-wp-tests/wp-content';
if (! is_dir($contentDir.'/uploads')) {
    mkdir($contentDir.'/uploads', 0777, true);
}

$testsDir = getenv('WP_PHPUNIT__DIR') ?: $here.'/vendor/wp-phpunit/wp-phpunit';

require_once $testsDir.'/includes/functions.php';
require $testsDir.'/includes/bootstrap.php';
