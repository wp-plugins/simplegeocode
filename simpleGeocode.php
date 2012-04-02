<?php
/*
	Plugin Name: simpleGeocode
	Plugin URI: http://timesdispatch.com
	Description: Enter an address and have Google generate a map. A widget allows you to place a map of all markers in any sidebar. If the sidebar appears in on a post page, there is an option to only show the marker for that post. There is also a shortcode available [simpleGeocode] that allows you to place a map on any page or post. 
	Version: 1.0
	Author: Matthew Rosenberg
	Author URI: http://matthewrosenberg.com
	License: GPLv2
*/
 
/*
    Copyright 2012  Media General  (email : mrosenberg@timesdispatch.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


class Simple_Geocode {

/*
 * @package simpleGeocode
 * @since 1.0
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
	
	public function __construct() {
		
		// Admin: Only load these on post.php and new-post.php
		add_action( 'admin_print_scripts-post.php', array( $this, 'scripts' ) );
		add_action( 'admin_print_scripts-post-new.php', array( $this, 'scripts' ) );
		add_action( 'admin_head-post.php', array( $this, 'ajax_handoff' ) );
		add_action( 'admin_head-post-new.php', array( $this, 'ajax_handoff' ) );
		 
		// Admin: General
		add_action( 'add_meta_boxes', array( $this, 'geo_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'geo_save_postdata' ) );
		add_action( 'wp_ajax_ajax_query', array( $this, 'ajax_query' ) );
		
		// Public; Load these for the theme. 
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
		add_shortcode( 'simpleGeocode', array( $this, 'shortcode' ) );
		
		// This file contains the simpleGeocode widget
		include_once 'simpleGeocode_widget.php';
	}
	

	// External requirements for public dynamic map
	function scripts() { 
		wp_register_script( 'simpleGMA', "http://maps.googleapis.com/maps/api/js?sensor=true");
		wp_register_script( 'simpleGeocodeJS', plugin_dir_url(__FILE__) . 'simpleGeocode.js');
		wp_enqueue_script( 'simpleGMA' );
		wp_enqueue_script( 'simpleGeocodeJS' );
	}
	
	/**
	 * Prints in the admin header and handles the ajax callback. This will probably be deprecated in the next version. 
	 *
	 * @since 1.0
	 *
	 * @global array $post
	 * @param    integer    $nonce    Generates the security nonce sent to the callback function.
	 **/
	
	function ajax_handoff() {
		global $post;
		$nonce = wp_create_nonce  ('simple_geocode');

		echo '
		<script type="text/javascript">
		jQuery(document).ready(function($) {
		
			var data = {
				action: "ajax_query",
				post_id: '.$post->ID.',
				_nonce: "'.$nonce.'"
			};
		
			jQuery.post(ajaxurl, data, function(response) {
					geoBuildMap(response); 
			}, "json")
		});
		</script>';
	}
		
	/**
	 * The callback function for ajax_handoff. Used in the admin. 
	 *
	 * @since 1.0
	 *
	 * @param integer $post_id The post ID number sent by ajax_handoff
	 * @param array $geo_data The post information retrieved from geo_query
	 * @param array $response json enconded data sent to the javascript
	 **/	

	public function ajax_query() {
	
		check_ajax_referer( 'simple_geocode', '_nonce' );
		
		$post_id = intval( $_POST[ 'post_id' ] );
		
		$geo_data = $this->geo_query( $post_id );
		
		if ($geo_data) :
			$response = json_encode(array( 'geomarker' => $geo_data ) );
			print ($response);
		endif;
		
		die();
	}
	
	/**
	 * The callback function for data retrieved by the widgets and shortcode
	 *
	 * @since 1.0
	 *
	 * @param integer $post_id The post ID number sent by widget
	 * @param array $geo_data The post information retrieved from geo_query
	 * @param array $response json enconded data sent to the javascript
	 * @return array only sends data if geo_data is true.
	 **/	
	
	public function post_query( $geo_id ) {
		$post_id = $geo_id;
		$geo_data = $this->geo_query( $post_id );
		
		if ($geo_data) :
			$response = json_encode(array( 'geomarker' => $geo_data ) );
			return $response;
		endif;
	}
	
	/**
	 * The main database query
	 *
	 * If a post ID is not sent, the query returns all posts with attaches _simple_geocode postmeta
	 *
	 * @since 1.0
	 *
	 * @param integer $post_id The post ID number sent by the sender function.
	 * @param array $geo_query An array of values for get_posts().
	 * @param array $geo_posts An array of all the post data.
	 * @param array $geo_data  A filtered array of only the data we want for the marker.
	 * @param array $geo_info  The _simple_geocode postmeta data foreach post.
	 * @param array $cat_info  All the categories for each post.
	 * @param array $geo_cat   category data split into ID and name.
	 * @return array|string    Either an array of post data or null if no posts are found.
     *
	 **/	

	private function geo_query( $post_id ) {
	
		if ( null == $post_id ) :
			// This query gathers all posts that have geo data attached.
			$geo_query = array( 'meta_key' => '_simple_geocode' );
		else :
			// This query only gathers the defined post with an additional check for data.
			$geo_query = array( 'post__in' => array( $post_id ), 'meta_key' => '_simple_geocode' );
		endif;
		
		$geo_posts = get_posts( $geo_query );

		// .
		$geo_data = array();
		
		foreach ( $geo_posts as $geo_post ) : setup_postdata( $geo_post );
		
			$geo_info = get_post_meta( $geo_post->ID, '_simple_geocode', true );
			$cat_info = get_the_category( $geo_post->ID );
			
			$geo_cat = array();
			
			foreach ( $cat_info as $cat ) :
			
				array_push ( $geo_cat, array( 'category_name' => $cat->name, 'category_id' => $cat->cat_ID ) );
			
			endforeach;
			
			array_push( $geo_data,  array( 
							'ID'         => $geo_post->ID,
							'permalink'  => get_permalink( $geo_post->ID ),
							'title'      => get_the_title( $geo_post->ID ),
							'excerpt'    => get_the_excerpt(),
							'categories' => $geo_cat,
							'latitude'   => $geo_info[ 'lat' ],
							'longitude'  => $geo_info[ 'lng' ],
							'address'    => $geo_info[ 'address' ]
						));
						
		endforeach;	
		
		if ( ! empty( $geo_data ) ) :
			return $geo_data;
		else :
			return "null";
		endif;		
	
	} // Close simpleGeocodeQuery
	
	/**
	 * Shortcode 
	 *
	 * Extracts the arguments from the shortcode and setsup the map. 
	 *
	 * @since 1.0
	 * @param array $atts      The values supplies by the user in the shortcode, if any.
	 * @param array $response  json enconded data sent to the javascript.
	 * @param string $div_id   The map container div id to insert the map into.  
	 * @param string $geo_div  Removed all spaces if any from teh name and strip to underscores. 
	 * @param string $output   HTML output sent to return
	 * @return string 
     *
	 **/
	
	function shortcode( $atts ) {
		$response = $this->post_query( null );
		
		extract( shortcode_atts( array( 'height' => '300px', 'div' => 'simple_geocode' ), $atts ) );
		
		$div_id = strtolower('simple_geocode_' . $div );
		
		$geo_div = str_replace( ' ', '_', $div_id );

		$output  = '<style> #'.$geo_div.' img {max-width:none;}</style>';
		$output .= '
					<div id="directions_container" style="display:none;">
        				<button onCLick="geoBuildMap(response, div)">Close</button> 
        				<div id="directions_panel"></div>
    				</div>';
		$output .= '<div id="'.$geo_div.'" style="margin-top:20px; height: '.$height.';"></div>';
		$output .= '<script> 
						jQuery(function($) {
							var response = '.$response.';
							var div = "'.$geo_div.'";
							$(document).ready(function() {
								geoBuildMap(response, div);
							});
						});
			  		</script>';
		
		return $output;
	}
	

	/**
	 * Post Meta Box Setup
	 *
	 * Defines the needed meta box to enter the address and display the map.  
	 *
	 * @since 1.0
     *
	 **/
	
	function geo_meta_boxes() {
	  add_meta_box( 
		'geo_address',
		 __( 'simpleGeocode', 'address_meta' ),
		array( $this, 'geo_box' ),
		'post',
		'normal',
		'high' 
	  );
	}
	
	/**
	 * Post Meta Box Output 
	 *
	 * Prints the needed meta box to enter the address and display the map. 
	 *
	 * @since 1.0
	 * @global array $post All the post data. Needed for the ID.
	 * @param array $geo_meta The simpleGeocode postmeta.
	 * @param string $geo_address The entered address or the default message 
	 * @param array $geo_coordinates The latitude and longitude of retrienved from teh postmeta or default values. 
     *
	 **/
	
	// Print the meta box on the posts page. 
	function geo_box( $post ) {
		
		// This section retrieves variables from teh database and sets defaults if no value exists. 
		$geo_meta = get_post_meta( $post->ID, '_simple_geocode', true );
		$geo_address = ( null == $geo_meta[ 'address' ] ) ? 'Enter Address' : $geo_meta[ 'address' ];
		$geo_coordinates = ( null == $geo_meta[ 'lat' ] ) ? array( 'lat' => 0, 'lng' => 0 ) : $geo_meta;

		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), 'field_security' );
	  
		echo '
			<div id="simpleGeoform">
				<label for="geo_address_field">Address</label>	
				<br/>	 	
				<input id="geo_address_field" name="geo_address_field" type="text" size="100" value="'.$geo_address.'"/>
				<input id="geoCode_submit" onClick="geoCodeAddress()" class="button" type="button" value="geoCode" />
				<input id="geoCode_remove" onClick="geoResetMap()"class="button" type="button" value="Remove" />
				<input id="geo_post_coordinates" 
				       name="geo_post_coordinates" 
					   type="hidden" 
					   value="'.$geo_coordinates["lat"].', '.$geo_coordinates["lng"].'" />	   
				<input id="geo_post_id" type="hidden" value="'.$post->ID.'" />
				<input id="geo_post_delete" name="geo_post_delete" type="hidden" value="false" />
			</div>
			
			<div id="map_container">   
				<p class="howto"><i>Map Preview</i></p>
				<span id="geo_coordinates"></span>
				<div id="simpleGeoMap" style="margin-top:20px; width: 100%; height: 400px"></div>
			</div>';
	}
	
	/**
	 * Post Meta Box Database Write
	 *
	 * Saves the data entered in the metabox form to the database. 
	 *
	 * @since 1.0
	 * @global integer $post_id 
	 * @param string $geo_address The address entered into the form.
	 * @param string $geo_lat_long The javascript printed values returned from the geocoder.
	 * @param string $geo_delete_data If set, triggers simpleGeocode postmeta deletion. 
	 * @param string $geo_replace Geo data with "(" and ")" removed.
	 * @param array $geo_split Seperated latitude and longitude based on the comma.
	 * @param array $geo_data Recombined associative array of latitude, longitude, and address.
     *
	 **/

	function geo_save_postdata( $post_id ) {
	
		// Verify if this is an auto save routine. 
		// If it is our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;
		
		// Verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( ! isset( $_POST[ 'field_security' ] ) )
			return;
		
		if ( ! wp_verify_nonce( $_POST[ 'field_security' ], plugin_basename( __FILE__ ) ) )
			return;
		
		
		// Check permissions
		if ( 'page' == $_POST[ 'post_type' ] ) :
			if ( ! current_user_can( 'edit_page', $post_id ) )
				return;
		else 
			if ( ! current_user_can( 'edit_post', $post_id ) )
				return;
		endif;
		
		
		$geo_address       = $_POST['geo_address_field'];
		$geo_lat_long      = $_POST['geo_post_coordinates'];
		$geo_delete_data   = $_POST['geo_post_delete'];
		
		$geo_replace = str_replace( array( '(', ')'), " ",  $geo_lat_long);
		$geo_split = explode( ",", $geo_replace);
		
		$geo_data = array();
		$geo_data[ 'lat' ]     = $geo_split[0];
		$geo_data[ 'lng' ]     = $geo_split[1];
		$geo_data[ 'address' ] = $geo_address;
		
		
		// This checks to see if data was actually created and we got a good geocode from Google.
		if ( 0 != $geo_data[ 'lat' ] || 0 != $geo_data[ 'lng' ] ) :
			// Save the data to the database	
			update_post_meta( $post_id, '_simple_geocode', $geo_data);
		endif;  
		
		if ( "true" == $geo_delete_data ) :
			delete_post_meta( $post_id, '_simple_geocode' );
		endif;
	}		
		
} // End simpleGeocode class


// Create a new simpleGeocode object. There may be a better way to do this through add_action.
if ( class_exists( 'Simple_Geocode' ) ) $simple_geocode = new Simple_Geocode;

