<?php
/*
Plugin Name: Hybrid Slideshow
Plugin URI: http://www.hybridvigordesign.com/uncategorized/hybrid-slideshow
Description:  A simple jquery powered slideshow with drag and drop image ordering.
Author: David LaTour
Version: 1.7.1
Author URI: http://www.hybridvigordesign.com

Copyright 2010 by David LaTour

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

// Hooks for initalization, ajax, script insertion, etc...
register_activation_hook(__FILE__, 'hybrid_slideshow_activate');
add_action('admin_menu', 'hybrid_slideshow_create_menu');
add_action('admin_head', 'hybrid_slideshow_action_header');
add_action('wp_ajax_hybrid_special_action', 'hybrid_slideshow_action_callback');
add_action('admin_print_scripts', 'hybrid_slideshow_admin_scripts');
add_action('get_header', 'hybrid_slideshow_front_scripts');
add_shortcode('hybrid_slideshow', 'hybrid_slideshow');
add_action('widgets_init', 'hybrid_slideshow_register_widgets');
add_action('wp_head', 'hybrid_slideshow_header_output');
add_action('init', 'update_database_structure');

// Define plugin url
define("HYBRID_SLIDESHOW_URL", WP_PLUGIN_URL . '/hybrid-slideshow/');

// Activation hook - set default values
function hybrid_slideshow_activate() {
	$settings['width'] = get_option('hybrid-slideshow-option-width');
	$settings['height'] = get_option('hybrid-slideshow-option-height');
	$settings['delay'] = get_option('hybrid-slideshow-option-delay');
	$settings['transition'] = get_option('hybrid-slideshow-option-transition');
	$settings['random'] = get_option('hybrid-slideshow-option-random');
	$settings['javascript'] = get_option('hybrid-slideshow-option-javascript');
	
	if(!isset($settings['width']) || $settings['width'] == "0") { update_option('hybrid-slideshow-option-width', '400'); }
	if(!isset($settings['height']) || $settings['height'] == "0") { update_option('hybrid-slideshow-option-height', '280'); }
	if(!isset($settings['delay']) || $settings['delay'] == "0") { update_option('hybrid-slideshow-option-delay', '3'); }
	if(!isset($settings['transition']) || $settings['transition'] == "0") { update_option('hybrid-slideshow-option-transition', '2'); }
}

// Check for older version database data structure of images and update appropriately if necessary 
function update_database_structure() {
	$current_images = get_option('hybrid-slideshow-option-images');
	if( $current_images ) {
		if ( !is_array( $current_images[0] ) ) {
			$new_order = array();
			foreach( $current_images as $image ) {
				$new_order[] = array(
					'image' => $image,
					'url' => ''
				);
			delete_option('hybrid-slideshow-option-images');
			update_option('hybrid-slideshow-option-images', $new_order);
			}
		} 
	}
}

// Create plugin menu pages
function hybrid_slideshow_create_menu() {
	add_menu_page('Hybrid Slideshow', 'Slideshow', 'administrator', __FILE__ , 'hybrid_slideshow_page');
	add_submenu_page(__FILE__, 'Images', 'Images', 'administrator', __FILE__.'_hybrid_slideshow_images' , 'hybrid_slideshow_images_page');
	add_action('admin_init', 'hybrid_slideshow_register_settings');
}

// Register plugin settings
function hybrid_slideshow_register_settings() {
	register_setting('hybrid-slideshow-settings-group', 'hybrid-slideshow-option-width', 'intval');
	register_setting('hybrid-slideshow-settings-group', 'hybrid-slideshow-option-height', 'intval');
	register_setting('hybrid-slideshow-settings-group', 'hybrid-slideshow-option-delay', 'intval');
	register_setting('hybrid-slideshow-settings-group', 'hybrid-slideshow-option-transition', 'intval');
	register_setting('hybrid-slideshow-settings-group', 'hybrid-slideshow-option-random');
	register_setting('hybrid-slideshow-settings-group', 'hybrid-slideshow-option-javascript');
}

// Load jquery for slideshow
function hybrid_slideshow_front_scripts() {
	if (!is_admin()) {
		wp_enqueue_script('jquery');
	}
}

// Load jquery scripts for admin drag and drop
function hybrid_slideshow_admin_scripts() {
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-sortable');
}

// Strips text from li id's leaving us with integer
function strip_text(&$value, $key) {
	$value = str_replace('listItem_', '', $value);
}

// Ajax processing function
function hybrid_slideshow_action_callback() {
	// Call function which will strip text from li id's and assign to array
	array_walk($_POST['order'], 'strip_text');
	$order_array = $_POST['order'];
	
	// Retrieve current images from options database table
	$current_images = get_option('hybrid-slideshow-option-images');	
	
	// Build new array with images in updated order
	$new_order = array();
	foreach($order_array as $order) {
		foreach($current_images as $key => $value) {
			if($order == $key) {
				$new_order[] = $value;
			}
		}
	}
	update_option('hybrid-slideshow-option-images', $new_order);
	
	// Reset list item id's
	?>
	<script type="text/javascript" >
		jQuery(document).ready(function($) {
			var i = 0
			jQuery('#sortable li').each(function(){
				var name = 'listItem_' + i;
				$(this).attr('id', name);
				$(this).children('form.url').children('input.add-url').val(i);
				$(this).children('form.delete').children('input.delete-img').val(i);
				i++;
			});
		});
	</script>	
	<?php
	die(true);
}

// css & js output for admin head
function hybrid_slideshow_action_header() {
	?>
	<link href="<?php echo HYBRID_SLIDESHOW_URL; ?>css/admin.css" rel="stylesheet" type="text/css" />

	<script type="text/javascript">
		jQuery(document).ready(function($) {
			jQuery('#sortable li:odd').addClass('stripe');
			jQuery( "#sortable" ).sortable({
				handle : '.handle', 
				update: function(event, ui) {
					jQuery('#sortable li').removeClass('stripe');
					jQuery('#sortable li:odd').addClass('stripe');
					var order = $('#sortable').sortable('toArray');
					var data = {
						action: 'hybrid_special_action',
						order: order
					};
					jQuery.post(ajaxurl, data, function(response){
						jQuery('#response').html('Got this from the server: ' + response);
					});
				}
			});
		});
	</script>
	<?php
}

// This is the settings page output
function hybrid_slideshow_page() { ?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32"></div>
		<h2>Hybrid Slideshow Settings</h2>
		
		<form action="options.php" method="post" id="hybrid-slideshow-settings">
			<?php settings_fields('hybrid-slideshow-settings-group'); ?>
			
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><label for="hybrid-slideshow-option-width">Width:</label></td>
						<td><input type="text" name="hybrid-slideshow-option-width" value="<?php echo get_option('hybrid-slideshow-option-width'); ?>" class="small-text" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="hybrid-slideshow-option-height">Height:</label></th>
						<td><input type="text" name="hybrid-slideshow-option-height" value="<?php echo get_option('hybrid-slideshow-option-height'); ?>" class="small-text" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="hybrid-slideshow-option-delay">Time between transition (seconds):</label></th>
						<td><input type="text" name="hybrid-slideshow-option-delay" value="<?php echo get_option('hybrid-slideshow-option-delay'); ?>" class="small-text" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="hybrid-slideshow-option-transition">Transition length (seconds):</label></th>
						<td><input type="text" name="hybrid-slideshow-option-transition" value="<?php echo get_option('hybrid-slideshow-option-transition'); ?>" class="small-text" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="hybrid-slideshow-option-random">Randomize image order:</label></th>
						<td><input type="checkbox" name="hybrid-slideshow-option-random" value="true" <?php if( get_option('hybrid-slideshow-option-random') == 'true' ) echo 'checked="checked" '; ?>/></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="hybrid-slideshow-option-javascript">Use your own javascript:</label></th>
						<td><input type="checkbox" name="hybrid-slideshow-option-javascript" value="true" <?php if( get_option('hybrid-slideshow-option-javascript') == 'true' ) echo 'checked="checked" '; ?>/></td>
					</tr>
				</tbody>
			</table>
			
			<p class="submit"><input type="submit" class="button-primary" value="Update" /></p>
				
		</form>
	</div>
<?php }

// This is the images page output
function hybrid_slideshow_images_page() {
	
	// If form has been submitted then deal with uploading the image
	if(isset($_POST['submitted'])) {
		if(isset($_POST['action']) && $_POST['action'] == 'wp_handle_upload') {
			check_admin_referer('hybrid_upload_nonce');
			// Image upload form handler
			$upload = wp_handle_upload($_FILES['uploaded_file'], 0);
			extract($upload);

			if(isset($error)) {
				echo $error; 
			} else { 
				// If no errors then validate, resize, and create thumbnail image
				$error = hybrid_slideshow_process_image($file, $type);
				if($error) {
					echo $error;
					unlink($file);
				} 
			}
		} elseif(isset($_POST['delete'])) { // Image delete handler
			check_admin_referer('hybrid_delete_nonce');
			$image_id = $_POST['delete'];
			$current_images = get_option('hybrid-slideshow-option-images');
			if($current_images) {
				unset($current_images[$image_id]);
				$current_images = array_values($current_images);
				update_option('hybrid-slideshow-option-images', $current_images);
			}
			unset($_POST['delete']);
		} elseif(isset($_POST['add_url'])) { // Save image url
			check_admin_referer('hybrid_url_nonce');
			$image_id = $_POST['add_url'];
			$current_images = get_option('hybrid-slideshow-option-images');
			if($current_images) {
				$current_images[$image_id]['url'] = esc_attr($_POST['url']);
				update_option('hybrid-slideshow-option-images', $current_images);
			}
			unset($_POST['add_url']);
		}
	}
	?>
	
	<div class="wrap">
		<?php $current_images = get_option('hybrid-slideshow-option-images'); ?>
		<div id="icon-upload" class="icon32"></div>
		<h2>Images</h2>
		
		<h3>Add an Image</h3>
		
		<div id="response"></div>
	
		<form action="" method="post" enctype="multipart/form-data" id="hybrid-slideshow-upload">
			<?php if(function_exists('wp_nonce_field')) { wp_nonce_field('hybrid_upload_nonce'); } ?>
			<input type="hidden" name="MAX_FILE_SIZE" value="1000000">
			<input type="file" name="uploaded_file" />
			<input type="hidden" name="submitted" value="true" />
			<input type="hidden" name="action" id="action" value="wp_handle_upload" />
			<input type="submit" class="button-primary" value="Upload"/>
		</form>
		
		<h3>Manage Images</h3>
			
		<?php
		$current_images = get_option('hybrid-slideshow-option-images');
		
		if($current_images) { ?>
			<div id="sorthead">
				<table>
					<tr>
						<td class="left">Image <span>Order</span> <span class="url-title">Link (url)</span></td>
						<td class="right">Delete</td>
					</tr>		
				</table>
			</div>
			
			<ul id="sortable">
				<?php
				$i = 0;
				foreach($current_images as $image_array) {
					$extension = strrchr($image_array['image'], '.');
					$length = strlen($extension);
					$base = substr($image_array['image'], 0, -($length));
					$upload_dir = wp_upload_dir();
					echo '<li id="listItem_' . $i . ' class=">';
					echo '<img src="' . $upload_dir['baseurl'] . '/' . $base . '-thumb' . $extension . '" />';

					ob_start();
					include( plugin_dir_path( __FILE__ ) . 'svg/move.svg' );
					$icon = ob_get_contents();
					ob_end_clean();
					echo '<span class="handle">' . $icon . '</span>';

					echo '<form action="" method="post" class="url">'; 
					if(function_exists('wp_nonce_field')) { wp_nonce_field('hybrid_url_nonce'); } 
					echo '<input type="text" name="url" value="' . $image_array['url'] . '" /><input type="hidden" name="submitted" value="true" /><input type="hidden" name="add_url" value="' . $i . '" class="add-url" /><input type="submit" name="submit" value="Save" class="url-btn" /></form>';
					echo '<form action="" method="post" class="delete">'; 

					ob_start();
					include( plugin_dir_path( __FILE__ ) . 'svg/trash.svg' );
					$icon = ob_get_contents();
					ob_end_clean();

					echo '<form action="" method="post" class="delete">'; 
					if(function_exists('wp_nonce_field')) { wp_nonce_field('hybrid_delete_nonce'); }
					echo '<input type="hidden" name="submitted" value="true" /><input type="hidden" name="delete" value="' . $i . '" class="hidden delete-img" /><button class="trash">' . $icon . '</button></form>';

					echo '</li>';
					$i++;
				}
				?>
			</ul>
			
			<div id="sortfoot">
				<table>
					<tr>
						<td class="left">Image <span>Order</span> <span class="url-title">Link (url)</span></td>
						<td class="right">Delete</td>
					</tr>		
				</table>
			</div>
		<?php
		}
		?>
	</div><!-- end .wrap -->
	<?php
}

// This function handles all of the image processing
function hybrid_slideshow_process_image($file, $type) {
	
	//	Check that file is actually an image
	if(strpos($type, 'image') === FALSE) {
		$error = '<div class="error" id="message"><p>Sorry, but the file you uploaded does not seem to be a valid image. Please try again.</p></div>';
		return $error;
	}
	
	//	Check that image meets the minimum width & height requirements
	list($width, $height) = getimagesize($file);
	$hybrid_slideshow_width = get_option('hybrid-slideshow-option-width');
	$hybrid_slideshow_height = get_option('hybrid-slideshow-option-height');

	if($width < $hybrid_slideshow_width || $height < $hybrid_slideshow_height) {
		$error = '<div class="error" id="message"><p>Sorry, but this image does not meet the minimum height/width requirements. Please upload another image</p></div>';
		return $error;
	} else {
		// We can now resize the image, create a thumbnail, and store in the database
		$upload_dir = wp_upload_dir();
		//	get the image dimensions
		list($width, $height) = getimagesize($file);
		$hybrid_slideshow_width = get_option('hybrid-slideshow-option-width');
		$hybrid_slideshow_height = get_option('hybrid-slideshow-option-height');
		//	if the image is larger than the width/height requirements, then scale it down.
		if($width > $hybrid_slideshow_width || $height > $hybrid_slideshow_height) {
			//	resize the image
			$resized = image_resize($file, $hybrid_slideshow_width, $hybrid_slideshow_height, true);
			//	delete the original
			unlink($file);
			$file = $resized;
		}
		
		$thumbnail = image_resize($file, 60, 60, true, 'thumb');
		
		$current_upload_dir = $upload_dir['subdir'];
		$subpath = strrchr($file, '/');
		$image_path = $current_upload_dir . $subpath;
		
		$current_images = get_option('hybrid-slideshow-option-images');
		if($current_images) {
			$current_images[] = array( 'image' => $image_path, 'url' => '' );
			update_option('hybrid-slideshow-option-images', $current_images);
		} else {
			$options_array = array( array( 'image' => $image_path, 'url' => '' ) );
			update_option('hybrid-slideshow-option-images', $options_array);
		}
	}
}

// Output the slideshow list of images
function hybrid_slideshow() { 
	$current_images = get_option('hybrid-slideshow-option-images');
	if( get_option('hybrid-slideshow-option-random') == 'true' ) { shuffle($current_images); }
	$image_width = get_option('hybrid-slideshow-option-width');
	$image_height = get_option('hybrid-slideshow-option-height');
	$upload_dir = wp_upload_dir();
	if($current_images) { 
		$output = '<ul id="hybrid-slideshow">' . "\n";
		$i = 0;
		foreach($current_images as $image_array) {
			$url = $image_array['url'];
			$output .= '<li id="h-slide-' . ( $i + 1 ) . '">';
			if( $url != '' ) { $output .= '<a href="' .  $url . '">'; }
			$output .= '<img src="' . $upload_dir['baseurl'] . $image_array['image'] . '" width="' . $image_width . '" height="' . $image_height . '" alt="slide-' . ($i + 1) .'" />';
			if( $url != '' ) { $output .= '</a>';}
			$output .= '</li>' . "\n";
			$i++;
		}
		$output .= '</ul>';
	}
	return $output;
}

// Register & create the slideshow widget
function hybrid_slideshow_register_widgets() {
	register_widget('hybrid_slideshow_widget');
}

class hybrid_slideshow_widget extends WP_Widget {
	function hybrid_slideshow_widget() {
		$widget_ops = array('classname' => 'hybrid_slideshow_widget', 'description' => 'Slideshow widget');
		$this->WP_Widget('hybrid-slideshow', 'Hybrid Slideshow', $widget_ops);
	}
	function form($instance) {
		$defaults = array('title' => 'Slideshow');
		$instance = wp_parse_args((array) $instance, $defaults);
		$title = strip_tags($instance['title']);
		?>
		<p>Title: <input class="widefat" type="text" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($title); ?>" /></p>
	<?php
	}
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		return $instance;
	}
	function widget($args, $instance) {
		extract($args);
		$title = apply_filters('widget_title', $instance['title']);
		if(!empty($title)) { echo $before_title . $title . $after_title; };
		echo $before_widget;
		echo hybrid_slideshow();
		echo $after_widget;
	}
}

// Frontend slideshow header output
function hybrid_slideshow_header_output() { ?>
	<link href="<?php echo HYBRID_SLIDESHOW_URL; ?>css/slideshow.css" rel="stylesheet" type="text/css" />
	<style type="text/css">
		#hybrid-slideshow {
			max-width: <?php echo get_option('hybrid-slideshow-option-width'); ?>px;
		}
	</style>
	
	<?php if( get_option('hybrid-slideshow-option-javascript') != 'true' ) { ?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			var numberOfPhotos = jQuery('#hybrid-slideshow li').length;
			$('#hybrid-slideshow li:not(#hybrid-slideshow li:first)').css('opacity', 0);
			jQuery('#hybrid-slideshow li:first').addClass('active');
			if( numberOfPhotos > 1 ) {
				var hybridTimer = setInterval('rotatePics()', <?php echo get_option('hybrid-slideshow-option-delay'); ?>000);
			}
		});
	
		function rotatePics() {
			var current = jQuery('#hybrid-slideshow li.active') ?  jQuery('#hybrid-slideshow li.active') : jQuery('#hybrid-slideshow li:first');
			var next = (current.next().length) ? current.next() : jQuery('#hybrid-slideshow li:first');
			next.addClass('active').stop(true).animate({opacity: 1.0}, <?php echo get_option('hybrid-slideshow-option-transition'); ?>000);
			current.stop(true).animate({opacity: 0.0}, <?php echo get_option('hybrid-slideshow-option-transition'); ?>000).removeClass('active');
		}
		</script>
	<?php
	}
}

?>