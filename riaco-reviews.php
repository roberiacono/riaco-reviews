<?php
/**
 * Plugin Name: RIACO Reviews – Customer Reviews & Testimonials
 * Description: Collect, manage, and display customer reviews with Grid and Masonry layouts via blocks and shortcodes.
 * Version: 1.2.2
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Roberto Iacono
 * Author URI: https://riacoplugins.com/
 * Plugin URI: https://wordpress.org/plugins/riaco-reviews/
 * Text Domain: riaco-reviews
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RIACO_REVIEWS_VERSION', '1.2.2' );
define( 'RIACO_REVIEWS_FILE',    __FILE__ );
define( 'RIACO_REVIEWS_DIR',     plugin_dir_path( __FILE__ ) );
define( 'RIACO_REVIEWS_URL',     plugin_dir_url( __FILE__ ) );

spl_autoload_register( function ( $class ) {
    if ( strpos( $class, 'RIACO\\Reviews\\' ) !== 0 ) {
        return;
    }
    $relative = substr( $class, strlen( 'RIACO\\Reviews\\' ) );
    $file = __DIR__ . '/includes/' . str_replace( '\\', '/', $relative ) . '.php';
    if ( file_exists( $file ) ) {
        require $file;
    }
} );

use RIACO\Reviews\Plugin;

$riaco_reviews_plugin = new Plugin( __FILE__, RIACO_REVIEWS_VERSION );
$riaco_reviews_plugin->load();

register_activation_hook( __FILE__, [ $riaco_reviews_plugin, 'on_activation' ] );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
