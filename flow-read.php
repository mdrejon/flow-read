<?php 
/**
 * Plugin Name: FlowRead – Smart Reading Experience for Websites
 * Description:  FlowRead is a WordPress plugin that enhances the reading experience on your website by providing a clean, distraction-free layout and customizable reading options.
 * Version: 1.0.2
 * Author: sydurrahman
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: flowread
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Require Composer autoloader
 */
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Initialize the main plugin
 * 
 * @return \FlowRead\Plugin
 */
function flowread() {
    return \FlowRead\Plugin::instance();
}

// Kick-off the plugin
flowread();