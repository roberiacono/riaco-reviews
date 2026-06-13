<?php
/**
 * Plugin Name: RIACO Reviews
 * Description: Collect, manage, and display customer reviews with Grid and Masonry layouts via blocks and shortcodes.
 * Version: 1.0.2
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Roberto Iacono
 * Author URI: https://riacoplugins.com/
 * Plugin URI: https://github.com/roberiacono/riaco-reviews
 * Text Domain: riaco-reviews
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RIACO_REVIEWS_VERSION', '1.0.2' );
define( 'RIACO_REVIEWS_FILE',    __FILE__ );
define( 'RIACO_REVIEWS_DIR',     plugin_dir_path( __FILE__ ) );
define( 'RIACO_REVIEWS_URL',     plugin_dir_url( __FILE__ ) );

require __DIR__ . '/vendor/autoload.php';

use RIACO\Reviews\Plugin;

$plugin = new Plugin( __FILE__, RIACO_REVIEWS_VERSION );
$plugin->load();

register_activation_hook( __FILE__, [ $plugin, 'on_activation' ] );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
