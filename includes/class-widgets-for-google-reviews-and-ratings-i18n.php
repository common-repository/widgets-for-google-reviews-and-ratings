<?php

class Widgets_For_Google_Reviews_And_Ratings_i18n {

    public function load_plugin_textdomain() {

        load_plugin_textdomain(
                'widgets-for-google-reviews-and-ratings',
                false,
                plugin_basename(dirname(WGRR_PLUGIN_FILE)) . '/languages'
        );
    }
}
