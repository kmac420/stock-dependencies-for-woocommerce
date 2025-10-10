<?php
// Test DB settings
define('DB_NAME', 'wordpress_test');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_HOST', '127.0.0.1');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

// WordPress test settings
define('ABSPATH', dirname(dirname(__FILE__)) . '/');
define('WP_DEBUG', true);
define('WP_TESTS_DOMAIN', 'example.org');
define('WP_TESTS_EMAIL', 'admin@example.org');
define('WP_TESTS_TITLE', 'Test Blog');
define('WP_PHP_BINARY', 'php');

define('WP_TESTS_MULTISITE', false);
define('WP_ALLOW_MULTISITE', false);

// Test suite configuration
define('WP_TESTS_CONFIG_FILE_PATH', __FILE__);
define('WP_TESTS_DIR', '/var/folders/fb/06hzwlys0q91566xp75gj6yc0000gn/T/wordpress-tests-lib');

// Prevent filesystem modifications
define('DISALLOW_FILE_EDIT', true);
define('DISALLOW_FILE_MODS', true);

// Memory limits
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '256M');

// Load WordPress Settings
require_once ABSPATH . 'wp-settings.php';