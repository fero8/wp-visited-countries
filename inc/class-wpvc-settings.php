<?php
/**
 * Class for handling settings admin pages
 *
 * @package WPVC
 */

if ( ! class_exists( 'WPVC_Settings' ) ) {
	
	class WPVC_Settings extends WPVC_Base {
		
		function __construct( $activate  = false ) {
			
			parent::__construct( WPVC_SETTINGS_KEY, $this->generate_defaults(), 'wpvc-settings', 'ammap_settings', $activate );
			
			add_action( 'admin_init', array( &$this, 'init' ) );
			
		}
		
		private function generate_defaults() {
			$d = array();
			$d['txt_visited'] = "I've visited";
			$d['txt_lived'] = "I've lived in";
			$d['txt_total'] = "Total: ";
			
			$d['int_map_width'] = WPVC_DEFAULT_MAP_WIDTH;
			$d['int_map_height'] = WPVC_DEFAULT_MAP_HEIGHT;	
			$d['hex_water'] = WPVC_DEFAULT_MAP_WATER;
			$d['hex_visited'] = 'CC0000';
			$d['hex_not_visited'] = '00B82E';
			$d['hex_normal'] = 'B8B8B8';
			$d['hex_hover'] = 'FF0A0A';
			
			$d['bool_smap'] = 'true';
			$d['hex_smap_bg'] = '444444';
			$d['hex_smap'] = 'AFEA48';
			$d['hex_smap_border'] = 'FFFFFF';
			$d['hex_smap_rectangle'] = 'FFFFFF';
			$d['hex_smap_button'] = '000000';
			$d['pos_smap_button'] = 'BL';
			
			$d['bool_zoom'] = 'true';
			$d['hex_zoom_bg'] = 'FFCC00';
			$d['hex_zoom_hover'] = 'FF6600';
			$d['hex_zoom_outline'] = 'FFFFFF';
			
			$d['int_balloon_txt'] = '12';
			$d['font_balloon_txt'] = 'Tahoma';
			$d['hex_balloon_bg'] = 'FFFFFF';
			$d['hex_balloon_txt'] = '000000';
			
			return $d;
		}
		
		public function init() {
			parent::init();
			
			add_settings_section( $this->option_name.'_widget', 'Widget Settings', 
					array( &$this, 'print_section_content' ), $this->option_name );
			
			add_settings_section( $this->option_name.'_map', 'General Map Settings', 
					array( &$this, 'print_section_content' ), $this->option_name );
			
			add_settings_section( $this->option_name.'_smap', 'Small Map Settings', 
					array( &$this, 'print_section_content' ), $this->option_name );
			
			add_settings_section( $this->option_name.'_zoom', 'Zoom Settings', 
					array( &$this, 'print_section_content' ), $this->option_name );
			
			add_settings_section( $this->option_name.'_balloon', 'Description Balloon Settings', 
					array( &$this, 'print_section_content' ), $this->option_name );
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
		public function validate( $input ) {
			$this->verify_nonce( $this->option_name.'_nonce', 'edit_settings' );
			
			if( isset( $input['reset'] ) || !empty( $input['reset'] ) ) {
				// Reset button is clicked
				// replace the inputs with the default values
				$input = $this->get_defaults();
			} else {
				// Saving the changes and verifying the values
				$input = $this->verify_type( $input );
			}
			
			$this->prepare_xml( $input );
			return $input;
		}
		
		public function print_section_content() {
			echo wp_nonce_field( 'edit_settings', $this->option_name.'_nonce' );
		}
		
		/**
		 * Defines and adds fields
		 *
		 * @access public
		 */
		public function populate_fields() {
			
			$fields = array(
				array( 
					'id' => 'txt_visited',
					'type' => 'input',
					'title' => __( 'Text for countries visited', 'wpvc-plugin' ),
					'size' => '',
					'tags' => '',
					'prefield' => '',
					'postfield' => '',
					'section' => $this->option_name.'_widget'
				),
				array( 
					'id' => 'txt_lived',
					'type' => 'input',
					'title' => __( 'Text for countries lived', 'wpvc-plugin' ),
					'size' => '',
					'tags' => '',
					'prefield' => '',
					'postfield' => '',
					'section' => $this->option_name.'_widget'
				),
				array( 
					'id' => 'txt_total',
					'type' => 'input',
					'title' => __( 'Text for total countries', 'wpvc-plugin' ),
					'size' => '',
					'tags' => '',
					'prefield' => '',
					'postfield' => '',
					'section' => $this->option_name.'_widget'
				),
				array( 
					'id' => 'int_map_width',
					'type' => 'input',
					'title' => __( 'Map Width', 'wpvc-plugin' ),
					'size' => '4',
					'tags' => 'maxlength="4"',
					'prefield' => '',
					'postfield' => ' px',
					'section' => $this->option_name.'_map'
				),
				array( 
					'id' => 'int_map_height',
					'type' => 'input',
					'title' => __( 'Map Height', 'wpvc-plugin' ),
					'size' => '4',
					'tags' => 'maxlength="4"',
					'prefield' => '',
					'postfield' => ' px',
					'section' => $this->option_name.'_map'
				),
				array( 
					'id' => 'hex_water',
					'type' => 'input',
					'title' => __( 'Background color', 'wpvc-plugin' ),
					'size' => self::field_color_size,
					'tags' => 'maxlength="'.self::field_color_size.'"',
					'prefield' => '# ',
					'postfield' => $this->parse_desc( __( 'Background color', 'wpvc-plugin' ) . '. '
							. __( 'Default value', 'wpvc-plugin' ) . ': <code>'.$this->get_default('hex_water').'</code>'),
					'section' => $this->option_name.'_map'
				),
				array( 
					'id' => 'hex_normal',
					'type' => 'input',
					'title' => __( 'Default color', 'wpvc-plugin' ),
					'size' => self::field_color_size,
					'tags' => 'maxlength="'.self::field_color_size.'"',
					'prefield' => '# ',
					'postfield' => $this->parse_desc( __( 'Default color for countries', 'wpvc-plugin' ) . '. '
							. __( 'Default value', 'wpvc-plugin' ) . ': <code>'.$this->get_default('hex_normal').'</code>'),
					'section' => $this->option_name.'_map'
				),
				array( 
					'id' => 'hex_visited',
					'type' => 'input',
					'title' => __( 'Visited color', 'wpvc-plugin' ),
					'size' => self::field_color_size,
					'tags' => 'maxlength="'.self::field_color_size.'"',
					'prefield' => '# ',
					'postfield' => $this->parse_desc( __( 'Color for countries you have visited or traveled to', 'wpvc-plugin' ) . '. '
							. __( 'Default value', 'wpvc-plugin' ) . ': <code>'.$this->get_default('hex_visited').'</code>'),
					'section' => $this->option_name.'_map'
				),
				array( 
					'id' => 'hex_not_visited',
					'type' => 'input',
					'title' => __( 'Lived color', 'wpvc-plugin' ),
					'size' => self::field_color_size,
					'tags' => 'maxlength="'.self::field_color_size.'"',
					'prefield' => '# ',
					'postfield' => $this->parse_desc( __( 'Color for countries you have lived in', 'wpvc-plugin' ) . '. '
							. __( 'Default value', 'wpvc-plugin' ) . ': <code>'.$this->get_default('hex_not_visited').'</code>'),
					'section' => $this->option_name.'_map'
				),
				array( 
					'id' => 'hex_hover',
					'type' => 'input',
					'title' => __( 'Hover color', 'wpvc-plugin' ),
					'size' => self::field_color_size,
					'tags' => 'maxlength="'.self::field_color_size.'"',
					'prefield' => '# ',
					'postfield' => $this->parse_desc( __( 'Color used when a country region is hovered', 'wpvc-plugin' ) . '. '
							. __( 'Default value', 'wpvc-plugin' ) . ': <code>'.$this->get_default('hex_hover').'</code>'),
					'section' => $this->option_name.'_map'
				),
				array( 
					'id' => 'bool_smap',
					'type' => 'bool',
					'title' => __( 'Show small map?', 'wpvc-plugin' ),
					'size' => '',
					'tags' => '',
					'prefield' => '',
					'postfield' => '',
					'section' => $this->option_name.'_smap'
				),
				array( 
					'id' => 'hex_smap_bg',
					'type' => 'input',
					'title' => __( 'Background color', 'wpvc-plugin' ),
					'size' => self::field_color_size,
					'tags' => 'maxlength="'.self::field_color_size.'"',
					'prefield' => '# ',
					'postfield' => $this->parse_desc( __( 'Background color of a small map', 'wpvc-plugin' ) . '. '
							. __( 'Default value', 'wpvc-plugin' ) . ': <code>'.$this->get_default('hex_smap_bg').'</code>'),
					'section' => $this->option_name.'_smap'
				),
				array( 
					'id' => 'hex_smap',
					'type' => 'input',
					'title' => __( 'Country color', 'wpvc-plugin' ),
					'size' => self::field_color_size,
					'tags' => 'maxlength="'.self::field_color_size.'"',
					'prefield' => '# ',
					'postfield' => $this->parse_desc( __( 'Color of the countries on a small map', 'wpvc-plugin' ) . '. '
							. __( 'Default value', 'wpvc-plugin' ) . ': <code>'.$this->get_default('hex_smap').'</code>'),
					'section' => $this->option_name.'_smap'
				),
				array( 
					'id' => 'hex_smap_border',
					'type' => 'input',
					'title' => __( 'Border color', 'wpvc-plugin' ),
					'size' => self::field_color_size,
					'tags' => 'maxlength="'.self::field_color_size.'"',
					'prefield' => '# ',
					'postfield' => $this->parse_desc( __( 'Color for country\'s border on a small map', 'wpvc-plugin' ) . '. '
							. __( 'Default value', 'wpvc-plugin' ) . ': <code>'.$this->get_default('hex_smap_border').'</code>'),
					'section' => $this->option_name.'_smap'
				),
				array( 
					'id' => 'hex_smap_rectangle',
					'type' => 'input',
					'title' => __( 'Rectangle color', 'wpvc-plugin' ),
					'size' => self::field_color_size,
					'tags' => 'maxlength="'.self::field_color_size.'"',
					'prefield' => '# ',
					'postfield' => $this->parse_desc( __( 'Color of rectangle, indicating active area on a small map', 'wpvc-plugin' ) . '. '
							. __( 'Default value', 'wpvc-plugin' ) . ': <code>'.$this->get_default('hex_smap_rectangle').'</code>'),
					'section' => $this->option_name.'_smap'
				),
				array( 
					'id' => 'hex_smap_button',
					'type' => 'input',
					'title' => __( 'Collapse button color', 'wpvc-plugin' ),
					'size' => self::field_color_size,
					'tags' => 'maxlength="'.self::field_color_size.'"',
					'prefield' => '# ',
					'postfield' => $this->parse_desc( __( 'Colapse button arrow color (background color is the same ' 
							. 'as small map border color)', 'wpvc-plugin' ) . '. '
							. __( 'Default value', 'wpvc-plugin' ) . ': <code>'.$this->get_default('hex_smap_button').'</code>'),
					'section' => $this->option_name.'_smap'
				),
				array( 
					'id' => 'pos_smap_button',
					'type' => 'select_pos_button',
					'title' => __( 'Button position', 'wpvc-plugin' ),
					'size' => self::field_color_size,
					'tags' => 'maxlength="'.self::field_color_size.'"',
					'prefield' => '# ',
					'postfield' => $this->parse_desc( __( 'Position of the collapse button', 'wpvc-plugin' ) . '. '
							. __( 'Default value', 'wpvc-plugin' ) . ': <code>'.$this->get_default('pos_smap_button').'</code>'),
					'section' => $this->option_name.'_smap'
				),
				array( 
					'id' => 'bool_zoom',
					'type' => 'bool',
					'title' => __( 'Show zoom control?', 'wpvc-plugin' ),
					'size' => '',
					'tags' => '',
					'prefield' => '',
					'postfield' => '',
					'section' => $this->option_name.'_zoom'
				),
				array( 
					'id' => 'hex_zoom_bg',
					'type' => 'input',
					'title' => __( 'Background color', 'wpvc-plugin' ),
					'size' => self::field_color_size,
					'tags' => 'maxlength="'.self::field_color_size.'"',
					'prefield' => '# ',
					'postfield' => $this->parse_desc( __( 'Background color of "+", "-", scroller buttons and navigation arrows', 'wpvc-plugin' ) . '. '
							. __( 'Default value', 'wpvc-plugin' ) . ': <code>'.$this->get_default('hex_zoom_bg').'</code>'),
					'section' => $this->option_name.'_zoom'
				),
				array( 
					'id' => 'hex_zoom_hover',
					'type' => 'input',
					'title' => __( 'Hover color', 'wpvc-plugin' ),
					'size' => self::field_color_size,
					'tags' => 'maxlength="'.self::field_color_size.'"',
					'prefield' => '# ',
					'postfield' => $this->parse_desc( __( 'Hover color of "+", "-", scroller buttons and navigation arrows', 'wpvc-plugin' ) . '. '
							. __( 'Default value', 'wpvc-plugin' ) . ': <code>'.$this->get_default('hex_zoom_hover').'</code>'),
					'section' => $this->option_name.'_zoom'
				),
				array( 
					'id' => 'hex_zoom_outline',
					'type' => 'input',
					'title' => __( 'Outline color', 'wpvc-plugin' ),
					'size' => self::field_color_size,
					'tags' => 'maxlength="'.self::field_color_size.'"',
					'prefield' => '# ',
					'postfield' => $this->parse_desc( __( 'Outline color of "+", "-", scroller buttons and navigation arrows', 'wpvc-plugin' ) . '. '
							. __( 'Default value', 'wpvc-plugin' ) . ': <code>'.$this->get_default('hex_zoom_outline').'</code>'),
					'section' => $this->option_name.'_zoom'
				),
				array( 
					'id' => 'int_balloon_txt',
					'type' => 'input',
					'title' => __( 'Font size', 'wpvc-plugin' ),
					'size' => 2,
					'tags' => 'maxlength="'.self::field_color_size.'"',
					'prefield' => '',
					'postfield' => 'pt',
					'section' => $this->option_name.'_balloon'
				),
				array( 
					'id' => 'font_balloon_txt',
					'type' => 'select_font',
					'title' => __( 'Font name', 'wpvc-plugin' ),
					'size' => '',
					'tags' => '',
					'prefield' => '',
					'postfield' => '',
					'section' => $this->option_name.'_balloon'
				),
				array( 
					'id' => 'hex_balloon_txt',
					'type' => 'input',
					'title' => __( 'Text color', 'wpvc-plugin' ),
					'size' => self::field_color_size,
					'tags' => 'maxlength="'.self::field_color_size.'"',
					'prefield' => '# ',
					'postfield' => $this->parse_desc( __( 'Color for the text in the description balloon', 'wpvc-plugin' ) . '. '
							. __( 'Default value', 'wpvc-plugin' ) . ': <code>'.$this->get_default('hex_balloon_txt').'</code>' ),
					'section' => $this->option_name.'_balloon'
				),
				array( 
					'id' => 'hex_balloon_bg',
					'type' => 'input',
					'title' => __( 'Background color', 'wpvc-plugin' ),
					'size' => self::field_color_size,
					'tags' => 'maxlength="'.self::field_color_size.'"',
					'prefield' => '# ',
					'postfield' => $this->parse_desc( __( 'Background color for the description balloon', 'wpvc-plugin' ) . '. '
							. __( 'Default value', 'wpvc-plugin' ) . ': <code>'.$this->get_default('hex_balloon_bg').'</code>' ),
					'section' => $this->option_name.'_balloon'
				)
			);
			
			$this->add_fields( $fields, &$this );
			
		}
		
		
		/**
		 * Print each field according to its type
		 *
		 * @access public
		 *
		 * @param array $field
		 */
		public function print_fields( $field ) {
			
			$data = $this->get_option();
			
			switch( $field['type'] ) {
				
				case 'input':
					$this->print_input($field, $data);
				break;
				
				case 'bool':
					printf( 
						'<select name="%s" id="%s">'
						. '<option value="true"%s>%s</option>'
						. '<option value="false"%s>%s</option>'
						. '</select>'
						, $field['name']																	//select name
						, $field['name']																	//select id
						, ( isset($data) && $data['bool_smap'] == 'true' ) ? ' selected="selected"' : ''	//selected tag for "Show"
						, __( 'Show', 'wpvc-plugin' )
						, ( isset($data) && $data['bool_smap'] == 'false' ) ? ' selected="selected"' : ''	//selected tag for "Hide"
						, __( 'Hide', 'wpvc-plugin' )
					);
				break;
				
				case 'select_font':
					printf( 
						'<select name="%s" id="%s">'
						. '<option value="Tahoma"%s>Tahoma</option>'
						. '<option value="Times New Roman"%s>Times New Roman</option>'
						. '<option value="Verdana"%s>Verdana</option>'
						. '</select>'
						, $field['name'], $field['name']
						, ( isset($data) && $data['font_balloon_txt'] == 'Tahoma' ) ? ' selected="selected"' : ''
						, ( isset($data) && $data['font_balloon_txt'] == 'Times New Roman' ) ? ' selected="selected"' : ''
						, ( isset($data) && $data['font_balloon_txt'] == 'Verdana' ) ? ' selected="selected"' : ''
					);
				break;
				
				case 'select_pos_button':
					printf( 
						'<select name="%s" id="%s">'
						. '<option value="BL"%s>%s</option>'
						. '<option value="BR"%s>%s</option>'
						. '<option value="TL"%s>%s</option>'
						. '<option value="TR"%s>%s</option>'
						. '</select>'
						, $field['name'], $field['name']
						, ( isset($data) && $data['pos_smap_button'] == 'BL' ) ? ' selected="selected"' : ''
						, __( 'Bottom Left', 'wpvc-plugin' )
						, ( isset($data) && $data['pos_smap_button'] == 'BR' ) ? ' selected="selected"' : ''
						, __( 'Bottom Right', 'wpvc-plugin' )
						, ( isset($data) && $data['pos_smap_button'] == 'TL' ) ? ' selected="selected"' : ''
						, __( 'Top Left', 'wpvc-plugin' )
						, ( isset($data) && $data['pos_smap_button'] == 'TR' ) ? ' selected="selected"' : ''
						, __( 'Top Right', 'wpvc-plugin' )
					);
				break;
			}
		}
		
		/**
		 * Empty setting values are replaced with default values
		 *
		 * @access protected
		 *
		 * @param array $settings
		 * @return array
		 */
		protected function recheck_values( $settings ) {
			
			if( empty( $settings ) )
				$settings = $this->get_option();
			
			foreach( $settings as $key => $setting ) {
				$type = $this->get_type( $key );
				
				switch( $type ) {
					case 'hex':
						if( empty( $setting ) )
							$settings[ $key ] = $this->get_default( $key );
						
						$settings[ $key ] = $this->add_hashtag( $settings[ $key ] );
					break;
					
					default:
						if( empty( $setting ) )
							$settings[ $key ] = $this->get_default( $key );
					break;
				}
			}
			
			return $settings;
		}
		
		protected function prepare_xml( $settings = null ) {
			$settings = $this->recheck_values( $settings );
			
			$data = '<?xml version="1.0" encoding="UTF-8"?>'. "\n";
			$data .= "
<settings>
  <font>$settings[font_balloon_txt]</font>
  <redraw>true</redraw>
  <add_time_stamp>true</add_time_stamp>
  <color_change_time_start>0</color_change_time_start>
  <color_change_time_hover>0.1</color_change_time_hover>
    
  <background>
    <color>$settings[hex_smap_bg]</color>
  </background>
  
  <zoom>
    <enabled>$settings[bool_zoom]</enabled>
    <arrows_enabled>false</arrows_enabled>
    <color>$settings[hex_zoom_bg]</color>
    <color_hover>$settings[hex_zoom_hover]</color_hover>
    <alpha>70</alpha>
    <outline_color>$settings[hex_zoom_outline]</outline_color>
    <x>5</x>
    <y>30</y>
    <height>30%</height>
    <max>1500%</max>
  </zoom>
  
  <small_map>
    <enabled>$settings[bool_smap]</enabled>
    <x>0%</x>
    <color>$settings[hex_smap]</color>
    <border_width>2</border_width>
    <border_color>$settings[hex_smap_border]</border_color>
    <rectangle_color>$settings[hex_smap_rectangle]</rectangle_color>
    <collapse_button_color>$settings[hex_smap_button]</collapse_button_color>
    <collapse_button_position>$settings[pos_smap_button]</collapse_button_position>
  </small_map>  
  
  <area>                                                        
    <balloon_text>
      <![CDATA[<font size=\"$settings[int_balloon_txt]pt\"><b>{title}</b></font>{description}]]>
    </balloon_text>
    <active_only_if_value_set>true</active_only_if_value_set>
    <color_solid>$settings[hex_visited]</color_solid>
    <color_light>$settings[hex_not_visited]</color_light>
    <color_hover>$settings[hex_hover]</color_hover>
    <color_unlisted>$settings[hex_normal]</color_unlisted>
  </area>
  
  <legend>
    <enabled>false</enabled>
  </legend>
  
  <text_box>
    <enabled>false</enabled>
  </text_box>
  
  <balloon>
    <color>$settings[hex_balloon_bg]</color>
    <text_color>$settings[hex_balloon_txt]</text_color>
  </balloon>
</settings>
";
		
			$this->write_xml( $data );
		}
	}

}

?>