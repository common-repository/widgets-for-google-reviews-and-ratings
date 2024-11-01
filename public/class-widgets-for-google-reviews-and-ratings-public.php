<?php

class Widgets_For_Google_Reviews_And_Ratings_Public {

    private $plugin_name;
    private $version;
    public $place_details;
    public $is_min;
    public $get_review_url;
    public $hide_date;
    public $hide_profile_picture;
    public $hide_google_logo;
    public $repocean_hide_rating_text;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->place_details = get_option('wgrr_g_place_details', '');
        add_shortcode('repocean_reviews', array($this, 'repocean_reviews_shortcode'));
        $this->is_min = !( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG );
        $this->get_review_url = REPOCEAN_URL . 'reviews/getJson/' . ($this->place_details['place_id'] ?? '');
        $this->update_review_count = REPOCEAN_URL . 'reviews/update_review_count/' . ($this->place_details['place_id'] ?? '');
        $this->hide_date = 'yes' === get_option('repocean_hide_date', 'no');
        $this->hide_profile_picture = 'yes' === get_option('repocean_hide_profile_picture', 'no');
        $this->hide_google_logo = 'yes' === get_option('repocean_hide_google_logo', 'no');
        $this->repocean_hide_rating_text = 'yes' === get_option('repocean_hide_rating_text', 'no');
        add_action('repocean_update_review_count', array($this, 'update_google_place_review_count'));
    }

    public function repocean_reviews_shortcode($atts) {
        $atts = shortcode_atts(
                array(
                    'layout' => 'slider',
                    'limit' => '100'
                ),
                $atts,
                'repocean_reviews'
        );
        $output = '';
        switch ($atts['layout']) {
            case 'slider':
                $output .= $this->repocean_reviews_slider_shortcode($atts);
                break;
            case 'grid':
                $output .= $this->repocean_reviews_grid_shortcode($atts);
                break;
            case 'list':
                $output .= $this->repocean_reviews_list_shortcode($atts);
                break;
            case 'sidebar':
                $output .= $this->repocean_reviews_sidebar_shortcode($atts);
                break;
            default:
                $output .= $this->repocean_reviews_slider_shortcode($atts);
                break;
        }
        return $output;
    }

    public function repocean_reviews_slider_shortcode($atts) {
        try {
            if (!empty($this->place_details)) {
                $limit = isset($atts['limit']) ? $atts['limit'] : 100;
                wp_enqueue_script($this->plugin_name . '-slick-carousel-js', plugin_dir_url(__FILE__) . 'js/slick.min.js', ['jquery'], '1.8.1', true);
                if ($this->is_min) {
                    wp_enqueue_script($this->plugin_name . 'slider', plugin_dir_url(__FILE__) . 'js/widgets-for-google-reviews-and-ratings-slider.min.js', ['jquery'], $this->version, true);
                    wp_enqueue_style($this->plugin_name . '-slider', plugin_dir_url(__FILE__) . 'css/widgets-for-google-reviews-and-ratings-slider.min.css', [], $this->version, 'all');
                } else {
                    wp_enqueue_script($this->plugin_name . 'slider', plugin_dir_url(__FILE__) . 'js/widgets-for-google-reviews-and-ratings-slider.js', ['jquery'], $this->version, true);
                    wp_enqueue_style($this->plugin_name . '-slider', plugin_dir_url(__FILE__) . 'css/widgets-for-google-reviews-and-ratings-slider.css', [], $this->version, 'all');
                }
                $place_data = $this->place_details;
                $address_components = $place_data['address_components'] ?? [];
                $address_parts = [
                    $address_components[0]['long_name'] ?? '',
                    $address_components[1]['long_name'] ?? '',
                    $address_components[2]['long_name'] ?? '',
                    $address_components[3]['long_name'] ?? ''
                ];
                $streetAddress = implode(', ', array_filter($address_parts));
                $addressLocality = $address_components[4]['long_name'] ?? '';
                $addressRegion = $address_components[5]['long_name'] ?? '';
                $addressRegion .= isset($address_components[7]['long_name']) ? ', ' . $address_components[7]['long_name'] : '';
                $postalCode = $address_components[9]['long_name'] ?? '';
                $addressCountry = $address_components[8]['long_name'] ?? '';
                $formatted_phone_number = $place_data['formatted_phone_number'] ?? '';
                $html = '<div class="repocean-slider-main" itemscope itemtype="https://schema.org/LocalBusiness">';
                $html .= '<meta itemprop="name" content="' . esc_attr($place_data['name'] ?? '') . '">';
                $html .= '<meta itemprop="telephone" content="' . esc_attr($formatted_phone_number) . '">';
                $html .= '<meta itemprop="image" content="' . esc_attr($place_data['icon'] ?? '') . '">';
                $html .= '<div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
                $html .= '<meta itemprop="streetAddress" content="' . esc_attr($streetAddress) . '">';
                $html .= '<meta itemprop="addressLocality" content="' . esc_attr($addressLocality) . '">';
                $html .= '<meta itemprop="addressRegion" content="' . esc_attr($addressRegion) . '">';
                $html .= '<meta itemprop="postalCode" content="' . esc_attr($postalCode) . '">';
                $html .= '<meta itemprop="addressCountry" content="' . esc_attr($addressCountry) . '">';
                $html .= '</div>';
                $html .= '<div itemprop="geo" itemscope itemtype="https://schema.org/GeoCoordinates">';
                $html .= '<meta itemprop="latitude" content="' . esc_attr($place_data['geometry']['location']['lat'] ?? '') . '">';
                $html .= '<meta itemprop="longitude" content="' . esc_attr($place_data['geometry']['location']['lng'] ?? '') . '">';
                $html .= '</div>';
                $html .= '<div itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">';
                $html .= '<meta itemprop="ratingValue" content="' . esc_attr($place_data['rating'] ?? '') . '">';
                $html .= '<meta itemprop="bestRating" content="5">';
                $html .= '<meta itemprop="reviewCount" content="' . esc_attr($place_data['user_ratings_total'] ?? '') . '">';
                $html .= '</div>';
                $repocean_google_review = 'repocean_google_review';
                $reviews = get_transient($repocean_google_review);
                if (false === $reviews) {
                    $response = wp_remote_get($this->get_review_url);
                    if (is_wp_error($response)) {
                        return $html . 'Unable to retrieve reviews at this time.</div>';
                    }
                    $data = wp_remote_retrieve_body($response);
                    $reviews = json_decode($data, true);
                    if (empty($reviews)) {
                        return $html . 'No reviews found.</div>';
                    }
                    do_action('repocean_update_review_count');
                    set_transient($repocean_google_review, $reviews, 1 * HOUR_IN_SECONDS);
                }
                if (empty($reviews)) {
                    return $html . 'No reviews found.</div>';
                }
                $html .= '<div class="repocean-content-wrapper"><div class="repocean-slider-box-parent">';
                $reviews = array_slice($reviews, 0, $limit);
                foreach ($reviews as $review) {
                    $published_date = !empty($review['published_date']) ? sprintf(__('%s ago', 'widgets-for-google-reviews-and-ratings'), human_time_diff($review['published_date'] / 1000000)) : '';
                    $author_name = esc_html($review['author_name'] ?? '');
                    $author_name = mb_strimwidth($author_name, 0, 25, '...');
                    $profile_photo_url = esc_url($review['profile_photo_url'] ?? '');
                    $text = esc_html($review['text'] ?? '');
                    $rating = esc_html($review['rating'] ?? '');
                    $read_more_class = (strlen($text) > 180) ? 'max63' : 'max87';
                    $read_more_hide_show = (strlen($text) > 180) ? 'show' : 'hide';
                    $html .= '<div class="slider-box" itemprop="review" itemscope itemtype="https://schema.org/Review">';
                    $html .= '<div class="slider-box-inner" style="display:none;">';
                    $html .= '<div class="img-text-content">';
                    $html .= '<div class="profile-img-info">';
                    if($this->hide_profile_picture === false) {
                        $html .= '<div class="profile-img">';
                        $html .= '<img src="' . $profile_photo_url . '" alt="Reviewer Image" itemprop="image">';
                        $html .= '</div>';
                    }
                    $html .= '<div class="profile-info">';
                    $html .= '<div class="profile-title">';
                    $html .= '<h6 itemprop="author" itemscope itemtype="https://schema.org/Person"><span itemprop="name">' . $author_name . '</span></h6>';
                    $html .= '</div>';
                    if ($this->hide_date === false) {
                        $html .= '<div class="profile-date"><p>' . $published_date . '</p></div>';
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                    if( $this->hide_google_logo === false ) {
                        $html .= '<div class="right-img">';
                        $html .= '<img src="' . WGRR_ASSET_URL . 'public/css/images/google-icon.svg" alt="Google_icon">';
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                    $html .= '<div class="review-box-parent">';
                    $html .= '<div class="review-box" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating" data-rating="' . $rating . '">';
                    $html .= '<meta itemprop="ratingValue" content="' . $rating . '">';
                    $html .= '<meta itemprop="bestRating" content="5">';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '<div class="description ' . $read_more_class . '" itemprop="reviewBody">';
                    $html .= '<p>' . $text . '</p>';
                    $html .= '</div>';
                    $html .= '<div class="button-content ' . $read_more_hide_show . '">';
                    $html .= '<div class="readmore-button">';
                    $html .= '<a href="#">Read more</a>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '</div>';
                }
                $html .= '</div></div>';
                if( $this->repocean_hide_rating_text === false ) {
                    $html .= '<div class="repocean-footer" style="display:none;">';
                    $html .= '<div class="repocean-rating-text">';
                    $html .= '<span class="nowrap"><strong>' . __('Google', 'widgets-for-google-reviews-and-ratings') . '</strong> ' . __('Rating Score:', 'widgets-for-google-reviews-and-ratings') . ' </span>';
                    $html .= '<span class="nowrap"><strong>' . number_format($place_data['rating'] ?? 0, 1) . '</strong> ' . __('out of 5,', 'widgets-for-google-reviews-and-ratings') . ' </span>';
                    $html .= '<span class="nowrap">' . __('based on', 'widgets-for-google-reviews-and-ratings') . ' <strong>' . ($place_data['user_ratings_total'] ?? 0) . ' ' . __('reviews', 'widgets-for-google-reviews-and-ratings') . '</strong></span>';
                    $html .= '</div></div>';
                }
                $html .= '</div>';
                return $html;
            } else {
                return "Your Google Place not connected";
            }
        } catch (Exception $ex) {
            
        }
    }

    public function repocean_reviews_grid_shortcode($atts) {
        try {
            if (!empty($this->place_details)) {
                $limit = isset($atts['limit']) ? $atts['limit'] : 100;
                if ($this->is_min) {
                    wp_enqueue_script($this->plugin_name . 'grid', plugin_dir_url(__FILE__) . 'js/widgets-for-google-reviews-and-ratings-grid.min.js', ['jquery'], $this->version, true);
                    wp_enqueue_style($this->plugin_name . '-grid', plugin_dir_url(__FILE__) . 'css/widgets-for-google-reviews-and-ratings-grid.min.css', [], $this->version, 'all');
                } else {
                    wp_enqueue_script($this->plugin_name . 'grid', plugin_dir_url(__FILE__) . 'js/widgets-for-google-reviews-and-ratings-grid.js', ['jquery'], $this->version, true);
                    wp_enqueue_style($this->plugin_name . '-grid', plugin_dir_url(__FILE__) . 'css/widgets-for-google-reviews-and-ratings-grid.css', [], $this->version, 'all');
                }

                $place_data = $this->place_details;
                $address_components = $place_data['address_components'] ?? [];
                $address_parts = [
                    $address_components[0]['long_name'] ?? '',
                    $address_components[1]['long_name'] ?? '',
                    $address_components[2]['long_name'] ?? '',
                    $address_components[3]['long_name'] ?? ''
                ];
                $streetAddress = implode(', ', array_filter($address_parts));
                $addressLocality = $address_components[4]['long_name'] ?? '';
                $addressRegion = $address_components[5]['long_name'] ?? '';
                $addressRegion .= isset($address_components[7]['long_name']) ? ', ' . $address_components[7]['long_name'] : '';
                $postalCode = $address_components[9]['long_name'] ?? '';
                $addressCountry = $address_components[8]['long_name'] ?? '';
                $formatted_phone_number = $place_data['formatted_phone_number'] ?? '';

                $html = '<div class="repocean-grid-main" itemscope itemtype="https://schema.org/LocalBusiness">';
                $html .= '<meta itemprop="name" content="' . esc_attr($place_data['name'] ?? '') . '">';
                $html .= '<meta itemprop="telephone" content="' . esc_attr($formatted_phone_number) . '">';
                $html .= '<meta itemprop="image" content="' . esc_attr($place_data['icon'] ?? '') . '">';
                $html .= '<div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
                $html .= '<meta itemprop="streetAddress" content="' . esc_attr($streetAddress) . '">';
                $html .= '<meta itemprop="addressLocality" content="' . esc_attr($addressLocality) . '">';
                $html .= '<meta itemprop="addressRegion" content="' . esc_attr($addressRegion) . '">';
                $html .= '<meta itemprop="postalCode" content="' . esc_attr($postalCode) . '">';
                $html .= '<meta itemprop="addressCountry" content="' . esc_attr($addressCountry) . '">';
                $html .= '</div>';
                $html .= '<div itemprop="geo" itemscope itemtype="https://schema.org/GeoCoordinates">';
                $html .= '<meta itemprop="latitude" content="' . esc_attr($place_data['geometry']['location']['lat'] ?? '') . '">';
                $html .= '<meta itemprop="longitude" content="' . esc_attr($place_data['geometry']['location']['lng'] ?? '') . '">';
                $html .= '</div>';
                $html .= '<div itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">';
                $html .= '<meta itemprop="ratingValue" content="' . esc_attr($place_data['rating'] ?? '') . '">';
                $html .= '<meta itemprop="bestRating" content="5">';
                $html .= '<meta itemprop="reviewCount" content="' . esc_attr($place_data['user_ratings_total'] ?? '') . '">';
                $html .= '</div>';

                $repocean_google_review = 'repocean_google_review';
                $reviews = get_transient($repocean_google_review);
                if (false === $reviews) {
                    $response = wp_remote_get($this->get_review_url);
                    if (is_wp_error($response)) {
                        return '<div class="repocean-grid-main">Unable to retrieve reviews at this time.</div>';
                    }
                    $data = wp_remote_retrieve_body($response);
                    $reviews = json_decode($data, true);
                    if (empty($reviews)) {
                        return '<div class="repocean-grid-main">No reviews found.</div>';
                    }
                    do_action('repocean_update_review_count');
                    set_transient($repocean_google_review, $reviews, 1 * HOUR_IN_SECONDS);
                }
                if (empty($reviews)) {
                    return '<div class="repocean-grid-main">No reviews found.</div>';
                }
                $html .= '<div class="content-wrapper">';
                $html .= '<div class="grid-outer">';
                if( $this->repocean_hide_rating_text === false ) {
                    $html .= '<div class="review-sub-title">';
                    $html .= '<div class="review-link">';
                    $html .= '<div class="excellent-text">';
                    $html .= '<strong>' . __('EXCELLENT', 'widgets-for-google-reviews-and-ratings') . '</strong>';
                    $html .= '</div>';

                    $place_rating = isset($place_data['rating']) ? floatval($place_data['rating']) : 0;
                    $full_stars = floor($place_rating);
                    $has_half_star = ($place_rating - $full_stars) >= 0.5;
                    $total_stars = 5;

                    $html .= '<div class="review-box-parent">';
                    for ($i = 1; $i <= $total_stars; $i++) {
                        if ($i <= $full_stars) {
                            $html .= '<div class="review-box"><img src="' . WGRR_ASSET_URL . 'public/css/images/fill-review-icon.svg" alt="Full Star"></div>';
                        } elseif ($has_half_star && $i == ($full_stars + 1)) {
                            $html .= '<div class="review-box"><img src="' . WGRR_ASSET_URL . 'public/css/images/fill-half-icon.svg" alt="Half Star"></div>';
                        } else {
                            $html .= '<div class="review-box"><img src="' . WGRR_ASSET_URL . 'public/css/images/fill-empty-icon.svg" alt="Empty Star"></div>';
                        }
                    }
                    $html .= '</div>';
                    $html .= '<div class="review-text excellent-text"><strong>' . esc_html($place_data['user_ratings_total'] ?? 0) . ' reviews</strong></div>';
                    if( $this->hide_google_logo === false ) {
                        $html .= '<div class="gi-small-logo"><img src="' . WGRR_ASSET_URL . 'public/css/images/google-brand.png" alt="Google_icon"></div>';
                    }
                    $html .= '</div>';
                    $html .= '</div>'; // review-sub-title
                }
                $html .= '<div class="grid-box-parent">';
                $reviews = array_slice($reviews, 0, $limit);
                foreach ($reviews as $review) {
                    $published_date = !empty($review['published_date']) ? sprintf(__('%s ago', 'widgets-for-google-reviews-and-ratings'), human_time_diff($review['published_date'] / 1000000)) : '';
                    $author_name = esc_html($review['author_name'] ?? '');
                    $author_name = mb_strimwidth($author_name, 0, 25, '...');
                    $profile_photo_url = esc_url($review['profile_photo_url'] ?? '');
                    $text = esc_html($review['text'] ?? '');
                    $read_more_class = (strlen($text) > 180) ? 'max63' : 'max87';
                    $read_more_hide_show = (strlen($text) > 180) ? 'show' : 'hide';
                    $rating = isset($review['rating']) ? floatval($review['rating']) : 0;

                    $html .= '<div class="grid-box" itemprop="review" itemscope itemtype="https://schema.org/Review">';
                    $html .= '<div class="grid-box-inner">';
                    $html .= '<div class="img-text-content">';
                    $html .= '<div class="profile-img-info">';
                    if($this->hide_profile_picture === false) {
                        $html .= '<div class="profile-img">';
                        $html .= '<img src="' . esc_url($profile_photo_url) . '" alt="Reviewer Image" itemprop="image">';
                        $html .= '</div>';
                    }
                    $html .= '<div class="profile-info">';
                    $html .= '<div class="profile-title">';
                    $html .= '<h6 itemprop="author" itemscope itemtype="https://schema.org/Person"><span itemprop="name">' . $author_name . '</span></h6>';
                    $html .= '</div>';
                    if ($this->hide_date === false) {
                        $html .= '<div class="profile-date"><p>' . $published_date . '</p></div>';
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                    if( $this->hide_google_logo === false ) {
                        $html .= '<div class="right-img">';
                        $html .= '<div class="right-img-wrapper">';
                        $html .= '<img src="' . WGRR_ASSET_URL . 'public/css/images/google-icon.svg" alt="Google_icon">';
                        $html .= '</div>';
                        $html .= '</div>';
                    }
                    $html .= '</div>';

                    $html .= '<div class="review-content">';
                    $html .= '<div class="review-box-parent">';
                    $full_stars_review = floor($rating);
                    $has_half_star_review = ($rating - $full_stars_review) >= 0.5;
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $full_stars_review) {
                            $html .= '<div class="review-box"><img src="' . WGRR_ASSET_URL . 'public/css/images/fill-review-icon.svg" alt="Full Star"></div>';
                        } elseif ($has_half_star_review && $i == ($full_stars_review + 1)) {
                            $html .= '<div class="review-box"><img src="' . WGRR_ASSET_URL . 'public/css/images/fill-half-icon.svg" alt="Half Star"></div>';
                        } else {
                            $html .= '<div class="review-box"><img src="' . WGRR_ASSET_URL . 'public/css/images/fill-empty-icon.svg" alt="Empty Star"></div>';
                        }
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '<div class="description ' . $read_more_class . '" itemprop="reviewBody">';
                    $html .= '<p>' . $text . '</p>';
                    $html .= '</div>';
                    $html .= '<div class="button-content ' . $read_more_hide_show . '">';
                    $html .= '<div class="readmore-button">';
                    $html .= '<a href="#">Read more</a>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '</div>';
                }
                $html .= '</div>';
                $html .= '<button id="loadMore" class="load-more-btn">Load More</button>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
                return $html;
            } else {
                return "Your Google Place not connected";
            }
        } catch (Exception $ex) {
            return 'An error occurred.';
        }
    }

    public function repocean_reviews_list_shortcode($atts) {
        try {
            if (!empty($this->place_details)) {
                $limit = isset($atts['limit']) ? $atts['limit'] : 100;
                if ($this->is_min) {
                    wp_enqueue_script($this->plugin_name . 'list', plugin_dir_url(__FILE__) . 'js/widgets-for-google-reviews-and-ratings-list.min.js', ['jquery'], $this->version, true);
                    wp_enqueue_style($this->plugin_name . '-list', plugin_dir_url(__FILE__) . 'css/widgets-for-google-reviews-and-ratings-list.min.css', [], $this->version, 'all');
                } else {
                    wp_enqueue_script($this->plugin_name . 'list', plugin_dir_url(__FILE__) . 'js/widgets-for-google-reviews-and-ratings-list.js', ['jquery'], $this->version, true);
                    wp_enqueue_style($this->plugin_name . '-list', plugin_dir_url(__FILE__) . 'css/widgets-for-google-reviews-and-ratings-list.css', [], $this->version, 'all');
                }
                $place_data = $this->place_details;
                $address_components = $place_data['address_components'] ?? [];
                $address_parts = [
                    $address_components[0]['long_name'] ?? '',
                    $address_components[1]['long_name'] ?? '',
                    $address_components[2]['long_name'] ?? '',
                    $address_components[3]['long_name'] ?? ''
                ];
                $streetAddress = implode(', ', array_filter($address_parts));
                $addressLocality = $address_components[4]['long_name'] ?? '';
                $addressRegion = $address_components[5]['long_name'] ?? '';
                $addressRegion .= isset($address_components[7]['long_name']) ? ', ' . $address_components[7]['long_name'] : '';
                $postalCode = $address_components[9]['long_name'] ?? '';
                $addressCountry = $address_components[8]['long_name'] ?? '';
                $formatted_phone_number = $place_data['formatted_phone_number'] ?? '';
                $html = '<div class="repocean-list-main" itemscope itemtype="https://schema.org/LocalBusiness">';
                $html .= '<meta itemprop="name" content="' . esc_attr($place_data['name'] ?? '') . '">';
                $html .= '<meta itemprop="telephone" content="' . esc_attr($formatted_phone_number) . '">';
                $html .= '<meta itemprop="image" content="' . esc_attr($place_data['icon'] ?? '') . '">';
                $html .= '<div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
                $html .= '<meta itemprop="streetAddress" content="' . esc_attr($streetAddress) . '">';
                $html .= '<meta itemprop="addressLocality" content="' . esc_attr($addressLocality) . '">';
                $html .= '<meta itemprop="addressRegion" content="' . esc_attr($addressRegion) . '">';
                $html .= '<meta itemprop="postalCode" content="' . esc_attr($postalCode) . '">';
                $html .= '<meta itemprop="addressCountry" content="' . esc_attr($addressCountry) . '">';
                $html .= '</div>';
                $html .= '<div itemprop="geo" itemscope itemtype="https://schema.org/GeoCoordinates">';
                $html .= '<meta itemprop="latitude" content="' . esc_attr($place_data['geometry']['location']['lat'] ?? '') . '">';
                $html .= '<meta itemprop="longitude" content="' . esc_attr($place_data['geometry']['location']['lng'] ?? '') . '">';
                $html .= '</div>';
                $html .= '<div itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">';
                $html .= '<meta itemprop="ratingValue" content="' . esc_attr($place_data['rating'] ?? '') . '">';
                $html .= '<meta itemprop="bestRating" content="5">';
                $html .= '<meta itemprop="reviewCount" content="' . esc_attr($place_data['user_ratings_total'] ?? '') . '">';
                $html .= '</div>';
                $repocean_google_review = 'repocean_google_review';
                $reviews = get_transient($repocean_google_review);
                if (false === $reviews) {
                    $response = wp_remote_get($this->get_review_url);
                    if (is_wp_error($response)) {
                        return '<div class="repocean-list-main">Unable to retrieve reviews at this time.</div>';
                    }
                    $data = wp_remote_retrieve_body($response);
                    $reviews = json_decode($data, true);
                    if (empty($reviews)) {
                        return '<div class="repocean-list-main">No reviews found.</div>';
                    }
                    do_action('repocean_update_review_count');
                    set_transient($repocean_google_review, $reviews, 1 * HOUR_IN_SECONDS);
                }
                if (empty($reviews)) {
                    return '<div class="repocean-list-main">No reviews found.</div>';
                }
                $html .= '<div class="content-wrapper">';
                $html .= '<div class="list-outer">';
                if( $this->repocean_hide_rating_text === false ) {
                    $html .= '<div class="review-sub-title">';
                    $html .= '<div class="review-link">';
                    $html .= '<div class="excellent-text">';
                    $html .= '<strong>' . __('EXCELLENT', 'widgets-for-google-reviews-and-ratings') . '</strong>';
                    $html .= '</div>';
                    $html .= '<div class="review-box-parent">';
                    $place_rating = isset($place_data['rating']) ? floatval($place_data['rating']) : 0;
                    $full_stars = floor($place_rating);
                    $has_half_star = ($place_rating - $full_stars) >= 0.5;
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $full_stars) {
                            $html .= '<div class="review-box"><img src="' . WGRR_ASSET_URL . 'public/css/images/fill-review-icon.svg" alt="Full Star"></div>';
                        } elseif ($has_half_star && $i == ($full_stars + 1)) {
                            $html .= '<div class="review-box"><img src="' . WGRR_ASSET_URL . 'public/css/images/fill-half-icon.svg" alt="Half Star"></div>';
                        } else {
                            $html .= '<div class="review-box"><img src="' . WGRR_ASSET_URL . 'public/css/images/fill-empty-icon.svg" alt="Empty Star"></div>';
                        }
                    }
                    $html .= '</div>';
                    $html .= '<div class="review-text excellent-text"><strong>' . esc_html($place_data['user_ratings_total'] ?? 0) . ' reviews</strong></div>';
                    $html .= '<div class="gi-small-logo"><img src="' . WGRR_ASSET_URL . 'public/css/images/google-brand.png" alt="Google_icon"></div>';
                    $html .= '</div>';
                    $html .= '</div>';
                }
                $html .= '<div class="list-box-parent">';
                $reviews = array_slice($reviews, 0, $limit);
                foreach ($reviews as $review) {
                    $published_date = !empty($review['published_date']) ? sprintf(__('%s ago', 'widgets-for-google-reviews-and-ratings'), human_time_diff($review['published_date'] / 1000000)) : '';
                    $author_name = esc_html($review['author_name'] ?? '');
                    $author_name = mb_strimwidth($author_name, 0, 25, '...');
                    $profile_photo_url = esc_url($review['profile_photo_url'] ?? '');
                    $text = esc_html($review['text'] ?? '');
                    $read_more_hide_show = (strlen($text) > 180) ? 'show' : 'hide';
                    $rating = isset($review['rating']) ? floatval($review['rating']) : 0;
                    $html .= '<div class="list-box" itemprop="review" itemscope itemtype="https://schema.org/Review">';
                    $html .= '<div class="list-box-inner">';
                    $html .= '<div class="img-text-content">';
                    $html .= '<div class="profile-img-info">';
                    if($this->hide_profile_picture === false) {
                        $html .= '<div class="profile-img">';
                        $html .= '<img src="' . esc_url($profile_photo_url) . '" alt="Reviewer Image" itemprop="image">';
                        $html .= '</div>';
                    }
                    $html .= '<div class="profile-info">';
                    $html .= '<div class="profile-title">';
                    $html .= '<h6 itemprop="author" itemscope itemtype="https://schema.org/Person"><span itemprop="name">' . $author_name . '</span></h6>';
                    $html .= '</div>';
                    if ($this->hide_date === false) {
                        $html .= '<div class="profile-date"><p>' . $published_date . '</p></div>';
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                    if( $this->hide_google_logo === false ) {
                        $html .= '<div class="right-img">';
                        $html .= '<div class="right-img-wrapper">';
                        $html .= '<img src="' . WGRR_ASSET_URL . 'public/css/images/google-icon.svg" alt="Google_icon">';
                        $html .= '</div>';
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                    $html .= '<div class="review-content">';
                    $html .= '<div class="review-box-parent">';
                    $full_stars_review = floor($rating);
                    $has_half_star_review = ($rating - $full_stars_review) >= 0.5;
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $full_stars_review) {
                            $html .= '<div class="review-box"><img src="' . WGRR_ASSET_URL . 'public/css/images/fill-review-icon.svg" alt="Full Star"></div>';
                        } elseif ($has_half_star_review && $i == ($full_stars_review + 1)) {
                            $html .= '<div class="review-box"><img src="' . WGRR_ASSET_URL . 'public/css/images/fill-half-icon.svg" alt="Half Star"></div>';
                        } else {
                            $html .= '<div class="review-box"><img src="' . WGRR_ASSET_URL . 'public/css/images/fill-empty-icon.svg" alt="Empty Star"></div>';
                        }
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '<div class="description" itemprop="reviewBody">';
                    $html .= '<p>' . $text . '</p>';
                    $html .= '</div>';
                    $html .= '<div class="button-content ' . $read_more_hide_show . '">';
                    $html .= '<div class="readmore-button">';
                    $html .= '<a href="#">Read more</a>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '</div>';
                }
                $html .= '</div>';
                $html .= '<button id="loadMoreList" class="load-more-btn">Load More</button>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
                return $html;
            } else {
                return "Your Google Place not connected";
            }
        } catch (Exception $ex) {
            return 'An error occurred.';
        }
    }

    public function repocean_reviews_sidebar_shortcode($atts) {
        try {
            if (!empty($this->place_details)) {
                $limit = isset($atts['limit']) ? $atts['limit'] : 100;
                wp_enqueue_script($this->plugin_name . '-slick-carousel-js', plugin_dir_url(__FILE__) . 'js/slick.min.js', ['jquery'], '1.8.1', true);
                if ($this->is_min) {
                    wp_enqueue_script($this->plugin_name . '-sidebar', plugin_dir_url(__FILE__) . 'js/widgets-for-google-reviews-and-ratings-sidebar.min.js', ['jquery'], $this->version, true);
                    wp_enqueue_style($this->plugin_name . '-sidebar', plugin_dir_url(__FILE__) . 'css/widgets-for-google-reviews-and-ratings-sidebar.min.css', [], $this->version, 'all');
                } else {
                    wp_enqueue_script($this->plugin_name . '-sidebar', plugin_dir_url(__FILE__) . 'js/widgets-for-google-reviews-and-ratings-sidebar.js', ['jquery'], $this->version, true);
                    wp_enqueue_style($this->plugin_name . '-sidebar', plugin_dir_url(__FILE__) . 'css/widgets-for-google-reviews-and-ratings-sidebar.css', [], $this->version, 'all');
                }
                $place_data = $this->place_details;
                $address_components = $place_data['address_components'] ?? [];
                $address_parts = [
                    $address_components[0]['long_name'] ?? '',
                    $address_components[1]['long_name'] ?? '',
                    $address_components[2]['long_name'] ?? '',
                    $address_components[3]['long_name'] ?? ''
                ];
                $streetAddress = implode(', ', array_filter($address_parts));
                $addressLocality = $address_components[4]['long_name'] ?? '';
                $addressRegion = $address_components[5]['long_name'] ?? '';
                $addressRegion .= isset($address_components[7]['long_name']) ? ', ' . $address_components[7]['long_name'] : '';
                $postalCode = $address_components[9]['long_name'] ?? '';
                $addressCountry = $address_components[8]['long_name'] ?? '';
                $formatted_phone_number = $place_data['formatted_phone_number'] ?? '';
                $html = '<div class="repocean-sidebar-main" itemscope itemtype="https://schema.org/LocalBusiness">';
                $html .= '<meta itemprop="name" content="' . esc_attr($place_data['name'] ?? '') . '">';
                $html .= '<meta itemprop="telephone" content="' . esc_attr($formatted_phone_number) . '">';
                $html .= '<meta itemprop="image" content="' . esc_attr($place_data['icon'] ?? '') . '">';
                $html .= '<div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
                $html .= '<meta itemprop="streetAddress" content="' . esc_attr($streetAddress) . '">';
                $html .= '<meta itemprop="addressLocality" content="' . esc_attr($addressLocality) . '">';
                $html .= '<meta itemprop="addressRegion" content="' . esc_attr($addressRegion) . '">';
                $html .= '<meta itemprop="postalCode" content="' . esc_attr($postalCode) . '">';
                $html .= '<meta itemprop="addressCountry" content="' . esc_attr($addressCountry) . '">';
                $html .= '</div>';
                $html .= '<div itemprop="geo" itemscope itemtype="https://schema.org/GeoCoordinates">';
                $html .= '<meta itemprop="latitude" content="' . esc_attr($place_data['geometry']['location']['lat'] ?? '') . '">';
                $html .= '<meta itemprop="longitude" content="' . esc_attr($place_data['geometry']['location']['lng'] ?? '') . '">';
                $html .= '</div>';
                $html .= '<div itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">';
                $html .= '<meta itemprop="ratingValue" content="' . esc_attr($place_data['rating'] ?? '') . '">';
                $html .= '<meta itemprop="bestRating" content="5">';
                $html .= '<meta itemprop="reviewCount" content="' . esc_attr($place_data['user_ratings_total'] ?? '') . '">';
                $html .= '</div>';
                $repocean_google_review = 'repocean_google_review';
                $reviews = get_transient($repocean_google_review);
                if (false === $reviews) {
                    $response = wp_remote_get($this->get_review_url);
                    if (is_wp_error($response)) {
                        return '<div class="repocean-sidebar-main">Unable to retrieve reviews at this time.</div>';
                    }
                    $data = wp_remote_retrieve_body($response);
                    $reviews = json_decode($data, true);
                    if (empty($reviews)) {
                        return '<div class="repocean-sidebar-main">No reviews found.</div>';
                    }
                    do_action('repocean_update_review_count');
                    set_transient($repocean_google_review, $reviews, 1 * HOUR_IN_SECONDS);
                }
                if (empty($reviews)) {
                    return '<div class="repocean-sidebar-main">No reviews found.</div>';
                }
                $html .= '<div class="content-wrapper">';
                $html .= '<div class="slider-outer">';
                $html .= '<div class="slider-box-parent">';
                $html .= '<div class="slider-box">';
                $html .= '<div class="slider-box-inner">';
                $html .= '<div class="top-part">';
                if( $this->repocean_hide_rating_text === false ) {
                    $html .= '<div class="top-text-content">';
                    $html .= '<strong class="gi-rating"> Excellent rating </strong>';
                    $html .= '<span class="nowrap">Based on ' . esc_attr($place_data['user_ratings_total'] ?? '0') . ' reviews</span>';
                    $html .= '</div>';
                }
                $html .= '<div class="img-content">';
                if( $this->hide_google_logo === false ) {
                    $html .= '<div class="google-logo">';
                    $html .= '<img src="' . WGRR_ASSET_URL . 'public/css/images/google-brand.png" alt="Google_icon">';
                    $html .= '</div>';
                }
                $html .= '<div class="review-box-parent">';
                $place_rating = isset($place_data['rating']) ? floatval($place_data['rating']) : 0;
                $full_stars = floor($place_rating);
                $has_half_star = ($place_rating - $full_stars) >= 0.5;
                for ($i = 1; $i <= 5; $i++) {
                    if ($i <= $full_stars) {
                        $html .= '<div class="review-box"><img src="' . WGRR_ASSET_URL . 'public/css/images/fill-review-icon.svg" alt="Full Star"></div>';
                    } elseif ($has_half_star && $i == ($full_stars + 1)) {
                        $html .= '<div class="review-box"><img src="' . WGRR_ASSET_URL . 'public/css/images/fill-half-icon.svg" alt="Half Star"></div>';
                    } else {
                        $html .= '<div class="review-box"><img src="' . WGRR_ASSET_URL . 'public/css/images/fill-empty-icon.svg" alt="Empty Star"></div>';
                    }
                }
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<div class="bottom-part slider-start">';
                $reviews = array_slice($reviews, 0, $limit);
                foreach ($reviews as $review) {
                    $published_date = !empty($review['published_date']) ? sprintf(__('%s ago', 'widgets-for-google-reviews-and-ratings'), human_time_diff($review['published_date'] / 1000000)) : '';
                    $author_name = esc_html($review['author_name'] ?? '');
                    $author_name = mb_strimwidth($author_name, 0, 25, '...');
                    $profile_photo_url = esc_url($review['profile_photo_url'] ?? '');
                    $text = esc_html($review['text'] ?? '');
                    $rating = isset($review['rating']) ? floatval($review['rating']) : 0;
                    $read_more_class = (strlen($text) > 180) ? 'max63' : '';
                    $read_more_hide_show = (strlen($text) > 180) ? 'show' : 'hide';
                    $html .= '<div class="bottom-part-inner" itemprop="review" itemscope itemtype="https://schema.org/Review">';
                    $html .= '<div class="bottom-part-inner-second">';
                    $html .= '<div class="description ' . $read_more_class . '" itemprop="reviewBody">';
                    $html .= '<p>' . $text . '</p>';
                    $html .= '</div>';
                    $html .= '<div class="button-content ' . $read_more_hide_show . '">';
                    $html .= '<div class="readmore-button">';
                    $html .= '<a href="#">Read more</a>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '<div class="img-text-content">';
                    $html .= '<div class="profile-img-info">';
                    if($this->hide_profile_picture === false) {
                        $html .= '<div class="profile-img">';
                        $html .= '<img src="' . $profile_photo_url . '" alt="Profile image of ' . $author_name . '" itemprop="image">';
                        $html .= '</div>';
                    }
                    $html .= '<div class="profile-info">';
                    $html .= '<div class="profile-title">';
                    $html .= '<h6 itemprop="author" itemscope itemtype="https://schema.org/Person"><span itemprop="name">' . $author_name . '</span></h6>';
                    $html .= '</div>';
                    if ($this->hide_date === false) {
                        $html .= '<div class="profile-date">';
                        $html .= '<p>' . $published_date . '</p>';
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '</div>';
                    
                    $html .= '</div>';
                    $html .= '</div>';
                }
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
                return $html;
            } else {
                return "Your Google Place is not connected.";
            }
        } catch (Exception $ex) {
            return 'An error occurred.';
        }
    }

    function update_google_place_review_count() {
        try {
            $response_review_count = wp_remote_get($this->update_review_count);
            if (!is_wp_error($response_review_count)) {
                $data_review_count = wp_remote_retrieve_body($response_review_count);
                $review_count_data = json_decode($data_review_count, true);
                if (isset($review_count_data['review_count'])) {
                    $this->place_details['user_ratings_total'] = $review_count_data['review_count'];
                    update_option('wgrr_g_place_details', $this->place_details);
                }
            }
        } catch (Exception $ex) {
            
        }
    }
}
