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
        wp_enqueue_style( 'flowread-reading-bar', FLOWREAD_PLUGIN_URL . 'assets/app/css/reading-progressbar.css', [], FLOWREAD_VERSION );
        wp_enqueue_script( 'flowread-reading-bar', FLOWREAD_PLUGIN_URL . 'assets/app/js/reading-progressbar.js', [ 'jquery' ], FLOWREAD_VERSION, true );
    }
 

    public function render_progress_bar() {
        // Get options
        $options = get_option( 'flowread_reading_bar', [] );

        // Get current post type
        $current_post_type = get_post_type();
        
        // Get current template
        $current_template = get_page_template_slug();

        // Check if progress bar should display on current page
        $display_position = isset( $options['display_position'] ) ? $options['display_position'] : '';
        $selected_post_types = isset( $options['post_types'] ) ? (array) $options['post_types'] : []; 

        // Verify post type and template constraints
        $show_on_post_type = empty( $selected_post_types ) || in_array( $current_post_type, $selected_post_types, true );
 
        // Don't show if constraints are not met
        if ( ! $display_position || ( ! empty( $selected_post_types ) && ! $show_on_post_type ) ) {
            return;
        }

        // Get styling options
        $style = isset( $options['style'] ) ? $options['style'] : 'classic';
        $height = isset( $options['height'] ) ? absint( $options['height'] ) : 4;
        $background_color = isset( $options['background_color'] ) ? $options['background_color'] : '#f0f0f0';
        $primary_color = isset( $options['primary_color'] ) ? $options['primary_color'] : '#007cba';
        $secondary_color = isset( $options['secondary_color'] ) ? $options['secondary_color'] : '#442334';

        // Inline styles
        $bar_style = sprintf(
            'height: %dpx; background-color: %s;',
            $height,
            esc_attr( $background_color )
        );

        if ( 'gradient' === $style ) {
            $progress_style = sprintf(
                'background: linear-gradient(to right, %s, %s); height: 100%%; width: 0%%;',
                esc_attr( $primary_color ),
                esc_attr( $secondary_color )
            );
        } else {
            $progress_style = sprintf(
                'background-color: %s; height: 100%%; width: 0%%;',
                esc_attr( $primary_color )
            );
        }

        // Add data attributes for JavaScript
        $data_attrs = sprintf(
            'data-position="%s" data-style="%s" data-height="%d"',
            esc_attr( $display_position ),
            esc_attr( $style ),
            $height
        );

        echo sprintf(
            '<div id="flowread-progress-bar" class="flowread-position-%s flowread-style-%s" style="%s" %s><div class="progress" style="%s"></div></div>',
            esc_attr( $display_position ),
            esc_attr( $style ),
            esc_attr( $bar_style ),
            esc_attr( $data_attrs ),
            esc_attr( $progress_style )
        );
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

                // Sanitize secondary_color
                if ( isset( $_POST['flowread_reading_bar']['secondary_color'] ) ) {
                    $settings['secondary_color'] = sanitize_hex_color( wp_unslash( $_POST['flowread_reading_bar']['secondary_color'] ) );
                }
                
                // Sanitize post_types
                if ( isset( $_POST['flowread_reading_bar']['post_types'] ) ) {
                    $settings['post_types'] = array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['flowread_reading_bar']['post_types'] ) );
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

        ?>
        <div class="flow-read-settings-wrap flowread-progressbar-settings">
            <div class="flow-read-heading">
                <h2><?php esc_html_e( 'Reading Progress Bar Settings', 'flow-read' ); ?></h2>
                <p><?php esc_html_e( 'Configure the appearance and behavior of the reading progress bar on your site.', 'flow-read' ); ?></p>
 
            </div>
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
                        <input type="text" class="flowread-color-field" id="flowread_background_color" name="flowread_reading_bar[background_color]" data-default-color="#f0f0f0" value="<?php echo isset( $options['background_color'] ) ? esc_attr( $options['background_color'] ) : '#f0f0f0'; ?>" />
                    </div>

                    <!-- Primary Color -->
                    <div class="setting-group-field" style="width: calc(33% - 16px);">
                        <label for="flowread_primary_color">
                            <?php esc_html_e( 'Primary Color', 'flow-read' ); ?>
                        </label>
                        <input type="text" class="flowread-color-field" id="flowread_primary_color" name="flowread_reading_bar[primary_color]" data-default-color="#007cba" value="<?php echo isset( $options['primary_color'] ) ? esc_attr( $options['primary_color'] ) : '#007cba'; ?>" />
                    </div>

                       <!-- Secondary Color -->
                    <div class="setting-group-field" id="flowread_secondary_color_field" style="width: calc(33% - 16px);">
                        <label for="flowread_secondary_color">
                            <?php esc_html_e( 'Secondary Color', 'flow-read' ); ?>
                        </label>
                        <input type="text" class="flowread-color-field" id="flowread_secondary_color" name="flowread_reading_bar[secondary_color]" data-default-color="#007cba" value="<?php echo isset( $options['secondary_color'] ) ? esc_attr( $options['secondary_color'] ) : '#007cba'; ?>" />
                    </div>
                     
                </div> 
 

              
                <div class="setting-group flow-read-flex">
                      <!-- Post Types -->
                    <div class="setting-group-field" style="width: 100%;">
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
 
                    
                </div>

             

                <?php submit_button( __( 'Save Settings', 'flow-read' ) ); ?>
            </form>
        </div>
        <?php
   
        return $content;
    }

    public function flowread_settings_tabs_menus( $tabs ) {
        $tabs['progressbar'] = __( 'Reading Progress Bar', 'flow-read' );
        return $tabs;
    }
}

