<?php

class Widgets_For_Google_Reviews_And_Ratings {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        if (defined('WGRR_VERSION')) {
            $this->version = WGRR_VERSION;
        } else {
            $this->version = '1.0.6';
        }
        $this->plugin_name = 'widgets-for-google-reviews-and-ratings';
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-widgets-for-google-reviews-and-ratings-loader.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-widgets-for-google-reviews-and-ratings-i18n.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-widgets-for-google-reviews-and-ratings-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-widgets-for-google-reviews-and-ratings-widget.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-widgets-for-google-reviews-and-ratings-public.php';
        $this->loader = new Widgets_For_Google_Reviews_And_Ratings_Loader();
    }

    private function set_locale() {
        $plugin_i18n = new Widgets_For_Google_Reviews_And_Ratings_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    private function define_admin_hooks() {
        $plugin_admin = new Widgets_For_Google_Reviews_And_Ratings_Admin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles', 999);
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'wgrr_reviews_menu');
        $this->loader->add_action('wgrr_widget_customizer_connect_google_setting', $plugin_admin, 'wgrr_widget_customizer_connect_google_setting');
        $this->loader->add_action('wgrr_widget_customizer_get_settings_setting', $plugin_admin, 'wgrr_widget_customizer_get_settings_setting');
        $this->loader->add_action('wgrr_widget_customizer_get_settings_setting_save_field', $plugin_admin, 'wgrr_widget_customizer_get_settings_setting_save_field');
        $this->loader->add_action('wgrr_widget_customizer_setting', $plugin_admin, 'wgrr_widget_customizer_setting', 10, 1);
        $this->loader->add_action('wp_ajax_wgrr_save_place_details', $plugin_admin, 'wgrr_save_place_details');
        $this->loader->add_action('wp_ajax_delete_place_details', $plugin_admin, 'wgrr_delete_place_details');
        $this->loader->add_action('wgrr_widget_customizer_select_layout_setting', $plugin_admin, 'wgrr_widget_customizer_select_layout_setting');
        $this->loader->add_action('admin_notices', $plugin_admin, 'wgrr_display_message');
        $this->loader->add_action('widgets_init', $plugin_admin, 'register_repocean_reviews_widget');
        $basename = WGRR_PLUGIN_BASE_NAME;
        $prefix = is_network_admin() ? 'network_admin_' : '';
        $this->loader->add_filter("{$prefix}plugin_action_links_$basename", $plugin_admin, 'plugin_action_links', 10, 1);
        $this->loader->add_filter('plugin_row_meta', $plugin_admin, 'add_plugin_meta_links', 10, 2);
        $this->loader->add_action('admin_init', $plugin_admin, 'wrrr_plugin_redirect_after_activation');
        $this->loader->add_action('wp_ajax_repocean_hide_review_button', $plugin_admin, 'repocean_hide_review_button');
    }

    private function define_public_hooks() {
        new Widgets_For_Google_Reviews_And_Ratings_Public($this->get_plugin_name(), $this->get_version());
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_loader() {
        return $this->loader;
    }

    public function get_version() {
        return $this->version;
    }
}
