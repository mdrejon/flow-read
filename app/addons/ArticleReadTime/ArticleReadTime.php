<?php 
namespace FlowRead\Addons\ArticleReadTime;
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ArticleReadTime {
    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );  
        add_filter( 'flowread_settings_tab_content', [ $this, 'render_article_read_time_settings_content' ], 11, 2 );
        add_filter( 'flowread_settings_tabs_menus', [ $this, 'flowread_settings_tabs_menus' ], 11, 2 );
        add_filter( 'the_content', [ $this, 'add_reading_time_to_content' ], 10 );
        add_action( 'wp_head', [ $this, 'add_custom_styles' ] );
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'flowread-article-read-time', FLOWREAD_PLUGIN_URL . 'assets/app/css/article-read-time.css' );
        wp_enqueue_script( 'flowread-article-read-time', FLOWREAD_PLUGIN_URL . 'assets/app/js/article-read-time.js', [ 'jquery' ], null, true );
    }
 
    /**
     * Calculate reading time for content
     */
    public function calculate_reading_time( $content ) {
        $options = get_option( 'flowread_article_read_time', [] );
        $words_per_minute = isset( $options['words_per_minute'] ) ? absint( $options['words_per_minute'] ) : 200;
        
        // Strip HTML tags and count words
        $text = wp_strip_all_tags( $content );
        $word_count = str_word_count( $text );
        
        // Calculate reading time in minutes
        $reading_time = ceil( $word_count / $words_per_minute );
        
        return $reading_time;
    }

    /**
     * Check if reading time should be displayed
     */
    public function should_display_reading_time() {
        $options = get_option( 'flowread_article_read_time', [] );
        
        // Check if post type is selected
        $selected_post_types = isset( $options['post_types'] ) ? (array) $options['post_types'] : [];
        $current_post_type = get_post_type();
        
        if ( empty( $selected_post_types ) || ! in_array( $current_post_type, $selected_post_types, true ) ) {
            return false;
        }
        
        // Check display location
        $selected_locations = isset( $options['display_location'] ) ? (array) $options['display_location'] : [];
        
        if ( empty( $selected_locations ) ) {
            return false;
        }
        
        // Check if current page matches display location
        if ( is_singular() && in_array( 'single_post', $selected_locations, true ) ) {
            return true;
        }
        
        if ( ( is_home() || is_front_page() ) && in_array( 'home_page', $selected_locations, true ) ) {
            return true;
        }
        
        if ( is_archive() && in_array( 'archive_page', $selected_locations, true ) ) {
            return true;
        }
        
        return false;
    }

    /**
     * Render reading time HTML
     */
    public function render_reading_time( $reading_time ) {
        $options = get_option( 'flowread_article_read_time', [] );
        
        $before_text = isset( $options['before_text'] ) ? $options['before_text'] : __( 'Estimated reading time: ', 'flow-read' );
        $after_text = isset( $options['after_text'] ) ? $options['after_text'] : __( ' minute read', 'flow-read' );
        
        $reading_time_text = $reading_time > 1 ? $reading_time . $after_text : $reading_time . $after_text;
        
        $html = '<div class="flowread-article-read-time">';
        $html .= '<span class="flowread-read-time-text">';
        $html .= esc_html( $before_text );
        $html .= ' <strong>' . esc_html( $reading_time ) . '</strong> ';
        $html .= esc_html( $after_text );
        $html .= '</span>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Add reading time to content
     */
    public function add_reading_time_to_content( $content ) {
        if ( ! $this->should_display_reading_time() ) {
            return $content;
        }
        
        $options = get_option( 'flowread_article_read_time', [] );
        $position = isset( $options['position'] ) ? $options['position'] : 'before_content';
        
        $reading_time = $this->calculate_reading_time( $content );
        $reading_time_html = $this->render_reading_time( $reading_time );
        
        if ( 'after_content' === $position ) {
            return $content . $reading_time_html;
        } else {
            return $reading_time_html . $content;
        }
    }

    /**
     * Add custom styles to head
     */
    public function add_custom_styles() {
        if ( ! $this->should_display_reading_time() ) {
            return;
        }
        
        $options = get_option( 'flowread_article_read_time', [] );
        
        $font_size = isset( $options['font_size'] ) ? absint( $options['font_size'] ) : 14;
        $margin = isset( $options['margin'] ) ? absint( $options['margin'] ) : 10;
        $padding = isset( $options['padding'] ) ? absint( $options['padding'] ) : 10;
        $background_color = isset( $options['background_color'] ) ? sanitize_hex_color( $options['background_color'] ) : '#f5f5f5';
        $text_color = isset( $options['text_color'] ) ? sanitize_hex_color( $options['text_color'] ) : '#333333';
        
        ?>
        <style type="text/css">
            .flowread-article-read-time .flowread-read-time-text {
                font-size: <?php echo esc_attr( $font_size ); ?>px;
                margin: <?php echo esc_attr( $margin ); ?>px auto;
                padding: <?php echo esc_attr( $padding ); ?>px;
                background-color: <?php echo esc_attr( $background_color ); ?>;
                color: <?php echo esc_attr( $text_color ); ?>;
                border-radius: 4px;
                display: inline-block;
            }
            
            .flowread-read-time-text {
                display: inline-block;
            }
            
            .flowread-read-time-text strong {
                font-weight: 600;
                color: <?php echo esc_attr( $text_color ); ?>;
            }
        </style> 
        <?php
    }

 

    public function render_article_read_time_settings_content( $content, $tab ) {
        if ( 'article_read_time' !== $tab ) {
            return $content;
        }

             
        // Handle form submission
        if ( isset( $_POST['flowread_article_read_time_nonce_field'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['flowread_article_read_time_nonce_field'] ) ), 'flowread_article_read_time_nonce' ) ) {
            if ( isset( $_POST['flowread_article_read_time'] ) ) {
                $settings = [];
                
                // Sanitize post_types
                if ( isset( $_POST['flowread_article_read_time']['post_types'] ) ) {
                    $settings['post_types'] = array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['flowread_article_read_time']['post_types'] ) );
                }
                
                // Sanitize words_per_minute
                if ( isset( $_POST['flowread_article_read_time']['words_per_minute'] ) ) {
                    $settings['words_per_minute'] = absint( $_POST['flowread_article_read_time']['words_per_minute'] );
                }
                
                // Sanitize display_location
                if ( isset( $_POST['flowread_article_read_time']['display_location'] ) ) {
                    $settings['display_location'] = array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['flowread_article_read_time']['display_location'] ) );
                }
                
                // Sanitize position
                if ( isset( $_POST['flowread_article_read_time']['position'] ) ) {
                    $settings['position'] = sanitize_text_field( wp_unslash( $_POST['flowread_article_read_time']['position'] ) );
                }
                
                // Sanitize before_text
                if ( isset( $_POST['flowread_article_read_time']['before_text'] ) ) {
                    $settings['before_text'] = sanitize_text_field( wp_unslash( $_POST['flowread_article_read_time']['before_text'] ) );
                }
                
                // Sanitize after_text
                if ( isset( $_POST['flowread_article_read_time']['after_text'] ) ) {
                    $settings['after_text'] = sanitize_text_field( wp_unslash( $_POST['flowread_article_read_time']['after_text'] ) );
                }
                
                // Sanitize font_size
                if ( isset( $_POST['flowread_article_read_time']['font_size'] ) ) {
                    $settings['font_size'] = absint( $_POST['flowread_article_read_time']['font_size'] );
                }
                
                // Sanitize margin
                if ( isset( $_POST['flowread_article_read_time']['margin'] ) ) {
                    $settings['margin'] = absint( $_POST['flowread_article_read_time']['margin'] );
                }
                
                // Sanitize padding
                if ( isset( $_POST['flowread_article_read_time']['padding'] ) ) {
                    $settings['padding'] = absint( $_POST['flowread_article_read_time']['padding'] );
                }
                
                // Sanitize background_color
                if ( isset( $_POST['flowread_article_read_time']['background_color'] ) ) {
                    $settings['background_color'] = sanitize_hex_color( wp_unslash( $_POST['flowread_article_read_time']['background_color'] ) );
                }
                
                // Sanitize text_color
                if ( isset( $_POST['flowread_article_read_time']['text_color'] ) ) {
                    $settings['text_color'] = sanitize_hex_color( wp_unslash( $_POST['flowread_article_read_time']['text_color'] ) );
                }
                update_option( 'flowread_article_read_time', $settings );
                
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully!', 'flow-read' ) . '</p></div>';
            }
        }


        // Get saved options
        $options = get_option( 'flowread_article_read_time', [] );
        
        // Define available choices
        $display_locations = [
            'single_post' => __( 'Single Post', 'flow-read' ),
            'home_page' => __( 'Home Page / Blog Page', 'flow-read' ),
            'archive_page' => __( 'Archive Page', 'flow-read' ),
        ];

        $positions = [
            'before_content' => __( 'Before Content', 'flow-read' ),
            'after_content' => __( 'After Content', 'flow-read' ),
        ];

        $post_types = get_post_types( [ 'public' => true ], 'objects' ); 

        ?>
        <div class="flow-read-settings-wrap flowread-article-read-time-settings">
            <div class="flow-read-heading">
                <h2><?php esc_html_e( 'Article Read Time Settings', 'flow-read' ); ?></h2>
                <p><?php esc_html_e( 'Configure the article read time display on your site.', 'flow-read' ); ?></p>
 
            </div>
            <form method="post" action="">
                <?php wp_nonce_field( 'flowread_article_read_time_nonce', 'flowread_article_read_time_nonce_field' ); ?>
                
                <!-- Post Types -->
                <div class="setting-group flow-read-flex">
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
                                    <input type="checkbox" name="flowread_article_read_time[post_types][]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $selected_post_types, true ) ); ?> />
                                    <?php echo esc_html( $post_type->label ); ?>
                                </label>
                                <br />
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Words Per Minute -->
                <div class="setting-group flow-read-flex">
                    <div class="setting-group-field" style="width: calc(33% - 16px);">
                        <label for="flowread_words_per_minute">
                            <?php esc_html_e( 'Words Per Minute', 'flow-read' ); ?>
                        </label>
                        <input type="number" id="flowread_words_per_minute" name="flowread_article_read_time[words_per_minute]" min="1" value="<?php echo isset( $options['words_per_minute'] ) ? esc_attr( $options['words_per_minute'] ) : '200'; ?>" />
                    </div>

                    <!-- Position -->
                    <div class="setting-group-field" style="width: calc(33% - 16px);">
                        <label for="flowread_position">
                            <?php esc_html_e( 'Article Read Time Position', 'flow-read' ); ?>
                        </label>
                        <select id="flowread_position" name="flowread_article_read_time[position]">
                            <option value=""><?php esc_html_e( '-- Select Position --', 'flow-read' ); ?></option>
                            <?php foreach ( $positions as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( isset( $options['position'] ) ? $options['position'] : '', $value ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Font Size -->
                    <div class="setting-group-field" style="width: calc(33% - 16px);">
                        <label for="flowread_font_size">
                            <?php esc_html_e( 'Font Size (px)', 'flow-read' ); ?>
                        </label>
                        <input type="number" id="flowread_font_size" name="flowread_article_read_time[font_size]" min="8" value="<?php echo isset( $options['font_size'] ) ? esc_attr( $options['font_size'] ) : '14'; ?>" />
                    </div>
                </div>

                <!-- Before and After Text -->
                <div class="setting-group flow-read-flex">
                    <div class="setting-group-field" style="width: calc(50% - 8px);">
                        <label for="flowread_before_text">
                            <?php esc_html_e( 'Before Text', 'flow-read' ); ?>
                        </label>
                        <input type="text" id="flowread_before_text" name="flowread_article_read_time[before_text]" value="<?php echo isset( $options['before_text'] ) ? esc_attr( $options['before_text'] ) : 'Estimated reading time: '; ?>" />
                    </div>

                    <div class="setting-group-field" style="width: calc(50% - 8px);">
                        <label for="flowread_after_text">
                            <?php esc_html_e( 'After Text', 'flow-read' ); ?>
                        </label>
                        <input type="text" id="flowread_after_text" name="flowread_article_read_time[after_text]" value="<?php echo isset( $options['after_text'] ) ? esc_attr( $options['after_text'] ) : ' minute read'; ?>" />
                    </div>
                </div>

                <!-- Display Location -->
                <div class="setting-group flow-read-flex">
                    <div class="setting-group-field" style="width: 100%;">
                        <label><?php esc_html_e( 'Show Estimated Read Time On', 'flow-read' ); ?></label>
                        <div class="checkbox-group">
                            <?php
                            $selected_locations = isset( $options['display_location'] ) ? (array) $options['display_location'] : [];
                            foreach ( $display_locations as $value => $label ) :
                                ?>
                                <label>
                                    <input type="checkbox" name="flowread_article_read_time[display_location][]" value="<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, $selected_locations, true ) ); ?> />
                                    <?php echo esc_html( $label ); ?>
                                </label>
                                <br />
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Margin and Padding -->
                <div class="setting-group flow-read-flex">
                    <div class="setting-group-field" style="width: calc(50% - 8px);">
                        <label for="flowread_margin">
                            <?php esc_html_e( 'Margin (px)', 'flow-read' ); ?>
                        </label>
                        <input type="number" id="flowread_margin" name="flowread_article_read_time[margin]" min="0" value="<?php echo isset( $options['margin'] ) ? esc_attr( $options['margin'] ) : '10'; ?>" />
                    </div>

                    <div class="setting-group-field" style="width: calc(50% - 8px);">
                        <label for="flowread_padding">
                            <?php esc_html_e( 'Padding (px)', 'flow-read' ); ?>
                        </label>
                        <input type="number" id="flowread_padding" name="flowread_article_read_time[padding]" min="0" value="<?php echo isset( $options['padding'] ) ? esc_attr( $options['padding'] ) : '10'; ?>" />
                    </div>
                </div>

                <!-- Colors -->
                <div class="setting-group flow-read-flex">
                    <div class="setting-group-field" style="width: calc(50% - 8px);">
                        <label for="flowread_background_color">
                            <?php esc_html_e( 'Background Color', 'flow-read' ); ?>
                        </label>
                        <input type="text" class="flowread-color-field" id="flowread_background_color" name="flowread_article_read_time[background_color]" data-default-color="#f5f5f5" value="<?php echo isset( $options['background_color'] ) ? esc_attr( $options['background_color'] ) : '#f5f5f5'; ?>" />
                    </div>

                    <div class="setting-group-field" style="width: calc(50% - 8px);">
                        <label for="flowread_text_color">
                            <?php esc_html_e( 'Text Color', 'flow-read' ); ?>
                        </label>
                        <input type="text" class="flowread-color-field" id="flowread_text_color" name="flowread_article_read_time[text_color]" data-default-color="#333333" value="<?php echo isset( $options['text_color'] ) ? esc_attr( $options['text_color'] ) : '#333333'; ?>" />
                    </div>
                </div>

                <?php submit_button( __( 'Save Settings', 'flow-read' ) ); ?>
            </form>
        </div>
        <?php
   
        return $content;
    }

    public function flowread_settings_tabs_menus( $tabs ) {
        $tabs['article_read_time'] = __( 'Article Read Time', 'flow-read' );
        return $tabs;
    }
}

