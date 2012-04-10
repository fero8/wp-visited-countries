<?php
/**
 * Main class
 *
 * @package WPVC
 */
	

class WPVC_Master {
	
	/**
	 * Activates this plugin when it is first installed
	 *
	 * @access public
	 */
	function activate() {
		new WPVC_Settings( true );
		new WPVC_Countries( true );
	}

	/**
	 * Deactivates this plugin and removes all related option settings
	 *
	 * @access public
	 */
	function deactivate() {
		delete_option( WPVC_VERSION_KEY );	
		delete_option( WPVC_SETTINGS_KEY );	
		delete_option( WPVC_ADD_COUNTRIES_KEY );	
		unregister_widget( 'WPVC_Map_Widget' );					
	}
	
	function init() {
		add_option( WPVC_VERSION_KEY, WPVC_VERSION_NUM );
		// TODO: filter deprecated?
		//add_filter( 'plugin_action_links', array( 'WPVC_Master', 'add_action_links' ), 10, 2 );
		add_action( 'admin_menu',  array( 'WPVC_Master', 'add_pages' ) );
		add_shortcode( 'wp-visited-countries', array( 'WPVC_Master', 'handle_shortcode' ) );
		// TODO: add_filter( 'the_posts', array( 'WPVC_Master', 'enqueue_scripts' ) );
		
		//load the translated strings
		load_plugin_textdomain( 'wpvc-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}		
	
	/**
	 * Adds menu and sub-menu pages to the admin panel
	 *
	 * @access public
	 */
	function add_pages() {
		global $wpvc_settings_class, $wpvc_countries_class;
		
		if ( !function_exists( 'current_user_can' ) || !current_user_can( 'manage_options' ) )
			wp_die('You do not have sufficient permissions to access this page.');
		
		if( !is_a( $wpvc_settings_class, 'WPVC_Settings' ) )
			$wpvc_settings_class = new WPVC_Settings();
		
		if( !is_a( $wpvc_countries_class, 'WPVC_Countries' ) )
			$wpvc_countries_class = new WPVC_Countries();
		
		add_menu_page( 'Visited Countries', 'Visited Countries', 'manage_options', 'wpvc-settings', '' );
		$s_page = add_submenu_page( 'wpvc-settings', 'Manage Settings', 'Settings', 'manage_options', 
				'wpvc-settings', array( 'WPVC_Master', 'display_settings' ) );
		$c_page = add_submenu_page( 'wpvc-settings', 'Manage Countries', 'Countries', 'manage_options', 
				'wpvc-countries', array( 'WPVC_Master', 'display_countries' ) );
		
		$wpvc_settings_class->add_actions( $s_page );
		$wpvc_countries_class->add_actions( $c_page );
	}

	function display_settings() {
		require_once WPVC_PATH . 'inc/wpvc-settings.php';
	}

	function display_countries() {
		
		require_once WPVC_PATH . 'inc/class-wpvc-list-table.php';

		$wpvc_list_table = new WPVC_Country_List_Table();
		
		require_once WPVC_PATH . 'inc/wpvc-countries.php';
	
	}
	
	/**
	 * Handles shortcode [wp-visited-countries] to display a map with specified attributes
	 *
	 * @access public
	 *
	 * @param array $atts Attributes (width and height)
	 * @return string
	 */
	function handle_shortcode( $atts, $content = '' ) {
	
		extract( shortcode_atts( array(
			'width' => '',
			'height' => '',
			'id' => null,
		), $atts ) );
		
		$content = self::parse_text( $content );
		if( !empty( $content ) )
			$content = '<div class="wpvc-description">' . $content . "</div>";
		
		return self::get_script( $width, $height, $id ) . $content;
	}
	
	/**
	 * Analyze input text. If the text contains {num}, {total}, and/or {percent}
	 * it will be changed to the corresponding numbers
	 *
	 * @access public
	 *
	 * @param string $txt
	 * @return string The modified text
	 */
	function parse_text( $txt ) {
		if( empty( $txt ) )
			return '';
		
		$txt = str_replace( '{total}', WPVC_TOTAL_COUNTRIES, $txt );
		
		if( strpos( $txt, '{num}' ) !== false || strpos( $txt, '{percent}' ) !== false ) {
			
			$option = get_option( WPVC_ADD_COUNTRIES_KEY );
			$num = 0;
			
			if( $option )
				$num = count( $option ) ;
			
			$percent = number_format( $num/WPVC_TOTAL_COUNTRIES * 100, 2 ) . '%';
			
			$txt = str_replace( '{num}', $num, $txt );
			$txt = str_replace( '{percent}', $percent, $txt );
		}
		
		return $txt;
	}
	
	/**
	 * Get the JavaScript codes for displaying a map
	 *
	 * @access public
	 *
	 * @param int $width The width of the map to be displayed
	 * @param int $height The height of the map to be displayed
	 * @param string $id The DIV id where the map is written to
	 * @return string
	 */
	function get_script( $width, $height, $id = 'wpvc-flashcontent' ) {
		global $blog_id;
		
		$option = get_option( WPVC_SETTINGS_KEY );
		$bgcolor = $option['hex_water'];
		
		// replace all empty values to the default ones
		
		if( empty( $id ) )
			$id = 'wpvc-flashcontent';
		
		if( empty( $bgcolor ) )
			$bgcolor = WPVC_DEFAULT_MAP_WATER;
		
		if( substr( $bgcolor, 0, 1 ) !== '#' )
			$bgcolor = '#' . $bgcolor;
			
		if( empty( $width ) ) {
			
			if( ! empty( $option[ 'int_map_width' ] ) )
				$width = $option[ 'int_map_width' ];
			else
				$width = WPVC_DEFAULT_MAP_WIDTH;
		
		}
			
		if( empty( $height ) ) {
		
			if( ! empty( $option[ 'int_map_height' ] ) )
				$height = $option[ 'int_map_height' ];
			else
				$height = WPVC_DEFAULT_MAP_HEIGHT;
		}
		
		$script = '<script type="text/javascript" src="' . WPVC_URL . 'ammap/swfobject.js"></script>'
			.'<script type="text/javascript" src="' . WPVC_URL . 'ammap/ammap.js"></script>'
			.'<div id="' . $id . '"></div><script type="text/javascript">
			var wpvc = {
				path	:	"' . WPVC_URL . '",
				width	:	'. $width . ',
				height	:	' . $height . ',
				bgcolor	:	"' . $bgcolor . '",
				id		:	"' . $id . '",
				blogid	:	"' . $blog_id . '"
			};
			wpvc_ammap(wpvc)</script>';
		
		return $script;
	}
	
	/**
	 * TODO: this one only works for pages/posts. Not for plugin. So this function is not used yet 
	 * Based on: http://beerpla.net/2010/01/13/wordpress-plugin-development-how-to-include-css-and-javascript-conditionally-and-only-when-needed-by-the-posts/
	 */
	function enqueue_scripts( $posts ){
		if (empty($posts)) return $posts;
	 
		foreach ($posts as $post) {
			
			if( stripos( $post->post_content, '[wp-visited-countries' ) !== false ) {
				
				wp_enqueue_script( 'swfobject', WPVC_URL . 'ammap/swfobject.js' );
				wp_enqueue_script( 'ammap', WPVC_URL . 'ammap/ammap.js', array('swfobject') );
				
				break;
			}
		}
	 
		return $posts;
	}
	
	/**
	 * Adds a shortcut link in the plugin page to the main settings page
	 *
	 * @access public
	 *
	 * @return array
	 */
	function add_action_links($links, $file) {
		static $this_plugin;

		if (!$this_plugin) {
			$this_plugin = plugin_basename(__FILE__);
		}

		if ($file == $this_plugin) {
			// The "page" query string value must be equal to the slug
			// of the Settings admin page, i.e. wpvc-settings
			$settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wpvc-settings">Settings</a>';
			array_unshift($links, $settings_link);
		}

		return $links;
	}
}
?>