<?php

/**
 * WordPress test configuration for the drift lane.
 *
 * Values default to the local DDEV environment (bedrock WordPress core + the
 * dedicated `wordpress_test` database on the DDEV MariaDB) and are overridable
 * by environment variables so CI can point at its own WordPress + MySQL service.
 *
 * This is a test-only config: it must never touch the live site database.
 */

declare(strict_types=1);

$absPath = getenv('WP_TESTS_ABSPATH') ?: '/var/www/html/web/wp/';
define('ABSPATH', rtrim($absPath, '/').'/');

// Isolate WordPress content (uploads, etc.) in a throwaway directory so the
// drift lane never writes into the host site's wp-content.
$contentDir = getenv('WP_TESTS_CONTENT_DIR') ?: sys_get_temp_dir().'/sproutset-wp-tests/wp-content';
define('WP_CONTENT_DIR', $contentDir);

define('DB_NAME', getenv('WP_TESTS_DB_NAME') ?: 'wordpress_test');
define('DB_USER', getenv('WP_TESTS_DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('WP_TESTS_DB_PASSWORD') ?: 'root');
define('DB_HOST', getenv('WP_TESTS_DB_HOST') ?: 'db');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

$table_prefix = 'wptests_';

define('WP_TESTS_DOMAIN', 'example.org');
define('WP_TESTS_EMAIL', 'admin@example.org');
define('WP_TESTS_TITLE', 'Sproutset Drift Lane');
define('WP_PHP_BINARY', 'php');

define('WP_DEBUG', true);
