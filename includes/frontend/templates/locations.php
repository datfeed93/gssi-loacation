<?php
global $gssi_location_settings, $post;

$output = $this->getCustomCss();
$autoload_class = (!$gssi_location_settings['autoload'] ) ? 'class="gssi-not-loaded"' : '';

$output = '<div id="gssi-wrap">';
ob_start();
?>

<?php
$page_id = null;

if ($post->post_parent):
    $page_id = get_top_ancestor($post->ID);
else:
    $page_id = $post->ID;
endif;

$default_address = '';
if($gssi_location_settings['start_address']){
    $default_address = $gssi_location_settings['start_address'];
}

$taxonomy_objects = get_categories('taxonomy=gssi_location_category&type=gssi_locations&order=DESC');
?>
<div class="wrap-map-content">
    <div class="gssi-search gssi-clearfix <?php echo $this->getCssClasses() ?>">
        <div class="section-inner">
            <!--<div class="closest-location <?php /* has-position */ ?>">
            </div>-->
            <div id="gssi-search-wrap" class="gssi-search-wrap">
                <form autocomplete="off" data-address="<?php echo $default_address; ?>" id="custom_form">
                    <div class="gssi-input">
                        <div class="gssi-location-search-wrap">
                            <span class="input-text-span"><input id="gssi-search-input" type="text" value="<?php echo apply_filters('gssi_search_input', '') ?>" name="gssi-search-input" placeholder=""/><span><?php echo esc_html(__('Enter a postal code, city or address', 'gssi_location')) ?></span></span>
                            <?php
                                if ( $taxonomy_objects && ! is_wp_error( $taxonomy_objects ) ) : 
                                    echo '<span id="gssi-checkbox-filter">';
                                    foreach ( $taxonomy_objects as $term ): 
                                        $term_name = $term->name;                                        
                                        $term_slug = $term->slug;
                                        $term_id = $term->term_id;
                                        $id_input = 'gssi-search-checkbox-'.$term_slug;
                                        $locator_icon = get_term_meta( $term->term_id, 'locator_miniicon', true );
                                        $locator_color = get_term_meta( $term->term_id, 'locator_color', true );
                                        if($locator_icon){
                                            $style='background:url('.wp_get_attachment_url( $locator_icon ).') no-repeat scroll 21px 0;min-height:41px;background-size:41px 41px;';
                                            $label_style = 'padding-left:46px;';
                                        }else{
                                            $style='';
                                            $label_style='';
                                        }
                                        if($locator_color){
                                            $label_style .='color:#'.$locator_color.';';
                                        }else{
                                            $label_style .='';
                                        }
                                        
                                    ?>
                                        <span class="<?php echo $id_input; ?>" style="<?php echo $style; ?>">
                                            <input id="<?php echo $id_input; ?>" type="checkbox" value="<?php echo $term_id; ?>" name="<?php echo $id_input; ?>" placeholder="" />
                                            <label for="<?php echo $id_input; ?>" style="<?php echo $label_style; ?>">
                                                <?php echo $term_name;  ?>
                                            </label>
                                        </span>
                                <?php
                                    endforeach;
                                    echo '</div>';
                                endif;
                            ?>
                            <input id="gssi-search-btn" type="submit" class="btn ghost" value="<?php echo esc_attr(__('Submit', 'gssi_location')) ?>">
                        </div>
                    </div>   
                </form>
            </div>
        </div>
    </div>
    
    <div id="gssi-location-gmap" class="gssi-location-gmap-canvas"></div>
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="entry-content">
            <?php if ($post->post_parent): ?>
                <h1><?php the_title(); ?></h1>
            <?php endif; ?>

            <div id="gssi-result-list">
                <div id="gssi-locations" <?php echo $autoload_class ?>>
                    <ul></ul>
                </div>
                <div id="gssi-direction-details">
                    <ul></ul>
                </div>
            </div>
        </div><!-- .entry-content -->

    </article><!-- #post-## -->
</div>
<?php
$output .= ob_get_clean();
$output .= '</div>';
return $output;
