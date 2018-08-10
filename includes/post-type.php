<?php

if (!defined('ABSPATH'))
    exit;

if (!class_exists('GssiLocationPostType')) {

    class GssiLocationPostType {

        /**
         * Constructor
         */
        public function __construct() {
            add_action('init', array($this, 'registerPostTypes'), 10, 1);
            add_action('init', array($this, 'registerTaxonomies'), 10, 1);
            add_action('manage_gssi_locations_posts_custom_column', array($this, 'customColumns'), 10, 2);

            add_filter('enter_title_here', array($this, 'changeDefaultTitle'));
            add_filter('manage_edit-gssi_locations_columns', array($this, 'editColumns'));
            add_filter('manage_edit-gssi_locations_sortableColumns', array($this, 'sortableColumns'));
            add_filter('request', array($this, 'sortColumns'));
        }

        /**
         * Register the location post type.
         * 
         * @return void
         */
        public function registerPostTypes() {

            // The labels for the gssi_locations post type.
            $labels = apply_filters('gssi_post_type_labels', array(
                'name' => __('Locations', 'gssi_location'),
                'all_items' => __('All Locations', 'gssi_location'),
                'singular_name' => __('Location', 'gssi_location'),
                'add_new' => __('New Location', 'gssi_location'),
                'add_new_item' => __('Add New Location', 'gssi_location'),
                'edit_item' => __('Edit Location', 'gssi_location'),
                'new_item' => __('New Location', 'gssi_location'),
                'view_item' => __('View Locations', 'gssi_location'),
                'search_items' => __('Search Locations', 'gssi_location'),
                'not_found' => __('No Locations found', 'gssi_location'),
                'not_found_in_trash' => __('No Locations found in trash', 'gssi_location'),
                    )
            );

            // The arguments for the gssi_locations post type.
            $args = apply_filters('gssi_post_type_args', array(
                'labels' => $labels,
                'public' => true,
                'exclude_from_search' => true,
                'show_ui' => true,
                'menu_position' => apply_filters('gssi_post_type_menu_position', null),
                'rewrite' => array('slug' => __('locations')),
                'query_var' => 'gssi_locations',
                'supports' => array('title', 'editor', 'author', 'excerpt', 'revisions', 'thumbnail')
                    )
            );

            register_post_type('gssi_locations', $args);
        }

        /**
         * Register the GSSI custom taxonomy.
         * 
         * @return void
         */
        public function registerTaxonomies() {
            $labels = array(
                'name' => __('Location Categories', 'gssi_location'),
                'singular_name' => __('Location Category', 'gssi_location'),
                'search_items' => __('Search Location Categories', 'gssi_location'),
                'all_items' => __('All Location Categories', 'gssi_location'),
                'parent_item' => __('Parent Location Category', 'gssi_location'),
                'parent_item_colon' => __('Parent Location Category:', 'gssi_location'),
                'edit_item' => __('Edit Location Category', 'gssi_location'),
                'update_item' => __('Update Location Category', 'gssi_location'),
                'add_new_item' => __('Add New Location Category', 'gssi_location'),
                'new_item_name' => __('New Location Category Name', 'gssi_location'),
                'menu_name' => __('Location Categories', 'gssi_location'),
            );

            $args = apply_filters('gssi_location_category_args', array(
                'labels' => $labels,
                'public' => true,
                'hierarchical' => true,
                'show_ui' => true,
                'show_admin_column' => true,
                'update_count_callback' => '_update_post_term_count',
                'query_var' => true,
                'rewrite' => array('slug' => __('location-category', 'gssi_location'))
                    )
            );

            register_taxonomy('gssi_location_category', 'gssi_locations', $args);
        }

        
        public function changeDefaultTitle($title) {

            $screen = get_current_screen();

            if ($screen->post_type == 'gssi_locations') {
                $title = __('Enter store title here', 'gssi_location');
            }

            return $title;
        }

        
        public function editColumns($columns) {

            $columns['address'] = __('Address', 'gssi_location');
            $columns['city'] = __('City', 'gssi_location');
            $columns['state'] = __('State', 'gssi_location');
            $columns['zip'] = __('Zip', 'gssi_location');

            return $columns;
        }
        
        public function customColumns($column, $post_id) {

            switch ($column) {
                case 'address':
                    echo esc_html(get_post_meta($post_id, 'gssi_address', true));
                    break;
                case 'city':
                    echo esc_html(get_post_meta($post_id, 'gssi_city', true));
                    break;
                case 'state':
                    echo esc_html(get_post_meta($post_id, 'gssi_state', true));
                    break;
                case 'zip':
                    echo esc_html(get_post_meta($post_id, 'gssi_zip', true));
                    break;
            }
        }

        /**
         * Define the columns that are sortable.
         *
         * @param  array $columns List of sortable columns
         * @return array
         */
        public function sortableColumns($columns) {

            $custom = array(
                'address' => 'gssi_address',
                'city' => 'gssi_city',
                'state' => 'gssi_state',
                'zip' => 'gssi_zip'
            );

            return wp_parse_args($custom, $columns);
        }

        /**
         * Set the correct column sort parameters.
         *
         * @param  array $vars Column sorting parameters
         * @return array $vars The column sorting parameters inc the correct orderby and gssi meta_key
         */
        public function sortColumns($vars) {

            if (isset($vars['post_type']) && $vars['post_type'] == 'gssi_locations') {
                if (isset($vars['orderby'])) {
                    if ($vars['orderby'] === 'gssi_address') {
                        $vars = array_merge($vars, array(
                            'meta_key' => 'gssi_address',
                            'orderby' => 'meta_value'
                                ));
                    }

                    if ($vars['orderby'] === 'gssi_city') {
                        $vars = array_merge($vars, array(
                            'meta_key' => 'gssi_city',
                            'orderby' => 'meta_value'
                                ));
                    }

                    if ($vars['orderby'] === 'gssi_state') {
                        $vars = array_merge($vars, array(
                            'meta_key' => 'gssi_state',
                            'orderby' => 'meta_value'
                                ));
                    }

                    if ($vars['orderby'] === 'gssi_zip') {
                        $vars = array_merge($vars, array(
                            'meta_key' => 'gssi_zip',
                            'orderby' => 'meta_value'
                                ));
                    }
                }
            }

            return $vars;
        }

    }

}