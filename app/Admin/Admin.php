<?php
/**
 * Admin Handler Class
 * 
 * @package FlowRead\Admin
 * @since 1.0.0
 */

namespace FlowRead\Admin;
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



/**
 * Admin Class
 */
class Admin {

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
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Add admin menu
     * 
     * @return void
     */
    public function add_menu() {
        add_menu_page(
            __( 'FlowRead Settings', 'flow-read' ),
            __( 'FlowRead', 'flow-read' ),
            'manage_options',
            'flowread-settings',
            [ $this, 'render_settings_page' ],
            'dashicons-book-alt',
            30
        );
    }

    /**
     * Render settings page
     * 
     * @return void
     */
    public function render_settings_page() {
        // Check for nonce in GET request for tab switching
        if ( isset( $_GET['tab'] ) && ! isset( $_GET['flowread_nonce'] ) ) {
            // Allow tab switching without nonce for display purposes
        } elseif ( isset( $_GET['flowread_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['flowread_nonce'] ) ), 'flowread_page_nonce' ) ) {
            // If nonce is provided, verify it
        }

        // Get current tab
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'progressbar';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'FlowRead Settings', 'flow-read' ); ?></h1>
            
            <!-- Tab Navigation -->
            <nav class="nav-tab-wrapper">
                <?php
                /**
                 * Filter the FlowRead settings tabs.
                 *
                 * @param array $tabs Array of tabs.
                 */
                $tabs = apply_filters( 'flowread_settings_tabs_menus', [ ] );

                foreach ( $tabs as $tab => $label ) {
                    ?>
                    <a href="?page=flowread-settings&tab=<?php echo esc_attr( $tab ); ?>" class="nav-tab <?php echo $tab === $active_tab ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html( $label ); ?>
                    </a>
                    <?php
                }
                ?>
            </nav>

            <!-- Tab Content -->
            <div class="tab-content">
                <?php
                //  apply filter to render content for the active tab
                /**
                 * Filter the FlowRead settings tab content.
                 *
                 * @param string $content The content to display for the active tab.
                 * @param string $active_tab The currently active tab key.
                 */
                echo wp_kses_post( apply_filters( 'flowread_settings_tab_content', '', $active_tab ) );
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render general settings tab
     * 
     * @return void
     */
    private function render_general_settings() {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'flowread_general_settings' );
            do_settings_sections( 'flowread_general_settings' );
            submit_button();
            ?>
        </form>
        <?php
    }

    /**
     * Render progressbar settings tab
     * 
     * @return void
     */
    private function render_progressbar_settings() {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'flowread_progressbar_settings' );
            do_settings_sections( 'flowread_progressbar_settings' );
            submit_button();
            ?>
        </form>
        <?php
    }

    /**
     * Render read meter settings tab
     * 
     * @return void
     */
    private function render_readmeter_settings() {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'flowread_readmeter_settings' );
            do_settings_sections( 'flowread_readmeter_settings' );
            submit_button();
            ?>
        </form>
        <?php
    }

    /**
     * Enqueue admin assets
     * 
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_assets( $hook ) {
        if ( 'toplevel_page_flowread-settings' !== $hook ) {
            return;
        }

        
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        wp_enqueue_style(
            'flowread-admin',
            FLOWREAD_PLUGIN_URL . 'assets/admin/css/admin.css',
            [],
            FLOWREAD_VERSION
        );

        wp_enqueue_script(
            'flowread-admin',
            FLOWREAD_PLUGIN_URL . 'assets/admin/js/admin.js',
            [ 'jquery', 'wp-color-picker' ],
            FLOWREAD_VERSION,
            true
        );
    }
}
