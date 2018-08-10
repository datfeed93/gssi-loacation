<?php
if (!defined('ABSPATH'))
    exit;

global $gssi_location_settings;
?>

<div id="gssi-wrap" class="wrap gssi-settings ">
    <h2><?php _e('Location Settings', 'gssi_location'); ?></h2>
    <div id="general">
        <form id="gssi-settings-form" method="post" action="options.php" autocomplete="off" accept-charset="utf-8">
            <?php settings_fields('gssi_location_settings'); ?>
            <?php do_settings_sections('gssi_location_settings'); ?>
            <div class="postbox-container">
                <div class="metabox-holder">
                    <div id="gssi-api-settings" class="postbox">
                        <h3 class="hndle"><span><?php _e('Google Maps API', 'gssi_location'); ?></span></h3>
                        <div class="inside">
                            <p>
                                <label for="gssi-api-server-key"><?php _e('Server key', 'gssi_location'); ?>:<span class="gssi-info"><span class="gssi-info-text gssi-hide"><a href="https://developers.google.com/maps/documentation/javascript/get-api-key"><?php echo __('Get Api Key here') ?></a></span></span></label> 
                                <input type="text" value="<?php echo esc_attr($gssi_location_settings['api_server_key']); ?>" name="api_server_key"  class="textinput" id="gssi-api-server-key">
                            </p>
                            <p>
                                <label for="gssi-api_browser_key"><?php _e('Browser key', 'gssi_location'); ?>:</label>
                                <input type="text" value="<?php echo esc_attr($gssi_location_settings['api_browser_key']); ?>" name="api_browser_key"  class="textinput" id="gssi-api_browser_key">
                            </p>
                            <p>
                                <label for="gssi-start-latlng"><?php _e('Start Location', 'gssi_location'); ?>:</label> 
                                <input type="text" value="<?php echo esc_attr($gssi_location_settings['start_latlng']); ?>" name="start_latlng"  class="textinput" id="gssi-start-latlng">
                            </p>
                            <p>
                                <label for="gssi-start_address"><?php _e('Start Location Address', 'gssi_location'); ?>:</label>
                                <input type="text" value="<?php echo esc_attr($gssi_location_settings['start_address']); ?>" name="start_address"  class="textinput" id="gssi-start_address">
                            </p>
                            <p>
                                <label for="gssi-autocompelete"><?php _e('Search Autocomplete', 'gssi_location'); ?>:</label> 
                                <input type="checkbox" <?php echo $gssi_location_settings['autocomplete'] ? 'checked' : '' ?> class="textinput" id="gssi-autocompelete"/>
                                <input type="hidden" value="<?php echo $gssi_location_settings['autocomplete'] ? 1 : 0 ?>" name="autocomplete"  class="textinput"/>
                            </p>
                            <p>
                                <label for="gssi-auto-locate"><?php _e('Auto Locate', 'gssi_location'); ?>:</label> 
                                <input type="checkbox" <?php echo $gssi_location_settings['auto_locate'] ? 'checked' : '' ?>  class="textinput" id="gssi-auto-locate"/>
                                <input type="hidden" value="<?php echo $gssi_location_settings['auto_locate'] ? 1 : 0 ?>" name="auto_locate"  class="textinput"/>
                            </p>
                            <p>
                                <label for="gssi-address-format"><?php _e('Address Format', 'gssi_location'); ?>:</label> 
                                <input type="text" value="<?php echo esc_attr($gssi_location_settings['address_format']); ?>" name="address_format"  class="textinput" id="gssi-address-format">
                            </p>
                            <p>
                                <label for="gssi-infowindow_style"><?php _e('Info Window Style', 'gssi_location'); ?>:</label> 
                                <select name="infowindow_style" id="gssi-infowindow_style">
                                    <option value="default" <?php echo esc_attr($gssi_location_settings['infowindow_style']) == 'default' ? 'selected' : ''; ?>><?php echo __('default', 'gssi_location') ?></option>
                                    <option value="infobox" <?php echo esc_attr($gssi_location_settings['infowindow_style']) == 'infobox' ? 'selected' : ''; ?>><?php echo __('infobox', 'gssi_location') ?></option>
                                </select>
                            </p>
                            <p>
                                <label for="gssi-zoom_level"><?php _e('Zoom Level', 'gssi_location'); ?>:</label> 
                                <select name="zoom_level" id="gssi-zoom_level">
                                    <?php for($i = 15; $i <=100; $i++): ?>
                                        <option value="<?php echo $i ?>" <?php echo esc_attr($gssi_location_settings['zoom_level']) == $i ? 'selected' : ''; ?>><?php echo $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </p>
                            <p>
                                <label for="gssi-location_page"><?php _e('Location Page', 'gssi_location'); ?>:</label> 
                                <select name="location_page" id="gssi-location_page">
                                    <option value="0"></option>
                                    <?php if (count($pages)): ?>
                                        <?php foreach ($pages as $_page): ?>
                                            <option value="<?php echo esc_attr($_page->ID) ?>" <?php echo ($gssi_location_settings['location_page'] == $_page->ID) ? 'selected' : '' ?>>
                                                <?php echo esc_attr($_page->post_title) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </p>
                            <p>
                                <label for="gssi-autoload_limit"><?php _e('Number Location per page', 'gssi_location'); ?>:</label> 
                                <input type="text" value="<?php echo esc_attr($gssi_location_settings['autoload_limit']); ?>" name="autoload_limit"  class="textinput" id="gssi-autoload_limit">
                            </p>
                            
                            <?php echo $markerUploader; ?>
                            
                            <p>
                                <label for="gssi-map_style"><?php _e('Map style script', 'gssi_location'); ?>:</label> 
                                <textarea name="map_style"  class="textinput" id="gssi-map_style"><?php echo strip_tags( stripslashes( json_decode( $gssi_location_settings['map_style'] ) ) ); ?></textarea>
                            </p>
                            
                            <p class="submit">
                                <?php submit_button(); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

</div>    