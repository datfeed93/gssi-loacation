<?php

if (!defined('ABSPATH'))
    exit;

if (!class_exists('GssiLocationSettings')) {

    class GssiLocationSettings {

        /**
         *
         * @var []
         */
        protected $_defaultSettings;

        /**
         * create location data when active plugin.
         * @return void
         */
        public function installLocationData() {
            // Create the default settings.
            $this->setLocationSettings();

            update_option('gssi_location_version', GSSI_LOCATION_VERSION_NUM);
        }

        /**
         * 
         * @param [] $newSettings
         * return void
         */
        public function setLocationSettings($newSettings = null) {
            if ($newSettings != null) {
                update_option('gssi_location_settings', $newSettings);
            } else {
                $settings = get_option( 'gssi_location_settings', false );            

                if ( !$settings ) {
                    update_option( 'gssi_location_settings', $this->getDefaultSetting() );
                }
            }
        }
        
        /**
         * 
         * @return []
         */
        public function getSettings() {
            return get_option( 'gssi_location_settings', $this->getDefaultSetting() );
        }

        /**
         * 
         * @return []
         */
        public function getDefaultSetting() {
            if ($this->_defaultSettings == null) {
                $this->_defaultSettings = array(
                    'api_browser_key' => '',
                    'api_server_key' => '',
                    'api_language' => 'en',
                    'api_region' => '',
                    'api_geocode_component' => 0,
                    'distance_unit' => 'km',
                    'max_results' => '[25],50,75,100',
                    'search_radius' => '10,25,[50],100,200,500',
                    'marker_effect' => 'bounce',
                    'address_format' => 'city_comma_state_comma_zip',
                    'hide_distance' => 0,
                    'hide_country' => 0,
                    'show_contact_details' => 0,
                    'auto_locate' => 0,
                    'autocomplete' => 1,
                    'autoload' => 1,
                    'autoload_limit' => 10,
                    'run_fitbounds' => 1,
                    'zoom_level' => 15,
                    'auto_zoom_level' => 15,
                    'start_name' => '',
                    'start_latlng' => '49.2577143,-123.1939439',
                    'height' => 350,
                    'map_type' => 'roadmap',
                    'map_style' => '',
                    'type_control' => 0,
                    'streetview' => 0,
                    'results_dropdown' => 1,
                    'radius_dropdown' => 1,
                    'category_filter' => 0,
                    'category_filter_type' => 'dropdown',
                    'infowindow_width' => 225,
                    'search_width' => 179,
                    'label_width' => 95,
                    'control_position' => 'left',
                    'scrollwheel' => 1,
                    'marker_clusters' => 0,
                    'cluster_zoom' => 0,
                    'cluster_size' => 0,
                    'new_window' => 0,
                    'reset_map' => 0,
                    'template_id' => 'default',
                    'listing_below_no_scroll' => 0,
                    'direction_redirect' => 0,
                    'more_info' => 0,
                    'location_url' => 0,
                    'phone_url' => 0,
                    'marker_streetview' => 0,
                    'marker_zoom_to' => 0,
                    'more_info_location' => 'info window',
                    'mouse_focus' => 1,
                    'start_marker' => 'red.png',
                    'location_marker' => 'blue.png',
                    'editor_country' => '',
                    'editor_hour_input' => 'dropdown',
                    'editor_hour_format' => 12,
                    'editor_map_type' => 'roadmap',
                    'hide_hours' => 0,
                    'permalinks' => 0,
                    'infowindow_style' => 'default',
                    'show_credits' => 0,
                    'debug' => 0,
                    'deregister_gmaps' => 0,
                    'location_page' => 0,
                    'start_address'=>'1890 Hwy 5 W, Troy, ON L0R 2B0, Canada'
                );
            }
            return $this->_defaultSettings;
        }

    }

}
