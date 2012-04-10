<?php
/*
Plugin Name: WP Visited Countries
Plugin URI: http://wordpress.amellie.net/
Version: 1.0.0
Description: Shows a map of visited countries in a page
Author: Amalia S.
Author URI: http://amellie.net/
*/

/*
Copyright (C) 2012  Amalia S.

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

define( 'WPVC_URL', plugin_dir_url( __FILE__ ) );
define( 'WPVC_PATH', WP_PLUGIN_DIR.'/wp-visited-countries/' );

/* keys for options and/or Settings API */
define( 'WPVC_VERSION_KEY', 'wpvc_version' );
define( 'WPVC_SETTINGS_KEY', 'wpvc_settings' );
define( 'WPVC_ADD_COUNTRIES_KEY', 'wpvc_countries' );
define( 'WPVC_EDIT_COUNTRIES_KEY', 'wpvc_country' );

define( 'WPVC_VERSION_NUM', '1.0.0' );
define( 'WPVC_DEFAULT_MAP_WIDTH', 700 );
define( 'WPVC_DEFAULT_MAP_HEIGHT', 400 );
define( 'WPVC_DEFAULT_MAP_WATER', 'E0E0E0' );
define( 'WPVC_TOTAL_COUNTRIES', 215 );

if( is_admin() ) {
	require_once WPVC_PATH . 'inc/class-wpvc-base.php';
	require_once WPVC_PATH . 'inc/class-wpvc-settings.php';
	require_once WPVC_PATH . 'inc/class-wpvc-countries.php';

	$wpvc_settings_class;
	$wpvc_countries_class;
}

require_once WPVC_PATH . 'inc/class-wpvc-widget.php';
require_once WPVC_PATH . 'inc/class-wpvc-master.php';

function wp_visited_countries_init() {

	register_activation_hook( __FILE__,  array( 'WPVC_Master', 'activate' ) );
	register_deactivation_hook( __FILE__,  array( 'WPVC_Master', 'deactivate' ) );
	add_option( WPVC_VERSION_KEY, WPVC_VERSION_NUM );
	add_filter( 'plugin_action_links', array( 'WPVC_Master', 'add_action_links' ), 10, 2 );
	add_action( 'admin_menu',  array( 'WPVC_Master', 'add_pages' ) );
	add_shortcode( 'wp-visited-countries', array( 'WPVC_Master', 'handle_shortcode' ) );
	// TODO: add_filter( 'the_posts', array( 'WPVC_Master', 'enqueue_scripts' ) );
	
	//load the translated strings
	load_plugin_textdomain( 'wpvc-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
}

add_action( 'init', 'wp_visited_countries_init');
add_action( 'widgets_init', array( 'WPVC_Map_Widget', 'load_widget' ) );
?>