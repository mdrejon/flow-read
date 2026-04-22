<?php
/**
 * Plugin Class
 * 
 * @package FlowRead
 * @since 1.0.0
 */

namespace FlowRead;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}
 

/**
 * Main Plugin Class
 */
final class Plugin {

    /**
     * Plugin version
     */
    const VERSION = '1.0.3';

    /**
     * Singleton instance
     * 
     * @var Plugin
     */
    private static $instance = null;

    /**
     * Constructor
     */
    private function __construct() {
        $this->define_constants();
        $this->init_hooks();
    }

    /**
     * Get singleton instance
     * 
     * @return Plugin
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Define plugin constants
     * 
     * @return void
     */
    private function define_constants() {
        define( 'FLOWREAD_VERSION', self::VERSION );
        define( 'FLOWREAD_PLUGIN_DIR', plugin_dir_path( dirname( __FILE__ ) ) );
        define( 'FLOWREAD_PLUGIN_URL', plugin_dir_url( dirname( __FILE__ ) ) );
        define( 'FLOWREAD_PLUGIN_FILE', dirname( __FILE__ ) . '/flow-read.php' );
    }

    /**
     * Initialize hooks
     * 
     * @return void
     */
    private function init_hooks() {
        add_action( 'init', [ $this, 'plugin_loaded' ], 9 );
        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
    }

    /**
     * Plugin loaded callback
     * 
     * @return void
     */
    public function plugin_loaded() {
        // Initialize plugin features here
        $this->init_components();
        do_action( 'flowread_loaded' );
    }

    /**
     * Initialize plugin components
     * 
     * @return void
     */
    private function init_components() {
        // Initialize Admin
        if ( is_admin() ) {
            new Admin\Admin();
        }

        // Initialize Frontend
        if ( ! is_admin() ) {
            new Frontend\Frontend();
        }

        
        new Addons\Addons();
    }

    /**
     * Load plugin textdomain
     * 
     * @return void
     */
    public function load_textdomain() {
        // Translations are automatically loaded by WordPress for plugins hosted on wordpress.org
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserializing
     */
    public function __wakeup() {
        throw new \Exception( 'Cannot unserialize singleton' );
    }
}
