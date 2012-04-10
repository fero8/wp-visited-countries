<?php
/**
 * Base class for handling admin pages
 *
 * @package WPVC
 */

if ( ! class_exists( 'WPVC_Base' ) ) {
	
	class WPVC_Base {
		
		protected $option_name;
		protected $page_name;
		protected $file_name;		// XML file name
		private $defaults; 			// default values of each field
		private $error;
		private $field_titles;
		const field_color_size = 6;
		const field_default_size = 40;
		
		function __construct( $opt_name, $def, $p, $f, $activate = false ) {
			
			$this->option_name 		= $opt_name;
			$this->defaults 		= $def;
			$this->page_name 		= $p;
			$this->field_titles 	= array();
			$this->file_name 		= $this->get_file_name( $f );
			
			if ( ! $this->get_option() ) {
				
				/* Set to defaults if option does not exist */
				update_option( $this->option_name, $this->defaults );
				// create xml file
				$this->prepare_xml( null );
			
			} else if( !file_exists( $this->file_name ) ) {
				
				$this->prepare_xml( null );
			}
			
			if( $activate )
				$this->handle_activation();
		}
		
		protected function init() {
			// Register a setting and its sanitization callback
			register_setting( $this->option_name, $this->option_name, array( &$this, 'validate' ) );
			
			add_action( 'admin_notices', array( &$this, 'add_validation_notice' ) );
		}
		
		private function get_file_name( $name ) {
			global $blog_id;
			
			return WPVC_PATH . 'ammap/' . $name . '_' . $blog_id . '.xml';
		}
		
		//TODO: in the future, do more with upgrade
		private function handle_activation() {
			$version = get_option( WPVC_VERSION_KEY );
			
			if( $version !== WPVC_VERSION_NUM ) {
				//TODO -> upgrade
				update_option( WPVC_VERSION_KEY, WPVC_VERSION_NUM );
			}
			
			//if( !function_exists( 'is_plugin_active_for_network' ) )
				//return;
			
			
			//if( !file_exists( $this->file_name ) )
				//$this->prepare_xml( null );
		}
		
		public function add_validation_notice(){
			global $pagenow;
			
			if ( $pagenow == 'admin.php' && $_GET[ 'page' ] == $this->page_name ) {
				
				if ( ( isset( $_GET[ 'updated' ] ) && $_GET[ 'updated' ] == 'true' ) || 
						( isset( $_GET[ 'settings-updated' ] ) && $_GET[ 'settings-updated' ] == 'true' ) ) {
						
					$errors = get_settings_errors();
					
					if( empty( $errors ) )
						return;
					
					foreach( $errors as $error ) {
						echo '<div class="' . $error[ 'type' ] . '"><p><b>';
						
						if( $error[ 'type' ] == 'error' && $error[ 'code' ] !== 'country_duplicate' )
							echo $this->field_titles[ $error['code'] ];
						
						echo $error[ 'message' ] . '</b></p></div>';
					}
				}
			}
		}
		
		public function add_actions($page) {
			// action that is run when the head of the settings/countries admin page is loaded
			add_action( 'admin_head-'. $page, array( &$this, 'populate_fields' ) );
		}
		
		/**
		 * Returns a default value of a field
		 *
		 * @access protected
		 *
		 * @param string $key Field key
		 * @return string
		 */
		protected function get_default( $key ) {
			if( empty( $this->defaults ) || !isset( $this->defaults[ $key ] ) )
				return '';
			
			return $this->defaults[ $key ];
		}
		protected function get_defaults() {
			return $this->defaults;
		}
		
		protected function get_option() {
			return get_option( $this->option_name );	
		}
		
		protected function get_request($var) {
			return( isset( $_REQUEST[$var] ) ? $_REQUEST[$var] : '' );
		}
		
		protected function add_field ( $id, $title, &$class, $function, $option_name, $section_name, $args ) {
			
			add_settings_field( $id, $title, array( &$class, $function ), 
					$option_name, $section_name, $args );
			
			$this->field_titles[ $id ] = $title;
		}
		
		/**
		 * Add fields for display
		 *
		 * @access protected
		 */
		protected function add_fields( $fields, &$class, $function = 'print_fields' ) {
			
			foreach( $fields as $f ) {
				$f['name'] = $class->option_name.'['.$f['id'].']';
				//$f['id'] = $class->option_name.$f['id'];
				
				$label = array( 'label_for' => $f['name'] );
				$args = array_merge($f);
				
				$class->add_field( $f['id'], $f['title'], &$class, $function, 
					$class->option_name, $f['section'], $args );
			}
		}
		
		/**
		 * Displays the input html code
		 *
		 * @access protected
		 *
		 * @param array $field Field settings
		 * @param array $data
		 * @return string
		 */
		protected function print_input( $field, $data = null ) {
			
			printf( '%s<input name="%s" id="%s" type="text" value="%s" size="%s" %s/>%s'
				, $field[ 'prefield' ]															// texts before input
				, $field[ 'name' ]																// input name
				, $field[ 'name' ]																// input id
				, ( isset($data) ? esc_attr( $data[ $field[ 'id' ] ] ) : "" )					// input value
				, ( !empty( $field[ 'size' ] ) ? $field[ 'size' ] : self::field_default_size )	// input size
				, ( !empty( $field['tags'] ) ? $field['tags'].' ' : '' )						// all other input tags
				, $field['postfield']															// texts after input
			);
		}
		
		protected function parse_desc($desc) {
			return '<span class="description">'.$desc.'</span>';
		}
		
		protected function verify_nonce( $nonce_name, $nonce_action, $post = true ) {
			if( ( $post && empty($_POST) ) || !wp_verify_nonce( $this->get_request( $nonce_name ), $nonce_action ) )
				die( 'Oops. This is illegal!' );
		}
		
		/**
		 * Checks the values of each input fields and replace them with defaults
		 * if necessary
		 *
		 * @access protected
		 *
		 * @param array $input
		 * @param string $message Output message upon successful submission
		 * @return array
		 */
		protected function verify_type( $input, $message = '' ) {
			$opts = $this->get_option();
			$this->error = false;
			//print_r($input);
			
			foreach( $input as $key => $value ) {
				$type = $this->get_type( $key );
				
				switch( $type ) {
					
					case 'int': //check integer
						if( $this->validate_int( $value ) )
							break;
						
						$this->error = true;
						$this->add_error( $key, __( ' must be an integer', 'wpvc-plugin' ) );
						$input[ $key ] = $this->get_default( $key );
					break;
					
					case 'txt': //check text
						$input[ $key ] = $this->validate_text( $value );
					break;
					
					/* TODO: add boolean
					case 'bool':
						if( $this->validate_bool( $value ) ) {
							break;
					break;*/
					
					case 'hex': //check hexadecimal code color
						if( $this->validate_hex( $value ) ) {
							$input[ $key ] = strtoupper( $input[ $key ] );
							break;
						}
						
						$this->error = true;
						$this->add_error( $key, __( ' must be a hexadecimal', 'wpvc-plugin' ) );
						$input[ $key ] = $this->get_default( $key );
					break;
				}
			}
			
			if( !$this->error ) {
				//successful
				if( empty( $message ) )
					$message = __( 'Settings saved', 'wpvc-plugin' );
				$this->add_success( $message );
			}
			
			return $input;
		}
		
		protected function is_error() {
			return $this->error;
		}
		
		/**
		 * Adds error message to Settings API error handling
		 *
		 * @access protected
		 */
		protected function add_error( $key, $message ) {
			add_settings_error( $key, $key, $message, 'error' );
		}
		
		/**
		 * Adds successful message to Settings API
		 *
		 * @access protected
		 */
		protected function add_success( $message ) {
			add_settings_error( 'success', 'success', $message, 'updated' );
		}
		
		/**
		 * Returns the type of the field (i.e. int, bool, txt, etc)
		 *
		 * @access protected
		 *
		 * @param string $name Field key
		 * @return string
		 */
		protected function get_type( $name ) {
			$temp = explode( '_', $name );
			return $temp[0];
		}
		
		private function validate_int( $i ) {
			return is_numeric( $i );
		}
		
		private function validate_hex( $h ) {
			if( empty( $h ) ) return true;
			return preg_match( '/^\#?[A-Fa-f0-9]{3}([A-Fa-f0-9]{3})$/', $h );
		}
		
		private function validate_text( $t ) {
			return stripslashes( wp_filter_nohtml_kses( $t ) );
		}
		
		protected function add_hashtag( $color ) {
			if( empty( $color ) )
				return '';
			else if( substr( $color, 0, 1 ) == '#' )
				return $color;
			else
				return '#' . $color;
		}
		
		protected function remove_hashtag( $color ) {
			if( empty( $color ) )
				return $color;
			return replace_str( '#', '', $color );
		}
		
		protected function write_xml( $data ) {
			if( empty( $this->file_name ) )
				return;
			
			$handle = fopen( $this->file_name, 'w' ) or die( "Problem occurs when trying to add a file" );
			fwrite($handle, $data);
			fclose($handle);
		}
	}
}


?>