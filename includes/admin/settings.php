<?php
if (!defined('ABSPATH'))
    exit;

if (!class_exists('GssiLocationSettingPage')) {

    class GssiLocationSettingPage {
        
        public function __construct() {
            add_action('admin_menu', array($this, 'createAdminMenu'));
            add_action('admin_init', array($this, 'registerSettings'));
            
            add_action('admin_enqueue_scripts', array($this, 'setupAdminScripts'));
        }

        /**
         * @return void
         */
        public function createAdminMenu() {
            add_submenu_page(
                    'edit.php?post_type=gssi_locations', __('Settings', 'gssi_location'), __('Settings', 'gssi_location'), 'administrator', 'gssi_location_settings', array($this, 'loadTemplate')
            );
        }

        /**
         * @return void
         */
        public function loadTemplate() {
            $args = array(
                'sort_order' => 'asc',
                'sort_column' => 'post_title',
                'hierarchical' => 1,
                'exclude' => '',
                'include' => '',
                'meta_key' => '',
                'meta_value' => '',
                'authors' => '',
                'child_of' => 0,
                'parent' => -1,
                'exclude_tree' => '',
                'number' => '',
                'offset' => 0,
                'post_type' => 'page',
                'post_status' => 'publish'
            );
            $pages = get_pages($args);
            $markerUploader = $this->pinUploader();
            require 'templates/settings.php';
        }

        protected function pinUploader($width = 100, $height = 100) {
            global $gssi_location_settings;
            // Set variables
            $defaultImage = GSSI_LOCATION_URL . 'includes/frontend/img/markers/' . $gssi_location_settings['location_marker'];

            if (!empty($gssi_location_settings['location_marker']) 
                    && is_numeric($gssi_location_settings['location_marker'])) {
                $image_attributes = wp_get_attachment_image_src($gssi_location_settings['location_marker'], array($width, $height));
                $src = $image_attributes[0];
                $value = $gssi_location_settings['location_marker'];
            } else {
                $src = $defaultImage;
                $value = '';
            }

            // Print HTML field
            return '<p class="upload">
                    <label for="">'.__('Location Marker', 'gssi_location').'</label>
                    <img data-src="' . $defaultImage . '" src="' . $src . '" width="' . $width . 'px" height="' . $height . 'px" />
                    <input type="hidden" name="location_marker" id="gssi-location_marker" value="' . $value . '" />
                    <button type="button" class="upload_marker_button button">' . __('Upload', 'gssi_location') . '</button>
                </p>';
        }

        /**
         * @return void
         */
        public function registerSettings() {
            register_setting('gssi_location_settings', 'gssi_location_settings', array($this, 'sanitizeSettings'));
        }

        /**
         * 
         * @global type $gssi_location_settings
         * @return []
         */
        public function sanitizeSettings() {
            global $gssi_location_settings, $gssi_location_default_setting;

            $output = [];

            if (count($gssi_location_default_setting)) {
                foreach ($gssi_location_default_setting as $key => $value) {
                    if (isset($_POST[$key]) && $_POST[$key] != '') {
                        if ($key == 'map_style') {
                            $output[$key] = json_encode( strip_tags( trim( $_POST[$key] ) ) );
                        } else {
                            $output[$key] = sanitize_text_field($_POST[$key]);
                        }
                    } else {
                        $output[$key] = $value;
                    }
                }
            }

            return $output;
        }
        
        public function setupAdminScripts() {
            global $gssi_location_settings;
            
            wp_enqueue_media();
            
            wp_enqueue_script('gssi-location-admin-js', plugins_url('/js/gssi-location-admin.js', __FILE__), array('jquery'), GSSI_LOCATION_VERSION_NUM, true);


            // for the edit location post type
            if (( get_post_type() == 'gssi_locations' ) || ( isset($_GET['post_type']) && ( $_GET['post_type'] == 'gssi_locations' ) )) {
                wp_enqueue_style('jquery-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/smoothness/jquery-ui.css');
                wp_enqueue_style('gssi--location-admin-css', plugins_url('/css/style.css', __FILE__), false);

                wp_enqueue_script('jquery-ui-dialog');
                wp_enqueue_script('gssi-gmap', ( '//maps.google.com/maps/api/js'.gssi_get_gmap_api_params('browser_key')), false, GSSI_LOCATION_VERSION_NUM, true);
                
                wp_localize_script('gssi-location-admin-js', 'gssiSettings', array(
                    'defaultLatLng'  => $gssi_location_settings['start_latlng'],
                    'defaultZoom'    => 6,
                    'mapType'        => 'roadmap',
                    'requiredFields' => array( 'address', 'city', 'country' ),
                    'ajaxurl'        => admin_url( 'admin-ajax.php' ),
                    'noAddress'       => __( 'Cannot determine the address at this location.', 'gssi_location' ),
                    'geocodeFail'     => __( 'Geocode was not successful for the following reason', 'gssi_location' ),
                    'securityFail'    => __( 'Security check failed, reload the page and try again.', 'gssi_location' ),
                    'requiredFields'  => __( 'Please fill in all the required location details.', 'gssi_location' ),
                    'missingGeoData'  => __( 'The map preview requires all the location details.', 'gssi_location' ),
                    'closedDate'      => __( 'Closed', 'gssi_location' ),
                    'styleError'      => __( 'The code for the map style is invalid.', 'gssi_location' ),
                    'dismissNotice'   => __( 'Dismiss this notice.', 'gssi_location' )
                ));
            }
        }

    }

}