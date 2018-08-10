<?php

if ( !defined( 'ABSPATH' ) ) exit;

add_action( 'gssi_location_category_add_form_fields', 'gssi_add_term_meta_field', 10, 2 );
function gssi_add_term_meta_field($taxonomy) {
    ?>
    <div class="form-field term-locator_marker">
        <label for="locator_marker"><?php _e('Locator Marker', 'gssi_location'); ?></label>
        <div id="locator_marker_preview" style="width: 200px;">
            
        </div>
        <input type="hidden" id="locator_marker" name="locator_marker" value=""/>
		<input class="upload_image_button button" name="_add_locator_marker" id="_add_locator_marker" type="button" value="<?php _e('Select/Upload Image', 'gssi_location'); ?>" />
		<script>
			jQuery(document).ready(function($) {
				$('#_add_locator_marker').click(function() {
					wp.media.editor.send.attachment = function(props, attachment) {
						$('#locator_marker').val(attachment.id);
                        $('#locator_marker_preview').html('');
						$('<img style="max-width: 100%; height: auto;"/>').attr('src', attachment.url).appendTo($('#locator_marker_preview'));
					}
					wp.media.editor.open(this);
					return false;
				});
			});
        </script>
    </div>
    <div class="form-field term-locator_miniicon">
        <label for="locator_miniicon"><?php _e('Locator Icon', 'gssi_location'); ?></label>
        <div id="locator_miniicon_preview" style="width: 200px;">
            
        </div>
        <input type="hidden" id="locator_miniicon" name="locator_miniicon" value=""/>
		<input class="upload_image_button button" name="_add_locator_miniicon" id="_add_locator_miniicon" type="button" value="<?php _e('Select/Upload Image', 'gssi_location'); ?>" />
		<script>
			jQuery(document).ready(function($) {
				$('#_add_locator_miniicon').click(function() {
					wp.media.editor.send.attachment = function(props, attachment) {
						$('#locator_miniicon').val(attachment.id);
                        $('#locator_miniicon_preview').html('');
						$('<img style="max-width: 100%; height: auto;"/>').attr('src', attachment.url).appendTo($('#locator_miniicon_preview'));
					}
					wp.media.editor.open(this);
					return false;
				});
			});
        </script>
    </div>
    <div class="form-field term-locator_color">
        <label for="locator_color"><?php _e('Locator Color', 'gssi_location'); ?></label>
        <input type="text" id="locator_color" name="locator_color" value=""/>
    </div>
    <?php
}

add_action( 'gssi_location_category_edit_form_fields', 'gssi_edit_term_meta_field', 10, 2 );
function gssi_edit_term_meta_field( $term, $taxonomy ){
    $locator_marker = get_term_meta( $term->term_id, 'locator_marker', true );
    $locator_miniicon = get_term_meta( $term->term_id, 'locator_miniicon', true );
    $locator_color = get_term_meta( $term->term_id, 'locator_color', true );
    
    ?>
    <tr class="form-field term-locator_marker">
        <th scope="row"><label for="locator_marker"><?php _e('Locator Marker', 'gssi_location'); ?></label></th>
        <td>
            <div id="locator_marker_preview" style="width: 200px;">
                <?php if (!empty($locator_marker)): ?>
                <img src="<?php echo wp_get_attachment_url( $locator_marker ); ?>" style="max-width: 100%; height: auto;" />
                <?php endif; ?>
            </div>
            <input type="hidden" id="locator_marker" name="locator_marker" value="<?php echo $locator_marker; ?>"/>
            <input class="upload_image_button button" name="_add_locator_marker" id="_add_locator_marker" type="button" value="<?php _e('Select/Upload Image', 'gssi_location'); ?>" />
            <script>
                jQuery(document).ready(function($) {
                    $('#_add_locator_marker').click(function() {
                        wp.media.editor.send.attachment = function(props, attachment) {
                            $('#locator_marker').val(attachment.id);
                            $('#locator_marker_preview').html('');
                            $('<img style="max-width: 100%; height: auto;"/>').attr('src', attachment.url).appendTo($('#locator_marker_preview'));
                        }
                        wp.media.editor.open(this);
                        return false;
                    });
                });
            </script>
        </td>
    </tr>
    <tr class="form-field term-locator_miniicon">
        <th scope="row"><label for="locator_miniicon"><?php _e('Locator Icon', 'gssi_location'); ?></label></th>
        <td>
            <div id="locator_miniicon_preview" style="width: 200px;">
                <?php if (!empty($locator_miniicon)): ?>
                <img src="<?php echo wp_get_attachment_url( $locator_miniicon ); ?>" style="max-width: 100%; height: auto;" />
                <?php endif; ?>
            </div>
            <input type="hidden" id="locator_miniicon" name="locator_miniicon" value="<?php echo $locator_miniicon; ?>"/>
            <input class="upload_image_button button" name="_add_locator_miniicon" id="_add_locator_miniicon" type="button" value="<?php _e('Select/Upload Image', 'gssi_location'); ?>" />
            <script>
                jQuery(document).ready(function($) {
                    $('#_add_locator_miniicon').click(function() {
                        wp.media.editor.send.attachment = function(props, attachment) {
                            $('#locator_miniicon').val(attachment.id);
                            $('#locator_miniicon_preview').html('');
                            $('<img style="max-width: 100%; height: auto;"/>').attr('src', attachment.url).appendTo($('#locator_miniicon_preview'));
                        }
                        wp.media.editor.open(this);
                        return false;
                    });
                });
            </script>
        </td>
    </tr>
    <tr class="form-field term-locator_color">
        <th scope="row"><label for="locator_color"><?php _e('Locator Color', 'gssi_location'); ?></label></th>
        <td><input type="text" id="locator_color" name="locator_color" value="<?php echo $locator_color; ?>"/></td>
    </tr>
    <?php
}

add_action( 'created_gssi_location_category', 'gssi_save_term_meta_field', 10, 2 );
function gssi_save_term_meta_field( $term_id ){
    if( isset( $_POST['locator_marker'] ) && '' !== $_POST['locator_marker'] ){
        add_term_meta( $term_id, 'locator_marker', $_POST['locator_marker'], true );
    }
    if( isset( $_POST['locator_miniicon'] ) && '' !== $_POST['locator_miniicon'] ){
        add_term_meta( $term_id, 'locator_miniicon', $_POST['locator_miniicon'], true );
    }
    if( isset( $_POST['locator_color'] ) && '' !== $_POST['locator_color'] ){
        add_term_meta( $term_id, 'locator_color', $_POST['locator_color'], true );
    }
}

add_action( 'edited_gssi_location_category', 'gssi_update_term_meta_field', 10, 2 ); 
function gssi_update_term_meta_field( $term_id ){
    if( isset( $_POST['locator_marker'] ) && '' !== $_POST['locator_marker'] ){
        update_term_meta( $term_id, 'locator_marker', $_POST['locator_marker'] );
    }
    if( isset( $_POST['locator_miniicon'] ) && '' !== $_POST['locator_miniicon'] ){
        update_term_meta( $term_id, 'locator_miniicon', $_POST['locator_miniicon'] );
    }
    if( isset( $_POST['locator_color'] ) && '' !== $_POST['locator_color'] ){
        update_term_meta( $term_id, 'locator_color', $_POST['locator_color'] );
    }
}