<?php
/**
 * Frontend Handler Class
 * 
 * @package FlowRead\Frontend
 * @since 1.0.0
 */

namespace FlowRead\Frontend;

/**
 * Frontend Class
 */
class Frontend {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     * 
     * @return void
     */
    private function init_hooks() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Enqueue frontend assets
     * 
     * @return void
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'flowread-frontend',
            FLOWREAD_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            FLOWREAD_VERSION
        );

        wp_enqueue_script(
            'flowread-frontend',
            FLOWREAD_PLUGIN_URL . 'assets/js/frontend.js',
            [ 'jquery' ],
            FLOWREAD_VERSION,
            true
        );

        wp_localize_script(
            'flowread-frontend',
            'flowreadData',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'flowread_nonce' ),
            ]
        );
    }
}
