<?php
/**
 * Plugin Name: Ref247 Affiliate Tracking
 * Plugin URI: https://ref247.io
 * Description: Complete affiliate tracking and commission management for WordPress. Track referrals, manage commissions, and grow your program with powerful analytics.
 * Version: 1.0.0
 * Author: Ref247
 * Author URI: https://ref247.io
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ref247-affiliate-tracking
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 *
 * @package Ref247
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('REF247_PLUGIN_FILE', __FILE__);
define('REF247_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('REF247_PLUGIN_URL', plugin_dir_url(__FILE__));
define('REF247_PLUGIN_VERSION', '1.0.0');
define('REF247_TEXT_DOMAIN', 'ref247-affiliate-tracking');

// Require composer autoloader
require_once REF247_PLUGIN_PATH . 'vendor/autoload.php';

/**
 * Bootstrap the plugin
 */
function ref247_bootstrap() {
    // Initialize the main plugin class
    $plugin = new Ref247\Core\Plugin();
    $plugin->run();
}

add_action('plugins_loaded', 'ref247_bootstrap');