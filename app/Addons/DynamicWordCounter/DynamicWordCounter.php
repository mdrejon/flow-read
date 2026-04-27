<?php
/**
 * Dynamic Word Counter Addon
 *
 * @package FlowRead\Addons\DynamicWordCounter
 * @since   1.1.0
 */

namespace FlowRead\Addons\DynamicWordCounter;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * DynamicWordCounter class.
 *
 * Backend and frontend settings are stored separately:
 *   flowread_dwc_backend  — admin editor (Gutenberg + Classic)
 *   flowread_dwc_frontend — frontend textarea selectors
 */
class DynamicWordCounter {

    const BACKEND_OPTION_KEY  = 'flowread_dwc_backend';
    const FRONTEND_OPTION_KEY = 'flowread_dwc_frontend';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_enqueue_scripts',          [ $this, 'enqueue_admin_assets' ] );
        add_action( 'wp_enqueue_scripts',             [ $this, 'enqueue_frontend_assets' ] );
        add_filter( 'flowread_settings_tabs_menus',   [ $this, 'register_settings_tab' ], 12 );
        add_filter( 'flowread_settings_tab_content',  [ $this, 'render_settings_content' ], 12, 2 );

        $this->register_word_count_columns();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Return saved backend (admin editor) options.
     *
     * @return array
     */
    private function get_backend_options() {
        return (array) get_option( self::BACKEND_OPTION_KEY, [] );
    }

    /**
     * Return saved frontend options.
     *
     * @return array
     */
    private function get_frontend_options() {
        return (array) get_option( self::FRONTEND_OPTION_KEY, [] );
    }

    /**
     * Return selected post types from backend options.
     *
     * @return array
     */
    private function get_selected_post_types() {
        $opts = $this->get_backend_options();
        return isset( $opts['post_types'] ) ? (array) $opts['post_types'] : [];
    }

    /**
     * Build the inline CSS string from a given options array.
     *
     * @param  array $opts Options array (backend or frontend).
     * @return string
     */
    public function get_custom_css( $opts ) {
        $bg_color       = ! empty( $opts['bg_color'] )         ? sanitize_hex_color( $opts['bg_color'] )         : '#ffffff';
        $text_color     = ! empty( $opts['text_color'] )        ? sanitize_hex_color( $opts['text_color'] )        : '#333333';
        $progress_color = ! empty( $opts['progress_color'] )    ? sanitize_hex_color( $opts['progress_color'] )    : '#007cba';
        $progress_bg    = ! empty( $opts['progress_bg_color'] ) ? sanitize_hex_color( $opts['progress_bg_color'] ) : '#e0e0e0';
        $font_size      = ! empty( $opts['font_size'] )         ? absint( $opts['font_size'] )                     : 13;

        $bg_color       = $bg_color       ?: '#ffffff';
        $text_color     = $text_color     ?: '#333333';
        $progress_color = $progress_color ?: '#007cba';
        $progress_bg    = $progress_bg    ?: '#e0e0e0';
        $font_size      = $font_size      ?: 13;

        $css  = ".flowread-dwc-wrap { background-color: {$bg_color}; color: {$text_color}; font-size: {$font_size}px; }\n";
        $css .= ".flowread-dwc-progress-bar-inner { background-color: {$progress_color}; }\n";
        $css .= ".flowread-dwc-progress-bar-track { background-color: {$progress_bg}; }\n";

        return $css;
    }

    /**
     * Build the wp_localize_script config array for the given context.
     *
     * @param  string $context 'admin' or 'frontend'.
     * @return array
     */
    private function build_script_config( $context = 'admin' ) {
        $opts = 'admin' === $context ? $this->get_backend_options() : $this->get_frontend_options();

        return [
            'context'           => $context,
            'minWords'          => isset( $opts['min_words'] )         ? absint( $opts['min_words'] )             : 0,
            'maxWords'          => isset( $opts['max_words'] )         ? absint( $opts['max_words'] )             : 0,
            'displayMode'       => isset( $opts['display_mode'] )      ? $opts['display_mode']                    : 'inline',
            'floatingPos'       => isset( $opts['floating_position'] ) ? $opts['floating_position']               : 'bottom-right',
            'showProgressBar'   => ! empty( $opts['show_progress_bar'] ),
            'excludeHtml'       => ! empty( $opts['exclude_html'] ),
            'excludeShortcodes' => ! empty( $opts['exclude_shortcodes'] ),
            'excludeNumbers'    => ! empty( $opts['exclude_numbers'] ),
            'selectors'         => 'frontend' === $context && isset( $opts['selectors'] ) ? $opts['selectors'] : '',
            'i18n'              => [
                'words'       => __( 'words',        'flowread' ),
                'tooShort'    => __( 'Too short',    'flowread' ),
                'tooLong'     => __( 'Too long',     'flowread' ),
                'withinRange' => __( 'Within range', 'flowread' ),
                'minLabel'    => __( 'Min',          'flowread' ),
                'maxLabel'    => __( 'Max',          'flowread' ),
                'wordCount'   => __( 'Word Count',   'flowread' ),
                'submitError' => 'admin' === $context
                    ? __( 'Please meet the word count requirement before publishing.', 'flowread' )
                    : __( 'Please meet the word count requirement before submitting.',  'flowread' ),
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Asset enqueuing
    // -------------------------------------------------------------------------

    /**
     * Enqueue assets on post add/edit screens for enabled post types.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets( $hook ) {
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
            return;
        }

        $selected_post_types = $this->get_selected_post_types();
        if ( empty( $selected_post_types ) ) {
            return;
        }

        $screen           = get_current_screen();
        $current_post_type = $screen ? $screen->post_type : '';

        if ( ! in_array( $current_post_type, $selected_post_types, true ) ) {
            return;
        }

        wp_enqueue_style(
            'flowread-dynamic-word-counter',
            FLOWREAD_PLUGIN_URL . 'assets/app/css/dynamic-word-counter.css',
            [],
            FLOWREAD_VERSION
        );

        wp_enqueue_script(
            'flowread-dynamic-word-counter-admin',
            FLOWREAD_PLUGIN_URL . 'assets/admin/js/dynamic-word-counter.js',
            [ 'jquery', 'wp-data' ],
            FLOWREAD_VERSION,
            true
        );

        wp_localize_script(
            'flowread-dynamic-word-counter-admin',
            'flowreadDWC',
            $this->build_script_config( 'admin' )
        );

        wp_add_inline_style( 'flowread-dynamic-word-counter', $this->get_custom_css( $this->get_backend_options() ) );
    }

    /**
     * Enqueue assets on the frontend when CSS selectors are configured.
     */
    public function enqueue_frontend_assets() {
        $opts      = $this->get_frontend_options();
        $selectors = isset( $opts['selectors'] ) ? trim( $opts['selectors'] ) : '';

        if ( empty( $selectors ) ) {
            return;
        }

        wp_enqueue_style(
            'flowread-dynamic-word-counter',
            FLOWREAD_PLUGIN_URL . 'assets/app/css/dynamic-word-counter.css',
            [],
            FLOWREAD_VERSION
        );

        wp_enqueue_script(
            'flowread-dynamic-word-counter',
            FLOWREAD_PLUGIN_URL . 'assets/app/js/dynamic-word-counter.js',
            [ 'jquery' ],
            FLOWREAD_VERSION,
            true
        );

        wp_localize_script(
            'flowread-dynamic-word-counter',
            'flowreadDWC',
            $this->build_script_config( 'frontend' )
        );

        wp_add_inline_style( 'flowread-dynamic-word-counter', $this->get_custom_css( $opts ) );
    }

    // -------------------------------------------------------------------------
    // Post list word-count column
    // -------------------------------------------------------------------------

    /**
     * Register columns for every enabled post type when the option is active.
     */
    private function register_word_count_columns() {
        $options = $this->get_backend_options();

        if ( empty( $options['show_word_count_column'] ) ) {
            return;
        }

        foreach ( $this->get_selected_post_types() as $post_type ) {
            add_filter( "manage_{$post_type}_posts_columns",        [ $this, 'add_word_count_column' ] );
            add_action( "manage_{$post_type}_posts_custom_column",  [ $this, 'render_word_count_column' ], 10, 2 );
        }
    }

    /**
     * Add "Word Count" column header.
     *
     * @param  array $columns Existing columns.
     * @return array
     */
    public function add_word_count_column( $columns ) {
        $columns['flowread_word_count'] = __( 'Word Count', 'flowread' );
        return $columns;
    }

    /**
     * Output word count for a post in the custom column.
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     */
    public function render_word_count_column( $column, $post_id ) {
        if ( 'flowread_word_count' !== $column ) {
            return;
        }

        $opts    = $this->get_backend_options();
        $content = get_post_field( 'post_content', $post_id );
        $text    = ! empty( $opts['exclude_html'] ) ? wp_strip_all_tags( $content ) : wp_strip_all_tags( $content );
        if ( ! empty( $opts['exclude_shortcodes'] ) ) {
            $text = preg_replace( '/\[.*?\]/s', ' ', $text );
        }
        if ( ! empty( $opts['exclude_numbers'] ) ) {
            $text = preg_replace( '/\b\d+\b/', ' ', $text );
        }

        echo esc_html( number_format_i18n( str_word_count( $text ) ) );
    }

    // -------------------------------------------------------------------------
    // Settings tab
    // -------------------------------------------------------------------------

    /**
     * Register the settings tab label.
     *
     * @param  array $tabs Existing tabs.
     * @return array
     */
    public function register_settings_tab( $tabs ) {
        $tabs['dynamic_word_counter'] = __( 'Dynamic Word Counter', 'flowread' );
        return $tabs;
    }

    /**
     * Render settings page content for this tab.
     *
     * @param  string $content Current rendered content.
     * @param  string $tab     Active tab key.
     * @return string
     */
    public function render_settings_content( $content, $tab ) {
        if ( 'dynamic_word_counter' !== $tab ) {
            return $content;
        }

        // ------------------------------------------------------------------
        // Handle form submission
        // ------------------------------------------------------------------
        if (
            isset( $_POST['flowread_dwc_nonce_field'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['flowread_dwc_nonce_field'] ) ), 'flowread_dwc_nonce' )
        ) {
            // ── Backend ───────────────────────────────────────────────────
            if ( isset( $_POST['flowread_dwc_backend'] ) ) {
                $raw = wp_unslash( $_POST['flowread_dwc_backend'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $b   = [];

                if ( isset( $raw['post_types'] ) ) {
                    $b['post_types'] = array_map( 'sanitize_text_field', (array) $raw['post_types'] );
                }
                $b['min_words']              = isset( $raw['min_words'] )             ? absint( $raw['min_words'] )                          : 0;
                $b['max_words']              = isset( $raw['max_words'] )             ? absint( $raw['max_words'] )                          : 0;
                $b['display_mode']           = isset( $raw['display_mode'] )          ? sanitize_text_field( $raw['display_mode'] )          : 'inline';
                $b['floating_position']      = isset( $raw['floating_position'] )     ? sanitize_text_field( $raw['floating_position'] )     : 'bottom-right';
                $b['show_progress_bar']      = ! empty( $raw['show_progress_bar'] )   ? 1 : 0;
                $b['exclude_html']           = ! empty( $raw['exclude_html'] )        ? 1 : 0;
                $b['exclude_shortcodes']     = ! empty( $raw['exclude_shortcodes'] )  ? 1 : 0;
                $b['exclude_numbers']        = ! empty( $raw['exclude_numbers'] )     ? 1 : 0;
                $b['show_word_count_column'] = ! empty( $raw['show_word_count_column'] ) ? 1 : 0;
                $b['bg_color']               = isset( $raw['bg_color'] )              ? sanitize_hex_color( $raw['bg_color'] )               : '#ffffff';
                $b['text_color']             = isset( $raw['text_color'] )            ? sanitize_hex_color( $raw['text_color'] )             : '#333333';
                $b['progress_color']         = isset( $raw['progress_color'] )        ? sanitize_hex_color( $raw['progress_color'] )         : '#007cba';
                $b['progress_bg_color']      = isset( $raw['progress_bg_color'] )     ? sanitize_hex_color( $raw['progress_bg_color'] )      : '#e0e0e0';
                $b['font_size']              = isset( $raw['font_size'] )             ? absint( $raw['font_size'] )                         : 13;

                update_option( self::BACKEND_OPTION_KEY, $b );
            }

            // ── Frontend ──────────────────────────────────────────────────
            if ( isset( $_POST['flowread_dwc_frontend'] ) ) {
                $raw = wp_unslash( $_POST['flowread_dwc_frontend'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $f   = [];

                $f['selectors']          = isset( $raw['selectors'] )           ? sanitize_textarea_field( $raw['selectors'] )       : '';
                $f['min_words']          = isset( $raw['min_words'] )           ? absint( $raw['min_words'] )                        : 0;
                $f['max_words']          = isset( $raw['max_words'] )           ? absint( $raw['max_words'] )                        : 0;
                $f['display_mode']       = isset( $raw['display_mode'] )        ? sanitize_text_field( $raw['display_mode'] )        : 'inline';
                $f['floating_position']  = isset( $raw['floating_position'] )   ? sanitize_text_field( $raw['floating_position'] )   : 'bottom-right';
                $f['show_progress_bar']  = ! empty( $raw['show_progress_bar'] ) ? 1 : 0;
                $f['exclude_html']       = ! empty( $raw['exclude_html'] )      ? 1 : 0;
                $f['exclude_shortcodes'] = ! empty( $raw['exclude_shortcodes'] )? 1 : 0;
                $f['exclude_numbers']    = ! empty( $raw['exclude_numbers'] )   ? 1 : 0;
                $f['bg_color']           = isset( $raw['bg_color'] )            ? sanitize_hex_color( $raw['bg_color'] )             : '#ffffff';
                $f['text_color']         = isset( $raw['text_color'] )          ? sanitize_hex_color( $raw['text_color'] )           : '#333333';
                $f['progress_color']     = isset( $raw['progress_color'] )      ? sanitize_hex_color( $raw['progress_color'] )       : '#007cba';
                $f['progress_bg_color']  = isset( $raw['progress_bg_color'] )   ? sanitize_hex_color( $raw['progress_bg_color'] )    : '#e0e0e0';
                $f['font_size']          = isset( $raw['font_size'] )           ? absint( $raw['font_size'] )                        : 13;

                update_option( self::FRONTEND_OPTION_KEY, $f );
            }

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully!', 'flowread' ) . '</p></div>';
        }

        // ------------------------------------------------------------------
        // Render form
        // ------------------------------------------------------------------
        $b_opts = $this->get_backend_options();
        $f_opts = $this->get_frontend_options();

        $post_types          = get_post_types( [ 'public' => true ], 'objects' );
        $selected_post_types = isset( $b_opts['post_types'] ) ? (array) $b_opts['post_types'] : [];

        $display_modes = [
            'inline'   => __( 'Inline (below editor / textarea)', 'flowread' ),
            'floating' => __( 'Floating badge',                   'flowread' ),
        ];
        $display_modes_admin = [
            'inline'   => __( 'Inline (Top Right)', 'flowread' ),
            'floating' => __( 'Floating badge',                   'flowread' ),
        ];

        $floating_positions = [
            'bottom-right' => __( 'Bottom Right', 'flowread' ),
            'bottom-left'  => __( 'Bottom Left',  'flowread' ),
            'top-right'    => __( 'Top Right',    'flowread' ),
            'top-left'     => __( 'Top Left',     'flowread' ),
        ];

        ?>
        <div class="flow-read-settings-wrap flowread-dwc-settings">

            <div class="flow-read-heading">
                <h2><?php esc_html_e( 'Dynamic Word Counter Settings', 'flowread' ); ?></h2>
                <p><?php esc_html_e( 'Configure real-time word counting with min/max limits, progress indicator, and UI customisation.', 'flowread' ); ?></p>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field( 'flowread_dwc_nonce', 'flowread_dwc_nonce_field' ); ?>

                <!-- ════════════════════════════════════════════════════════ -->
                <!-- BACKEND (Admin Editor) Settings                         -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div class="flow-read-heading" style="margin-top:8px;">
                    <h3 style="border-left:4px solid #007cba;padding-left:10px;margin-bottom:4px;">
                        <?php esc_html_e( 'Backend — Admin Editor Settings', 'flowread' ); ?>
                    </h3>
                    <p><?php esc_html_e( 'Shown in the post add/edit screen for selected post types (Gutenberg + Classic Editor).', 'flowread' ); ?></p>
                </div>

                <!-- Post Types -->
                <div class="setting-group flow-read-flex">
                    <div class="setting-group-field" style="width:100%;">
                        <label><?php esc_html_e( 'Apply to Post Types', 'flowread' ); ?></label>
                        <div class="checkbox-group">
                            <?php foreach ( $post_types as $pt ) :
                                if ( 'attachment' === $pt->name ) { continue; }
                                ?>
                                <label>
                                    <input type="checkbox"
                                           name="flowread_dwc_backend[post_types][]"
                                           value="<?php echo esc_attr( $pt->name ); ?>"
                                           <?php checked( in_array( $pt->name, $selected_post_types, true ) ); ?> />
                                    <?php echo esc_html( $pt->label ); ?>
                                </label>
                                <br />
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Word Limits -->
                <div class="setting-group flow-read-flex">
                    <div class="setting-group-field" style="width:calc(50% - 8px);">
                        <label for="flowread_b_min_words"><?php esc_html_e( 'Minimum Words', 'flowread' ); ?></label>
                        <input type="number" id="flowread_b_min_words"
                               name="flowread_dwc_backend[min_words]"
                               min="0"
                               value="<?php echo esc_attr( isset( $b_opts['min_words'] ) ? $b_opts['min_words'] : 0 ); ?>" />
                        <p class="description"><?php esc_html_e( 'Set to 0 to disable.', 'flowread' ); ?></p>
                    </div>
                    <div class="setting-group-field" style="width:calc(50% - 8px);">
                        <label for="flowread_b_max_words"><?php esc_html_e( 'Maximum Words', 'flowread' ); ?></label>
                        <input type="number" id="flowread_b_max_words"
                               name="flowread_dwc_backend[max_words]"
                               min="0"
                               value="<?php echo esc_attr( isset( $b_opts['max_words'] ) ? $b_opts['max_words'] : 0 ); ?>" />
                        <p class="description"><?php esc_html_e( 'Set to 0 to disable.', 'flowread' ); ?></p>
                    </div>
                </div>

                <!-- Display Mode -->
                <div class="setting-group flow-read-flex">
                    <div class="setting-group-field" style="width:calc(50% - 8px);">
                        <label for="flowread_b_display_mode"><?php esc_html_e( 'Display Mode', 'flowread' ); ?></label>
                        <select id="flowread_b_display_mode" name="flowread_dwc_backend[display_mode]">
                            <?php foreach ( $display_modes_admin as $val => $lbl ) : ?>
                                <option value="<?php echo esc_attr( $val ); ?>"
                                    <?php selected( isset( $b_opts['display_mode'] ) ? $b_opts['display_mode'] : 'inline', $val ); ?>>
                                    <?php echo esc_html( $lbl ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e( 'Floating badge is fixed to the viewport corner.', 'flowread' ); ?></p>
                    </div>
                    <div class="setting-group-field" style="width:calc(50% - 8px);">
                        <label for="flowread_b_floating_position"><?php esc_html_e( 'Floating Badge Position', 'flowread' ); ?></label>
                        <select id="flowread_b_floating_position" name="flowread_dwc_backend[floating_position]">
                            <?php foreach ( $floating_positions as $val => $lbl ) : ?>
                                <option value="<?php echo esc_attr( $val ); ?>"
                                    <?php selected( isset( $b_opts['floating_position'] ) ? $b_opts['floating_position'] : 'bottom-right', $val ); ?>>
                                    <?php echo esc_html( $lbl ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Options -->
                <div class="setting-group flow-read-flex">
                    <div class="setting-group-field" style="width:100%;">
                        <label><?php esc_html_e( 'Options', 'flowread' ); ?></label>
                        <div class="checkbox-group">
                            <?php $this->render_shared_checkboxes( $b_opts, 'flowread_dwc_backend', true ); ?>
                        </div>
                    </div>
                </div>

                <!-- UI Customisation -->
                <div class="flow-read-heading" style="margin-top:16px;">
                    <h4 style="margin-bottom:4px;"><?php esc_html_e( 'UI Customisation (Backend)', 'flowread' ); ?></h4>
                </div>
                <?php $this->render_ui_customisation( $b_opts, 'flowread_dwc_backend', 'b' ); ?>

                <hr style="margin:32px 0;border-color:#ddd;" />

                <!-- ════════════════════════════════════════════════════════ -->
                <!-- FRONTEND Settings                                        -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div class="flow-read-heading">
                    <h3 style="border-left:4px solid #46b450;padding-left:10px;margin-bottom:4px;">
                        <?php esc_html_e( 'Frontend Settings', 'flowread' ); ?>
                    </h3>
                    <p><?php esc_html_e( 'Attaches to any textarea / input field on the public site using CSS selectors.', 'flowread' ); ?></p>
                </div>

                <!-- CSS Selectors -->
                <div class="setting-group flow-read-flex">
                    <div class="setting-group-field" style="width:100%;">
                        <label for="flowread_fe_selectors"><?php esc_html_e( 'Target CSS Selectors', 'flowread' ); ?></label>
                        <textarea id="flowread_fe_selectors"
                                  name="flowread_dwc_frontend[selectors]"
                                  rows="3"
                                  style="width:100%;"><?php echo isset( $f_opts['selectors'] ) ? esc_textarea( $f_opts['selectors'] ) : ''; ?></textarea>
                        <p class="description"><?php esc_html_e( 'CSS selectors for frontend textarea / input fields (e.g. #comment, .my-field). Separate multiple selectors with commas.', 'flowread' ); ?></p>
                    </div>
                </div>

                <!-- Word Limits -->
                <div class="setting-group flow-read-flex">
                    <div class="setting-group-field" style="width:calc(50% - 8px);">
                        <label for="flowread_fe_min_words"><?php esc_html_e( 'Minimum Words', 'flowread' ); ?></label>
                        <input type="number" id="flowread_fe_min_words"
                               name="flowread_dwc_frontend[min_words]"
                               min="0"
                               value="<?php echo esc_attr( isset( $f_opts['min_words'] ) ? $f_opts['min_words'] : 0 ); ?>" />
                        <p class="description"><?php esc_html_e( 'Set to 0 to disable.', 'flowread' ); ?></p>
                    </div>
                    <div class="setting-group-field" style="width:calc(50% - 8px);">
                        <label for="flowread_fe_max_words"><?php esc_html_e( 'Maximum Words', 'flowread' ); ?></label>
                        <input type="number" id="flowread_fe_max_words"
                               name="flowread_dwc_frontend[max_words]"
                               min="0"
                               value="<?php echo esc_attr( isset( $f_opts['max_words'] ) ? $f_opts['max_words'] : 0 ); ?>" />
                        <p class="description"><?php esc_html_e( 'Set to 0 to disable.', 'flowread' ); ?></p>
                    </div>
                </div>

                <!-- Display Mode -->
                <div class="setting-group flow-read-flex">
                    <div class="setting-group-field" style="width:calc(50% - 8px);">
                        <label for="flowread_fe_display_mode"><?php esc_html_e( 'Display Mode', 'flowread' ); ?></label>
                        <select id="flowread_fe_display_mode" name="flowread_dwc_frontend[display_mode]">
                            <?php foreach ( $display_modes as $val => $lbl ) : ?>
                                <option value="<?php echo esc_attr( $val ); ?>"
                                    <?php selected( isset( $f_opts['display_mode'] ) ? $f_opts['display_mode'] : 'inline', $val ); ?>>
                                    <?php echo esc_html( $lbl ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e( 'Inline: shown below the textarea. Floating: badge anchored to the corner of the textarea.', 'flowread' ); ?></p>
                    </div>
                    <div class="setting-group-field" style="width:calc(50% - 8px);">
                        <label for="flowread_fe_floating_position"><?php esc_html_e( 'Floating Badge Position', 'flowread' ); ?></label>
                        <select id="flowread_fe_floating_position" name="flowread_dwc_frontend[floating_position]">
                            <?php foreach ( $floating_positions as $val => $lbl ) : ?>
                                <option value="<?php echo esc_attr( $val ); ?>"
                                    <?php selected( isset( $f_opts['floating_position'] ) ? $f_opts['floating_position'] : 'bottom-right', $val ); ?>>
                                    <?php echo esc_html( $lbl ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Options -->
                <div class="setting-group flow-read-flex">
                    <div class="setting-group-field" style="width:100%;">
                        <label><?php esc_html_e( 'Options', 'flowread' ); ?></label>
                        <div class="checkbox-group">
                            <?php $this->render_shared_checkboxes( $f_opts, 'flowread_dwc_frontend', false ); ?>
                        </div>
                    </div>
                </div>

                <!-- UI Customisation -->
                <div class="flow-read-heading" style="margin-top:16px;">
                    <h4 style="margin-bottom:4px;"><?php esc_html_e( 'UI Customisation (Frontend)', 'flowread' ); ?></h4>
                </div>
                <?php $this->render_ui_customisation( $f_opts, 'flowread_dwc_frontend', 'fe' ); ?>

                <?php submit_button( __( 'Save Settings', 'flowread' ) ); ?>
            </form>
        </div>
        <script>
        (function () {
            function toggleFloatingPos( selectId, floatingSelectId ) {
                var sel  = document.getElementById( selectId );
                var wrap = document.getElementById( floatingSelectId )
                               ? document.getElementById( floatingSelectId ).closest( '.setting-group-field' )
                               : null;
                if ( ! sel || ! wrap ) { return; }
                function update() {
                    wrap.style.display = ( sel.value === 'floating' ) ? '' : 'none';
                }
                sel.addEventListener( 'change', update );
                update();
            }
            toggleFloatingPos( 'flowread_b_display_mode',  'flowread_b_floating_position' );
            toggleFloatingPos( 'flowread_fe_display_mode', 'flowread_fe_floating_position' );
        }());
        </script>
        <?php

        return $content;
    }

    // ─── Reusable form partials ───────────────────────────────────────────────

    /**
     * Render shared option checkboxes.
     *
     * @param array  $opts        Saved options.
     * @param string $input_name  Input name prefix.
     * @param bool   $show_column Show the post-list word-count column checkbox (backend only).
     */
    private function render_shared_checkboxes( $opts, $input_name, $show_column ) {
        ?>
        <label>
            <input type="checkbox"
                   name="<?php echo esc_attr( $input_name ); ?>[show_progress_bar]"
                   value="1"
                   <?php checked( ! empty( $opts['show_progress_bar'] ) ); ?> />
            <?php esc_html_e( 'Show Progress Bar', 'flowread' ); ?>
        </label>
        <br />
        <label>
            <input type="checkbox"
                   name="<?php echo esc_attr( $input_name ); ?>[exclude_html]"
                   value="1"
                   <?php checked( ! empty( $opts['exclude_html'] ) ); ?> />
            <?php esc_html_e( 'Exclude HTML Tags from Word Count', 'flowread' ); ?>
        </label>
        <br />
        <label>
            <input type="checkbox"
                   name="<?php echo esc_attr( $input_name ); ?>[exclude_shortcodes]"
                   value="1"
                   <?php checked( ! empty( $opts['exclude_shortcodes'] ) ); ?> />
            <?php esc_html_e( 'Exclude Shortcodes from Word Count', 'flowread' ); ?>
        </label>
        <br />
        <label>
            <input type="checkbox"
                   name="<?php echo esc_attr( $input_name ); ?>[exclude_numbers]"
                   value="1"
                   <?php checked( ! empty( $opts['exclude_numbers'] ) ); ?> />
            <?php esc_html_e( 'Exclude Numbers from Word Count', 'flowread' ); ?>
        </label>
        <?php if ( $show_column ) : ?>
        <br />
        <label>
            <input type="checkbox"
                   name="<?php echo esc_attr( $input_name ); ?>[show_word_count_column]"
                   value="1"
                   <?php checked( ! empty( $opts['show_word_count_column'] ) ); ?> />
            <?php esc_html_e( 'Show Word Count Column in Post List', 'flowread' ); ?>
        </label>
        <?php endif;
    }

    /**
     * Render UI customisation colour/size fields.
     *
     * @param array  $opts       Saved options.
     * @param string $input_name Input name prefix.
     * @param string $id_pfx     Short prefix for HTML element IDs ('b' or 'fe').
     */
    private function render_ui_customisation( $opts, $input_name, $id_pfx ) {
        ?>
        <div class="setting-group flow-read-flex">
            <div class="setting-group-field" style="width:calc(33% - 16px);">
                <label for="flowread_dwc_<?php echo esc_attr( $id_pfx ); ?>_bg_color"><?php esc_html_e( 'Background Color', 'flowread' ); ?></label>
                <input type="text" class="flowread-color-field"
                       id="flowread_dwc_<?php echo esc_attr( $id_pfx ); ?>_bg_color"
                       name="<?php echo esc_attr( $input_name ); ?>[bg_color]"
                       data-default-color="#ffffff"
                       value="<?php echo esc_attr( isset( $opts['bg_color'] ) ? $opts['bg_color'] : '#ffffff' ); ?>" />
            </div>
            <div class="setting-group-field" style="width:calc(33% - 16px);">
                <label for="flowread_dwc_<?php echo esc_attr( $id_pfx ); ?>_text_color"><?php esc_html_e( 'Text Color', 'flowread' ); ?></label>
                <input type="text" class="flowread-color-field"
                       id="flowread_dwc_<?php echo esc_attr( $id_pfx ); ?>_text_color"
                       name="<?php echo esc_attr( $input_name ); ?>[text_color]"
                       data-default-color="#333333"
                       value="<?php echo esc_attr( isset( $opts['text_color'] ) ? $opts['text_color'] : '#333333' ); ?>" />
            </div>
            <div class="setting-group-field" style="width:calc(33% - 16px);">
                <label for="flowread_dwc_<?php echo esc_attr( $id_pfx ); ?>_font_size"><?php esc_html_e( 'Font Size (px)', 'flowread' ); ?></label>
                <input type="number"
                       id="flowread_dwc_<?php echo esc_attr( $id_pfx ); ?>_font_size"
                       name="<?php echo esc_attr( $input_name ); ?>[font_size]"
                       min="8" max="32"
                       value="<?php echo esc_attr( isset( $opts['font_size'] ) ? $opts['font_size'] : 13 ); ?>" />
            </div>
        </div>
        <div class="setting-group flow-read-flex">
            <div class="setting-group-field" style="width:calc(50% - 8px);">
                <label for="flowread_dwc_<?php echo esc_attr( $id_pfx ); ?>_progress_color"><?php esc_html_e( 'Progress Bar Color', 'flowread' ); ?></label>
                <input type="text" class="flowread-color-field"
                       id="flowread_dwc_<?php echo esc_attr( $id_pfx ); ?>_progress_color"
                       name="<?php echo esc_attr( $input_name ); ?>[progress_color]"
                       data-default-color="#007cba"
                       value="<?php echo esc_attr( isset( $opts['progress_color'] ) ? $opts['progress_color'] : '#007cba' ); ?>" />
            </div>
            <div class="setting-group-field" style="width:calc(50% - 8px);">
                <label for="flowread_dwc_<?php echo esc_attr( $id_pfx ); ?>_progress_bg_color"><?php esc_html_e( 'Progress Bar Track Color', 'flowread' ); ?></label>
                <input type="text" class="flowread-color-field"
                       id="flowread_dwc_<?php echo esc_attr( $id_pfx ); ?>_progress_bg_color"
                       name="<?php echo esc_attr( $input_name ); ?>[progress_bg_color]"
                       data-default-color="#e0e0e0"
                       value="<?php echo esc_attr( isset( $opts['progress_bg_color'] ) ? $opts['progress_bg_color'] : '#e0e0e0' ); ?>" />
            </div>
        </div>
        <?php
    }
}
