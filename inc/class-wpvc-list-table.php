<?php
if( !class_exists( 'WP_List_Table' ) ){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WPVC_Country_List_Table extends WP_List_Table {
	var $wpvc_nonce;

	function __construct(){
		global $status, $page;
		
		//Set parent defaults
		parent::__construct( array(
			'singular'  => 'country',     //singular name of the listed records
			'plural'    => 'countries',    //plural name of the listed records
			'ajax'      => false        //does this table support ajax?
		) );
		
		$this->wpvc_nonce = wp_create_nonce('wpvc_nonce_list_table');
	}

	function column_default( $item, $column_name ){
		$temp = $item[ $column_name ];
		
		switch( $column_name ){
		
		case 'txt_desc':
			return $temp;
		
		case 'hex_country':
			if( empty( $temp ) )
				return $temp;
			
			return '#' . $temp;
			
		case 'url_country':
			if( empty( $temp ) )
				return $temp;
			
			$shorten = substr( $temp, 0, 15 );
			
			if( $shorten !== $temp )
				$shorten = $shorten . '...';
			
			return sprintf( '<a href="http://%s" target="_blank">%s</a>', $temp, $shorten );
			
		case 'int_visited':
			return ( $temp == 1 ) ? __( 'Visited', 'wpvc-plugin' ) : __( 'Lived', 'wpvc-plugin' );
		
		default:
			return print_r( $item,true ); //Show the whole array for troubleshooting purposes
		}
	}
	
	function column_country_name( $item ){
		//Build row actions
		$key = $this->get_country_key( $item['country_name'] );
		
		$actions = array(
			'edit'      => sprintf( '<a href="?page=%s&action=%s&country=%s&_wpnonce=%s">%s</a>',
					$_REQUEST['page'], 'edit', $key, $this->wpvc_nonce, __( 'Edit', 'wpvc-plugin' ) ),
			'delete'    => sprintf( '<a href="?page=%s&action=%s&country=%s&_wpnonce=%s">%s</a>',
					$_REQUEST['page'], 'delete', $key, $this->wpvc_nonce, __( 'Delete', 'wpvc-plugin' ) ),
		);
		
		//Return the title contents
		return sprintf('<strong>%s</strong>%s', $this->get_country_name( $item['country_name'] ), $this->row_actions($actions));
	}

	function column_cb( $item ){
		return sprintf(
			'<input type="checkbox" name="country[]" value="%s" />',
			/*$2%s*/ $this->get_country_key( $item['country_name'] )                //The value of the checkbox should be the record's id
		);
	}

	function get_columns(){
		$columns = array(
			'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
			'country_name'     => __( 'Title', 'wpvc-plugin' ),
			'int_visited'    => __( 'Visited/Lived', 'wpvc-plugin' ),
			'txt_desc'  => __( 'Description', 'wpvc-plugin' ),
			'hex_country'  => __( 'Color', 'wpvc-plugin' ),
			'url_country'  => __( 'URL', 'wpvc-plugin' )
		);
		return $columns;
	}
	
	function get_sortable_columns() {
		$sortable_columns = array(
			'country_name'  => array( 'country_name', true ),     //true means its already sorted
			'int_visited'    => array( 'int_visited', false )
		);
		return $sortable_columns;
	}

	function get_bulk_actions() {
		$actions = array(
			'delete'    => 'Delete'
		);
		return $actions;
	}
	
	function process_bulk_action() {
		//global $wpdb;
		//echo "<br /><br />-------------------- process_bulk_action <br /><br />";
		//print_r($_REQUEST);
		if( isset( $_REQUEST['country'] ) && empty( $_REQUEST['country'] ) )
			$_REQUEST['country'] = 0;
		
		if( 'delete' === $this->current_action() ) {
			$options = get_option( WPVC_ADD_COUNTRIES_KEY );
			update_option( WPVC_ADD_COUNTRIES_KEY, $options );
		}
		
	}
	
	function prepare_items() {
		global $wpdb;
		
		/**
		 * First, lets decide how many records per page to show
		 */
		$per_page = 10;
		
		
		/**
		 * REQUIRED. Now we need to define our column headers. This includes a complete
		 * array of columns to be displayed (slugs & titles), a list of columns
		 * to keep hidden, and a list of columns that are sortable. Each of these
		 * can be defined in another method (as we've done here) before being
		 * used to build the value for our _column_headers property.
		 */
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		
		
		/**
		 * REQUIRED. Finally, we build an array to be used by the class for column 
		 * headers. The $this->_column_headers property takes an array which contains
		 * 3 other arrays. One for all columns, one for hidden columns, and one
		 * for sortable columns.
		 */
		$this->_column_headers = array( $columns, $hidden, $sortable );
		
		$this->process_bulk_action();
		
		$data = get_option( WPVC_ADD_COUNTRIES_KEY );
		
		if( isset( $_GET[ 's' ] ) ) {
			if( empty( $_GET[ 's' ] ) ) {
				wp_redirect( remove_query_arg('s') );
			} else
				$data = $this->get_search_results( $data, $_GET['s'] );
		}
		
		function usort_reorder( $a, $b ){
			$orderby = ( !empty($_REQUEST['orderby']) ) ? $_REQUEST['orderby'] : 'country_name';	//If no sort, default to title
			$order = ( !empty($_REQUEST['order']) ) ? $_REQUEST['order'] : 'asc';					//If no order, default to asc
			$result = strcmp( $a[$orderby], $b[$orderby] ); 										//Determine sort order
			return ( $order==='asc' ) ? $result : -$result; 										//Send final sort direction to usort
		}
		usort($data, 'usort_reorder');
		
		
		//pagination
		$current_page = $this->get_pagenum();
		$total_items = count( $data );
		$data = array_slice( $data, ( ( $current_page-1 ) * $per_page ), $per_page );
		
		$this->items = $data;
		
		$this->set_pagination_args( array(
			'total_items' => $total_items,                  //WE have to calculate the total number of items
			'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
			'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
		) );
	}
	
	private function get_search_results( $countries, $query ) {
		$results = array();
		
		foreach( $countries as $key => $country ) {
			$country_name = $this->get_country_name( $country[ 'country_name' ] );
			
			if( preg_match( '/'.$query.'/i', $country_name ) )
				$results[ $key ] = $country;
		}
		
		return $results;
	}
		
	private function get_country_key( $name ) {
		$temp = explode( "_", $name );
		return $temp[1];
	}
	private function get_country_name( $name ) {
		$temp = explode( "_", $name );
		return $temp[0];
	}
	
}
?>