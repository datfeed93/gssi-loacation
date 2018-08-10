<?php

if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'GssiLocationMetaBoxes' ) ) {

    class GssiLocationMetaBoxes {

        public function __construct() {
            add_action( 'add_meta_boxes',        array( $this, 'addMetaBoxes' ) );
            add_action( 'save_post',             array( $this, 'savePost' ) );
            add_action( 'post_updated_messages', array( $this, 'locationUpdateMessage' ) );
        }
        
        public function addMetaBoxes() {
            add_meta_box( 'gssi_location_location', __( 'Location', 'gssi_location' ), array( $this, 'createLocationMetaFields' ), 'gssi_locations', 'normal', 'high' );
            add_meta_box( 'gssi_location_additional', __( 'Additional Information', 'gssi_location' ), array( $this, 'createAdditionalMetaFields' ), 'gssi_locations', 'normal', 'high' );
            add_meta_box( 'gssi_location_open_hour', __( 'Opening Hours', 'gssi_location' ), array( $this, 'createOpenHourMetaboxes' ), 'gssi_locations', 'normal', 'high' );
            add_meta_box( 'gssi_location_map_preview', __( 'Preview', 'gssi_location' ), array( $this, 'mapPreview' ), 'gssi_locations', 'side' );
        }
        
        public function createLocationMetaFields() {
            wp_nonce_field( 'save_location_meta', 'gssi_location_meta_nonce' );
            
            $metaFields = array(
                'address' => array(
                    'label'    => __( 'Address', 'gssi_location' ),
                    'required' => true
                ),
                'address2' => array(
                    'label' => __( 'Address 2', 'gssi_location' )
                ),
                'city' => array(
                    'label'    => __( 'City', 'gssi_location' ),
                    'required' => true
                ),
                'state' => array(
                    'label' => __( 'State', 'gssi_location' )
                ),
                'zip' => array(
                    'label' => __( 'Zip Code', 'gssi_location' )
                ),
                'country' => array(
                    'label'    => __( 'Country', 'gssi_location' ),
                    'required' => true
                ),
                'lat' => array(
                    'label' => __( 'Latitude', 'gssi_location' )
                ),
                'lng' => array(
                    'label' => __( 'Longitude', 'gssi_location' )
                )
            );
            $output = '<div class="gssi-location-meta">';
            foreach ( $metaFields as $key => $fieldData ){
                $output .= $this->textInput(array('key' => $key, 'data' => $fieldData));
            }
            $output .= '</div>';
            echo $output;
        }
        
        public function createAdditionalMetaFields() {
            $metaFields = array(
                'phone' => array(
                    'label' => __( 'Tel', 'gssi_location' )
                ),
                'email' => array(
                    'label' => __( 'Email', 'gssi_location' )
                ),
                'url' => array(
                    'label' => __( 'Url', 'gssi_location' )
                ),
                'facebook' => array(
                    'label' => __( 'Facebook', 'gssi_location' )
                ),
                'twitter' => array(
                    'label' => __( 'Twitter', 'gssi_location' )
                ),
                'instagram' => array(
                    'label' => __( 'Instagram', 'gssi_location' )
                )
            );
            $output = '<div class="gssi-location-meta">';
            foreach ( $metaFields as $key => $fieldData ){
                $output .= $this->textInput(array('key' => $key, 'data' => $fieldData));
            }
            $output .= '</div>';
            echo $output;
        }
        
        public function createOpenHourMetaboxes() {
            $output = '<div class="gssi-location-meta">';
            $output .= $this->openingHours();
            $output .= '</div>';
            echo $output;
        }

        /**
         * 
         * @param [] $args
         * @param boolean $single
         * @return string
         */
        public function setRequiredClass( $args, $single = false ) {

            if ( isset( $args['required'] ) && ( $args['required'] ) ) {
                if ( !$single ) {
                    $response = 'class="gssi-required"';
                } else {
                    $response = 'gssi-required';
                }

                return $response;
            }
        }

        /**
         * 
         * @param [] $args
         * @return string
         */
        public function isRequiredField( $args ) {

            if ( isset( $args['required'] ) && ( $args['required'] ) ) {
                $response = '<span class="gssi-star"> *</span>';

                return $response;
            }
        }

        /**
         * 
         * @global type $gssi_location_settings
         * @global type $pagenow
         * @param string $field_name
         * @return string
         */
        public function getPrefilledFieldData( $field_name ) {

            global $gssi_location_settings, $pagenow;

            $field_data = '';

            // Prefilled values are only used for new pages, not when a user edits an existing page.
            if ( $pagenow == 'post.php' && isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) {
                return;
            }

            $prefilled_fields = array(
                'country',
                'hours'
            );

            if ( in_array( $field_name, $prefilled_fields ) ) {
                $field_data = $gssi_location_settings['editor_' . $field_name];
            }

            return $field_data;
        }
        
        /**
         * 
         * @param [] $args
         * @return string
         */
        public function textInput( $args ) {

            $saved_value = $this->getLocationMeta( $args['key'] );

            // If there is no existing meta value, check if a prefilled value exists for the input field.
            if ( !$saved_value ) {
                $saved_value = $this->getPrefilledFieldData( $args['key'] );
            }
            $output = '<p>'
                    . '<label for="gssi-' . esc_attr($args['key']) . '">' . esc_html($args['data']['label']) . ': ' . $this->isRequiredField($args['data']) . '</label>'
                    . '<input id="gssi-' . esc_attr($args['key']) . '" ' . $this->setRequiredClass($args['data']) . ' type="text" name="gssi_location[' . esc_attr($args['key']) . ']" value="' . esc_attr($saved_value) . '" />'
                    . '</p>';
            return $output;
        }
        
        /**
         * @global type $post
         * @param type $location
         * @return type
         */
        public function openingHours( $location = 'location_page' ) {
            global  $post;

            $opening_days  = array( 
                'monday'    => __( 'Monday', 'gssi_location' ), 
                'tuesday'   => __( 'Tuesday', 'gssi_location' ),  
                'wednesday' => __( 'Wednesday', 'gssi_location' ),  
                'thursday'  => __( 'Thursday', 'gssi_location' ),  
                'friday'    => __( 'Friday', 'gssi_location' ),  
                'saturday'  => __( 'Saturday', 'gssi_location' ),
                'sunday'    => __( 'Sunday' , 'gssi_location' )
            );
            $openingHours = '';
            $hours         = '';

            if ( $location == 'location_page' ) {
                $openingHours = get_post_meta( $post->ID, 'gssi_hours' );
            }

            // If we don't have any opening hours, we use the defaults.
            if ( !isset( $openingHours[0]['monday'] ) ) {
                $openingHours = array(
                    'monday'    => array( '9:00 AM,5:00 PM' ),
                    'tuesday'   => array( '9:00 AM,5:00 PM' ),
                    'wednesday' => array( '9:00 AM,5:00 PM' ),
                    'thursday'  => array( '9:00 AM,5:00 PM' ),
                    'friday'    => array( '9:00 AM,5:00 PM' ),
                    'saturday'  => '',
                    'sunday'    => ''
                 );
            } else {
                $openingHours = $openingHours[0];
            }

            $hour_class = 'gssi-twentyfour-format';
            
            $output = '';
            ob_start();
            ?>

                <table id="gssi-location-hours" class="<?php echo $hour_class; ?>">
                    <tr>
                        <th><?php _e( 'Days', 'gssi_location' ); ?></th>
                        <th><?php _e( 'Opening Hours', 'gssi_location' ); ?></th>
                        <th></th>
                    </tr>
                    <?php
                    foreach ( $opening_days as $index => $day ) {
                        $i          = 0;
                        $hour_count = count( $openingHours[$index] );
                        ?>
                        <tr>
                            <td class="gssi-opening-day"><?php echo esc_html( $day ); ?></td>
                            <td id="gssi-hours-<?php echo esc_attr( $index ); ?>" class="gssi-opening-hours" data-day="<?php echo esc_attr( $index ); ?>">
                                <?php
                                if ( $hour_count > 0 ) {
                                    // Loop over the opening periods.
                                    while ( $i < $hour_count ) {
                                        if ( isset( $openingHours[$index][$i] ) ) {
                                            $hours = explode( ',', $openingHours[$index][$i] );
                                        } else {
                                            $hours = '';
                                        }

                                        // If we don't have two parts or one of them is empty, then we set the location to closed.
                                        if ( ( count( $hours ) == 2 ) && ( !empty( $hours[0] ) ) && ( !empty( $hours[1] ) ) ) {
                                            $args = array(
                                                'day'         => $index,
                                                'name'        => 'gssi_location[hours]',
                                                'hour_format' => 24,
                                                'hours'       => $hours
                                            );
                                            ?>
                                            <div class="gssi-current-period <?php if ( $i > 0 ) { echo 'gssi-multiple-periods'; } ?>">
                                                <?php echo $this->openingHoursDropdown( $args, 'open' ); ?>
                                                <span> - </span>
                                                <?php echo $this->openingHoursDropdown( $args, 'close' ); ?>
                                                <div class="gssi-icon-cancel-circled"></div>
                                            </div>
                                            <?php
                                        } else {
                                            $this->showLocationClosed( 'gssi_location[hours]', $index );
                                        }

                                        $i++;
                                    }
                                } else {
                                    $this->showLocationClosed( 'gssi_location[hours]', $index );
                                }
                                ?>
                            </td>
                            <td>
                                <div class="gssi-add-period">
                                    <div class="gssi-icon-plus-circled"></div>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
            <?php
            
            $output .= ob_get_clean();//$output = '';
            return $output;
        }
        
        public function showLocationClosed( $name, $day ) {
            echo '<p class="gssi-location-closed">' . __( 'Closed', 'gssi_location' ) . '<input type="hidden" name="' . esc_attr( $name ) . '[' . esc_attr( $day ) . ']" value="closed"></p>';
        }
        
        public function openingHoursDropdown( $args, $period ) {

            $select_index  = ( $period == 'open' ) ? 0 : 1;
            $selected_time = $args['hours'][$select_index];
            $select_name   = $args['name'] . '[' . strtolower( $args['day'] ) . '_' . $period . ']';
            $open          = strtotime( '12:00am' );
            $close         = strtotime( '11:59pm' );
            $hour_interval = 900;

            if ( $args['hour_format'] == 12 ) {
                $format = 'g:i A';
            } else {
                $format = 'H:i';
            }

            $select = '<select class="gssi-' . esc_attr( $period ) . '-hour" name="' . esc_attr( $select_name ) . '[]" autocomplete="off">';

            for ( $i = $open; $i <= $close; $i += $hour_interval ) {

                // If the selected time matches the current time then we set it to active.
                if ( $selected_time == date( $format, $i ) ) {
                    $selected = 'selected="selected"';
                } else {
                    $selected = '';
                }

                $select .= "<option value='" . date( $format, $i ) . "' $selected>" . date( $format, $i ) . "</option>";
            }

            $select .= '</select>';

            return $select;
        }

        
        public function getLocationMeta( $key ) {

            global $post;

            $location_meta = get_post_meta( $post->ID, 'gssi_' . $key, true );

            if ( $location_meta ) {
                return $location_meta;
            } else {
                return;
            }
        }
        
        public function savePost( $post_id ) {

            if ( empty( $_POST['gssi_location_meta_nonce'] ) || !wp_verify_nonce( $_POST['gssi_location_meta_nonce'], 'save_location_meta' ) )
                return;

            if ( !isset( $_POST['post_type'] ) || 'gssi_locations' !== $_POST['post_type'] )
                return;

            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
                return;

            if ( is_int( wp_is_post_revision( $post_id ) ) )
                return;

            if ( !current_user_can( 'edit_post', $post_id ) )
                return;

            $this->location_data = $_POST['gssi_location'];

            // Check if the hours are set through dropdowns.
            if ( isset( $this->location_data['hours'] ) && is_array( $this->location_data['hours'] ) && ( !empty( $this->location_data['hours'] ) ) ) {
                $this->location_data['hours'] = $this->formatOpeningHours();
            }

            foreach ( $this->location_data as $field_key => $field_data ) {                
                if ( is_array( $field_data ) ) {
                    if ( $this->isMultiArray( $field_data ) ) {
                        array_walk_recursive( $field_data, array($this, 'sanitizeMultiArray') );
                        update_post_meta( $post_id, 'gssi_' . $field_key, $field_data );
                    } else {
                        update_post_meta( $post_id, 'gssi_' . $field_key, array_map( 'sanitize_text_field', $field_data ) );
                    }
                } else {
                    update_post_meta( $post_id, 'gssi_' . $field_key, sanitize_text_field( $field_data ) );
                }
            }

            do_action( 'gssi_location_save_post', $this->location_data );
        }
        
        /**
         * 
         * @param [] $array
         * @return boolean
         */
        protected function isMultiArray( $array ) {
            foreach ( $array as $value ) {
                if ( is_array( $value ) ) return true;
            }

            return false;
        }
        
        /**
         * 
         * @param type $item
         * @param type $key
         */
        protected function sanitizeMultiArray( &$item, $key ) {
            $item = sanitize_text_field( $item );
        }

        
        public function formatOpeningHours() {

            $week_days = array( 
                'monday'    => __( 'Monday', 'gssi_location' ), 
                'tuesday'   => __( 'Tuesday', 'gssi_location' ),  
                'wednesday' => __( 'Wednesday', 'gssi_location' ),  
                'thursday'  => __( 'Thursday', 'gssi_location' ),  
                'friday'    => __( 'Friday', 'gssi_location' ),  
                'saturday'  => __( 'Saturday', 'gssi_location' ),
                'sunday'    => __( 'Sunday' , 'gssi_location' )
            );

            $locationHours = $this->location_data['hours'];

            foreach ( $week_days as $day => $value ) {
                $periods = array();

                if ( isset( $locationHours[$day . '_open'] ) && $locationHours[$day . '_open'] ) {
                    foreach ( $locationHours[$day . '_open'] as $opening_hour ) {
                        $hours     = $this->validateHour( $locationHours[$day.'_open'][0] ) . ',' . $this->validateHour( $locationHours[$day.'_close'][0] );
                        $periods[] = $hours;
                    }
                }

                $openingHours[$day] = $periods;
            }

            return $openingHours;
        }
        
        /**
         * @param type $hour
         * @return type
         */
        public function validateHour( $hour ) {
            $format = 'H:i';

            if ( date( $format, strtotime( $hour ) ) == $hour ) {
                return $hour;
            }
        }
        
        public function setPostPending( $post_id ) {

            global $wpdb;

            $wpdb->update( $wpdb->posts, array( 'post_status' => 'pending' ), array( 'ID' => $post_id ) );

            add_filter( 'redirect_post_location', array( $this, 'removeMessageArg' ) );
        }
        
        public function removeMessageArg( $location ) {
            return remove_query_arg( 'message', $location );
        }
        
        public function checkMissingMetaData( $post_id ) {

            foreach ( $this->meta_box_fields() as $tab => $meta_fields ) {
                foreach ( $meta_fields as $field_key => $field_data ) {

                    if ( isset( $field_data['required'] ) && $field_data['required'] ) {
                        $post_meta = get_post_meta( $post_id, 'gssi_' . $field_key, true );

                        if ( empty( $post_meta ) ) {
                            return true;
                        }
                    }
                }
            }
        }
        
        public function mapPreview() {
            ?>
            <div id="gssi-gmap-wrap"></div>
            <p class="gssi-submit-wrap">
                <a id="gssi-lookup-location" class="button-primary" href="#gssi-meta-nav"><?php _e( 'Preview', 'gssi_location' ); ?></a>
            </p>
            <?php
        }
        
        function locationUpdateMessage( $messages ) {

            $post             = get_post();
            $post_type        = get_post_type( $post );
            $post_type_object = get_post_type_object( $post_type );

            $messages['gssi_locations'] = array(
                0  => '', // Unused. Messages start at index 1.
                1  => __( 'Location updated.', 'gssi_location' ),
                2  => __( 'Custom field updated.', 'gssi_location' ),
                3  => __( 'Custom field deleted.', 'gssi_location' ),
                4  => __( 'Location updated.', 'gssi_location' ),
                5  => isset( $_GET['revision'] ) ? sprintf( __( 'Location restored to revision from %s', 'gssi_location' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
                6  => __( 'Location published.', 'gssi_location' ),
                7  => __( 'Location saved.', 'gssi_location' ),
                8  => __( 'Location submitted.', 'gssi_location' ),
                9  => sprintf(
                    __( 'Location scheduled for: <strong>%1$s</strong>.', 'gssi_location' ),
                    date_i18n( __( 'M j, Y @ G:i', 'gssi_location' ), strtotime( $post->post_date ) )
                ),
                10 => __( 'Location draft updated.', 'gssi_location' )
            );

            if ( ( 'gssi_locations' == $post_type ) && ( $post_type_object->publicly_queryable ) ) {
                $permalink = get_permalink( $post->ID );

                $view_link = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), __( 'View location', 'gssi_location' ) );
                $messages[ $post_type ][1] .= $view_link;
                $messages[ $post_type ][6] .= $view_link;
                $messages[ $post_type ][9] .= $view_link;

                $preview_permalink = add_query_arg( 'preview', 'true', $permalink );
                $preview_link = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), __( 'Preview location', 'gssi_location' ) );
                $messages[ $post_type ][8]  .= $preview_link;
                $messages[ $post_type ][10] .= $preview_link;
            }

            return $messages;
        }

    }
}