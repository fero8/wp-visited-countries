<?php
/**
 * Widget class for displaying a map of countries visited/lived
 *
 * @package WPVC
 */

class WPVC_Map_Widget extends WP_Widget {
	var $title;
	var $width;
	var $height;
	var $desc;
	var $total_countries;

	public function __construct() {
	
		$widget_ops = array( 'classname' => 'wp-visited-countries', 'description' => __( 'Show your visited countries map', 'wpvc-plugin' ) );
		parent::__construct( 'WPVC_Map_Widget', '&nbsp;' . __( 'Map of Visited Countries', 'wpvc-plugin' ), $widget_ops );
		
		$this->title = __( 'Map of Countries Visited', 'wpvc-plugin' );
		$option = get_option( WPVC_SETTINGS_KEY );
		$this->width = ( !empty( $option[ 'int_map_width' ] ) ? $option[ 'int_map_width' ] : WPVC_DEFAULT_MAP_WIDTH );
		$this->height = ( !empty( $option[ 'int_map_height' ] ) ? $option[ 'int_map_height' ] : WPVC_DEFAULT_MAP_HEIGHT );
		$this->desc = "I've been to {num} out of {total} countries ({percent})";
		$this->total_countries = 215;
		
	}
	
	function form( $instance ) {
	
		$instance = wp_parse_args( (array) $instance, 
				array(
					'title' => $this->title,
					'width' => $this->width,
					'height' => $this->height,
					'desc' => $this->desc
				) );
		$title = strip_tags( $instance['title'] );
		$width = strip_tags( $instance['width'] );
		$height = strip_tags( $instance['height'] );
		$desc = esc_textarea( $instance['desc'] );
		
		?>
		
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>">
					<?php _e( 'Title:', 'wpvc-plugin' ) ?> 
					<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
				</label>
			</p>			
		
			<p>
				<label for="<?php echo $this->get_field_id( 'width' ); ?>">
					<?php _e( 'Map Width:', 'wpvc-plugin' ) ?> 
					<input class="widefat" id="<?php echo $this->get_field_id( 'width' ); ?>" name="<?php echo $this->get_field_name( 'width' ); ?>" type="text" value="<?php echo esc_attr( $width ); ?>" />
				</label>
			</p>			
		
			<p>
				<label for="<?php echo $this->get_field_id( 'height' ); ?>">
					<?php _e( 'Map Height:', 'wpvc-plugin' ) ?> 
					<input class="widefat" id="<?php echo $this->get_field_id( 'height' ); ?>" name="<?php echo $this->get_field_name( 'height' ); ?>" type="text" value="<?php echo esc_attr( $height ); ?>" />
				</label>
			</p>			
		
			<p>
				<label for="<?php echo $this->get_field_id( 'desc' ); ?>">
					<?php _e( 'Additional Texts:', 'wpvc-plugin' ) ?> 
					<textarea cols="2" rows="5" class="widefat" id="<?php echo $this->get_field_id( 'desc' ); ?>" name="<?php echo $this->get_field_name( 'desc' ); ?>"><?php echo $desc; ?></textarea>
				</label> <p>You can use <code>{num}</code>, <code>{total}</code>, and <code>{percent}</code></p>
			</p>			
		
		<?php
		
	}
  
	function update( $new_instance, $old_instance ) {
	
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['width'] = is_numeric( $new_instance['width'] ) ? $new_instance['width'] : $this->width;
		$instance['height'] = is_numeric( $new_instance['height'] ) ? $new_instance['height'] : $this->height;
		
		$instance['desc'] = $new_instance['desc'];
		
		if ( current_user_can('unfiltered_html') )
			$instance['desc'] =  $instance['desc'];
		else
			$instance['desc'] = stripslashes( wp_filter_post_kses( addslashes($instance['desc']) ) ); // wp_filter_post_kses() expects slashed
		
		return $instance;
		
	}

	function widget( $args, $instance ) {

		extract( $args, EXTR_SKIP );
		
		echo $before_widget;
		
		$title = empty( $instance['title'] ) ? ' ' : apply_filters( 'widget_title', $instance['title'] );
		if ( !empty( $title ) ) { echo $before_title . $title . $after_title; };
		
		$wpvc = new WPVC_Master();
		?>

		<div class="wpvc-widget">
			<div class="wpvc-map"><?php echo $wpvc->get_script( $instance['width'], $instance['height'], 'wpvc-map-content' ); ?></div>
			<div class="wpvc-desc"><?php echo $wpvc->parse_text( $instance['desc'] ) ?></div>
		</div>

		<?php	  	
		echo $after_widget;
		
	}

	function load_widget() {
		register_widget( 'WPVC_Map_Widget' );
	
	}	
	
}
?>