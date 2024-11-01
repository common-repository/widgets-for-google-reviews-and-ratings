<?php

/**
 * @wordpress-plugin
 * Plugin Name:       Widgets for Google Business Reviews and Ratings
 * Plugin URI:        https://repocean.com/
 * Description:       Quickly and easily embed Google reviews into your WordPress site. Boost SEO, build trust, and increase sales with Google reviews.
 * Version:           1.0.6
 * Author:            repocean
 * Author URI:        https://profiles.wordpress.org/repocean/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       widgets-for-google-reviews-and-ratings
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('WGRR_VERSION', '1.0.6');

if (!defined('WGRR_PLUGIN_DIR')) {
    define('WGRR_PLUGIN_DIR', dirname(__FILE__));
}

if (!defined('REPOCEAN_URL')) {
    define('REPOCEAN_URL', 'https://repocean.com/');
}
if (!defined('WGRR_ASSET_URL')) {
    define('WGRR_ASSET_URL', plugin_dir_url(__FILE__));
}
if (!defined('WGRR_PLUGIN_FILE')) {
    define('WGRR_PLUGIN_FILE',  __FILE__);
}
if (!defined('WGRR_PLUGIN_BASE_NAME')) {
    define('WGRR_PLUGIN_BASE_NAME',  plugin_basename(__FILE__));
}



require plugin_dir_path(__FILE__) . 'includes/class-widgets-for-google-reviews-and-ratings.php';

function run_widgets_for_google_reviews_and_ratings() {
    $plugin = new Widgets_For_Google_Reviews_And_Ratings();
    $plugin->run();
}

run_widgets_for_google_reviews_and_ratings();


register_activation_hook(__FILE__, 'wrrr_plugin_activate');

function wrrr_plugin_activate() {
    // Set a transient to trigger the redirect
    set_transient('wrrr_plugin_activation_redirect', true, 30);
}
