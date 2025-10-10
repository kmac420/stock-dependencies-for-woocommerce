<?php
/**
 * PHPUnit bootstrap file for WooCommerce Stock Dependencies Plugin
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
    $_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
    define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
    // Load WooCommerce
    require dirname( dirname( dirname( __FILE__ ) ) ) . '/woocommerce/plugins/woocommerce/woocommerce.php';

    // Load our plugin
    require dirname( dirname( __FILE__ ) ) . '/wc-stock-dependencies.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

// Load WooCommerce test framework
require dirname( __FILE__ ) . '/framework/class-wc-unit-test-case.php';
require dirname( __FILE__ ) . '/framework/class-wc-mock-session-handler.php';
require dirname( __FILE__ ) . '/framework/class-wc-mock-wc-data.php';
require dirname( __FILE__ ) . '/framework/class-wc-mock-wc-object-query.php';
require dirname( __FILE__ ) . '/framework/class-wc-unit-test-factory.php';
require dirname( __FILE__ ) . '/framework/helpers/class-wc-helper-product.php';

// Set up WooCommerce settings
if ( ! defined( 'WC_TAX_ROUNDING_MODE' ) ) {
    define( 'WC_TAX_ROUNDING_MODE', 'auto' );
}
if ( ! defined( 'WC_USE_TRANSACTIONS' ) ) {
    define( 'WC_USE_TRANSACTIONS', false );
}

// Install WooCommerce
WC_Install::install();

// Setup some basic WooCommerce settings
update_option( 'woocommerce_enable_coupons', 'yes' );
update_option( 'woocommerce_calc_taxes', 'yes' );

// Ensure server variable is set for WP email functions
if ( ! isset( $_SERVER['SERVER_NAME'] ) ) {
    $_SERVER['SERVER_NAME'] = 'localhost';
}