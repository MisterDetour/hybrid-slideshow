<?php

/** 
 * Plugin Name: Hybrid Slideshow
 * Plugin URI: http://www.hybridvigordesign.com/uncategorized/hybrid-slideshow
 * Description:  A simple image slideshow.
 * Author: David LaTour
 * Version: 2.2.3
 * Author URI: http://www.hybridvigordesign.com
 * 
 * Copyright 2010 - by David LaTour
 * 
 * This program is free software: you can redistribute it and/or modify 
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * https://opensource.org/licenses/gpl-license.php
 */

/**
 * Exit if accessed directly
 */
if ( !defined( 'ABSPATH' ) ) {
	exit; 
}

/**
 * Activation hook
 */
register_activation_hook( __FILE__, array( 'Hybrid_Slideshow', 'install' ) );

/**
 * Main Hybrid_Slideshow Class
 *
 * @class Hybrid_Slideshow
 */
class Hybrid_Slideshow {

	function __construct() {

		$this->path = plugin_dir_path( __FILE__ );
		$this->dir = plugin_dir_url( __FILE__ );
		$this->version = '2.2';

		// Actions
		add_action( 'plugins_loaded', array( $this, 'update_data_structure' ) );
		add_action( 'admin_menu', array( $this, 'register_menu_items' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'wp_ajax_hybrid_special_action', array( $this, 'order_callback' ) );
		add_action( 'wp_ajax_hybrid_delete_action', array( $this, 'delete_callback' ) );
		add_action( 'wp_ajax_hybrid_url_action', array( $this, 'url_callback' ) );
		add_action( 'wp_ajax_hybrid_add_image', array( $this, 'image_callback' ) );
		add_shortcode( 'hybrid_slideshow', array( $this, 'shortcode' ) );
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
		add_action( 'wp_head', array( $this, 'header_output' ) );
		add_action( 'delete_attachment', array( $this, 'delete_image' ), 10, 1 );
		add_filter( 'pre_update_option_hybrid-slideshow-option-width', array( $this, 'update_width' ), 10, 3 );
		add_filter( 'pre_update_option_hybrid-slideshow-option-height', array( $this, 'update_height' ), 11, 3 );

	}

	/**
	 * Install
	 *
	 **/
	public static function install() {

		// Grab values
		$settings[ 'width' ] = get_option( 'hybrid-slideshow-option-width' );
		$settings[ 'height' ] = get_option( 'hybrid-slideshow-option-height' );
		$settings[ 'delay' ] = get_option( 'hybrid-slideshow-option-delay' );
		$settings[ 'transition' ] = get_option( 'hybrid-slideshow-option-transition' );
		$settings[ 'random' ] = get_option( 'hybrid-slideshow-option-random' );
		$settings[ 'javascript' ] = get_option( 'hybrid-slideshow-option-javascript' );
		
		// Set default values if needed
		if ( !isset( $settings[ 'width' ] ) || $settings[ 'width' ] == '0' ) { 
			update_option( 'hybrid-slideshow-option-width', '400' ); 
		}

		if ( !isset( $settings[ 'height' ] ) || $settings[ 'height' ] == '0' ) { 
			update_option( 'hybrid-slideshow-option-height', '280' ); 
		}

		if ( !isset( $settings[ 'delay' ]) || $settings[ 'delay' ] == '0' ) { 
			update_option( 'hybrid-slideshow-option-delay', '3' ); 
		}

		if ( !isset( $settings[ 'transition' ]) || $settings[ 'transition' ] == '0' ) { 
			update_option( 'hybrid-slideshow-option-transition', '2' ); 
		}
	}

	/**
	 *  Update old slideshow data structure in DB
	 *
	 **/
	public function update_data_structure() {
		$current_images = get_option( 'hybrid-slideshow-option-images' );

		if ( $current_images ) {
			if ( !is_array( $current_images[ 0 ] ) ) {
				// Convert to newer array format
				$new_order = array();
				foreach( $current_images as $image ) {
					$new_order[] = array(
						'image' => $image,
						'url' => ''
					);
					delete_option( 'hybrid-slideshow-option-images' );
					update_option( 'hybrid-slideshow-option-images', $new_order );
				}

			} elseif ( !is_numeric( $current_images[ 0 ][ 'image' ] ) ) {

				// Convert to new format, using media library to work with images
				$new_order = array();
				$upload_dir = wp_upload_dir()[ 'basedir' ]; 
				$slideshow_width = get_option( 'hybrid-slideshow-option-width' );
				$slideshow_height = get_option( 'hybrid-slideshow-option-height' );
				$dimension_string = '-' . $slideshow_width . 'x' . $slideshow_height;

				foreach ( $current_images as $image ) {
					
					$path = $upload_dir . $image[ 'image' ];
					$fullsize_path = str_replace( $dimension_string, '', $path );

					copy( $path, $fullsize_path );

					$filetype = wp_check_filetype( basename( $path ), null )[ 'type' ];
					$title = preg_replace( '/\.[^.]+$/', '', basename( $path ) );
					$title = substr( $title, 0, strrpos( $title, '-' ) );

					$attachment = array(
						'guid'           => $fullsize_path, 
						'post_mime_type' => $filetype,
						'post_title'     => $title,
						'post_content'   => '',
						'post_status'    => 'inherit'
					);

					$attach_id = wp_insert_attachment( $attachment, $fullsize_path, 0 );

					require_once( ABSPATH . 'wp-admin/includes/image.php' );
					$attach_data = wp_generate_attachment_metadata( $attach_id, $fullsize_path );
					wp_update_attachment_metadata( $attach_id, $attach_data );
					
					$new_order[] = array(
						'image' => $attach_id,
						'url' => $image[ 'url' ]
					);

					$ext = '.' . wp_check_filetype( $path )[ 'ext' ];
					$thumb_path = str_replace( $ext, '-thumb' . $ext, $path );
			
					if ( file_exists( $thumb_path ) ) wp_delete_file( $thumb_path );
					
				}

				Hybrid_Slideshow::update_images( $new_order, $slideshow_width, $slideshow_height );

				delete_option( 'hybrid-slideshow-option-images' );
				update_option( 'hybrid-slideshow-option-images', $new_order );
			}
			
		}
	}

	/**
	 *  Register menu items
	 *
	 **/
	public function register_menu_items() {
		add_menu_page( 'Hybrid Slideshow', 'Slideshow', 'administrator', 'hybrid-slideshow' , array( $this, 'slideshow_page' ) );
		add_submenu_page( 'hybrid-slideshow', 'Images', 'Images', 'administrator', 'hybrid-slideshow-images' , array( $this, 'images_page' ) );
		add_action( 'admin_init', array( $this, 'plugin_options' ) );
	}

	/**
	 *  Register plugin settings
	 *
	 **/
	public function plugin_options() {
		register_setting( 'hybrid-slideshow-settings-group', 'hybrid-slideshow-option-width', 'intval' );
		register_setting( 'hybrid-slideshow-settings-group', 'hybrid-slideshow-option-height', 'intval' );
		register_setting( 'hybrid-slideshow-settings-group', 'hybrid-slideshow-option-delay', 'intval' );
		register_setting( 'hybrid-slideshow-settings-group', 'hybrid-slideshow-option-transition', 'intval' );
		register_setting( 'hybrid-slideshow-settings-group', 'hybrid-slideshow-option-random' );
		register_setting( 'hybrid-slideshow-settings-group', 'hybrid-slideshow-option-javascript' );
	}

	/**
	 *  Load frontend assets
	 *
	 **/
	public function scripts() {

		wp_enqueue_script( 'jquery' );

	}

	/**
	 *  Load admin assets
	 *
	 **/
	public function admin_scripts( $hook ) {

		// Display on slideshow images page only
		if ( 'slideshow_page_hybrid-slideshow-images' != $hook ) {
        	return;
    	}

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_media();
		wp_register_script( 'media-uploader', plugins_url( 'js/media-uploader.js' , __FILE__ ), array( 'jquery' ), '1.00000000001' );
		wp_register_script( 'hybrid-custom', plugins_url( 'js/custom.js' , __FILE__ ), array( 'jquery' ), '1.0000000000003' );

		$params = array( 
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'delete_nonce' => wp_create_nonce( 'hybrid_delete_nonce' ), 
			'url_nonce' => wp_create_nonce( 'hybrid_url_nonce' )
		);

		wp_localize_script( 'hybrid-custom', 'ajax_object', $params ); 
		wp_enqueue_script( 'hybrid-custom' );
		
		$params = array( 
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'delete_nonce' => wp_create_nonce( 'hybrid_delete_nonce' ),
			'url_nonce' => wp_create_nonce( 'hybrid_url_nonce' )
		);
		
		wp_localize_script( 'media-uploader', 'ajax_object', $params ); 
		wp_enqueue_script( 'media-uploader' );

		wp_enqueue_style( 'hybrid-slideshow-admin', plugins_url( 'css/admin.css' , __FILE__ ), array(), '1.0000005' );

	}

	/**
	 *  Ajax callback - reorder slides
	 *
	 **/
	public function order_callback() {

		$order_array = $_POST[ 'order' ];

		// Retrieve current images from options database table
		$current_images = get_option( 'hybrid-slideshow-option-images' );	

		// Build new array with images in updated order
		$new_order = array();

		foreach ( $order_array as $img ) {
			$new_order[] = array( 'image' => $img[ 0 ], 'url' => $img[ 1 ] );	
		}

		update_option( 'hybrid-slideshow-option-images', $new_order );

		wp_die( 1 );
	}

	/**
	 *  Ajax callback - delete slide
	 *
	 **/
	public function delete_callback() {
		if ( !wp_verify_nonce( $_POST[ 'nonce'] , 'hybrid_delete_nonce' ) ) wp_die( 0 );

		$image_id = $_POST[ 'id' ];
		$current_images = get_option( 'hybrid-slideshow-option-images' );
		
		// delete as long as isn't registered image size from elsewhere
		if ( !Hybrid_Slideshow::check_existing_size() ) {
			Hybrid_Slideshow::delete_image( $current_images[ $image_id ][ 'image' ] );
		}

		if ( $current_images ) {
			unset( $current_images[ $image_id ] );
			$current_images = array_values( $current_images );
			update_option( 'hybrid-slideshow-option-images', $current_images );
		}
		
		wp_die( 1 );
	}

	/**
	 *  Ajax callback - save url
	 *
	 **/
	public function url_callback() {
		if ( ! wp_verify_nonce( $_POST[ 'nonce'] , 'hybrid_url_nonce' ) ) {
			wp_die( 'nonce didn\'t verify' );
		}

		$image_id = $_POST[ 'id' ];
		$current_images = get_option( 'hybrid-slideshow-option-images' );

		$current_images[ $image_id ][ 'url' ] = esc_url( $_POST[ 'url' ] );

		update_option( 'hybrid-slideshow-option-images', $current_images );

		wp_die();
	}

	/**
	 *  Add image ajax callback
	 *
	 **/
	function image_callback() {
		$img = intval( $_REQUEST[ 'image' ] );

		// Retrieve current images from options database table
		$current_images = get_option( 'hybrid-slideshow-option-images' );	

		$current_images[] = array( 'image' => $img, 'url' => '' );

		update_option( 'hybrid-slideshow-option-images', $current_images );

		// Create custom image size
		$path = wp_get_original_image_path( $img );
		$path_info = pathinfo( $path );
		$file_name = $path_info[ 'filename' ];
		$file_ext = $path_info[ 'extension' ];
		$upload_dir = wp_upload_dir(); 
		$image = wp_get_image_editor( $path );

		$width = get_option( 'hybrid-slideshow-option-width' );
		$height = get_option( 'hybrid-slideshow-option-height' );

		$new_path = $path_info[ 'dirname' ] . '/' . $file_name . '-' . $width . 'x' . $height . '.' . $file_ext;

		if ( ! is_wp_error( $image ) ) {
			$image->resize( $width, $height, true );
			$image->save( $new_path );
		}

		$img_data = array( 
			'img' => wp_get_attachment_image( $img, 'thumbnail' ), 
			'id' => $img
		);

		echo json_encode( $img_data );
		wp_die();
	}

	/**
	 *  Shortcode output
	 *
	 **/
	public static function shortcode() { 
		$current_images = get_option( 'hybrid-slideshow-option-images' );
		if ( get_option( 'hybrid-slideshow-option-random' ) == 'true' ) { shuffle( $current_images ); }
		$image_width = get_option( 'hybrid-slideshow-option-width' );
		$image_height = get_option( 'hybrid-slideshow-option-height' );
		$upload_dir = wp_upload_dir();

		if ( $current_images ) { 
			$output = '<ul id="hybrid-slideshow">' . "\n";
			$i = 0;
			foreach ( $current_images as $image_array ) {
				$url = $image_array[ 'url' ];
				$output .= '<li id="h-slide-' . ( $i + 1 ) . '">';
				if ( $url != '' ) { $output .= '<a href="' .  $url . '">'; }
				$output .= Hybrid_Slideshow::get_custom_image( $image_array[ 'image' ] );
				if ( $url != '' ) { $output .= '</a>';}
				$output .= '</li>' . "\n";
				$i++;
			}
			$output .= '</ul>';
		}
		return $output;
	}

	/**
	 *  Register the slideshow widget
	 *
	 **/
	function register_widgets() {
		register_widget( 'hybrid_slideshow_widget' );
	}

	/**
	 *  Frontend slideshow header output
	 *
	 **/
	public function header_output() { ?>
		<link href="<?php echo $this->dir; ?>/css/slideshow.css" rel="stylesheet" type="text/css" />
		<style type="text/css">
			#hybrid-slideshow {
				max-width: <?php echo get_option( 'hybrid-slideshow-option-width' ); ?>px;
			}
		</style>
		
		<?php if ( get_option( 'hybrid-slideshow-option-javascript' ) != 'true' ) { ?>
			<script type="text/javascript">
			jQuery(document).ready(function($) {
				var numberOfPhotos = jQuery( '#hybrid-slideshow li' ).length;
				$( '#hybrid-slideshow li:not( #hybrid-slideshow li:first )' ).css( 'opacity', 0 );
				jQuery( '#hybrid-slideshow li:first' ).addClass( 'active' );
				if ( numberOfPhotos > 1 ) {
					var hybridTimer = setInterval( 'rotatePics()', <?php echo get_option( 'hybrid-slideshow-option-delay' ); ?>000 );
				}
			});
		
			function rotatePics() {
				var current = jQuery( '#hybrid-slideshow li.active' ) ?  jQuery( '#hybrid-slideshow li.active' ) : jQuery( '#hybrid-slideshow li:first' );
				var next = ( current.next().length ) ? current.next() : jQuery( '#hybrid-slideshow li:first' );
				next.addClass( 'active' ).stop( true ).animate( { opacity: 1.0 }, <?php echo get_option( 'hybrid-slideshow-option-transition' ); ?>000 );
				current.stop( true ).animate( { opacity: 0.0 }, <?php echo get_option( 'hybrid-slideshow-option-transition' ); ?>000 ).removeClass( 'active' );
			}
			</script>
		<?php
		}
	}

	/**
	 *  Delete image
	 *
	 **/
	public static function delete_image( $img, $update = false ) {
		$path = wp_get_original_image_path( $img );

		$path_info = pathinfo( $path );
		$file_name = $path_info[ 'filename' ];
		$file_ext = $path_info[ 'extension' ];
		$upload_dir = wp_upload_dir(); 
		$image = wp_get_image_editor( $path );

		if ( $update ) {
			$width = get_option( 'hybrid-slideshow-option-width-prev' );
			$height = get_option( 'hybrid-slideshow-option-height-prev' );
		} else {
			$width = get_option( 'hybrid-slideshow-option-width' );
			$height = get_option( 'hybrid-slideshow-option-height' );
		}

		$new_path = $path_info[ 'dirname' ] . '/' . $file_name . '-' . $width . 'x' . $height . '.' . $file_ext;

		if ( file_exists( $new_path ) ) wp_delete_file( $new_path );

		// Delete the image from the slideshow array, if it isn't a dimension update
		if ( !$update ) {
			// Retrieve current images from options database table
			$current_images = get_option( 'hybrid-slideshow-option-images' );

			foreach ( $current_images as $key => $value ) {
				if ( intval( $value[ 'image' ] ) === intval( $img ) ) {
					unset( $current_images[ $key ] );
				}
			}
			
			$current_images = array_values( $current_images );
			update_option( 'hybrid-slideshow-option-images', $current_images );
		}
	}

	/**
	 *  Update slider width
	 *
	 **/
	public function update_width( $new_value, $old_value, $option ) {
		update_option( 'hybrid-slideshow-option-width-prev', $old_value );
		return $new_value;
	}

	/**
	 *  Update slider height
	 *
	 **/
	function update_height( $new_value, $old_value, $option ) {
		update_option( 'hybrid-slideshow-option-height-prev', $old_value );

		$prev_width = get_option( 'hybrid-slideshow-option-width-prev' );
		$prev_height = get_option( 'hybrid-slideshow-option-height-prev' );
		$width = get_option( 'hybrid-slideshow-option-width' );

		if ( intval( $new_value ) !== intval( $prev_height ) || intval( $prev_width ) !== intval( $width ) ) {
			
			$current_images = get_option( 'hybrid-slideshow-option-images' );

			foreach ( $current_images as $img ) {
				Hybrid_Slideshow::delete_image( $img[ 'image' ], true );
			}
			
			Hybrid_Slideshow::update_images( $current_images, $width, $new_value );

		}

		return $new_value;
	}

	/**
	 *  Settings page output
	 *
	 **/
	public function slideshow_page() { ?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"></div>
			<h2>Hybrid Slideshow Settings</h2>
			
			<form action="options.php" method="post" id="hybrid-slideshow-settings">
				<?php settings_fields( 'hybrid-slideshow-settings-group' ); ?>
				
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><label for="hybrid-slideshow-option-width">Width:</label></td>
							<td><input type="text" name="hybrid-slideshow-option-width" value="<?php echo get_option( 'hybrid-slideshow-option-width' ); ?>" class="small-text" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="hybrid-slideshow-option-height">Height:</label></th>
							<td><input type="text" name="hybrid-slideshow-option-height" value="<?php echo get_option( 'hybrid-slideshow-option-height' ); ?>" class="small-text" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="hybrid-slideshow-option-delay">Time between transition (seconds):</label></th>
							<td><input type="text" name="hybrid-slideshow-option-delay" value="<?php echo get_option( 'hybrid-slideshow-option-delay' ); ?>" class="small-text" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="hybrid-slideshow-option-transition">Transition length (seconds):</label></th>
							<td><input type="text" name="hybrid-slideshow-option-transition" value="<?php echo get_option( 'hybrid-slideshow-option-transition' ); ?>" class="small-text" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="hybrid-slideshow-option-random">Randomize image order:</label></th>
							<td><input type="checkbox" name="hybrid-slideshow-option-random" value="true" <?php if( get_option( 'hybrid-slideshow-option-random' ) == 'true' ) echo 'checked="checked" '; ?>/></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="hybrid-slideshow-option-javascript">Use your own javascript:</label></th>
							<td><input type="checkbox" name="hybrid-slideshow-option-javascript" value="true" <?php if( get_option( 'hybrid-slideshow-option-javascript' ) == 'true' ) echo 'checked="checked" '; ?>/></td>
						</tr>
					</tbody>
				</table>
				
				<p class="submit"><input type="submit" class="button-primary" value="Update" /></p>
					
			</form>
		</div>
	<?php 
	}

	/**
	 *  Images page output
	 *
	 **/
	function images_page() {

		// If form has been submitted then deal with uploading the image
		if( isset( $_POST[ 'submitted' ] ) ) {
			if ( isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'wp_handle_upload' ) {
				check_admin_referer( 'hybrid_upload_nonce' );
				// Image upload form handler
				$upload = wp_handle_upload( $_FILES[ 'uploaded_file' ], 0 );
				extract( $upload );

				if ( isset( $error ) ) {
					echo $error; 
				} else { 
					// If no errors then validate, resize, and create thumbnail image
					$error = Hybrid_Slideshow::process_image( $file, $type );
					if ( $error) {
						echo $error;
						unlink( $file );
					} 
				}
			} elseif ( isset( $_POST[ 'delete'] ) ) { // Image delete handler
				check_admin_referer( 'hybrid_delete_nonce' );
				$image_id = $_POST[ 'delete' ];
				$current_images = get_option( 'hybrid-slideshow-option-images' );
				
				// delete as long as isn't registered image size from elsewhere
				if ( !Hybrid_Slideshow::check_existing_size() ) {
					Hybrid_Slideshow::delete_image( $current_images[ $image_id ][ 'image' ] );
				}

				if ( $current_images ) {
					unset( $current_images[ $image_id ] );
					$current_images = array_values( $current_images );
					update_option( 'hybrid-slideshow-option-images', $current_images );
				}
				unset( $_POST[ 'delete' ] );
			} elseif ( isset( $_POST[ 'add_url' ] ) ) { // Save image url
				check_admin_referer( 'hybrid_url_nonce' );
				$image_id = $_POST[ 'add_url' ];
				$current_images = get_option( 'hybrid-slideshow-option-images' );
				if( $current_images ) {
					$current_images[ $image_id ][ 'url' ] = esc_attr( $_POST[ 'url' ] );
					update_option( 'hybrid-slideshow-option-images', $current_images );
				}
				unset( $_POST[ 'add_url' ] );
			}
		}
		$current_images = get_option( 'hybrid-slideshow-option-images' );
		?>
		
		<div class="wrap">
			<?php $current_images = get_option( 'hybrid-slideshow-option-images' ); ?>
			<div id="icon-upload" class="icon32"></div>
			<h2>Images</h2>
			
			<h3>Add an Image</h3>
			
			<div id="response"></div>
		
			<input id="upload_image_button" type="button" class="button-primary" value="Select Image" />
			
			<h3>Manage Images</h3>

			<div id="sorthead">
				<table>
					<tr>
						<td class="left">Image <span>Order</span> <span class="url-title">Link (url)</span></td>
						<td class="right">Delete</td>
					</tr>		
				</table>
			</div>
			
			<ul id="sortable">
				<?php if ( !$current_images ) : ?>
					<li class="slideshow-holder">No images yet</li>
				<?php else : 
					$i = 0;
					foreach ( $current_images as $image_array ) {
						$extension = strrchr( $image_array[ 'image' ], '.' );
						$length = strlen( $extension );
						$base = substr( $image_array[ 'image' ], 0, -( $length ) );
						$upload_dir = wp_upload_dir();
						echo '<li id="listItem_' . $image_array[ 'image' ] . '" class="">';
						echo wp_get_attachment_image( $image_array[ 'image' ], 'thumbnail' );

						ob_start();
						include( plugin_dir_path( __FILE__ ) . 'svg/move.svg' );
						$icon = ob_get_contents();
						ob_end_clean();

						echo '<span class="handle">' . $icon . '</span>';

						if ( function_exists( 'wp_nonce_field' ) ) { 
							wp_nonce_field( 'hybrid_url_nonce' ); 
						} 

						echo '<div class="url-control"><input type="text" name="url" value="' . $image_array[ 'url' ] . '" class="url" /></div>';
		
						ob_start();
						include( plugin_dir_path( __FILE__ ) . 'svg/trash.svg' );
						$icon = ob_get_contents();
						ob_end_clean();

						if ( function_exists( 'wp_nonce_field' ) ) { 
							wp_nonce_field( 'hybrid_delete_nonce' ); 
						}

						echo '<div class="delete"><button class="trash">' . $icon . '</button></div>';

						echo '</li>';
						$i++;
					}
				endif;
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
		</div><!-- end .wrap -->
		<?php
	}

	/**
	 *  Check if there's a custom image size registered with same dimensions as slideshow size
	 *
	 **/
	public static function check_existing_size() {
		global $_wp_additional_image_sizes; 

		$hybrid_slideshow_width = get_option( 'hybrid-slideshow-option-width' );
		$hybrid_slideshow_height = get_option( 'hybrid-slideshow-option-height' );

		foreach ( $_wp_additional_image_sizes as $img_size ) {
			if ( $img_size[ 'width' ] == $hybrid_slideshow_width &&
				$img_size[ 'height' ] == $hybrid_slideshow_height &&
				$img_size[ 'crop' ] == 1 ) return true;
		}

		return false;
	}

	/**
	 *  This function handles all of the image processing
	 *
	 **/
	public static function process_image( $file, $type ) {
		
		//	Check that file is actually an image
		if( strpos( $type, 'image' ) === FALSE ) {
			$error = '<div class="error" id="message"><p>Sorry, but the file you uploaded does not seem to be a valid image. Please try again.</p></div>';
			return $error;
		}
		
		//	Check that image meets the minimum width & height requirements
		list( $width, $height ) = getimagesize( $file );
		$hybrid_slideshow_width = get_option( 'hybrid-slideshow-option-width' );
		$hybrid_slideshow_height = get_option( 'hybrid-slideshow-option-height' );

		if ( $width < $hybrid_slideshow_width || $height < $hybrid_slideshow_height ) {
			$error = '<div class="error" id="message"><p>Sorry, but this image does not meet the minimum height/width requirements. Please upload another image</p></div>';
			return $error;
		} else {
			// We can now resize the image, create a thumbnail, and store in the database
			$upload_dir = wp_upload_dir();
			//	get the image dimensions
			list( $width, $height ) = getimagesize( $file );
			$hybrid_slideshow_width = get_option( 'hybrid-slideshow-option-width' );
			$hybrid_slideshow_height = get_option( 'hybrid-slideshow-option-height' );
			//	if the image is larger than the width/height requirements, then scale it down.
			if ( $width > $hybrid_slideshow_width || $height > $hybrid_slideshow_height ) {
				//	resize the image
				$resized = image_resize( $file, $hybrid_slideshow_width, $hybrid_slideshow_height, true );
				//	delete the original
				unlink( $file );
				$file = $resized;
			}
			
			$thumbnail = image_resize( $file, 60, 60, true, 'thumb' );
			
			$current_upload_dir = $upload_dir[ 'subdir' ];
			$subpath = strrchr( $file, '/' );
			$image_path = $current_upload_dir . $subpath;
			
			$current_images = get_option( 'hybrid-slideshow-option-images' );
			if ( $current_images ) {
				$current_images[] = array( 'image' => $image_path, 'url' => '' );
				update_option( 'hybrid-slideshow-option-images', $current_images );
			} else {
				$options_array = array( array( 'image' => $image_path, 'url' => '' ) );
				update_option( 'hybrid-slideshow-option-images', $options_array );
			}
		}
	}

	/**
	 *  Get the custom slideshow image
	 *
	 **/
	public static function get_custom_image( $id, $format = 'html' ) {
		$path = wp_get_original_image_path( $id );
		$path_info = pathinfo( $path );
		$file_name = $path_info[ 'filename' ];
		$file_ext = $path_info[ 'extension' ];
		$upload_dir = wp_upload_dir(); 
		$image = wp_get_image_editor( $path );

		$width = get_option( 'hybrid-slideshow-option-width' );
		$height = get_option( 'hybrid-slideshow-option-height' );

		if ( $format === 'path' ) {
			return $path_info[ 'dirname' ] . '/' . $file_name . '-' . $width . 'x' . $height . '.' . $file_ext;
		}

		$alt = get_post_meta( $id, '_wp_attachment_image_alt', true );
		$orig_url = wp_get_original_image_url( $id );
		
		$url = str_replace( basename( $orig_url ), '', $orig_url) . $file_name . '-' . $width . 'x' . $height . '.' . $file_ext;
		
		return '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '">';
	}

	/**
	 *  Update image sizes
	 *
	 **/
	public static function update_images( $current_images, $new_width, $new_height ) {
		
		foreach ( $current_images as $img ) {
			// Create custom image size
			$path = wp_get_original_image_path( $img[ 'image' ] );
			$path_info = pathinfo( $path );
			$file_name = $path_info[ 'filename' ];
			$file_ext = $path_info[ 'extension' ];
			$upload_dir = wp_upload_dir(); 
			$image = wp_get_image_editor( $path );

			$new_path = $path_info[ 'dirname' ]. '/' . $file_name . '-' . $new_width . 'x' . $new_height . '.' . $file_ext;

			if ( ! is_wp_error( $image ) ) {
				$image->resize( $new_width, $new_height, true );
				$image->save( $new_path );
			}
		}

	}

	/**
	 *  Update image sizes I DONT THINK THIS IS BEING USED!!!!
	 *
	 **/
	function update_image_sizes( $new_value, $old_value, $option ) {
		if ( intval( $new_value ) !== intval( $old_value ) && ! empty( $new_value ) ) {
			
			if ( $option === 'hybrid-slideshow-option-width' ) {
				update_option( 'hybrid-slideshow-option-width-prev', $old_value );
				return $new_value;
			} else {
				update_option( 'hybrid-slideshow-option-height-prev', $old_value );
			}

			$current_images = get_option( 'hybrid-slideshow-option-images' );
			
			$width = get_option( 'hybrid-slideshow-option-width' );

			foreach ( $current_images as $img ) {
				Hybrid_Slideshow::delete_image( $img[ 'image' ], true );
			}
			
			Hybrid_Slideshow::update_images( $current_images, $width, $new_value );

		}

		return $new_value;
	}

}

/**
 *  Create the slideshow widget
 *	 
 **/
class hybrid_slideshow_widget extends WP_Widget {

	function __construct() {

		$widget_ops = array( 
			'classname' => 'hybrid_slideshow_widget', 
			'description' => 'Slideshow widget'
		);

		parent::__construct( 'hybrid-slideshow', 'Hybrid Slideshow', $widget_ops );
	}

	function form( $instance ) {
		
		$defaults = array( 'title' => 'Slideshow' );
		$instance = wp_parse_args( ( array ) $instance, $defaults );
		$title = strip_tags( $instance[ 'title' ] );
		?>
		<p>Title: <input class="widefat" type="text" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>" /></p>
		<?php

	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance[ 'title' ] = strip_tags( $new_instance[ 'title' ] );
		return $instance;
	}

	function widget($args, $instance) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance[ 'title' ] );
		if ( !empty( $title ) ) { echo $before_title . $title . $after_title; }
		echo $before_widget;
		echo Hybrid_Slideshow::shortcode();
		echo $after_widget;
	}

}

/**
 * Template function
 *
 */
function hybrid_slideshow() {
	return Hybrid_Slideshow::shortcode();
}

/**
 * Fire it up
 *
 */
$hybrid_slideshow = new Hybrid_Slideshow();
?>