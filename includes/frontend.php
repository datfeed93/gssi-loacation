<?php
if (!defined('ABSPATH'))
    exit;
if (!class_exists('GssiFrontend')) {
    class GssiFrontend {
        private $load_scripts = array();
        private static $map_count = 0;
        public $sl_shortcode_atts;
        
        private $location_map_data = array();
        
        public function __construct() {
            require_once( GSSI_LOCATION_PLUGIN_DIR . 'includes/frontend/functions.php' );
            add_action('wp_ajax_location_search', array($this, 'locationSearch'));
            add_action('wp_ajax_nopriv_location_search', array($this, 'locationSearch'));
            add_action('wp_ajax_location_closet', array($this, 'locationCloset'));
            add_action('wp_ajax_nopriv_location_closet', array($this, 'locationCloset'));
            add_action('wp_ajax_make_ricky', array($this, 'makeRicky'));
            add_action('wp_ajax_nopriv_make_ricky', array($this, 'makeRicky'));
            
            add_action('wp_enqueue_scripts', array($this, 'addStyles'));
            add_action('wp_footer', array($this, 'addScripts'));
            add_filter('the_content', array($this, 'cpt_template'));
            add_filter('single_template', array($this, 'loadSingleLocationTemplate'));
            add_shortcode('gssi_location', array($this, 'showLocation'));
            add_shortcode('gssi_location_top_navigation', array($this, 'showLocationTopNavigation'));
            add_shortcode('gssi_address', array($this, 'showLocationAddress'));
            add_shortcode('gssi_hours', array($this, 'showOpeningHours'));
            add_shortcode('gssi_map', array($this, 'showLocationMap'));
            add_shortcode('gssi_location_content', array($this, 'locationContent'));
        }
        
        function loadSingleLocationTemplate($single) {
            global $wp_query, $post, $location_data;
            
            /* Checks for single template by post type */
            if ( $post->post_type == 'gssi_locations' ) { 
                    $location_data = apply_filters('gssi_location_data', $this->getLocationMetaData(array($post)));
                    array_push($this->load_scripts, 'gssi_location');
                    if ( file_exists( GSSI_LOCATION_PLUGIN_DIR . 'includes/frontend/templates/single-gssi_locations.php' ) ) {
                            return $single = GSSI_LOCATION_PLUGIN_DIR . 'includes/frontend/templates/single-gssi_locations.php'; 
                    }
            }
            return $single;
        }
        /**
         * Handle the Ajax search on the frontend.
         * 
         * @return json A list of location locations that are located within the selected search radius
         */
        public function locationSearch() {
            global $gssi_location_settings;
            $location_data = $this->findNearbyLocation();
            do_action('gssi_location_search');
            wp_send_json($location_data);
            exit();
        }        
        /**
         * Handle the Ajax search on the frontend.
         * 
         * @return json A list of location locations that are located within the selected search radius
         */
        public function locationCloset() {
            $location_data = [];
            
            $locationId = isset($_COOKIE['my_ricky']) ? $_COOKIE['my_ricky'] : false;

            $myRicky =  $this->findById($locationId);
            
            if ($myRicky) {
                wp_send_json(array($myRicky));
                exit();
            }
            
            if (isset($_POST['lat']) && !empty($_POST['lat']) 
                    && isset($_POST['lng']) && !empty($_POST['lng'])) {
                $location_data = $this->findNearbyLocation(
                        array(
                            'lng' => $_POST['lng'],
                            'lat' => $_POST['lat'],
                            'autoload' => true,
                            'autoload_limit' => 1
                        )
                );
                if (count($location_data)) {
                    $domain_name = preg_replace('/^www\./','',$_SERVER['SERVER_NAME']);
                    setcookie( 'closest_location', $location_data[0]['id'], time() + 30 * DAY_IN_SECONDS, COOKIEPATH, $domain_name, is_ssl() );
                }
            }
            wp_send_json($location_data);
            exit();
        }
        
        /**
         * make ricky by location id
         * @return []
         */
        public function makeRicky() {
            $domain_name = preg_replace('/^www\./','',$_SERVER['SERVER_NAME']);
            if (isset($_POST['location_id']) && !empty($_POST['location_id'])) {
                setcookie( 'my_ricky', $_POST['location_id'], time() + 30 * DAY_IN_SECONDS, COOKIEPATH, $domain_name, is_ssl() );
                echo 1;
                exit();
            }
            echo 0;
            exit();
        }
        /**
         * @global type $wpdb
         * @global type $gssi_location_settings
         * @param [] $args
         * @return []
         */
        public function findNearbyLocation($args = array()) {
            global $wpdb, $gssi_location_settings;
            $location_data = array();
            // distance unit: 6371 for km and 3959 for miles
            $placeholder_values[] = 6371;
            
            // The placeholder values for the prepared statement in the SQL query.
            if (empty($args)) {
                $args = $_GET;
            }
            array_push($placeholder_values, $args['lat'], $args['lng'], $args['lat']);
            // Check if we need to filter the results by category.
            if (isset($args['filter']) && $args['filter']) {
                $filter_ids = array_map('absint', explode(',', $args['filter']));
                $cat_filter = "INNER JOIN $wpdb->term_relationships AS term_rel ON posts.ID = term_rel.object_id
                               INNER JOIN $wpdb->term_taxonomy AS term_tax ON term_rel.term_taxonomy_id = term_tax.term_taxonomy_id
                                      AND term_tax.taxonomy = 'gssi_location_category'
                                      AND term_tax.term_id IN (" . implode(',', $filter_ids) . ")";
            } else {
                $cat_filter = '';
            }
            $group_by = 'GROUP BY posts.ID';
            /*
             * If autoload is enabled we need to check if there is a limit to the 
             * amount of locations we need to show.
             * 
             * Otherwise include the radius and max results limit in the sql query. 
             */
            if (isset($args['autoload']) && $args['autoload']) {
                $limit = '';
                if ($gssi_location_settings['autoload_limit']) {
                    $limit = 'LIMIT %d';
                    $placeholder_values[] = $gssi_location_settings['autoload_limit'];
                }
                $sql_sort = 'ORDER BY distance ' . $limit;
            } else {
                array_push($placeholder_values, $this->checkLocationFilter($args, 'search_radius'));
                $sql_sort = 'HAVING distance < %d ORDER BY distance';
                
                if (!isset($args['zoom'])) {
                    if (isset($args['on_search'])) {
                        array_push($placeholder_values, $gssi_location_settings['autoload_limit']);
                    } else {
                        array_push($placeholder_values, $this->checkLocationFilter($args, 'max_results'));
                    }
                    
                    $sql_sort .= ' LIMIT 0, %d';
                }
            }
            
            $placeholder_values = apply_filters('gssi_sql_placeholder_values', $placeholder_values);
            
            /*
             * The sql that will check which location locations fall within 
             * the selected radius based on the lat and lng values. 
             */
            $sql = apply_filters('gssi_sql', "SELECT post_lat.meta_value AS lat,
                           post_lng.meta_value AS lng,
                           posts.ID, 
                           ( %d * acos( cos( radians( %s ) ) * cos( radians( post_lat.meta_value ) ) * cos( radians( post_lng.meta_value ) - radians( %s ) ) + sin( radians( %s ) ) * sin( radians( post_lat.meta_value ) ) ) ) 
                        AS distance
                      FROM $wpdb->posts AS posts
                INNER JOIN $wpdb->postmeta AS post_lat ON post_lat.post_id = posts.ID AND post_lat.meta_key = 'gssi_lat'
                INNER JOIN $wpdb->postmeta AS post_lng ON post_lng.post_id = posts.ID AND post_lng.meta_key = 'gssi_lng'
                    $cat_filter
                     WHERE posts.post_type = 'gssi_locations' 
                       AND posts.post_status = 'publish' $group_by $sql_sort"
            );
            
            $locations = $wpdb->get_results($wpdb->prepare($sql, $placeholder_values));
            
            if ($locations) {
                $location_data = apply_filters('gssi_location_data', $this->getLocationMetaData($locations));
            } else {
                $location_data = apply_filters('gssi_no_results_sql', '');
            }
            //var_dump($location_data);die();
            return $location_data;
        }
        
        
        /**
         * 
         * @global type $wpdb
         * @param type $id
         * @return []|boolean
         */
        public function findById($id) {
            global $wpdb;
            
            /*
             * The sql that will check which location locations fall within 
             * the selected radius based on the lat and lng values. 
             */
            $sql = apply_filters('gssi_sql', "SELECT post_lat.meta_value AS lat,
                           post_lng.meta_value AS lng,
                           posts.ID
                      FROM $wpdb->posts AS posts
                INNER JOIN $wpdb->postmeta AS post_lat ON post_lat.post_id = posts.ID AND post_lat.meta_key = 'gssi_lat'
                INNER JOIN $wpdb->postmeta AS post_lng ON post_lng.post_id = posts.ID AND post_lng.meta_key = 'gssi_lng'
                     WHERE posts.post_type = 'gssi_locations' 
                     AND posts.ID = %d
                       AND posts.post_status = 'publish' GROUP BY posts.ID"
            );
            
            $locations = $wpdb->get_results($wpdb->prepare($sql, [$id]));
            if ($locations) {
                $location_data = apply_filters('gssi_location_data', $this->getLocationMetaData($locations));
                return $location_data[0];
            }
            return false;
        }
        
        public function getLocationMetaData($locations) {
            global $gssi_location_settings;
            $all_locations = array();
            // Get the list of location fields that we need to filter out of the post meta data.
            $meta_field_map = $this->frontendMetaFields();
            foreach ($locations as $location_key => $location) {
                // Get the post meta data for each location that was within the range of the search radius.
                $custom_fields = get_post_custom($location->ID);
                foreach ($meta_field_map as $meta_key => $meta_value) {
                    if (isset($custom_fields[$meta_key][0])) {
                        if (( isset($meta_value['type']) ) && (!empty($meta_value['type']) )) {
                            $meta_type = $meta_value['type'];
                        } else {
                            $meta_type = '';
                        }
                        // If we need to hide the opening hours, and the current meta type is set to hours we skip it.
                        if ($gssi_location_settings['hide_hours'] && $meta_type == 'hours') {
                            continue;
                        }
                        // Make sure the data is safe to use on the frontend and in the format we expect it to be.
                        switch ($meta_type) {
                            case 'numeric':
                                $meta_data = ( is_numeric($custom_fields[$meta_key][0]) ) ? $custom_fields[$meta_key][0] : 0;
                                break;
                            case 'email':
                                $meta_data = sanitize_email($custom_fields[$meta_key][0]);
                                break;
                            case 'url':
                                $meta_data = esc_url($custom_fields[$meta_key][0]);
                                break;
                            case 'hours':
                                $meta_data = $this->getOpeningHours($custom_fields[$meta_key][0], apply_filters('gssi_hide_closed_hours', false));
                                break;
                            case 'wp_editor':
                            case 'textarea':
                                $meta_data = wp_kses_post(wpautop($custom_fields[$meta_key][0]));
                                break;
                            case 'text':
                            default:
                                $meta_data = sanitize_text_field(stripslashes($custom_fields[$meta_key][0]));
                                break;
                        }
                        $location_meta[$meta_value['name']] = $meta_data;
                    } else {
                        $location_meta[$meta_value['name']] = '';
                    }
                    /*
                     * Include the post content if the "More info" option is enabled on the settings page,
                     * or if $include_post_content is set to true through the 'gssi_include_post_content' filter.
                     */
                    if (( $gssi_location_settings['more_info'] && $gssi_location_settings['more_info_location'] == 'location listings' ) || apply_filters('gssi_include_post_content', false)) {
                        $page_object = get_post($location->ID);
                        $location_meta['description'] = apply_filters('the_content', strip_shortcodes($page_object->post_content));
                    }
                    $location_meta['location'] = get_the_title($location->ID);
                    $location_meta['thumb'] = $this->get_location_thumb($location->ID, $location_meta['location']);
                    $location_meta['id'] = $location->ID;
                    if (!$gssi_location_settings['hide_distance'] && isset($location->distance)) {
                        $location_meta['distance'] = round($location->distance, 1);
                    }
                    if ($gssi_location_settings['permalinks']) {
                        $location_meta['permalink'] = get_permalink($location->ID);
                    }
                    $location_meta['detail_url'] = get_permalink($location->ID);
                    
                    $location_meta['is_ricky'] = isset($_COOKIE['my_ricky']) ? $_COOKIE['my_ricky'] : false;
                    $location_meta['is_closest'] = isset($_COOKIE['closest_location']) ? $_COOKIE['closest_location'] : false;
                }
                
                $categories = wp_get_post_terms($location->ID,"gssi_location_category");
                if (count($categories)) {
                    $locator_marker = get_term_meta( $categories[0]->term_id, 'locator_marker', true );
                    $locator_icon = get_term_meta( $categories[0]->term_id, 'locator_miniicon', true );                    
                    $locator_color = get_term_meta( $categories[0]->term_id, 'locator_color', true );
                    $location_meta['locator_color'] = $locator_color;
                    $location_meta['categoryMarkerUrl'] = wp_get_attachment_url($locator_marker);
                    $location_meta['locator_marker_url'] = wp_get_attachment_url($locator_marker);
                    $location_meta['locator_icon_url'] = wp_get_attachment_url($locator_icon);
                }
                
                $all_locations[] = apply_filters('gssi_location_meta', $location_meta, $location->ID);
            }
            return $all_locations;
        }
        
        public function frontendMetaFields() {
            $location_fields = array(
                'gssi_address' => array(
                    'name' => 'address'
                ),
                'gssi_address2' => array(
                    'name' => 'address2'
                ),
                'gssi_city' => array(
                    'name' => 'city'
                ),
                'gssi_state' => array(
                    'name' => 'state'
                ),
                'gssi_zip' => array(
                    'name' => 'zip'
                ),
                'gssi_country' => array(
                    'name' => 'country'
                ),
                'gssi_lat' => array(
                    'name' => 'lat',
                    'type' => 'numeric'
                ),
                'gssi_lng' => array(
                    'name' => 'lng',
                    'type' => 'numeric'
                ),
                'gssi_phone' => array(
                    'name' => 'phone'
                ),
                'gssi_fax' => array(
                    'name' => 'fax'
                ),
                'gssi_email' => array(
                    'name' => 'email',
                    'type' => 'email'
                ),
                'gssi_hours' => array(
                    'name' => 'hours',
                    'type' => 'hours'
                ),
                'gssi_url' => array(
                    'name' => 'url',
                    'type' => 'url'
                ),
                'gssi_facebook' => array(
                    'name' => 'facebook',
                    'type' => 'url'
                ),
                'gssi_twitter' => array(
                    'name' => 'twitter',
                    'type' => 'url'
                ),
                'gssi_instagram' => array(
                    'name' => 'instagram',
                    'type' => 'url'
                )
            );
            return apply_filters('gssi_frontendMetaFields', $location_fields);
        }
        
        public function get_location_thumb($post_id, $location_name) {
            $attr = array(
                'class' => 'gssi-location-thumb',
                'alt' => $location_name
            );
            $thumb = get_the_post_thumbnail($post_id, $this->get_location_thumb_size(), apply_filters('gssi_thumb_attr', $attr));
            return $thumb;
        }
        
        public function get_location_thumb_size() {
            $size = apply_filters('gssi_thumb_size', array(170, 110));
            return $size;
        }
        
        /**
         * 
         * @param type $hours
         * @param type $hide_closed
         * @return type
         */
        public function getOpeningHours($hours, $hide_closed) {
            $hours = maybe_unserialize($hours);
            /*
             * If the hours are set through the dropdown then we create a table for the opening hours.
             * Otherwise we output the data entered in the textarea. 
             */
            if (is_array($hours)) {
                $hours = $this->createOpeningHoursTable($hours, $hide_closed);
            } else {
                $hours = wp_kses_post(wpautop($hours));
            }
            return $hours;
        }
        
        public function createOpeningHoursTable($hours, $hide_closed) {
            $opening_days = array( 
                'monday'    => __( 'Monday', 'gssi_location' ), 
                'tuesday'   => __( 'Tuesday', 'gssi_location' ),  
                'wednesday' => __( 'Wednesday', 'gssi_location' ),  
                'thursday'  => __( 'Thursday', 'gssi_location' ),  
                'friday'    => __( 'Friday', 'gssi_location' ),  
                'saturday'  => __( 'Saturday', 'gssi_location' ),
                'sunday'    => __( 'Sunday' , 'gssi_location' )
            );
            // Make sure that we have actual opening hours, and not every day is empty.
            if ($this->notAlwaysClosed($hours)) {
                $hour_table = '<table class="gssi-opening-hours">';
                foreach ($opening_days as $index => $day) {
                    $i = 0;
                    $hour_count = count($hours[$index]);
                    // If we need to hide days that are set to closed then skip them.
                    if ($hide_closed && !$hour_count) {
                        continue;
                    }
                    $hour_table .= '<tr>';
                    $hour_table .= '<td>' . esc_html($day) . '</td>';
                    // If we have opening hours we show them, otherwise just show 'Closed'.
                    if ($hour_count > 0) {
                        $hour_table .= '<td>';
                        while ($i < $hour_count) {
                            $hour = explode(',', $hours[$index][$i]);
                            $hour_table .= '<time>' . esc_html(date('h:i a', strtotime($hour[0]))) . ' - ' . esc_html(date('h:i a', strtotime($hour[1]))) . '</time>';
                            $i++;
                        }
                        $hour_table .= '</td>';
                    } else {
                        $hour_table .= '<td>' . __('Closed', 'gssi_location') . '</td>';
                    }
                    $hour_table .= '</tr>';
                }
                $hour_table .= '</table>';
                return $hour_table;
            }
        }
        
        public function cpt_template($content) {
            global $gssi_location_settings, $post;
            if (isset($post->post_type) && $post->post_type == 'gssi_locations' && is_single() && in_the_loop()) {
                array_push($this->load_scripts, 'gssi_base');
                
                //$content .= '[gssi_location_content]';
                $content .= '[gssi_map]';
                $content .= '[gssi_address]';
                
                if (!$gssi_location_settings['hide_hours']) {
                    $content .= '[gssi_hours]';
                }
            }
            return $content;
        }
        
        public function checkSlShortcodeAtts($atts) {
            /*
             * Use a custom start location?
             *
             * If the provided location fails to geocode,
             * then the start location from the settings page is used.
             */
            if (isset($atts['start_location']) && $atts['start_location']) {
                $start_latlng = gssi_get_address_latlng($atts['start_location']);
                if (isset($start_latlng) && $start_latlng) {
                    $this->sl_shortcode_atts['js']['startLatlng'] = $start_latlng;
                }
            }
            if (isset($atts['auto_locate']) && $atts['auto_locate']) {
                $this->sl_shortcode_atts['js']['autoLocate'] = ( $atts['auto_locate'] == 'true' ) ? 1 : 0;
            }
            // Change the category slugs into category ids.
            if (isset($atts['category']) && $atts['category']) {
                $term_ids = gssi_get_term_ids($atts['category']);
                if ($term_ids) {
                    $this->sl_shortcode_atts['js']['categoryIds'] = implode(',', $term_ids);
                }
            }
            if (isset($atts['category_selection']) && $atts['category_selection']) {
                $this->sl_shortcode_atts['category_selection'] = gssi_get_term_ids($atts['category_selection']);
            }
            if (isset($atts['category_filter_type']) && in_array($atts['category_filter_type'], array('dropdown', 'checkboxes'))) {
                $this->sl_shortcode_atts['category_filter_type'] = $atts['category_filter_type'];
            }
            if (isset($atts['checkbox_columns']) && is_numeric($atts['checkbox_columns'])) {
                $this->sl_shortcode_atts['checkbox_columns'] = $atts['checkbox_columns'];
            }
            if (isset($atts['map_type']) && array_key_exists($atts['map_type'], gssi_get_map_types())) {
                $this->sl_shortcode_atts['js']['mapType'] = $atts['map_type'];
            }
            if (isset($atts['start_marker']) && $atts['start_marker']) {
                $this->sl_shortcode_atts['js']['startMarker'] = $atts['start_marker'] . '@2x.png';
            }
            if (isset($atts['location_marker']) && $atts['location_marker']) {
                $this->sl_shortcode_atts['js']['locationMarker'] = $atts['location_marker'] . '@2x.png';
            }
        }
        /**
         * Handle the [gssi_location] shortcode.
         *
         * @param  array  $atts   Shortcode attributes
         * @return string $output The gssi template
         */
        public function showLocation($atts) {
            global $gssi_location_settings;
            $atts = shortcode_atts(array(
                'template' => $gssi_location_settings['template_id'],
                'start_location' => '',
                'auto_locate' => '',
                'category' => '',
                'category_selection' => '',
                'category_filter_type' => '',
                'checkbox_columns' => '3',
                'map_type' => '',
                'start_marker' => '',
                'location_marker' => ''
                    ), $atts);
            $this->checkSlShortcodeAtts($atts);
            // Make sure the required scripts are included for the gssi_location shortcode.
            array_push($this->load_scripts, 'gssi_location');
            $output = include( GSSI_LOCATION_PLUGIN_DIR . 'includes/frontend/templates/locations.php' );
            return $output;
        }
        
        /**
         * @param [] $atts
         * @return string
         */
        public function showLocationTopNavigation($atts) {
            // Make sure the required scripts are included for the gssi_location_top_navigation shortcode.
            array_push($this->load_scripts, 'gssi_location');
            $output = include( GSSI_LOCATION_PLUGIN_DIR . 'includes/frontend/templates/top-navigation.php' );
            return $output;
        }
        
        public function showLocationAddress($atts) {
            global $post, $gssi_location_settings;
            $atts = gssi_bool_check(shortcode_atts(apply_filters('gssi_address_shortcode_defaults', array(
                'id' => '',
                'name' => true,
                'address' => true,
                'address2' => true,
                'city' => true,
                'state' => true,
                'zip' => true,
                'country' => true,
                'phone' => true,
                'fax' => true,
                'email' => true,
                'url' => true
                            )), $atts));
            if (get_post_type() == 'gssi_locations') {
                if (empty($atts['id'])) {
                    if (isset($post->ID)) {
                        $atts['id'] = $post->ID;
                    } else {
                        return;
                    }
                }
            } else if (empty($atts['id'])) {
                return __('If you use the [gssi_address] shortcode outside a location page you need to set the ID attribute.', 'gssi_location');
            }
            $content = '<div class="gssi-locations-details">';
            // if ($atts['name'] && $location_name = get_the_title($atts['id'])) {
            //     $content .= '<span><strong>' . esc_html($location_name) . '</strong></span>';
            // }
            $content .= '<div class="gssi-location-address">';
            if ($atts['address'] && $location_address = get_post_meta($atts['id'], 'gssi_address', true)) {
                $content .= '<span>' . esc_html($location_address) . '</span>, ';
            }
            if ($atts['address2'] && $location_address2 = get_post_meta($atts['id'], 'gssi_address2', true)) {
                $content .= '<span>' . esc_html($location_address2) . '</span>, ';
            }
            $address_format = explode('_', $gssi_location_settings['address_format']);
            $count = count($address_format);
            $i = 1;
            // Loop over the address parts to make sure they are shown in the right order.
            foreach ($address_format as $address_part) {
                // Make sure the shortcode attribute is set to true for the $address_part, and it's not the 'comma' part.
                if ($address_part != 'comma' && $atts[$address_part]) {
                    $post_meta = get_post_meta($atts['id'], 'gssi_' . $address_part, true);
                    if ($post_meta) {
                        /*
                         * Check if the next part of the address is set to 'comma'. 
                         * If so add the, after the current address part, otherwise just show a space 
                         */
                        if (isset($address_format[$i]) && ( $address_format[$i] == 'comma' )) {
                            $punctuation = ', ';
                        } else {
                            $punctuation = ' ';
                        }
                        // If we have reached the last item add a <br /> behind it.
                        $br = ( $count == $i ) ? '<br />' : '';
                        $content .= '<span>' . esc_html($post_meta) . $punctuation . '</span>';
                    }
                }
                $i++;
            }
            if ($atts['country'] && $location_country = get_post_meta($atts['id'], 'gssi_country', true)) {
                $content .= '<span>' . esc_html($location_country) . '</span>';
            }
            $content .= '</div>';
            // If either the phone, fax, email or url is set to true, then add the wrap div for the contact details.
            if ($atts['phone'] || $atts['fax'] || $atts['email'] || $atts['url']) {
                $content .= '<div class="gssi-contact-details">';
                if ($atts['phone'] && $location_phone = get_post_meta($atts['id'], 'gssi_phone', true)) {
                    $content .= '<div class="gssi-phone"><a href="tel:'.esc_html($location_phone).'"><i class="fas fa-phone"></i>'. esc_html($location_phone) . '</a></div>';
                }
                if ($atts['fax'] && $location_fax = get_post_meta($atts['id'], 'gssi_fax', true)) {
                    $content .= esc_html(__('Fax', 'gssi_location')) . ': <span>' . esc_html($location_fax) . '</span><br/>';
                }
//                if ($atts['email'] && $location_email = get_post_meta($atts['id'], 'gssi_email', true)) {
//                    $content .= esc_html(__('Email', 'gssi_location')) . ': <span>' . sanitize_email($location_email) . '</span><br/>';
//                }
//                if ($atts['url'] && $location_url = get_post_meta($atts['id'], 'gssi_url', true)) {
//                    $new_window = ( $gssi_location_settings['new_window'] ) ? 'target="_blank"' : '';
//                    $content .= '<div class="gssi-url"><a ' . $new_window . ' class="url" href="' . esc_url($location_url) . '">' . esc_url($location_url) . '</a></div>';
//                }
                $content .= '</div>';
            }
            $content .= '</div>';
            return $content;
        }
        
        public function showOpeningHours($atts) {
            global $post;
            $hide_closed = apply_filters('gssi_hide_closed_hours', false);
            $atts = gssi_bool_check(shortcode_atts(apply_filters('gssi_hour_shortcode_defaults', array(
                'id' => '',
                'hide_closed' => $hide_closed
                            )), $atts));
            if (get_post_type() == 'gssi_locations') {
                if (empty($atts['id'])) {
                    if (isset($post->ID)) {
                        $atts['id'] = $post->ID;
                    } else {
                        return;
                    }
                }
            } else if (empty($atts['id'])) {
                return __('If you use the [gssi_hours] shortcode outside a location page you need to set the ID attribute.', 'gssi_location');
            }
            $opening_hours = get_post_meta($atts['id'], 'gssi_hours');
            if ($opening_hours) {
                $output = $this->getOpeningHours($opening_hours[0], $atts['hide_closed']);
                return $output;
            }
        }
        
        public function showLocationMap($atts) {
            global $gssi_location_settings, $post;
            $atts = shortcode_atts(apply_filters('gssi_map_shortcode_defaults', array(
                'id' => '',
                'category' => '',
                'width' => '',
                'height' => $gssi_location_settings['height'],
                'zoom' => $gssi_location_settings['zoom_level'],
                'map_type' => $gssi_location_settings['map_type'],
                'map_type_control' => $gssi_location_settings['type_control'],
                'map_style' => '',
                'street_view' => $gssi_location_settings['streetview'],
                'scrollwheel' => $gssi_location_settings['scrollwheel'],
                'control_position' => $gssi_location_settings['control_position']
                    )), $atts);
            array_push($this->load_scripts, 'gssi_base');
            if (get_post_type() == 'gssi_locations') {
                if (empty($atts['id'])) {
                    if (isset($post->ID)) {
                        $atts['id'] = $post->ID;
                    } else {
                        return;
                    }
                }
            } else if (empty($atts['id']) && empty($atts['category'])) {
                return __('If you use the [gssi_map] shortcode outside a location page, then you need to set the ID or category attribute.', 'gssi_location');
            }
            if ($atts['category']) {
                $location_ids = get_posts(array(
                    'numberposts' => -1,
                    'post_type' => 'gssi_locations',
                    'post_status' => 'publish',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'gssi_location_category',
                            'field' => 'slug',
                            'terms' => explode(',', sanitize_text_field($atts['category']))
                        ),
                    ),
                    'fields' => 'ids'
                        ));
            } else {
                $location_ids = array_map('absint', explode(',', $atts['id']));
                $id_count = count($location_ids);
            }
            /*
             * The location url is included if:
             * 
             * - Multiple ids are set.
             * - The category attr is set.
             * - The shortcode is used on a post type other then 'gssi_locations'. No point in showing a location
             * url to the user that links back to the page they are already on.
             */
            if ($atts['category'] || isset($id_count) && $id_count > 1 || get_post_type() != 'gssi_locations' && !empty($atts['id'])) {
                $incl_url = true;
            } else {
                $incl_url = false;
            }
            $location_meta = array();
            $i = 0;
            foreach ($location_ids as $location_id) {
                $lat = get_post_meta($location_id, 'gssi_lat', true);
                $lng = get_post_meta($location_id, 'gssi_lng', true);
                
                $categories = wp_get_post_terms($location_id,"gssi_location_category");
                if (count($categories) > 0) {
                    $locator_marker = get_term_meta( $categories[0]->term_id, 'locator_marker', true );
                    if (!empty($locator_marker)) {
                        $locator_marker_url = wp_get_attachment_url($locator_marker);
                    }
                }
                
                // Make sure the latlng is numeric before collecting the other meta data.
                if (is_numeric($lat) && is_numeric($lng)) {
                    $location_meta[$i] = apply_filters('gssi_cpt_info_window_meta_fields', array(
                        'location' => get_the_title($location_id),
                        'address' => get_post_meta($location_id, 'gssi_address', true),
                        'address2' => get_post_meta($location_id, 'gssi_address2', true),
                        'city' => get_post_meta($location_id, 'gssi_city', true),
                        'state' => get_post_meta($location_id, 'gssi_state', true),
                        'zip' => get_post_meta($location_id, 'gssi_zip', true),
                        'country' => get_post_meta($location_id, 'gssi_country', true)
                            ), $location_id);
                    // Grab the permalink / url if necessary.
                    if ($incl_url) {
                        if ($gssi_location_settings['permalinks']) {
                            $location_meta[$i]['permalink'] = get_permalink($location_id);
                        } else {
                            $location_meta[$i]['url'] = get_post_meta($location_id, 'gssi_url', true);
                        }
                    }
                    $location_meta[$i]['lat'] = $lat;
                    $location_meta[$i]['lng'] = $lng;
                    $location_meta[$i]['id'] = $location_id;
                    if (isset($locator_marker_url) && !empty($locator_marker_url)) $location_meta[$i]['alternateMarkerUrl'] = $locator_marker_url; 
                    $i++;
                }
            }
            $output = '<div id="gssi-base-gmap_' . self::$map_count . '" class="gssi-location-gmap-canvas"></div>' . "\r\n";
            // Make sure the shortcode attributes are valid.
            $map_styles = $this->checkMapShortcodeAtts($atts);
            if ($map_styles) {
                if (isset($map_styles['css']) && !empty($map_styles['css'])) {
                    $output .= '<style>' . $map_styles['css'] . '</style>' . "\r\n";
                    unset($map_styles['css']);
                }
                if ($map_styles) {
                    $location_data['shortCode'] = $map_styles;
                }
            }
            $location_data['locations'] = $location_meta;
            $this->location_map_data[self::$map_count] = $location_data;
            self::$map_count++;
            return $output;
        }
        
        public function checkMapShortcodeAtts($atts) {
            $map_atts = array();
            if (isset($atts['width']) && is_numeric($atts['width'])) {
                $width = 'width:' . $atts['width'] . 'px;';
            } else {
                $width = '';
            }
            if (isset($atts['height']) && is_numeric($atts['height'])) {
                $height = 'height:' . $atts['height'] . 'px;';
            } else {
                $height = '';
            }
            if ($width || $height) {
                $map_atts['css'] = '#gssi-base-gmap_' . self::$map_count . ' {' . $width . $height . '}';
            }
            if (isset($atts['zoom']) && !empty($atts['zoom'])) {
                $map_atts['zoomLevel'] = gssi_valid_zoom_level($atts['zoom']);
            }
            if (isset($atts['map_type']) && !empty($atts['map_type'])) {
                $map_atts['mapType'] = gssi_valid_map_type($atts['map_type']);
            }
            if (isset($atts['map_type_control'])) {
                $map_atts['mapTypeControl'] = $this->shortcodeAttsBoolean($atts['map_type_control']);
            }
            if (isset($atts['map_style']) && $atts['map_style'] == 'default') {
                $map_atts['mapStyle'] = '';
            }
            if (isset($atts['street_view'])) {
                $map_atts['streetView'] = $this->shortcodeAttsBoolean($atts['street_view']);
            }
            if (isset($atts['scrollwheel'])) {
                $map_atts['scrollWheel'] = $this->shortcodeAttsBoolean($atts['scrollwheel']);
            }
            if (isset($atts['control_position']) && !empty($atts['control_position']) && ( $atts['control_position'] == 'left' || $atts['control_position'] == 'right' )) {
                $map_atts['controlPosition'] = $atts['control_position'];
            }
            return $map_atts;
        }
        
        public function shortcodeAttsBoolean($att) {
            if ($att === 'true' || absint($att)) {
                $att_val = 1;
            } else {
                $att_val = 0;
            }
            return $att_val;
        }
        
        public function checkLocationFilter($args, $filter) {
            if (isset($args[$filter]) && absint($args[$filter])) {
                $filter_value = $args[$filter];
            } else {
                $filter_value = $this->getDefaultFilterValue($filter);
            }
            return $filter_value;
        }
        
        public function checkAllowedFilterValue($args, $filter) {
            global $gssi_location_settings;
            $allowed = false;
            $max_filter_val = max(explode(',', str_replace(array('[', ']'), '', $gssi_location_settings[$filter])));
            if ((int) $args[$filter] <= (int) $max_filter_val) {
                $allowed = true;
            }
            return $allowed;
        }
        
        public function getDefaultFilterValue($type) {
            $settings = get_option('gssi_settings');
            $list_values = explode(',', $settings[$type]);
            foreach ($list_values as $k => $list_value) {
                // The default radius has a [] wrapped around it, so we check for that and filter out the [].
                if (strpos($list_value, '[') !== false) {
                    $response = filter_var($list_value, FILTER_SANITIZE_NUMBER_INT);
                    break;
                }
            }
            return $response;
        }
        
        public function notAlwaysClosed($opening_hours) {
            foreach ($opening_hours as $hours => $hour) {
                if (!empty($hour)) {
                    return true;
                }
            }
        }
        
        public function getCustomCss() {
            global $gssi_location_settings;
            $thumb_size = $this->get_location_thumb_size();
            $css = '<style>' . "\r\n";
            if (isset($thumb_size[0]) && is_numeric($thumb_size[0]) && isset($thumb_size[1]) && is_numeric($thumb_size[1])) {
                $css .= "\t" . "#gssi-locations .gssi-location-thumb {height:" . esc_attr($thumb_size[0]) . "px !important; width:" . esc_attr($thumb_size[1]) . "px !important;}" . "\r\n";
            }
            if ($gssi_location_settings['template_id'] == 'below_map' && $gssi_location_settings['listing_below_no_scroll']) {
                $css .= "\t" . "#gssi-location-gmap {height:" . esc_attr($gssi_location_settings['height']) . "px !important;}" . "\r\n";
                $css .= "\t" . "#gssi-locations, #gssi-direction-details {height:auto !important;}";
            } else {
                $css .= "\t" . "#gssi-locations, #gssi-direction-details, #gssi-location-gmap {height:" . esc_attr($gssi_location_settings['height']) . "px !important;}" . "\r\n";
            }
            /*
             * If the category dropdowns are enabled then we make it 
             * the same width as the search input field. 
             */
            if ($gssi_location_settings['category_filter'] && $gssi_location_settings['category_filter_type'] == 'dropdown' || isset($this->sl_shortcode_atts['category_filter_type']) && $this->sl_shortcode_atts['category_filter_type'] == 'dropdown') {
                $cat_elem = ',#gssi-category .gssi-dropdown';
            } else {
                $cat_elem = '';
            }
            $css .= "\t" . "#gssi-location-gmap .gssi-info-window {max-width:" . esc_attr($gssi_location_settings['infowindow_width']) . "px !important;}" . "\r\n";
            $css .= "\t" . ".gssi-input label, #gssi-radius label, #gssi-category label {width:" . esc_attr($gssi_location_settings['label_width']) . "px;}" . "\r\n";
            $css .= "\t" . "#gssi-search-input " . $cat_elem . " {width:" . esc_attr($gssi_location_settings['search_width']) . "px;}" . "\r\n";
            $css .= '</style>' . "\r\n";
            return $css;
        }
        
        public function getCssClasses() {
            global $gssi_location_settings;
            $classes = array();
            if ($gssi_location_settings['category_filter'] && $gssi_location_settings['results_dropdown'] && !$gssi_location_settings['radius_dropdown']) {
                $classes[] = 'gssi-cat-results-filter';
            } else if ($gssi_location_settings['category_filter'] && ( $gssi_location_settings['results_dropdown'] || $gssi_location_settings['radius_dropdown'] )) {
                $classes[] = 'gssi-filter';
            }
            // checkboxes class toevoegen?
            if (!$gssi_location_settings['category_filter'] && !$gssi_location_settings['results_dropdown'] && !$gssi_location_settings['radius_dropdown']) {
                $classes[] = 'gssi-no-filters';
            }
            if ($gssi_location_settings['category_filter'] && $gssi_location_settings['category_filter_type'] == 'checkboxes') {
                $classes[] = 'gssi-checkboxes-enabled';
            }
            if ($gssi_location_settings['results_dropdown'] && !$gssi_location_settings['category_filter'] && !$gssi_location_settings['radius_dropdown']) {
                $classes[] = 'gssi-results-only';
            }
            $classes = apply_filters('gssi_template_css_classes', $classes);
            if (!empty($classes)) {
                return join(' ', $classes);
            }
        }
        
        public function getDropdownList($list_type) {
            global $gssi_location_settings;
            $dropdown_list = '';
            $settings = explode(',', $gssi_location_settings[$list_type]);
            // Only show the distance unit if we are dealing with the search radius.
            if ($list_type == 'search_radius') {
                $distance_unit = ' ' . esc_attr($gssi_location_settings['distance_unit']);
            } else {
                $distance_unit = '';
            }
            foreach ($settings as $index => $setting_value) {
                // The default radius has a [] wrapped around it, so we check for that and filter out the [].
                if (strpos($setting_value, '[') !== false) {
                    $setting_value = filter_var($setting_value, FILTER_SANITIZE_NUMBER_INT);
                    $selected = 'selected="selected" ';
                } else {
                    $selected = '';
                }
                $dropdown_list .= '<option ' . $selected . 'value="' . absint($setting_value) . '">' . absint($setting_value) . $distance_unit . '</option>';
            }
            return $dropdown_list;
        }
        
        public function useCategoryFilter() {
            global $gssi_location_settings;
            $use_filter = false;
            // Is a filter type set through the shortcode, or is the filter option enable on the settings page?
            if (isset($this->sl_shortcode_atts['category_filter_type']) || $gssi_location_settings['category_filter']) {
                $use_filter = true;
            }
            return $use_filter;
        }
        
        public function createCategoryFilter() {
            global $gssi_location_settings;
            /*
             * If the category attr is set on the gssi shortcode, then
             * there is no need to ouput an extra category dropdown.
             */
            if (isset($this->sl_shortcode_atts['js']['categoryIds'])) {
                return;
            }
            $terms = get_terms('gssi_location_category');
            if (count($terms) > 0) {
                // Either use the shortcode atts filter type or the one from the settings page.
                if (isset($this->sl_shortcode_atts['category_filter_type'])) {
                    $filter_type = $this->sl_shortcode_atts['category_filter_type'];
                } else {
                    $filter_type = $gssi_location_settings['category_filter_type'];
                }
                // Check if we need to show the filter as checkboxes or a dropdown list
                if ($filter_type == 'checkboxes') {
                    if (isset($this->sl_shortcode_atts['checkbox_columns'])) {
                        $checkbox_columns = absint($this->sl_shortcode_atts['checkbox_columns']);
                    }
                    if (isset($checkbox_columns) && $checkbox_columns) {
                        $column_count = $checkbox_columns;
                    } else {
                        $column_count = 3;
                    }
                    $category = '<ul id="gssi-checkbox-filter" class="gssi-checkbox-' . $column_count . '-columns">';
                    foreach ($terms as $term) {
                        $category .= '<li>';
                        $category .= '<label>';
                        $category .= '<input type="checkbox" value="' . esc_attr($term->term_id) . '" ' . $this->setSelectedCategory($filter_type, $term->term_id) . ' />';
                        $category .= esc_html($term->name);
                        $category .= '</label>';
                        $category .= '</li>';
                    }
                    $category .= '</ul>';
                } else {
                    $category = '<div id="gssi-category">' . "\r\n";
                    $category .= '<label for="gssi-category-list">' . esc_html(__('Category', 'gssi_location')) . '</label>' . "\r\n";
                    $args = apply_filters('gssi_dropdown_category_args', array(
                        'show_option_none' => __('Any', 'gssi_location'),
                        'option_none_value' => '0',
                        'orderby' => 'NAME',
                        'order' => 'ASC',
                        'echo' => 0,
                        'selected' => $this->setSelectedCategory($filter_type),
                        'hierarchical' => 1,
                        'name' => 'gssi-category',
                        'id' => 'gssi-category-list',
                        'class' => 'gssi-dropdown',
                        'taxonomy' => 'gssi_location_category',
                        'hide_if_empty' => true
                            )
                    );
                    $category .= wp_dropdown_categories($args);
                    $category .= '</div>' . "\r\n";
                }
                return $category;
            }
        }
        
        public function setSelectedCategory($filter_type, $term_id = '') {
            $selected_id = '';
            // Check if the ID for the selected cat is either passed through the widget, or shortcode
            if (isset($_REQUEST['gssi-widget-categories'])) {
                $selected_id = absint($_REQUEST['gssi-widget-categories']);
            } else if (isset($this->sl_shortcode_atts['category_selection'])) {
                /*
                 * When the term_id is set, then it's a checkbox.
                 *
                 * Otherwise select the first value from the provided list since
                 * multiple selections are not supported in dropdowns.
                 */
                if ($term_id) {
                    // Check if the passed term id exists in the set shortcode value.
                    $key = array_search($term_id, $this->sl_shortcode_atts['category_selection']);
                    if ($key !== false) {
                        $selected_id = $this->sl_shortcode_atts['category_selection'][$key];
                    }
                } else {
                    $selected_id = $this->sl_shortcode_atts['category_selection'][0];
                }
            }
            if ($selected_id) {
                /*
                 * Based on the filter type, either return the ID of the selected category, 
                 * or check if the checkbox needs to be set to checked="checked".
                 */
                if ($filter_type == 'dropdown') {
                    return $selected_id;
                } else {
                    return checked($selected_id, $term_id, false);
                }
            }
        }
        
        public function getDropdownDefaults() {
            global $gssi_location_settings;
            $required_defaults = array(
                'max_results',
                'search_radius'
            );
            // Strip out the default values that are wrapped in [].
            foreach ($required_defaults as $required_default) {
                preg_match_all('/\[([0-9]+?)\]/', $gssi_location_settings[$required_default], $match, PREG_PATTERN_ORDER);
                $output[$required_default] = ( isset($match[1][0]) ) ? $match[1][0] : '25';
            }
            return $output;
        }
        
        public function addStyles() {
            wp_enqueue_style('gssi-fontawesome', GSSI_LOCATION_URL . 'includes/frontend/css/fontawesome-all.css', '', GSSI_LOCATION_VERSION_NUM);
            wp_enqueue_style('gssi-location-styles', GSSI_LOCATION_URL . 'includes/frontend/css/styles.css', '', GSSI_LOCATION_VERSION_NUM);
        }
        
        public function getMapControls() {
            global $gssi_location_settings, $is_IE;
            $classes = array();
            if ($gssi_location_settings['reset_map']) {
                $reset_button = '<div class="gssi-icon-reset"><span>&#xe801;</span></div>';
            } else {
                $reset_button = '';
            }
            /*
             * IE messes up the top padding for the icon fonts from fontello >_<.
             * 
             * Luckily it's the same in all IE version ( 8-11 ),
             * so adjusting the padding just for IE fixes it.
             */
            if ($is_IE) {
                $classes[] = 'gssi-ie';
            }
            // If the street view option is enabled, then we need to adjust the right margin for the map control div.
            if ($gssi_location_settings['streetview']) {
                $classes[] = 'gssi-street-view-exists';
            }
            if (!empty($classes)) {
                $class = 'class="' . join(' ', $classes) . '"';
            } else {
                $class = '';
            }
            $map_controls = '<div id="gssi-map-controls" ' . $class . '>' . $reset_button . '<div class="gssi-icon-direction"><span>&#xe800;</span></div></div>';
            return apply_filters('gssi_map_controls', $map_controls);
        }
        
        public function geolocation_errors() {
            $geolocation_errors = array(
                'denied' => __('The application does not have permission to use the Geolocation API.', 'gssi_location'),
                'unavailable' => __('Location information is unavailable.', 'gssi_location'),
                'timeout' => __('The geolocation request timed out.', 'gssi_location'),
                'generalError' => __('An unknown error occurred.', 'gssi_location')
            );
            return $geolocation_errors;
        }
        
        public function get_marker_props() {
            $marker_props = array(
                'scaledSize' => '51,63', // 50% of the normal image to make it work on retina screens.
                'origin' => '0,0',
                'anchor' => '12,35'
            );
            /*
             * If this is not defined, the url path will default to 
             * the url path of the GSSI plugin folder + /img/markers/
             * in the gssi-location-gmap.js.
             */
            if (defined('GSSI_MARKER_URI')) {
                $marker_props['url'] = GSSI_MARKER_URI;
            }
            return apply_filters('gssi_marker_props', $marker_props);
        }
        
        public function get_directions_travel_mode() {
            $default = 'driving';
            $travel_mode = apply_filters('gssi_direction_travel_mode', $default);
            $allowed_modes = array('driving', 'bicycling', 'transit', 'walking');
            if (!in_array($travel_mode, $allowed_modes)) {
                $travel_mode = $default;
            }
            return strtoupper($travel_mode);
        }
        /**
         * Load the required JS scripts.
         *
         * @return void
         */
        public function addScripts() {
            global $gssi_location_settings;
            // Only load the required js files on the location locator page or individual location pages.
            if (empty($this->load_scripts)) {
                return;
            }
            
            if (in_array('gssi_location', $this->load_scripts)) {
                wp_enqueue_script('gssi-location-navigation-js', apply_filters('gssi_location_navigation_js', GSSI_LOCATION_URL . 'includes/frontend/js/gssi-location-navigation.js'), array('jquery'), GSSI_LOCATION_VERSION_NUM, true);
            }
            
            $dropdown_defaults = $this->getDropdownDefaults();
            wp_enqueue_script('gssi-location-gmap', ( 'https://maps.google.com/maps/api/js' . gssi_get_gmap_api_params('browser_key')), '', null, true);
            
            $locationMarker = isset($gssi_location_settings['location_marker']) 
                    && is_numeric($gssi_location_settings['location_marker']) 
                    ? wp_get_attachment_image_src($gssi_location_settings['location_marker'])[0] 
                    : GSSI_LOCATION_URL . 'includes/frontend/img/markers/blue.png';
            
            $base_settings = array(
                'locationMarker' => $locationMarker,
                'mapType' => $gssi_location_settings['map_type'],
                'mapTypeControl' => $gssi_location_settings['type_control'],
                'zoomLevel' => $gssi_location_settings['zoom_level'],
                'startLatlng' => $gssi_location_settings['start_latlng'],
                'autoZoomLevel' => $gssi_location_settings['auto_zoom_level'],
                'scrollWheel' => $gssi_location_settings['scrollwheel'],
                'controlPosition' => $gssi_location_settings['control_position'],
                'url' => GSSI_LOCATION_URL,
                'markerIconProps' => $this->get_marker_props(),
                'locationUrl' => $gssi_location_settings['location_url'],
                'maxDropdownHeight' => apply_filters('gssi_max_dropdown_height', 300),
                'enableStyledDropdowns' => apply_filters('gssi_enable_styled_dropdowns', true),
                'mapTabAnchor' => apply_filters('gssi_map_tab_anchor', 'gssi-map-tab'),
                'mapTabAnchorReturn' => apply_filters('gssi_map_tab_anchor_return', false),
                'gestureHandling' => apply_filters('gssi_gesture_handling', 'auto'),
                'directionsTravelMode' => $this->get_directions_travel_mode(),
                'runFitBounds' => $gssi_location_settings['run_fitbounds'],
                'isSingle' => is_single()
            );
            $locator_map_settings = array(
                'startMarker' => 'red.png',
                'markerClusters' => $gssi_location_settings['marker_clusters'],
                'streetView' => $gssi_location_settings['streetview'],
                'autoComplete' => $gssi_location_settings['autocomplete'],
                'autoLocate' => $gssi_location_settings['auto_locate'],
                'autoLoad' => $gssi_location_settings['autoload'],
                'markerEffect' => $gssi_location_settings['marker_effect'],
                'markerStreetView' => $gssi_location_settings['marker_streetview'],
                'markerZoomTo' => $gssi_location_settings['marker_zoom_to'],
                'newWindow' => $gssi_location_settings['new_window'],
                'resetMap' => $gssi_location_settings['reset_map'],
                'directionRedirect' => 1,//$gssi_location_settings['direction_redirect'],
                'phoneUrl' => $gssi_location_settings['phone_url'],
                'moreInfoLocation' => $gssi_location_settings['more_info_location'],
                'mouseFocus' => $gssi_location_settings['mouse_focus'],
                'templateId' => $gssi_location_settings['template_id'],
                'maxResults' => $dropdown_defaults['max_results'],
                'searchRadius' => $dropdown_defaults['search_radius'],
                'distanceUnit' => $gssi_location_settings['distance_unit'],
                'geoLocationTimout' => apply_filters('gssi_geolocation_timeout', 7500),
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'mapControls' => $this->getMapControls()
            );
            /*
             * If no results are found then by default it will just show the
             * "No results found" text. This filter makes it possible to show
             * a custom HTML block instead of the "No results found" text.
             */
            $no_results_msg = apply_filters('gssi_no_results', '');
            if ($no_results_msg) {
                $locator_map_settings['noResults'] = $no_results_msg;
            }
            /*
             * If enabled, include the component filter settings.
             * @todo see https://developers.google.com/maps/documentation/javascript/releases#327
             * See https://developers.google.com/maps/documentation/javascript/geocoding#ComponentFiltering
             */
            if ($gssi_location_settings['api_region'] && $gssi_location_settings['api_geocode_component']) {
                $locator_map_settings['geocodeComponents'] = apply_filters('gssi_geocode_components', array(
                    'country' => strtoupper($gssi_location_settings['api_region'])
                        ));
            }
            // If the marker clusters are enabled, include the js file and marker settings.
            if ($gssi_location_settings['marker_clusters']) {
                wp_enqueue_script('gssi-cluster', GSSI_LOCATION_URL . 'includes/frontend/js/markerclusterer.js', array('gssi-location-js'), GSSI_LOCATION_VERSION_NUM, true); //not minified version is in the /js folder
                $base_settings['clusterZoom'] = $gssi_location_settings['cluster_zoom'];
                $base_settings['clusterSize'] = $gssi_location_settings['cluster_size'];
                $base_settings['clusterImagePath'] = 'https://cdn.rawgit.com/googlemaps/js-marker-clusterer/gh-pages/images/m';
            }
            // Check if we need to include the infobox script and settings.
            if ($gssi_location_settings['infowindow_style'] == 'infobox') {
                wp_enqueue_script('gssi-infobox', GSSI_LOCATION_URL . 'includes/frontend/js/infobox.js', array('gssi-location-gmap'), GSSI_LOCATION_VERSION_NUM, true); // Not minified version is in the /js folder
                $base_settings['infoWindowStyle'] = $gssi_location_settings['infowindow_style'];
                $base_settings = $this->getInfoboxSettings($base_settings);
            }
            // Include the map style.
            if (!empty($gssi_location_settings['map_style'])) {
                $base_settings['mapStyle'] = strip_tags(stripslashes(json_decode($gssi_location_settings['map_style'])));
            }
            wp_enqueue_script('gssi-location-js', apply_filters('gssi_location_gmap_js', GSSI_LOCATION_URL . 'includes/frontend/js/gssi-location-gmap.js'), array('jquery'), GSSI_LOCATION_VERSION_NUM, true);
            wp_enqueue_script('underscore');
            // Check if we need to include all the settings and labels or just a part of them.
            if (in_array('gssi_location', $this->load_scripts)) {
                $settings = wp_parse_args($base_settings, $locator_map_settings);
                $template = 'gssi_location';
                $labels = array(
                    'preloader' => __('Searching...', 'gssi_location'),
                    'noResults' => __('No results found', 'gssi_location'),
                    'moreInfo' => __('More info', 'gssi_location'),
                    'generalError' => __('Something went wrong, please try again!', 'gssi_location'),
                    'queryLimit' => __('API usage limit reached', 'gssi_location'),
                    'directions' => __('Get Directions', 'gssi_location'),
                    'noDirectionsFound' => __('No route could be found between the origin and destination', 'gssi_location'),
                    'startPoint' => __('Start location', 'gssi_location'),
                    'back' => __('Back', 'gssi_location'),
                    'streetView' => __('Street view', 'gssi_location'),
                    'zoomHere' => __('Zoom here', 'gssi_location'),
                    'closesMyRickyTitle' => __('This is My Location', 'gssi_location'),
                    'closesNotMyRickyTitle' => __('Your Closest Location', 'gssi_location')
                );
                wp_localize_script('gssi-location-js', 'gssiLocationLabels', $labels);
                wp_localize_script('gssi-location-js', 'gssiGeolocationErrors', $this->geolocation_errors());
            } else {
                $template = '';
                $settings = $base_settings;
            }
            // Check if we need to overwrite JS settings that are set through the [gssi] shortcode.
            if ($this->sl_shortcode_atts && isset($this->sl_shortcode_atts['js'])) {
                foreach ($this->sl_shortcode_atts['js'] as $shortcode_key => $shortcode_val) {
                    $settings[$shortcode_key] = $shortcode_val;
                }
            }
            wp_localize_script('gssi-location-js', 'gssiSettings', apply_filters('gssi_js_settings', $settings));
            gssi_create_underscore_templates($template);
            if (!empty($this->location_map_data)) {
                $i = 0;
                foreach ($this->location_map_data as $map) {
                    wp_localize_script('gssi-location-js', 'gssiMap_' . $i, $map);
                    $i++;
                }
            }
        }
        
        public function getInfoboxSettings($settings) {
            $infobox_settings = apply_filters('gssi_infobox_settings', array(
                'infoBoxClass' => 'gssi-infobox',
                'infoBoxCloseMargin' => '2px', // The margin can be written in css style, so 2px 2px 4px 2px for top, right, bottom, left
                'infoBoxCloseUrl' => '//www.google.com/intl/en_us/mapfiles/close.gif',
                'infoBoxClearance' => '40,40',
                'infoBoxDisableAutoPan' => 0,
                'infoBoxEnableEventPropagation' => 0,
                'infoBoxPixelOffset' => '-52,-45',
                'infoBoxZindex' => 1500
                    ));
            foreach ($infobox_settings as $infobox_key => $infobox_setting) {
                $settings[$infobox_key] = $infobox_setting;
            }
            return $settings;
        }
    }
    new GssiFrontend();
}
