<?php
/*
	Place this widget in any dynamic sidebar to display your map markers.
	The widget admin allows you to set a title, the map height, and an override for showing all markers. 
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

add_action( 'widgets_init', create_function( '', 'register_widget( "Simple_Geocode_Widget" );' ) );
class Simple_Geocode_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
	 		'simpleGeocode_map', // Base ID
			'simpleGeocode Map', // Name
			array( 'description' => __( 'simpleGeocode Map', 'simpleGeocode' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		global $post, $simple_geocode;
		
		extract( $args );

		$title      = apply_filters( 'widget_title', $instance['title'] );
		$height     = $instance['height'];
		$geo_global = $instance['geo_global'];


		
		$div_id = strtolower('simple_geocode_' . $instance['title'] );
		
		$geo_div = str_replace( ' ', '_', $div_id );
		
		echo $before_widget;
		if ( ! empty( $title ) ) :
			echo $before_title . $title . $after_title;
		endif;	
		
		if ( is_single() && false == $geo_global ) :
			$geo_id = $post->ID;
		else :
			$geo_id = null;
		endif;		
		
		$response = $simple_geocode->post_query( $geo_id );
		echo '<style> #'.$geo_div.' img {max-width:none;}</style>';
		echo '
			<div id="directions_container" data-role="controlgroup" data-type="horizontal" style="display:none;">
            	<button onCLick="geoBuildMap(response, div)">Close</button> 
            	<div id="directions_panel"></div>
    		</div>
			<div id="'.$geo_div.'" style="margin-top:20px; height: '.$height.';"></div>';
		
		echo '<script> 
				jQuery(function($) {
					var response = '.$response.';
					var div = "'.$geo_div.'";
					$(document).ready(function() {
						geoBuildMap(response, div);
					});
				});
			   </script>';

		echo $after_widget;

	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['height'] = strip_tags( $new_instance['height'] );
		$instance['geo_global'] = $new_instance['geo_global'];

		return $instance;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {

		if ( $instance ) {
			$title = esc_attr( $instance[ 'title' ] );
			$height = esc_attr( $instance[ 'height' ] );
			$geo_global = (bool) $instance[ 'geo_global' ];
			
		}
		else {
			$title  = __( 'New title', 'text_domain' );
			$height = __( '300px', 'text_domain' );
			$geo_global = false;
		}
		?>
        <style>
			.geo_row {margin-top:10px;}
		</style>	
        
		<div class="geo_row">
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
		</div>
        
        <div class="geo_row">
            <p class="description"><strong>Height:</strong> The widget will default to 100% width of its container but will default to 300px in height unless otherwise specififed here. </p>
            <p class="description"><strong>Global:</strong> Selecting this will force the map widget to show all geocoded posts regardless of the current page. This is useful for header and footer sidebar areas.</p>
        </div>
        
		<div class="geo_row">
            <label style="display:inline-block; margin-right: 5px;" for="<?php echo $this->get_field_id( 'height' ); ?>"><?php _e( 'Height:' ); ?></label><input style="display:inline-block; width:30%;" id="<?php echo $this->get_field_id( 'height' ); ?>" name="<?php echo $this->get_field_name( 'height' ); ?>" type="text" value="<?php echo $height; ?>" />
        </div>

        
		<div class="geo_row">
            <label style="display:inline-block; margin-right: 5px;" for="<?php echo $this->get_field_id( 'geo_global' ); ?>"><?php _e( 'Global:' ); ?></label><input style="width:1%; display:inline-block;" id="<?php echo $this->get_field_id( 'geo_global' ); ?>" name="<?php echo $this->get_field_name( 'geo_global' ); ?>" type="checkbox" <?php if ( true == $geo_global) echo 'checked="checked"'; ?> />
		</div>
		<?php 
	}

} // class simpleGeocode_Widget
