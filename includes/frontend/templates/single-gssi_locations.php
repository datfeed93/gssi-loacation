<?php
get_header();

global $gssi_location_settings, $post, $location_data;
?>
<?php
   
    $categories = wp_get_post_terms($post->ID,"gssi_location_category");
    if (count($categories) > 0) {
        $locator_icon = get_term_meta( $categories[0]->term_id, 'locator_miniicon', true );
        $locator_color = get_term_meta( $categories[0]->term_id, 'locator_color', true );
        if (!empty($locator_color)) {
            echo '<style>h1.entry-title {color: #'.$locator_color.';}</style>';
        }
    }
    
?>
<article id="container" class="location-detail-article">
    <div>
        <?php if (have_posts()): ?> 
            <?php while (have_posts()) : the_post(); ?>
                <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <div class="locations-detail-section">
                        <div class="section-inner">
                            <div class="locations-detail-content">
                                <div class="col summary">
                                    <div class="location-info">
                                        <div class="location-address">
                                            <?php echo do_shortcode('[gssi_address]'); ?>
                                        </div>
                                        <div class="location-additional-info">
                                            <div class="gssi_hours">
                                                <div class="hours-label"><?php echo '<i class="far fa-clock"></i>'.__('Hours: ', 'gssi_location') ?></div>
                                                <?php echo do_shortcode('[gssi_hours]'); ?>
                                            </div>
                                            <!-- <div class="lai-content">
                                                <a href="" class="get_direction_link"><?php echo '<i class="fas fa-map-marker"></i>'.__('Get Directions', 'gssi_location'); ?></a>
                                                <div class="socials">
                                                    <?php if (isset($location_data[0]['facebook']) && !empty($location_data[0]['facebook'])): ?>
                                                    <a href="<?php echo $location_data[0]['facebook'] ?>" class="facebook" title="facebook"><i class="fab fa-facebook-square"></i></a>
                                                    <?php endif; ?>
                                                    <?php if (isset($location_data[0]['instagram']) && !empty($location_data[0]['instagram'])): ?>
                                                    <a href="<?php echo $location_data[0]['instagram'] ?>" class="instagram" title="instagram"><i class="fab fa-instagram"></i></a>
                                                    <?php endif; ?>
                                                    <?php if (isset($location_data[0]['twitter']) && !empty($location_data[0]['twitter'])): ?>
                                                    <a href="<?php echo $location_data[0]['twitter'] ?>" class="twitter" title="twitter"><i class="fab fa-twitter-square"></i></a>
                                                    <?php endif; ?>
                                                </div>
                                            </div> -->
                                            <?php if (isset($location_data[0]['email']) && !empty($location_data[0]['email'])): ?>
                                                <a href="mailto:<?php echo $location_data[0]['email'] ?>" class="gssi_email" title="facebook"><?php echo $location_data[0]['email'] ?></a>
                                            <?php endif; ?>
                                            <?php if (isset($location_data[0]['url']) && !empty($location_data[0]['url'])): ?>
                                                <a href="<?php echo $location_data[0]['url'] ?>" class="email" title="facebook"><?php echo $location_data[0]['url'] ?></a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col map">
                                    <div class="actions-toolbar">
                                        <ul>                                          
                                            <li><a href="<?php echo gssi_get_location_page() ?>" class="change-location buttons">
                                                <?php echo __('Change location', 'gssi_location') ?>
                                            </a></li>
                                        </ul>
                                    </div>
                                    <?php echo do_shortcode('[gssi_map]'); ?>
                                    
                                    <div class="single-direction">
                                        
                                    </div>
                                </div>
                            </div>

                            <?php echo get_the_content(); ?>
                        </div>
                    </div>
                </div><!-- #post-## -->
            <?php endwhile; // end of the loop.  ?>
        <?php endif; ?>
    </div>
</article><!-- #container -->
<?php get_footer(); ?>