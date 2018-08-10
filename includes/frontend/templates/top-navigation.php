<?php

$locationId = isset($_COOKIE['my_ricky']) ? $_COOKIE['my_ricky'] : false;

$myRicky =  $this->findById($locationId);
$output = '<div class="header-location">';
ob_start();
?>

<div class="my-location-top store-location <?php echo $myRicky ? 'has-ricky' : '' ?> <?php echo $myRicky ? 'location-selected' : '' ?>">
    <span><?php echo !$myRicky ? __('Your Closest Store:', 'gssi_location')
            : __('My store:', 'gssi_location') ?></span>
    <ul>
        <li>
            <a href="<?php echo $myRicky ? $myRicky['detail_url'] : '#' ?>" class="header-location-link">
                <?php echo $myRicky ? $myRicky['location'] : __('', 'gssi_location') ?>
            </a>
        </li>
        <li class="header-location-button" style="display: none;">
            <a href="javascript:void(0);"><span class="desktop"><?php echo __('Make this my store', 'gssi_location') ?></span></a>
        </li>
        <li class="change-location" style="<?php echo !$myRicky ? 'display:none;' : '' ?>">
            <a href="<?php echo gssi_get_location_page() ?>" class="header-location-button" style="<?php echo !$myRicky ? 'display:none;' : '' ?>">
                <?php echo __('Change Location', 'gssi_location') ?>
            </a>
        </li>
    </ul>
</div>

<div class="top-choose-locations store-location" style="display: none;">
    <span><?php echo __('Select Your Location', 'gssi_location') ?>: </span>
    <ul>
        <li>
            <a href="<?php echo gssi_get_location_page() ?>" class="change-location"><?php echo __('Choose a Location', 'gssi_location') ?></a>
        </li>
    </ul>
</div>
<?php
$output .= ob_get_clean();
$output .= '</div>';
return $output;
