<?php

class Widgets_For_Google_Reviews_And_Ratings_Admin {

    private $plugin_name;
    private $version;
    public $place_details;
    public $admin_html;
    public $is_min;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = esc_html($plugin_name);
        $this->version = esc_html($version);
        $this->place_details = get_option('wgrr_g_place_details', '');
        $this->is_min = !( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG );
    }

    public function enqueue_styles() {
        if (isset($_GET['page']) && sanitize_text_field($_GET['page']) === 'google-reviews-settings') {
            if ($this->is_min) {
                wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/widgets-for-google-reviews-and-ratings-admin.min.css', [], $this->version, 'all');
            } else {
                wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/widgets-for-google-reviews-and-ratings-admin.css', [], $this->version, 'all');
            }
        }
    }

    public function enqueue_scripts() {
        if (isset($_GET['page']) && sanitize_text_field($_GET['page']) === 'google-reviews-settings') {
            if ($this->is_min) {
                wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/widgets-for-google-reviews-and-ratings-admin.min.js', ['jquery'], $this->version, false);
            } else {
                wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/widgets-for-google-reviews-and-ratings-admin.js', ['jquery'], $this->version, false);
            }
            wp_localize_script($this->plugin_name, 'wgrr_ajax', ['nonce' => wp_create_nonce('wgrr_nonce')]);
        }
    }

    public function wgrr_reviews_menu() {
        add_menu_page(
                esc_html__('Google Reviews Settings', 'widgets-for-google-reviews-and-ratings'),
                esc_html__('Google Reviews', 'widgets-for-google-reviews-and-ratings'),
                'manage_options',
                'google-reviews-settings',
                [$this, 'wgrr_reviews_settings_page'],
                esc_url(WGRR_ASSET_URL . 'admin/css/images/star.png'),
                15
        );
    }

    public function wgrr_reviews_settings_page() {
        $allowed_html = [
            'span' => ['style' => [], 'class' => []],
            'svg' => ['class' => [], 'viewBox' => [], 'xmlns' => []],
            'polygon' => ['points' => [], 'fill' => []],
            'defs' => [],
            'linearGradient' => ['id' => []],
            'stop' => ['offset' => [], 'stop-color' => []],
        ];
        $setting_tabs = apply_filters('wgrr_setting_tab', [
            'widget_customizer' => [
                'label' => esc_html__('Widget Customizer', 'widgets-for-google-reviews-and-ratings'),
                'sub_items' => [
                    'connect_google' => wp_kses('<span>1</span> Connect Google', $allowed_html),
                    'select_layout' => wp_kses('<span>2</span> Select Layout', $allowed_html),
                    'get_settings' => wp_kses('<span class="dashicons dashicons-admin-generic"></span> Settings', $allowed_html),
                ],
            ],
            'get_reviews' => ['label' => esc_html__('Get Reviews', 'widgets-for-google-reviews-and-ratings')],
        ]);
        $sub_tab = empty($this->place_details) ? 'connect_google' : 'select_layout';
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'widget_customizer';
        $current_subtab = isset($_GET['subtab']) ? sanitize_text_field($_GET['subtab']) : $sub_tab;
        ?>
        <div id="wgrr-plugin-settings-page">
            <h2 class="nav-tab-wrapper" style="display: none;">
                <?php
                foreach ($setting_tabs as $name => $tab) {
                    echo '<a href="' . esc_url(admin_url('admin.php?page=google-reviews-settings&tab=' . $name)) . '" class="nav-tab ' . ($current_tab == $name ? 'nav-tab-active' : '') . '">' . esc_html($tab['label']) . '</a>';
                }
                ?>
            </h2>

            <?php
            foreach ($setting_tabs as $setting_tab_key => $setting_tab_value) {
                if ($current_tab === $setting_tab_key) {
                    if (isset($setting_tab_value['sub_items'])) {
                        do_action('wgrr_' . esc_attr($setting_tab_key) . '_setting_save_field');
                        do_action('wgrr_' . esc_attr($setting_tab_key) . '_setting', $setting_tabs);
                        foreach ($setting_tab_value['sub_items'] as $key => $value) {
                            if ($current_subtab === $key) {
                                do_action('wgrr_' . esc_attr($setting_tab_key) . '_' . esc_attr($key) . '_setting_save_field');
                                do_action('wgrr_' . esc_attr($setting_tab_key) . '_' . esc_attr($key) . '_setting');
                            }
                        }
                    } else {
                        do_action('wgrr_' . esc_attr($setting_tab_key) . '_setting_save_field');
                        do_action('wgrr_' . esc_attr($setting_tab_key) . '_setting', $setting_tabs);
                    }
                }
            }
            ?>
        </div>
        <?php
    }

    public function wgrr_widget_customizer_setting($setting_tabs) {
        try {
            $sub_tab = empty($this->place_details) ? 'connect_google' : 'select_layout';
            $current_subtab = isset($_GET['subtab']) ? sanitize_text_field($_GET['subtab']) : $sub_tab;
            ?>
            <h2 class="nav-tab-wrapper">
                <?php
                foreach ($setting_tabs['widget_customizer']['sub_items'] as $name => $tab) {
                    $class = 'done';
                    if ($name === 'connect_google' && empty($this->place_details)) {
                        $class = '';
                    } elseif (($name === 'select_layout' || $name === 'get_settings') && empty($this->place_details)) {
                        $class = 'disabled';
                    }
                    if (($current_subtab === 'select_layout' || $current_subtab === 'connect_google') && $name === 'get_settings') {
                        // $class = 'disabled';
                    }
                    echo '<a href="' . esc_url(admin_url('admin.php?page=google-reviews-settings&tab=widget_customizer&subtab=' . $name)) . '" class="nav-tab ' . ($current_subtab == $name ? 'nav-tab-active ' . esc_attr($class) : esc_attr($class)) . '">' . wp_kses_post($tab) . '</a>';
                }
                ?>
            </h2>
            <?php
        } catch (Exception $ex) {
            // Handle exception
        }
    }

    public function wgrr_widget_customizer_connect_google_setting() {
        try {
            if (empty($this->place_details)) {
                echo '<br/><br/><iframe id="wrrr_google_connect" src="' . esc_url(REPOCEAN_URL . 'place.html') . '" width="100%" height="900px" scrolling="yes" frameborder="0" allowfullscreen></iframe>';
            } else {
                $this->wgrr_display_place_details($this->place_details);
            }
        } catch (Exception $ex) {
            // Handle exception
        }
    }

    public function wgrr_widget_customizer_get_settings_setting_save_field() {
        try {
            // Code for saving shortcode settings
        } catch (Exception $ex) {
            // Handle exception
        }
    }

    public function wgrr_save_place_details() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wgrr_nonce')) {
                wp_send_json_error(esc_html__('Nonce verification failed', 'widgets-for-google-reviews-and-ratings'));
            }
            if (isset($_POST['place']['placeId'])) {
                $place_data = json_decode(sanitize_text_field(stripslashes($_POST['place']['placeId'])), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    update_option('wgrr_g_place_details', $place_data);
                    wp_send_json(['success' => true, 'return_url' => admin_url('admin.php?page=google-reviews-settings&tab=widget_customizer&subtab=select_layout')]);
                } else {
                    wp_send_json_error(esc_html__('Error decoding JSON.', 'widgets-for-google-reviews-and-ratings'));
                }
            } else {
                wp_send_json_error(esc_html__('Error: Place data not received.', 'widgets-for-google-reviews-and-ratings'));
            }
        } catch (Exception $ex) {
            wp_send_json_error(esc_html__('An error occurred: ', 'widgets-for-google-reviews-and-ratings') . esc_html($ex->getMessage()));
        }
    }

    public function generate_star_rating($rating) {
        try {
            $fullStars = floor($rating);
            $partialStar = $rating - $fullStars;
            $emptyStars = 5 - ceil($rating);
            $starHtml = '';
            for ($i = 0; $i < $fullStars; $i++) {
                $starHtml .= '<svg class="star" viewBox="0 0 24 24"><polygon points="12,2 15,8 22,9 17,14 18,21 12,17 6,21 7,14 2,9 9,8" fill="#fb8e28"/></svg>';
            }
            if ($partialStar > 0) {
                $partialStarOffset = esc_attr($partialStar * 100);
                $starHtml .= '
                <svg class="star" viewBox="0 0 24 24">
                    <defs>
                        <linearGradient id="partial-grad">
                            <stop offset="' . $partialStarOffset . '%" stop-color="#fb8e28" />
                            <stop offset="' . $partialStarOffset . '%" stop-color="#ccc" />
                        </linearGradient>
                    </defs>
                    <polygon points="12,2 15,8 22,9 17,14 18,21 12,17 6,21 7,14 2,9 9,8" fill="url(#partial-grad)"/>
                </svg>';
            }
            for ($i = 0; $i < $emptyStars; $i++) {
                $starHtml .= '<svg class="star" viewBox="0 0 24 24"><polygon points="12,2 15,8 22,9 17,14 18,21 12,17 6,21 7,14 2,9 9,8" fill="#ccc"/></svg>';
            }
            return $starHtml;
        } catch (Exception $ex) {
            // Handle exception
        }
    }

    public function wgrr_display_place_details($place) {
        try {
            echo '<div id="place-details" class="">
                <div class="place-info">
                    <img id="place-img" src="' . esc_url(WGRR_ASSET_URL . 'admin/image/bussiness-logo.png') . '" alt="' . esc_attr__('Place Image', 'widgets-for-google-reviews-and-ratings') . '" width="50" height="50">
                    <div class="place-details-wrapper">
                        <div id="place-name">' . esc_html($place['name']) . '</div>
                        <div class="place-meta">
                            <span id="place-rating" class="star-rating">' . wp_kses($this->generate_star_rating($place['rating'] ?? 0), ['svg' => ['class' => [], 'viewBox' => []], 'polygon' => ['points' => [], 'fill' => []], 'defs' => [], 'linearGradient' => ['id' => []], 'stop' => ['offset' => [], 'stop-color' => []]]) . '</span>
                            <span id="place-reviews">' . esc_html($place['user_ratings_total'] ?? 0) . ' ' . esc_html__('reviews', 'widgets-for-google-reviews-and-ratings') . '</span>
                        </div>
                        <div id="place-address">' . esc_html($place['formatted_address']) . '</div>
                    </div>
                </div>
                <div id="additional-details"></div>
                <button id="wgrr-disconnect-btn-red" class="btn-primary">' . esc_html__('Disconnect', 'widgets-for-google-reviews-and-ratings') . '</button>
              </div>';
        } catch (Exception $ex) {
            // handle exception
        }
    }

    public function wgrr_delete_place_details() {
        try {
            check_ajax_referer('wgrr_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json_error();
            }
            delete_option('wgrr_g_place_details');
            delete_transient('repocean_google_review');
            set_transient('wgrr_place_disconnected_message', esc_html__('Place disconnected successfully. Connect a new place.', 'widgets-for-google-reviews-and-ratings'), 60);
            wp_send_json_success();
        } catch (Exception $ex) {
            // Handle exception
        }
    }

    public function wgrr_display_message() {
        try {
            if ($message = get_transient('wgrr_place_disconnected_message')) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
                delete_transient('wgrr_place_disconnected_message');
            }
        } catch (Exception $ex) {
            // Handle exception
        }
    }

    public function wgrr_widget_customizer_select_layout_setting() {
        try {
            ?>
            <div id="wgrr_accordion">
                <h2 class="select-layout-title">Select Layout</h2>
                <div class="layout-section">
                    <div class="layout-header-with-shodtcode">
                        <div class="layout-row">
                            <h3>Layout: Slider</h3>
                            <div class="shortcode-container">
                                <span>Your Shortcode:</span>
                                <code class="repocean-shortcode" id="repocean-shortcode-id">[repocean_reviews layout="slider"]</code>
                                <button class="repocean-btn repocean-tooltip">
                                    Copy to Clipboard
                                    <span class="repocean-tooltip-message" style="opacity: 0;">Copied!</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-content">
                        <?php echo $this->wgrr_display_slider_widget(); ?>
                    </div>
                </div>
                <div class="layout-section">
                    <div class="layout-header-with-shodtcode">
                        <div class="layout-row">
                            <h3>Layout: Grid</h3>
                            <div class="shortcode-container">
                                <span>Your Shortcode:</span>
                                <code class="repocean-shortcode" id="repocean-shortcode-id">[repocean_reviews layout="grid"]</code>
                                <button class="repocean-btn repocean-tooltip">
                                    Copy to Clipboard
                                    <span class="repocean-tooltip-message" style="opacity: 0;">Copied!</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-content">
                        <?php echo $this->wgrr_display_grid_widget(); ?>
                    </div>
                </div>
                <div class="layout-section">
                    <div class="layout-header-with-shodtcode">
                        <div class="layout-row">
                            <h3>Layout: List</h3>
                            <div class="shortcode-container">
                                <span>Your Shortcode:</span>
                                <code class="repocean-shortcode" id="repocean-shortcode-id">[repocean_reviews layout="list"]</code>
                                <button class="repocean-btn repocean-tooltip">
                                    Copy to Clipboard
                                    <span class="repocean-tooltip-message" style="opacity: 0;">Copied!</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-content">
                        <?php echo $this->wgrr_display_list_widget(); ?>
                    </div>
                </div>
                <div class="layout-section">
                    <div class="layout-header-with-shodtcode">
                        <div class="layout-row">
                            <h3>Layout: Sidebar</h3>
                            <div class="shortcode-container">
                                <span>Your Shortcode:</span>
                                <code class="repocean-shortcode" id="repocean-shortcode-id">[repocean_reviews layout="sidebar"]</code>
                                <button class="repocean-btn repocean-tooltip">
                                    Copy to Clipboard
                                    <span class="repocean-tooltip-message" style="opacity: 0;">Copied!</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-content">
                        <?php echo $this->wgrr_display_sidebar_widget(); ?>
                    </div>
                </div>
            </div>

            <?php
        } catch (Exception $ex) {
            // Handle exception
        }
    }

    public function wgrr_display_slider_widget() {
        echo do_shortcode('[repocean_reviews layout="slider"]');
    }

    public function wgrr_display_sidebar_widget() {
        echo do_shortcode('[repocean_reviews layout="sidebar"]');
    }

    public function wgrr_display_list_widget() {
        echo do_shortcode('[repocean_reviews layout="list"]');
    }

    public function wgrr_display_grid_widget() {
        echo do_shortcode('[repocean_reviews layout="grid"]');
    }

    public function register_repocean_reviews_widget() {
        register_widget('Widgets_For_Google_Reviews_And_Ratings_Widget');
    }

    public function wgrr_widget_customizer_get_settings_setting() {
        try {
            if (isset($_POST['repocean_submit'])) {
                update_option('repocean_hide_date', sanitize_text_field($_POST['repocean_hide_date']));
                update_option('repocean_hide_profile_picture', sanitize_text_field($_POST['repocean_hide_profile_picture']));
                update_option('repocean_hide_google_logo', sanitize_text_field($_POST['repocean_hide_google_logo']));
                update_option('repocean_hide_rating_text', sanitize_text_field($_POST['repocean_hide_rating_text'])); // New Option
                echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
            }
            $hide_date = get_option('repocean_hide_date', 'no');
            $hide_profile_picture = get_option('repocean_hide_profile_picture', 'no');
            $hide_google_logo = get_option('repocean_hide_google_logo', 'no');
            $hide_rating_text = get_option('repocean_hide_rating_text', 'no'); // New Option
            ?>
            <div class="repocean-settings-wrapper">
                <div class="repocean-settings-header">
                    <h2><?php echo __('Google Review Widget Settings', 'widgets-for-google-reviews-and-ratings'); ?></h2>
                </div>
                <form method="post" action="">
                    <div class="repocean-settings-container">
                        <table class="repocean-form-table">
                            <tr>
                                <td>
                                    <input type="checkbox" id="repocean_hide_date" name="repocean_hide_date" value="yes" <?php checked($hide_date, 'yes'); ?>>
                                    <label for="repocean_hide_date"><?php echo __('Hide Review Date', 'widgets-for-google-reviews-and-ratings'); ?></label>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="checkbox" id="repocean_hide_profile_picture" name="repocean_hide_profile_picture" value="yes" <?php checked($hide_profile_picture, 'yes'); ?>>
                                    <label for="repocean_hide_profile_picture"><?php echo __('Hide Reviewer Profile Picture', 'widgets-for-google-reviews-and-ratings'); ?></label>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="checkbox" id="repocean_hide_google_logo" name="repocean_hide_google_logo" value="yes" <?php checked($hide_google_logo, 'yes'); ?>>
                                    <label for="repocean_hide_google_logo"><?php echo __('Hide Google Logo', 'widgets-for-google-reviews-and-ratings'); ?></label>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="checkbox" id="repocean_hide_rating_text" name="repocean_hide_rating_text" value="yes" <?php checked($hide_rating_text, 'yes'); ?>>
                                    <label for="repocean_hide_rating_text"><?php echo __('Hide Widget Rating Text (e.g. Google Rating Score: 5.0 out of 5, based on XX reviews)', 'widgets-for-google-reviews-and-ratings'); ?></label>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="repocean-submit-container">
                        <button type="submit" name="repocean_submit" class="repocean-btn"><?php echo __('Save Changes', 'widgets-for-google-reviews-and-ratings'); ?></button>
                    </div>
                </form>
            </div>
            <?php
        } catch (Exception $ex) {
            echo '<div class="notice notice-error"><p>An error occurred while saving the settings. Please try again.</p></div>';
        }
    }

    public function plugin_action_links($actions) {
        $base_url = admin_url('admin.php?page=google-reviews-settings');
        $configure_url = $base_url;
        $configure = sprintf('<a href="%s">%s</a>', $configure_url, __('Settings', 'widgets-for-google-reviews-and-ratings'));
        $custom_actions = array(
            'settings' => $configure,
        );
        return array_merge($custom_actions, $actions);
    }

    public function add_plugin_meta_links($meta, $file) {
        if (basename($file) === basename(WGRR_PLUGIN_FILE)) {
            $meta[] = '<a href="https://wordpress.org/support/plugin/widgets-for-google-reviews-and-ratings/">' . __('Community support', 'widgets-for-google-reviews-and-ratings') . '</a>';
            $meta[] = '<a href="https://wordpress.org/support/plugin/widgets-for-google-reviews-and-ratings/reviews/#new-post" target="_blank" rel="noopener noreferrer">' . __('Rate our plugin', 'widgets-for-google-reviews-and-ratings') . '</a>';
        }
        return $meta;
    }

    public function wrrr_plugin_redirect_after_activation() {
        if (get_transient('wrrr_plugin_activation_redirect')) {
            delete_transient('wrrr_plugin_activation_redirect');
            if (is_network_admin() || isset($_GET['activate-multi'])) {
                return;
            }
            wp_redirect(admin_url('admin.php?page=google-reviews-settings'));
            exit;
        }
    }

    public function repocean_hide_review_button() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in.']);
            return;
        }
        update_user_meta($user_id, 'repocean_hide_review_notice', true);
        wp_send_json_success();
    }
}
?>
