<?php
/**
 * PHPUnit bootstrap for RIACO Reviews.
 *
 * Requires the WordPress test suite (WP_TESTS_DIR) and loads the plugin
 * before the suite runs. Install the test suite with bin/install-wp-tests.sh.
 */

// Support loading env from a .env.testing file if present.
$env_file = dirname( __DIR__ ) . '/.env.testing';
if ( file_exists( $env_file ) ) {
    foreach ( file( $env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $line ) {
        if ( str_starts_with( trim( $line ), '#' ) ) continue;
        if ( false === strpos( $line, '=' ) ) continue;
        [ $key, $value ] = explode( '=', $line, 2 );
        putenv( trim( $key ) . '=' . trim( $value ) );
    }
}

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
    $_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    echo "Could not find {$_tests_dir}/includes/functions.php." . PHP_EOL;
    echo "Run bin/install-wp-tests.sh to set up the WordPress test suite." . PHP_EOL;
    exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

// Load the plugin before WordPress finishes loading.
tests_add_filter( 'muplugins_loaded', static function () {
    require dirname( __DIR__ ) . '/riaco-reviews.php';
} );

require $_tests_dir . '/includes/bootstrap.php';

// Load WP admin helpers needed by some tests (dashboard widget functions, etc.).
require_once ABSPATH . 'wp-admin/includes/dashboard.php';
require_once ABSPATH . 'wp-admin/includes/template.php';
