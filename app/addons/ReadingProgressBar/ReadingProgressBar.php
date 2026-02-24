<?php 
namespace FlowRead\Addons\ReadingProgressBar;
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ReadingProgressBar {
    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_footer', [ $this, 'render_progress_bar' ] );
        
        add_filter( 'flowread_settings_tab_content', [ $this, 'render_progressbar_settings_content' ], 10, 2 );
        add_filter( 'flowread_settings_tabs_menus', [ $this, 'flowread_settings_tabs_menus' ], 10, 2 );
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'flowread-reading-bar', plugin_dir_url( __FILE__ ) . 'assets/reading-bar.css' );
        wp_enqueue_script( 'flowread-reading-bar', plugin_dir_url( __FILE__ ) . 'assets/reading-bar.js', [ 'jquery' ], null, true );
    }

    public function render_progress_bar() {
        echo '<div id="flowread-progress-bar"><div class="progress"></div></div>';
    }

    public function render_progressbar_settings_content( $content, $tab ) {
        if ( 'progressbar' !== $tab ) {
            return $content;
        }

             
        // Handle form submission
        if ( isset( $_POST['flowread_progressbar_nonce_field'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['flowread_progressbar_nonce_field'] ) ), 'flowread_progressbar_nonce' ) ) {
            if ( isset( $_POST['flowread_reading_bar'] ) ) {
                $settings = [];
                
                // Sanitize display_position
                if ( isset( $_POST['flowread_reading_bar']['display_position'] ) ) {
                    $settings['display_position'] = sanitize_text_field( wp_unslash( $_POST['flowread_reading_bar']['display_position'] ) );
                }
                
                // Sanitize style
                if ( isset( $_POST['flowread_reading_bar']['style'] ) ) {
                    $settings['style'] = sanitize_text_field( wp_unslash( $_POST['flowread_reading_bar']['style'] ) );
                }
                
                // Sanitize height
                if ( isset( $_POST['flowread_reading_bar']['height'] ) ) {
                    $settings['height'] = absint( $_POST['flowread_reading_bar']['height'] );
                }
                
                // Sanitize background_color
                if ( isset( $_POST['flowread_reading_bar']['background_color'] ) ) {
                    $settings['background_color'] = sanitize_hex_color( wp_unslash( $_POST['flowread_reading_bar']['background_color'] ) );
                }
                
                // Sanitize primary_color
                if ( isset( $_POST['flowread_reading_bar']['primary_color'] ) ) {
                    $settings['primary_color'] = sanitize_hex_color( wp_unslash( $_POST['flowread_reading_bar']['primary_color'] ) );
                }
                
                // Sanitize post_types
                if ( isset( $_POST['flowread_reading_bar']['post_types'] ) ) {
                    $settings['post_types'] = array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['flowread_reading_bar']['post_types'] ) );
                }
                
                // Sanitize templates
                if ( isset( $_POST['flowread_reading_bar']['templates'] ) ) {
                    $settings['templates'] = array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['flowread_reading_bar']['templates'] ) );
                }
                
                update_option( 'flowread_reading_bar', $settings );
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully!', 'flow-read' ) . '</p></div>';
            }
        }


        // Get saved options
        $options = get_option( 'flowread_reading_bar', [] );
        
        // Define available choices
        $display_positions = [
            'top' => __( 'Top', 'flow-read' ),
            'bottom' => __( 'Bottom', 'flow-read' ),
            'fixed-top' => __( 'Fixed Top', 'flow-read' ),
        ];

        $styles = [
            'classic' => __( 'Classic', 'flow-read' ),
            'gradient' => __( 'Gradient', 'flow-read' ),
            // 'animated' => __( 'Animated', 'flow-read' ),
        ];

        $post_types = get_post_types( [ 'public' => true ], 'objects' );
        $templates = get_page_templates();

        ?>
        <div class="flow-read-settings-wrap flowread-progressbar-settings">
            <h3><?php esc_html_e( 'Reading Progress Bar Settings', 'flow-read' ); ?></h3>
            <form method="post" action="">
                <?php wp_nonce_field( 'flowread_progressbar_nonce', 'flowread_progressbar_nonce_field' ); ?>
                
                <div class="setting-group flow-read-flex"> 
                    <!-- Display Position -->
                    <div class="setting-group-field" style="width: calc(33% - 16px);">
                        <label for="flowread_display_position">
                            <?php esc_html_e( 'Display Position', 'flow-read' ); ?>
                        </label>
                        <select id="flowread_display_position" name="flowread_reading_bar[display_position]">
                            <option value=""><?php esc_html_e( '-- Select Position --', 'flow-read' ); ?></option>
                            <?php foreach ( $display_positions as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( isset( $options['display_position'] ) ? $options['display_position'] : '', $value ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Style Selection -->
                    <div class="setting-group-field" style="width: calc(33% - 16px);">
                        <label for="flowread_style">
                            <?php esc_html_e( 'Style', 'flow-read' ); ?>
                        </label>
                        <select id="flowread_style" name="flowread_reading_bar[style]">
                            <option value=""><?php esc_html_e( '-- Select Style --', 'flow-read' ); ?></option>
                            <?php foreach ( $styles as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( isset( $options['style'] ) ? $options['style'] : '', $value ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Progress Bar Height -->
                    <div class="setting-group-field" style="width: calc(33% - 16px);">
                        <label for="flowread_height">
                            <?php esc_html_e( 'Progress Bar Height (px)', 'flow-read' ); ?>
                        </label>
                        <input type="number" id="flowread_height" name="flowread_reading_bar[height]" min="1" max="100" value="<?php echo isset( $options['height'] ) ? esc_attr( $options['height'] ) : '4'; ?>" />
                    </div>
                </div>

                <div class="setting-group flow-read-flex"> 
                    <!-- Background Color -->
                    <div class="setting-group-field" style="width: calc(33% - 16px);">
                        <label for="flowread_background_color">
                            <?php esc_html_e( 'Background Color', 'flow-read' ); ?>
                        </label>
                        <input type="color" id="flowread_background_color" name="flowread_reading_bar[background_color]" value="<?php echo isset( $options['background_color'] ) ? esc_attr( $options['background_color'] ) : '#f0f0f0'; ?>" />
                    </div>

                    <!-- Primary Color -->
                    <div class="setting-group-field" style="width: calc(33% - 16px);">
                        <label for="flowread_primary_color">
                            <?php esc_html_e( 'Primary Color', 'flow-read' ); ?>
                        </label>
                        <input type="color" id="flowread_primary_color" name="flowread_reading_bar[primary_color]" value="<?php echo isset( $options['primary_color'] ) ? esc_attr( $options['primary_color'] ) : '#007cba'; ?>" />
                    </div>
                     
                </div> 
 

              
                <div class="setting-group flow-read-flex">
                      <!-- Post Types -->
                    <div class="setting-group-field" style="width: calc(50% - 16px);">
                        <label><?php esc_html_e( 'Apply to Post Types', 'flow-read' ); ?></label>
                        <div class="checkbox-group">
                            <?php
                            $selected_post_types = isset( $options['post_types'] ) ? (array) $options['post_types'] : [];
                            foreach ( $post_types as $post_type ) :
                                if ( 'attachment' === $post_type->name ) {
                                    continue;
                                }
                                ?>
                                <label>
                                    <input type="checkbox" name="flowread_reading_bar[post_types][]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $selected_post_types, true ) ); ?> />
                                    <?php echo esc_html( $post_type->label ); ?>
                                </label>
                                <br />
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Templates -->
                    <div class="setting-group-field" style="width: calc(50% - 16px);">
                        <label><?php esc_html_e( 'Apply to Templates', 'flow-read' ); ?></label>
                        <div class="checkbox-group">
                            <?php
                            $selected_templates = isset( $options['templates'] ) ? (array) $options['templates'] : [];
                            foreach ( $templates as $template_name => $template_label ) :
                                ?>
                                <label>
                                    <input type="checkbox" name="flowread_reading_bar[templates][]" value="<?php echo esc_attr( $template_name ); ?>" <?php checked( in_array( $template_name, $selected_templates, true ) ); ?> />
                                    <?php echo esc_html( $template_label ); ?>
                                </label>
                                <br />
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                </div>

             

                <?php submit_button( __( 'Save Settings', 'flow-read' ) ); ?>
            </form>
        </div>
        <?php
   
        return $content;
    }

    public function flowread_settings_tabs_menus( $tabs ) {
        $tabs['progressbar'] = __( 'Progress Bar', 'flow-read' );
        return $tabs;
    }
}

