<?php

class Widgets_For_Google_Reviews_And_Ratings_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
                'repocean_reviews_widget',
                'Google Reviews',
                array('description' => __('A Widget to display Google Reviews', 'widgets-for-google-reviews-and-ratings'))
        );
    }

    public function widget($args, $instance) {
        echo wp_kses_post($args['before_widget']);
        if (!empty($instance['title'])) {
            echo wp_kses_post($args['before_title']) . esc_html(apply_filters('widget_title', $instance['title'])) . wp_kses_post($args['after_title']);
        }
        echo do_shortcode('[repocean_reviews]');
        echo wp_kses_post($args['after_widget']);
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : esc_html__('Google Reviews', 'widgets-for-google-reviews-and-ratings');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php echo esc_html__('Title:', 'widgets-for-google-reviews-and-ratings'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? wp_strip_all_tags($new_instance['title']) : '';
        return $instance;
    }
}
