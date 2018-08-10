<?php

/*
  Plugin Name: Gssi Location
  Description: Gssi Location
  Author: Gssi
  Author URI: https://example.com/
  Version: 1.0.0
  Text Domain: gssi_location
 */

if (!defined('GSSI_LOCATION_VERSION_NUM'))
    define('GSSI_LOCATION_VERSION_NUM', '1.0.0');

if (!defined('GSSI_LOCATION_URL'))
    define('GSSI_LOCATION_URL', plugin_dir_url(__FILE__));

if (!defined('GSSI_LOCATION_BASENAME'))
    define('GSSI_LOCATION_BASENAME', plugin_basename(__FILE__));

if (!defined('GSSI_LOCATION_PLUGIN_DIR'))
    define('GSSI_LOCATION_PLUGIN_DIR', plugin_dir_path(__FILE__));

if (!class_exists('GssiLocation')) {

    class GssiLocation {
        
        protected $_settings;
        
        protected $_posttype;
        
        protected $_adminSettingPage;
        
        protected $_locationMetaBoxes;


        public function __construct() {
            // Load text domain
            add_action('plugins_loaded', array($this, 'loadTextDomain'));

            require_once( GSSI_LOCATION_PLUGIN_DIR . 'includes/settings.php' );            
            $this->_settings = new GssiLocationSettings();

            require_once( GSSI_LOCATION_PLUGIN_DIR . 'includes/post-type.php' );            
            $this->_posttype = new GssiLocationPostType();
            
            require_once( GSSI_LOCATION_PLUGIN_DIR . 'includes/termmeta.php' );
            
            if (is_admin() || defined('WP_CLI') && WP_CLI) {
                require_once( GSSI_LOCATION_PLUGIN_DIR . 'includes/admin/settings.php' );
                $this->_adminSettingPage = new GssiLocationSettingPage();
                require_once( GSSI_LOCATION_PLUGIN_DIR . 'includes/admin/metaboxes.php' );
                $this->_locationMetaBoxes = new GssiLocationMetaBoxes();
            }
            
            require_once( GSSI_LOCATION_PLUGIN_DIR . 'includes/frontend.php' );

            $this->initGlobalVariables();

            register_activation_hook(__FILE__, array($this, 'install'));
        }

        // load plugin text domain
        public function loadTextDomain() {
            load_plugin_textdomain('gssi_location', false, dirname(__FILE__) . '/languages/');
        }

        public function initGlobalVariables() {
            global $gssi_location_settings, $gssi_location_default_setting;

            $gssi_location_settings = $this->_settings->getSettings();
            $gssi_location_default_setting = $this->_settings->getDefaultSetting();
        }

        public function install() {
            $this->_settings->installLocationData();
        }

    }

    $GLOBALS['gssi_location'] = new GssiLocation();
}