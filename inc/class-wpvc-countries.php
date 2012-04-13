<?php
/**
 * Class for handling countries admin pages
 *
 * @package WPVC
 */

if ( ! class_exists( 'WPVC_Countries' ) && is_admin() ) {
	
	class WPVC_Countries extends WPVC_Base {
		
		function __construct( $activate  = false ) {
			
			parent::__construct( WPVC_ADD_COUNTRIES_KEY, array(), 'wpvc-countries', 'ammap_data', $activate );
			
			add_action( 'admin_init', array( &$this, 'init' ) );
		}
		
		private function generate_defaults() {
			//visited
			$new = array();
			$new['country_name'] = 'United States_US';
			$new['txt_desc'] = "Travelled around the East Coast in 2000";
			$new['int_visited'] = 1;
			$new['hex_country'] = '';
			$new['hex_hover'] = '';
			$new['url_country'] = '';
			
			$d['US'] = $new;
			
			//lived
			$new = array();
			$new['country_name'] = 'Australia_AU';
			$new['txt_desc'] = "Lived here from 2004 to 2007";
			$new['int_visited'] = 2;
			$new['hex_country'] = '001CEB';
			$new['hex_hover'] = '';
			$new['url_country'] = 'wordpress.org';

			$d['AU'] = $new;				
			
			return $d;
		}
		
		function init() {
			parent::init();
			
			//section for adding a new country
			add_settings_section( $this->option_name.'_add', __( 'Add New Country', 'wpvc-plugin' ), 
					array( &$this, 'print_content_add' ), $this->option_name );
			
			//section for editing a country
			add_settings_section( $this->option_name.'_edit', '', 
					array( &$this, 'print_content_edit' ), WPVC_EDIT_COUNTRIES_KEY );
			
			if( $this->is_delete() )
				update_option( WPVC_ADD_COUNTRIES_KEY, $this->get_option() );
		}
		
		public function print_content_add() {
			self::print_content_section('add');
		}
		
		public function print_content_edit() {
			self::print_content_section('edit');
		}
		
		/**
		 * Prints out hidden value & nonce
		 *
		 * @access public
		 *
		 * @param string $val Section type (add or edit)
		 */
		public function print_content_section( $val ) {
			echo '<input type="hidden" name="wpvc_section" value="' . $val . '" />';
			echo wp_nonce_field( $this->option_name.'_'.$val, $this->option_name.'_nonce' );
		}
		
		function is_delete() {
			return ( isset( $_REQUEST['country'] ) && ( $_REQUEST['action'] === 'delete' || $_REQUEST['action2'] == 'delete' ) );
		}
		
		/**
		 * Validates and parses input fields upon submission. Empty values
		 * are replaced by default values if necessary
		 *
		 * @access public
		 *
		 * @param array $input
		 * @return array
		 */
		function validate( $input ) {
			$options = $this->get_option();
			
			if( isset( $_REQUEST['country'] ) && ( $_REQUEST['action'] === 'delete' || $_REQUEST['action2'] == 'delete' ) ) {
				// delete one or more countries
				
				$delete = $_REQUEST['country'];
				$count = 0;
				
				if (is_array($delete)) {
					// handle deletion for more than 1 country
					
					$this->verify_nonce( '_wpnonce', 'bulk-countries', false);
					
					foreach ($delete as $del) {
						unset( $options[$del] );
						$count++;
					}
				} else {
					// handle deletion for 1 country
					
					$this->verify_nonce( '_wpnonce', 'wpvc_nonce_list_table', false);
					unset( $options[ $_REQUEST['country'] ] );
					$count = 1;
				}
				unset($_REQUEST['country']);
				
				wp_redirect( add_query_arg('deleted', $count, admin_url( 'admin.php?page=wpvc-countries' ) ) );
				
			} else if( $_REQUEST['action'] == '-1' ) {
				
				die("[WPVC_Countries:validate] Something is wrong");
			
			} else {
				//edit or add a country
				
				$country_code = $this->get_country_key( $input['country_name'] );
				$country_name = $this->get_country_name( $input['country_name'] );
				
				//check if it attempts to add a  new country
				if(	$this->get_request('wpvc_section') === 'add' ) {
					//adding a new country
					
					$this->verify_nonce( $this->option_name.'_nonce', $this->option_name.'_add' );
					
					//check if duplication is detected
					if (is_array( $this->get_request('wpvc_countries') ) 
							&& isset ( $options[ $this->get_country_key( $_REQUEST['wpvc_countries']['country_name'] ) ] ) ) {
					
						//create an error because of duplication
						$this->add_error( 'country_duplicate', 
								sprintf( __( 'Country %s already exists. Unable to add the same country.', 'wpvc-plugin' ), $country_name ) );
						return $options;
					}
					
					//no duplication is detected, create a successful message
					$message = sprintf( __( 'Country %s has been added.', 'wpvc-plugin' ), $country_name );
				
				} else {
					//editing a country
					
					$this->verify_nonce( $this->option_name.'_nonce', $this->option_name.'_edit' );
					
					$message = sprintf( __( 'Country %s has been edited.', 'wpvc-plugin' ), $country_name );
				}
				
				// verifying and validating the inputs
				$input = $this->verify_type( $input, $message );
				
				if( !empty( $input['url_country'] )) {
					$input['url_country'] = esc_url( 'http://' . $input['url_country'], array( 'http', 'https' ) );
					$input['url_country'] = str_replace( 'http://', '', $input['url_country'] );
				}
				
				if( empty( $country_code ) )
					die( "[WPVC_Countries:validate] Country code is missing" );
					
				$options[ $country_code ] = $input;
			}
			
			// write changes to xml
			$this->prepare_xml( $options );
			
			return $options;
		}
		
		/**
		 * Defines and adds fields
		 *
		 * @access public
		 */
		public function populate_fields() {
			
			$fields = array(
				array( 
					'id' => 'country_name',
					'type' => 'select_country',
					'title' => __( 'Country', 'wpvc-plugin' ),
					'size' => '',
					'tags' => '',
					'prefield' => '',
					'postfield' => '',
					'section' => ''
				),
				array( 
					'id' => 'int_visited',
					'type' => 'select_country_value',
					'title' => __( 'Visited/Lived?', 'wpvc-plugin' ),
					'size' => '4',
					'tags' => 'maxlength="4"',
					'prefield' => '',
					'postfield' => ' px',
					'section' => ''
				),
				array( 
					'id' => 'txt_desc',
					'type' => 'input',
					'title' => __( 'Description', 'wpvc-plugin' ),
					'size' => '',
					'tags' => '',
					'prefield' => '',
					'postfield' => $this->parse_desc( __( 'Description is shown when a country is hovered', 'wpvc-plugin' ) ),
					'section' => ''
				),
				array( 
					'id' => 'hex_country',
					'type' => 'input',
					'title' => __( 'Country Color', 'wpvc-plugin' ),
					'size' => self::field_color_size,
					'tags' => 'maxlength="'.self::field_color_size.'"',
					'prefield' => '# ',
					'postfield' => $this->parse_desc( 
							__( 'Hex color code for the country. Default color will be used if not set', 'wpvc-plugin' ) ),
					'section' => ''
				),
				array( 
					'id' => 'hex_hover',
					'type' => 'input',
					'title' => __( 'Hover Color', 'wpvc-plugin' ),
					'size' => self::field_color_size,
					'tags' => 'maxlength="'.self::field_color_size.'"',
					'prefield' => '# ',
					'postfield' => $this->parse_desc( 
							__( 'Hover color for the country. Default color will be used if not set', 'wpvc-plugin' ) ),
					'section' => ''
				),
				array( 
					'id' => 'url_country',
					'type' => 'input',
					'title' => __( 'URL', 'wpvc-plugin' ),
					'size' => 30,
					'tags' => '',
					'prefield' => 'http://',
					'postfield' => '<br />' . $this->parse_desc( __( 'The URL which can be accessed by clicking the country. '.
							'Example: <code>wordpress.org</code> &#8212; do NOT include the <code>http://</code>', 'wpvc-plugin' ) ),
					'section' => ''
				)
			);
			
			foreach( $fields as $f ) {
				$f['name'] = $this->option_name.'['.$f['id'].']';
				//$f['id'] = $class->option_name.$f['id'];
				
				$label = array( 'label_for' => $f['name'] );
				$args = array_merge($f);
				
				//need to display fields in both add and edit country pages
				if( $this->get_request( 'action' ) == 'edit' )
					$this->add_field( $f['id'], $f['title'], &$this, 'print_fields', WPVC_EDIT_COUNTRIES_KEY, $this->option_name.'_edit', $args );
				else
					$this->add_field( $f['id'], $f['title'], &$this, 'print_fields', $this->option_name, $this->option_name.'_add', $args );
			}
			
		}
		
		/**
		 * Print each field according to its type
		 *
		 * @access public
		 *
		 * @param array $field
		 */
		public function print_fields($field) {
			$action = $this->get_request('action');
			$delete_action = ( $action  == 'delete' );
			$add_action = ( !$delete_action && $action !== 'edit' );
			
			$options = $this->get_option();
			$data = null;
			
			if( !$delete_action && isset( $_REQUEST['country'] ) && !empty( $_REQUEST['country'] ) ) {
				// if we land in edit country page
				$country = $_REQUEST['country'];
				$data = $options[$country];	
				$data['url_country'] = str_replace( 'http://', '', $data['url_country'] );
			} else {
				$country = '';
			}
			
			switch( $field['type'] ) {
				
				case 'input':
					$this->print_input($field, $data);
				break;
				
				case 'select_country_value':
					printf( 
						'<select name="%s" id="%s"><option value="1"%s>%s</option><option value="2"%s>%s</option></select>'
						, $field['name']																//input name
						, $field['name']																//input id
						, ( isset($data) && $data['int_visited'] == 1 ) ? ' selected="selected"' : ''	//selected tag for value "Visited"
						, __( 'Visited', 'wpvc-plugin' )
						, ( isset($data) && $data['int_visited'] == 2 ) ? ' selected="selected"' : ''	//selected tag for value "Lived"
						, __( 'Lived', 'wpvc-plugin' )
					);
				break;
				
				case 'select_country':
					printf( 
						'<select name="%s" id="%s"%s>', 
						$field['name'], $field['name'], 
						( $action  == 'edit' ? ' disabled="disabled"' : '' )
					);
					if( $add_action || $delete_action ) {
						foreach( $this->get_countries() as $key => $name ) {
							printf( '<option value="%s_%s">%s</option>', $name, $key, $name );
						}
						echo '</select>';
						
					} else {
						$key = $this->get_country_key( $data['country_name'] );
						$name = $this->get_country_name( $data['country_name'] );
						
						printf( '<option value="%s_%s">%s</option></select>', $name, $key, $name );
						printf( '<input type="hidden" name="%s" value="%s" />', $field['name'], $data['country_name']);
					}
					
					break;
			}
		}
		
		protected function get_request( $var ) {
			return( isset( $_REQUEST[$var] ) ? $_REQUEST[$var] : '' );
		}
		
		private function get_country_key( $name ) {
			$temp = explode( "_", $name);
			return $temp[1];
		}
		private function get_country_name( $name ) {
			$temp = explode( "_", $name);
			return $temp[0];
		}
		
		protected function prepare_xml( $countries = null ) {
			if( empty( $countries ) )
				$countries = $this->get_option();
			
			$data = '<?xml version="1.0" encoding="UTF-8"?>'. "\n\n"
				. '<map map_file="world3.swf" zoom="100%" zoom_x="7%" zoom_y="-8%">' . "\n\t<areas>";
			
			foreach ($countries as $country) {
			
				$key = $this->get_country_key( $country['country_name'] );
				$name = $this->get_country_name( $country['country_name'] );
				$country['hex_country'] = $this->add_hashtag( $country['hex_country'] );
				$country['hex_hover'] = $this->add_hashtag( $country['hex_hover'] );
				
				if ( !empty( $country['txt_desc'] ) )
					$country['txt_desc'] = '<br /><p>' . $country['txt_desc'] . '</p>';
				
				if ( !empty( $country['url_country'] ) )
					$country['url_country'] = 'http://' . $country['url_country'];
				
				if ( !empty( $country['txt_desc'] ) ) 
					$country['txt_desc'] = '<br /><p>'.$country['txt_desc'].'</p>';
					
				$data .= "
		<area mc_name=\"$key\" title=\"$name\" value=\"$country[int_visited]\" "
			. "url=\"$country[url_country]\" color=\"$country[hex_country]\" color_hover=\"$country[hex_hover]\" target=\"_blank\">
			<description>
				<![CDATA[$country[txt_desc]]]>
			</description>
		</area>";
		
			}
			
			$data .= "\n\t</areas>\n</map>";
			
			$this->write_xml( $data );
		}

		private function get_countries() {
			$countries = array( 'AF'=>'Afghanistan', 'AX'=>'Aland Islands', 'AL'=>'Albania', 'DZ'=>'Algeria', 'AD'=>'Andorra', 
					'AO'=>'Angola', 'AI'=>'Anguilla', 'AG'=>'Antigua and Barbuda', 'AR'=>'Argentina', 'AM'=>'Armenia', 
					'AW'=>'Aruba', 'AU'=>'Australia', 'AT'=>'Austria', 'AZ'=>'Azerbaijan', 'BS'=>'Bahamas', 
					'BH'=>'Bahrain', 'BD'=>'Bangladesh', 'BB'=>'Barbados', 'BY'=>'Belarus', 'BE'=>'Belgium', 'BZ'=>'Belize', 
					'BJ'=>'Benin', 'BM'=>'Bermuda', 'BT'=>'Bhutan', 'BO'=>'Bolivia', 'BA'=>'Bosnia and Herzegovina', 'BW'=>'Botswana', 
					'BR'=>'Brazil', 'BN'=>'Brunei Darussalam', 'BG'=>'Bulgaria', 'BF'=>'Burkina Faso', 'BI'=>'Burundi', 'KH'=>'Cambodia', 
					'CM'=>'Cameroon', 'CA'=>'Canada', 'CV'=>'Cape Verde', 'KY'=>'Cayman Islands', 'CF'=>'Central African Republic', 
					'TD'=>'Chad', 'CL'=>'Chile', 'CN'=>'China', 'CO'=>'Colombia', 'KM'=>'Comoros', 'CG'=>'Republic Of Congo', 
					'CD'=>'Democratic Republic Of The Congo', 'CR'=>'Costa Rica', 'CI'=>'Cote D\'ivoire', 'HR'=>'Croatia', 
					'CU'=>'Cuba', 'CY'=>'Cyprus', 'CZ'=>'Czech Republic', 'DK'=>'Denmark', 'DJ'=>'Djibouti', 'DM'=>'Dominica', 
					'DO'=>'Dominican Republic', 'EC'=>'Ecuador', 'EG'=>'Egypt', 'SV'=>'El Salvador', 'GQ'=>'Equatorial Guinea', 
					'ER'=>'Eritrea', 'EE'=>'Estonia', 'ET'=>'Ethiopia', 'FO'=>'Faeroe Islands', 'FK'=>'Falkland Islands', 'FJ'=>'Fiji', 
					'FI'=>'Finland', 'FR'=>'France', 'GF'=>'French Guiana', 'GA'=>'Gabon', 'GM'=>'Gambia', 'GE'=>'Georgia', 'DE'=>'Germany', 
					'GH'=>'Ghana', 'GR'=>'Greece', 'GL'=>'Greenland', 'GD'=>'Grenada', 'GP'=>'Guadeloupe', 'GT'=>'Guatemala', 'GN'=>'Guinea', 
					'GW'=>'Guinea-bissau', 'GY'=>'Guyana', 'HT'=>'Haiti', 'HN'=>'Honduras', 'HK'=>'Hong Kong', 'HU'=>'Hungary', 
					'IS'=>'Iceland', 'IN'=>'India', 'ID'=>'Indonesia', 'IR'=>'Iran', 'IQ'=>'Iraq', 'IE'=>'Ireland', 'IL'=>'Israel', 
					'IT'=>'Italy', 'JM'=>'Jamaica', 'JP'=>'Japan', 'JO'=>'Jordan', 'KZ'=>'Kazakhstan', 'KE'=>'Kenya', 'NR'=>'Nauru', 
					'KP'=>'North Korea', 'KR'=>'South Korea', 'KV'=>'Kosovo', 'KW'=>'Kuwait', 'KG'=>'Kyrgyzstan', 
					'LA'=>'Lao People\'s Democratic Republic', 'LV'=>'Latvia', 'LB'=>'Lebanon', 'LS'=>'Lesotho', 'LR'=>'Liberia', 
					'LY'=>'Libya', 'LI'=>'Liechtenstein', 'LT'=>'Lithuania', 'LU'=>'Luxembourg', 'MK'=>'Macedonia', 'MG'=>'Madagascar', 
					'MW'=>'Malawi', 'MY'=>'Malaysia', 'ML'=>'Mali', 'MT'=>'Malta', 'MQ'=>'Martinique', 'MR'=>'Mauritania', 
					'MU'=>'Mauritius', 'MX'=>'Mexico', 'MD'=>'Moldova', 'MN'=>'Mongolia', 'ME'=>'Montenegro', 'MS'=>'Montserrat', 
					'MA'=>'Morocco', 'MZ'=>'Mozambique', 'MM'=>'Myanmar', 'NA'=>'Namibia', 'NR'=>'Nauru', 'NP'=>'Nepal', 
					'NL'=>'Netherlands', 'AN'=>'Netherlands Antilles', 'NC'=>'New Caledonia', 'NZ'=>'New Zealand', 'NI'=>'Nicaragua', 
					'NE'=>'Niger', 'NG'=>'Nigeria', 'NO'=>'Norway', 'OM'=>'Oman', 'PK'=>'Pakistan', 'PW'=>'Palau', 
					'PS'=>'Palestinian Territories', 'PA'=>'Panama', 'PG'=>'Papua New Guinea', 'PY'=>'Paraguay', 'PE'=>'Peru', 
					'PH'=>'Philippines', 'PL'=>'Poland', 'PT'=>'Portugal', 'PR'=>'Puerto Rico', 'QA'=>'Qatar', 'RE'=>'Reunion', 
					'RO'=>'Romania', 'RU'=>'Russian Federation', 'RW'=>'Rwanda', 'KN'=>'Saint Kitts and Nevis', 'LC'=>'Saint Lucia', 
					'MF'=>'Saint Martin', 'VC'=>'Saint Vincent and Grenadines', 'WS'=>'Samoa', 'ST'=>'Sao Tome and Principe', 
					'SA'=>'Saudi Arabia', 'SN'=>'Senegal', 'RS'=>'Serbia', 'SL'=>'Sierra Leone', 'SG'=>'Singapore', 'SK'=>'Slovakia', 
					'SI'=>'Slovenia', 'SB'=>'Solomon Islands', 'SO'=>'Somalia', 'ZA'=>'South Africa', 
					'GS'=>'South Georgia & South Sandwich Islands', 'ES'=>'Spain', 'LK'=>'Sri Lanka', 'SD'=>'Sudan', 
					'SR'=>'Suriname', 'SJ'=>'Svalbard and Jan Mayen', 'SZ'=>'Swaziland', 'SE'=>'Sweden', 'CH'=>'Switzerland', 
					'SY'=>'Syrian Arab Republic', 'TW'=>'Taiwan', 'TJ'=>'Tajikistan', 'TZ'=>'Tanzania', 'TH'=>'Thailand', 
					'TL'=>'Timor-leste', 'TG'=>'Togo', 'TO'=>'Tonga', 'TT'=>'Trinidad and Tobago', 'TN'=>'Tunisia', 'TR'=>'Turkey', 
					'TM'=>'Turkmenistan', 'TC'=>'Turks and Caicos Islands', 'UG'=>'Uganda', 'UA'=>'Ukraine', 'AE'=>'United Arab Emirates', 
					'GB'=>'United Kingdom', 'US'=>'United States', 'UY'=>'Uruguay', 'UZ'=>'Uzbekistan', 'VU'=>'Vanuatu', 'VE'=>'Venezuela', 
					'VN'=>'Viet Nam', 'VG'=>'Virgin Islands, British', 'VI'=>'Virgin Islands, US', 'EH'=>'Western Sahara', 'YE'=>'Yemen', 
					'ZM'=>'Zambia', 'ZW'=>'Zimbabwe', 'GG'=>'Guernsey', 'JE'=>'Jersey', 'IM'=>'Isle Of Man', 'MV'=>'Maldives');
			array_multisort( $countries, SORT_ASC );
			return $countries;
		}
	
	}
	
}