<?php
function gssi_create_underscore_templates( $template ) {
    global $gssi_location_settings;
    
    if ( $template == 'gssi_location' ) {
    ?>
<script>
var locationAjaxUrl = '<?php echo admin_url( 'admin-ajax.php' ) ?>';
</script>
<script id="gssi-direction-single-template" type="text/template">
    <%= createDirectionUrl() %>
</script>
<script id="gssi-info-window-template" type="text/template">
    <?php
        $info_window_template = '<div data-location-id="<%= id %>" class="gssi-info-window">' . "\r\n";
        $info_window_template .= "\t\t" . '<%= thumb %>' . "\r\n";
        $info_window_template .= "\t\t" . '<div class="desc">' . "\r\n";
        $info_window_template .= "\t\t\t" .  gssi_location_header_template() . "\r\n";  // Check which header format we use
        $info_window_template .= "\t\t\t" . '<div class="address"><span><%= address %></span>' . "\r\n";
        $info_window_template .= "\t\t\t" . '<% if ( address2 ) { %>' . "\r\n";
        $info_window_template .= "\t\t\t" . '<span><%= address2 %></span>' . "\r\n";
        $info_window_template .= "\t\t\t" . '<% } %>' . "\r\n";
        $info_window_template .= "\t\t\t" . '<span>' . gssi_address_format_placeholders() . '</span></div>' . "\r\n"; // Use the correct address format
        $info_window_template .= "\t\t\t" . '<% if ( phone ) { %>' . "\r\n";
        $info_window_template .= "\t\t\t" . '<div class="phone-number"><span>Phone: <%= formatPhoneNumber( phone ) %></span></div>' . "\r\n";
        $info_window_template .= "\t\t\t" . '<% } %>' . "\r\n";
        $info_window_template .= "\t\t\t" . '<%= createInfoWindowActions( id ) %>' . "\r\n";
        $info_window_template .= "\t\t" . '</div>' . "\r\n";
        $info_window_template .= "\t\t" . '<div class="ricky-action">' . "\r\n";
        $info_window_template .= "\t\t\t" . '<% if ( is_ricky == id ) { %>' . "\r\n";
        $info_window_template .= "\t\t\t" . '<span>' . __('This is My Location', 'gssi_location') . '</span>' . "\r\n";
        $info_window_template .= "\t\t\t" . '<% } %>';
        $info_window_template .= apply_filters('gssi_info_window_menu_link', '');
        $info_window_template .= "\t\t\t" . '<a href="<%= detail_url %>" class="btn btn-clear">' . __('Details', 'gssi_location') . '</a>' . "\r\n";
        $info_window_template .= apply_filters('gssi_info_window_delivery_link', '');
        $info_window_template .= "\t\t" . '</div>' . "\r\n";
        $info_window_template .= "\t" . '</div>';
        echo apply_filters( 'gssi_info_window_template', $info_window_template . "\n" );
    ?>
</script>
<script id="gssi-listing-template" type="text/template">
    <?php
        $listing_template = '<li data-location-id="<%= id %>">' . "\r\n";
        $listing_template .= "\t\t" . '<div class="section-inner">' . "\r\n";
        $listing_template .= "\t\t" . '<%= thumb %>' . "\r\n";
        $listing_template .= "\t\t\t\t" . gssi_location_header_template( 'listing' ) . "\r\n"; // Check which header format we use
        $listing_template .= "\t\t" . '<div class="gssi-location-location">' . "\r\n";
        $listing_template .= "\t\t\t" . '<div class="address">' . "\r\n";
        $listing_template .= "\t\t\t\t" . '<span class="gssi-street"><%= address %></span>' . "\r\n";
        $listing_template .= "\t\t\t\t" . '<% if ( address2 ) { %>' . "\r\n";
        $listing_template .= "\t\t\t\t" . '<span class="gssi-street"><%= address2 %></span>' . "\r\n";
        $listing_template .= "\t\t\t\t" . '<% } %>';
        $listing_template .= "\t\t\t\t" . '<span>' . gssi_address_format_placeholders() . '</span>' . "\r\n"; // Use the correct address format
        $listing_template .= "\t\t\t" . '</div>' . "\r\n";
        $listing_template .= "\t\t\t\t" . '<div class="contact-directions"><% if ( phone ) { %>' . "\r\n";
        $listing_template .= "\t\t\t\t" . '<div class="phone-number"><span>Phone: <%= formatPhoneNumber( phone ) %></span></div>' . "\r\n";
        $listing_template .= "\t\t\t\t" . '<% } %>' . "\r\n";
        $listing_template .= "\t\t\t" . '<div class="gssi-direction-wrap">' . "\r\n";
        
        if ( !$gssi_location_settings['hide_distance'] ) {
            //$listing_template .= "\t\t\t\t" . '<%= distance %> ' . esc_html( $gssi_location_settings['distance_unit'] ) . '' . "\r\n";
        }
        
        $listing_template .= "\t\t\t\t" . '<%= createDirectionUrl() %>' . "\r\n"; 
        $listing_template .= "\t\t\t" . '</div></div>' . "\r\n";
        $listing_template .= "\t\t" . '<div class="ricky-action">' . "\r\n";
        $listing_template .= "\t\t\t" . '<% if ( is_ricky == id ) { %>' . "\r\n";
        $listing_template .= "\t\t\t" . '<span class="my-location-lbl">' . __('This Is My Location', 'gssi_location') . '</span>' . "\r\n";
        $listing_template .= "\t\t\t" . '<% } %>';
        $listing_template .= apply_filters('gssi_info_window_menu_link', '');
        $listing_template .= "\t\t\t" . '<a href="<%= detail_url %>" class="btn btn-clear">' . __('Location Details', 'gssi_location') . '</a>' . "\r\n";
        $listing_template .= apply_filters('gssi_info_window_delivery_link', '');
        $listing_template .= "\t\t" . '</div>' . "\r\n";
        $listing_template .= "\t\t" . '</div>' . "\r\n";
        $listing_template .= "\t\t" . '</div>' . "\r\n";//end section inner
        $listing_template .= "\t" . '</li>';
        echo apply_filters( 'gssi_listing_template', $listing_template . "\n" );
    ?>
</script>   
<script id="gssi-closest-template" type="text/template">
    <?php
        $listing_template  = "\t\t";
        
        $listing_template .= "\t\t" . '<div class="ricky-desc">';
        $listing_template .= "\t\t\t" . '<div class="gssi-location-info">' . "\r\n";
        $listing_template .= "\t\t\t\t" . gssi_location_header_template( 'closet' ) . "\r\n"; // Check which header format we use
        $listing_template .= "\t\t\t\t" . '<div class="gssi-address"><span class="gssi-street"><%= address %></span>' . "\r\n";
        $listing_template .= "\t\t\t\t" . '<% if ( address2 ) { %>' . "\r\n";
        $listing_template .= "\t\t\t\t" . '<span class="gssi-street"><%= address2 %></span>' . "\r\n";
        $listing_template .= "\t\t\t\t" . '<% } %>';
        $listing_template .= "\t\t\t\t" . '<span>' . gssi_address_format_placeholders() . '</span></div>' . "\r\n"; // Use the correct address format
        $listing_template .= "\t\t\t\t" . '<% if ( phone ) { %>' . "\r\n";
        $listing_template .= "\t\t\t\t" . '<div class="phone-number"><span>Phone: <%= formatPhoneNumber( phone ) %></span></div>' . "\r\n";
        $listing_template .= "\t\t\t\t" . '<% } %>' . "\r\n";
        $listing_template .= "\t\t\t" . '</div>' . "\r\n";
        $listing_template .= "\t\t" . '</div>';
        
        
        $listing_template .= "\t\t" . '<div class="make-ricky">';
        $listing_template .= "\t\t\t" . '<a href="<%= detail_url %>" class="btn btn-clear">' . __('Location Details', 'gssi_location') . '</a>' . "\r\n";
        $listing_template .= "\t\t" . '</div>';
        echo apply_filters( 'gssi_closest_template', $listing_template . "\n" );
    ?>
</script>         
    <?php
    } else {
    ?>
<script id="gssi-cpt-info-window-template" type="text/template">
    <?php
        $cpt_info_window_template = '<div class="gssi-info-window">' . "\r\n";
        $cpt_info_window_template .= "\t\t" . '<p class="gssi-no-margin">' . "\r\n";
        $cpt_info_window_template .= "\t\t\t" .  gssi_location_header_template( 'gssi_map' ) . "\r\n";
        $cpt_info_window_template .= "\t\t\t" . '<span><%= address %></span>' . "\r\n";
        $cpt_info_window_template .= "\t\t\t" . '<% if ( address2 ) { %>' . "\r\n";
        $cpt_info_window_template .= "\t\t\t" . '<span><%= address2 %></span>' . "\r\n";
        $cpt_info_window_template .= "\t\t\t" . '<% } %>' . "\r\n";
        $cpt_info_window_template .= "\t\t\t" . '<span>' . gssi_address_format_placeholders() . '</span>' . "\r\n"; // Use the correct address format 
        
        if ( !$gssi_location_settings['hide_country'] ) {
            $cpt_info_window_template .= "\t\t\t" . '<span class="gssi-country"><%= country %></span>' . "\r\n"; 
        }
        
        $cpt_info_window_template .= "\t\t" . '</p>' . "\r\n";
        $cpt_info_window_template .= "\t" . '</div>';
        echo apply_filters( 'gssi_cpt_info_window_template', $cpt_info_window_template . "\n" );
    ?>
</script>
    <?php
    }
}
/**
 * Create the more info template.
 *
 * @return string $more_info_template The template that is used to show the "More info" content
 */
function gssi_more_info_template() {
            
    global $gssi_location_settings;
    
    if ( $gssi_location_settings['more_info'] ) {
        $more_info_url = '#';
        if ( $gssi_location_settings['template_id'] == 'default' && $gssi_location_settings['more_info_location'] == 'info window' ) {
            $more_info_url = '#gssi-search-wrap';
        }
        if ( $gssi_location_settings['more_info_location'] == 'location listings' ) {
            $more_info_template = '<% if ( !_.isEmpty( phone ) || !_.isEmpty( fax ) || !_.isEmpty( email ) ) { %>' . "\r\n";
            $more_info_template .= "\t\t\t" . '<p><a class="gssi-location-details gssi-location-listing" href="#gssi-id-<%= id %>">' . esc_html( __( 'More info', 'gssi_location' ) ) . '</a></p>' . "\r\n";
            $more_info_template .= "\t\t\t" . '<div id="gssi-id-<%= id %>" class="gssi-more-info-listings">' . "\r\n";
            $more_info_template .= "\t\t\t\t" . '<% if ( description ) { %>' . "\r\n";
            $more_info_template .= "\t\t\t\t" . '<%= description %>' . "\r\n";
            $more_info_template .= "\t\t\t\t" . '<% } %>' . "\r\n";
            
            if ( !$gssi_location_settings['show_contact_details'] ) {
                $more_info_template .= "\t\t\t\t" . '<p>' . "\r\n";
                $more_info_template .= "\t\t\t\t" . '<% if ( phone ) { %>' . "\r\n";
                $more_info_template .= "\t\t\t\t" . '<span><strong>' . esc_html( __( 'Phone', 'gssi_location' ) ) . '</strong>: <%= formatPhoneNumber( phone ) %></span>' . "\r\n";
                $more_info_template .= "\t\t\t\t" . '<% } %>' . "\r\n";
                $more_info_template .= "\t\t\t\t" . '<% if ( fax ) { %>' . "\r\n";
                $more_info_template .= "\t\t\t\t" . '<span><strong>' . esc_html( __( 'Fax', 'gssi_location' ) ) . '</strong>: <%= fax %></span>' . "\r\n";
                $more_info_template .= "\t\t\t\t" . '<% } %>' . "\r\n";
                $more_info_template .= "\t\t\t\t" . '<% if ( email ) { %>' . "\r\n";
                $more_info_template .= "\t\t\t\t" . '<span><strong>' . esc_html( __( 'Email', 'gssi_location' ) ) . '</strong>: <%= email %></span>' . "\r\n";
                $more_info_template .= "\t\t\t\t" . '<% } %>' . "\r\n";
                $more_info_template .= "\t\t\t\t" . '</p>' . "\r\n";
            }
            if ( !$gssi_location_settings['hide_hours'] ) {
                $more_info_template .= "\t\t\t\t" . '<% if ( hours ) { %>' . "\r\n";
                $more_info_template .= "\t\t\t\t" . '<div class="gssi-location-hours"><strong>' . esc_html( __( 'Hours', 'gssi_location' ) ) . '</strong><%= hours %></div>' . "\r\n";
                $more_info_template .= "\t\t\t\t" . '<% } %>' . "\r\n";
            }
            $more_info_template .= "\t\t\t" . '</div>' . "\r\n"; 
            $more_info_template .= "\t\t\t" . '<% } %>';
        } else {
            $more_info_template = '<p><a class="gssi-location-details" href="' . $more_info_url . '">' . esc_html( __( 'More info', 'gssi_location' ) ) . '</a></p>';
        }
        return apply_filters( 'gssi_more_info_template', $more_info_template );
    }                 
}
/**
 * Create the location header template.
 *
 * @param  string $location        The location where the header is shown ( info_window / listing / gssi_map shortcode )
 * @return string $header_template The template for the location header
 */
function gssi_location_header_template( $location = 'info_window' ) {
    global $gssi_location_settings;
    if ( $gssi_location_settings['new_window'] ) {
        $new_window = ' target="_blank"';
    } else {
        $new_window = '';
    }
    /* 
     * To keep the code readable in the HTML source we ( unfortunately ) need to adjust the 
     * amount of tabs in front of it based on the location were it is shown. 
     */
    if ( $location == 'listing') {
        $tab = "\t\t\t\t";    
    } else {
        $tab = "\t\t\t";                 
    } ?>
    
    <?Php
    
    if ( $gssi_location_settings['permalinks'] ) {
        
        /**
         * It's possible the permalinks are enabled, but not included in the location data on 
         * pages where the [gssi_map] shortcode is used. 
         * 
         * So we need to check for undefined, which isn't necessary in all other cases.
         */
        if ( $location == 'gssi_map') {
            $header_template = '<% if ( typeof permalink !== "undefined" ) { %>' . "\r\n";
            $header_template .= $tab . '<h3 class="location-header gssi_map_h3" style=""><% if ( locator_icon_url ) { %><img class="locator_icon" src="<%= locator_icon_url %>"/><% } %> <a' . $new_window . ' href="<%= permalink %>" style="color:#<%= locator_color %>"><%= location %></a></h3>' . "\r\n";
            $header_template .= $tab . '<% } else { %>' . "\r\n";
            $header_template .= $tab . '<strong style="color:#<%= locator_color %>"><% if ( locator_icon_url ) { %><img class="locator_icon" src="<%= locator_icon_url %>"/><% } %> <%= location %></strong>' . "\r\n";
            $header_template .= $tab . '<% } %>';   
        } else {
            $header_template = '<strong><% if ( locator_icon_url ) { %><img class="locator_icon" src="<%= locator_icon_url %>"/><% } %> <a' . $new_window . ' href="<%= permalink %>" style="color:#<%= locator_color %>"><%= location %></a></strong>';
        }
    } else {
        $header_template = '<% if ( gssiSettings.locationUrl == 1 && url ) { %>' . "\r\n";
        $header_template .= $tab . '<strong><% if ( locator_icon_url ) { %><img class="locator_icon" src="<%= locator_icon_url %>"/><% } %> <a' . $new_window . ' href="<%= url %>" style="color:#<%= locator_color %>"><%= location %></a></strong>' . "\r\n";
        $header_template .= $tab . '<% } else { %>' . "\r\n";
        $header_template .= $tab . '<h3 class="location-header no_gssi_map" style="color:#<%= locator_color %>"><% if ( locator_icon_url ) { %><img class="locator_icon" src="<%= locator_icon_url %>"/><% } %> <%= location %></h3>' . "\r\n";
        $header_template .= $tab . '<% } %>'; 
    }
    
    if ($location != 'closet') {
        $header_template = '<h3 class="location-header no_closet"><% if ( locator_icon_url ) { %><img class="locator_icon" src="<%= locator_icon_url %>"/><% } %> <a' . $new_window . ' href="<%= detail_url %>" style="color:#<%= locator_color %>"><%= location %></a></h3>' . "\r\n";
    }
    return apply_filters( 'gssi_location_header_template', $header_template );
}
        
/**
 * Create the address placeholders based on the structure defined on the settings page.
 * 
 * @return string $address_placeholders A list of address placeholders in the correct order
 */
function gssi_address_format_placeholders() {
    global $gssi_location_settings;
    $address_format = explode( '_', $gssi_location_settings['address_format'] );
    $placeholders   = '';
    $part_count     = count( $address_format ) - 1;
    $i              = 0;
    foreach ( $address_format as $address_part ) {
        if ( $address_part != 'comma' ) {
            /* 
             * Don't add a space after the placeholder if the next part 
             * is going to be a comma or if it is the last part. 
             */
            if ( $i == $part_count || $address_format[$i + 1] == 'comma' ) {
                $space = '';    
            } else {
                $space = ' ';      
            }
            $placeholders .= '<%= ' . $address_part . ' %>' . $space;
        } else {
            $placeholders .= ', ';
        }
        $i++;
    }
    return $placeholders;
}
function gssi_get_gmap_api_params( $api_key_type, $geocode_params = false ) {
    global $gssi_location_settings;
    $api_params = '';
    $param_keys = array('key' );
    
    /*
     * The geocode params are included after the address so we need to 
     * use a '&' as the first char, but when the maps script is included on 
     * the front-end it does need to start with a '?'.
     */
    $first_sep = ( $geocode_params ) ? '&' : '?';
    foreach ( $param_keys as $param_key ) {
        $option_key = ( $param_key == 'key' ) ? $api_key_type : $param_key;
        
        $param_val = $gssi_location_settings['api_' . $option_key];
        
        if ( !empty( $param_val ) ) {
            $api_params .= $param_key . '=' . $param_val . '&';
        }
    }
    if ( $api_params ) {
        $api_params = $first_sep . rtrim( $api_params, '&' );
    }
    
    
    if ( ( $gssi_location_settings['autocomplete'] && $api_key_type == 'browser_key' ) || is_admin() ) {
        $api_params .= '&libraries=places';
    }
    return apply_filters( 'gssi_location_gmap_api_params', $api_params );
}
function gssi_get_map_types() {
    
    $map_types = array(
        'roadmap'   => __( 'Roadmap', 'gssi_location' ), 
        'satellite' => __( 'Satellite', 'gssi_location' ),  
        'hybrid'    => __( 'Hybrid', 'gssi_location' ),  
        'terrain'   => __( 'Terrain', 'gssi_location' )
    );
    
    return $map_types;
}
function gssi_bool_check( $atts ) {
    foreach ( $atts as $key => $val ) {
        if ( in_array( $val, array( 'true', '1', 'yes', 'on' ) ) ) {
            $atts[$key] = true;
        } else if ( in_array( $val, array( 'false', '0', 'no', 'off' ) ) ) {
            $atts[$key] = false;
        }
    }
    return $atts;
}
function gssi_get_address_latlng( $address ) {
    $latlng   = '';
    $response = gssi_call_geocode_api( $address );
    if ( !is_wp_error( $response ) ) {
        $response = json_decode( $response['body'], true );
        if ( $response['status'] == 'OK' ) {
            $latlng = $response['results'][0]['geometry']['location']['lat'] . ',' . $response['results'][0]['geometry']['location']['lng'];    
        }
    }
    return $latlng;
}
function gssi_call_geocode_api( $address ) {
    $url      = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode( $address ) . gssi_get_gmap_api_params( 'server_key', true );
    $response = wp_remote_get( $url );
    
    return $response;
}
function gssi_valid_zoom_level( $zoom_level ) {
    global $gssi_location_settings;
    $zoom_level = absint( $zoom_level );
    
    if ( ( $zoom_level < 1 ) || ( $zoom_level > 21 ) ) {
        $zoom_level = $gssi_location_settings['zoom_level'];    
    }
    return $zoom_level;
}
function gssi_valid_map_type( $map_type ) {
    
    $allowed_map_types = gssi_get_map_types();
    
    if ( !array_key_exists( $map_type, $allowed_map_types ) ) {
        $map_type = $gssi_location_settings['zoom_level'];
    }
    
    return $map_type;
}
function gssi_get_location_page() {
    global $gssi_location_settings;
    $locationsUrl = isset($gssi_location_settings['location_page']) ? get_permalink($gssi_location_settings['location_page']) : '#';
    
    return $locationsUrl;
}
function gssi_get_location_detail_header($postId, $type = 'location', $hasLocation = true, $hasMenu = true) {
    $output = '';
    $location = get_post($postId);
    $closestLocationId = isset($_COOKIE['closest_location']) ? $_COOKIE['closest_location'] : false;
    $myRickyId = isset($_COOKIE['my_ricky']) ? $_COOKIE['my_ricky'] : false;
    ob_start();
    ?>
    <div class="location-box">
        <?php if (!$hasLocation): ?>
            <div class="location-identity">
                <div class="section-inner">
                    <div class="h1">
                        <h3><?php echo __('We need to find you a Location', 'gssi_location') ?></h3>
                        <p>An explaination as to why the user needs to selec a location first lorem ipsum dolor sit amet, in fringilla hendrerit sagittis dolor, pellentesque elementum et purus, suscipit hymenaeos eleifend. Enim posuere in luctus turpis, elementum id, ridiculus vulputate vestibulum.</p>
                    </div>
                    <div class="location-identity-switch">
                        <a class="btn btn-clear" href='<?php echo gssi_get_location_page() ?>'><?php echo __('Choose Location', 'gssi_location') ?></a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php if ($myRickyId != $postId): ?>
                <div class="location-box-explanation">
                    <?php if ($type == 'menu' && !$hasMenu): ?>
                        <h2><?php echo __('There is no Menu!') ?></h2>
                    <?php endif; ?>
                    <?php if ($closestLocationId == $postId): ?>
                        <h2><?php echo __('This is Your Closest Location Location') ?></h2>
                    <?php endif; ?>
                    <div class="location-box-content">
                        <?php the_content(); ?>
                       
                        <div class="location-box-btns">
                            <a href="javascript:void(0);" data-location-id="<?php echo $postId ?>" class="btn btn-black btn-make-ricky">
                                <?php echo __('Make this my Location', 'gssi_location') ?>
                            </a>
                            <a class="btn btn-clear" href='<?php echo gssi_get_location_page() ?>'><?php echo __('Change Location', 'gssi_location') ?></a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="location-identity">
                <div class="section-inner">
                    <div class="h1"><?php echo $location->post_title ?></div>
                    <div class="location-identity-switch">
                        <?php if ($type != 'location'): ?>
                            <a class="btn" href='<?php echo get_the_permalink($postId) ?>'><?php echo __('Location Details', 'gssi_location') ?></a>
                        <?php endif; ?>
                        <?php if ($type != 'menu'): ?>
                            <a class="btn" href='#'><?php echo __('View Menu', 'gssi_location') ?></a>
                        <?php endif; ?>
                        <?php if ($type != 'delivery'): ?>
                            <a class="btn btn-clear btn-delivery" href='#'><?php echo __('Delivery Available', 'gssi_location') ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
        
    $output .= ob_get_clean();
    $output .= '</div>';
    return apply_filters('gssi_location_detail_top_content', $output);
}