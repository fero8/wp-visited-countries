<?php
$current_action = $wpvc_list_table->current_action();

if ( 'edit' === $current_action ) {
	
	$default = false;
	$text_page_title = __( 'Edit Country', 'wpvc-plugin' );
	$button_name = __( 'Save Changes', 'wpvc-plugin' );
	
} else {
	$default = true;
	$text_page_title = __( 'Manage Countries', 'wpvc-plugin' );
	$button_name = __( 'Add New Country', 'wpvc-plugin' );
	$wpvc_list_table->prepare_items();
}

?>

<div class="wrap nosubsub">
	<?php screen_icon( 'tools' ); ?><h2><?php echo $text_page_title ?></h2>
	
<?php
if ( isset($_REQUEST['deleted']) ) {

	echo '<div id="message" class="updated"><p>';
	$deleted = (int) $_REQUEST[ 'deleted' ];
	printf( _n( '%s country deleted', '%s countries deleted', $deleted, 'wpvc-plugin' ), $deleted );
	echo '</p></div>';
	$_SERVER[ 'REQUEST_URI' ] = remove_query_arg( array( 'deleted' ), $_SERVER[ 'REQUEST_URI' ] );

}

if ($default) {
?>
	<div id="col-right">
		<div class="col-wrap">
			<form method="get" id="countries-form">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php 
				$wpvc_list_table->search_box( __( 'Search Countries', 'wpvc-plugin' ), 'country' );
				$wpvc_list_table->display();
				?>
			</form>
		</div> <!--col-wrap-->
	</div> <!--col-right-->
	
	<div id="col-left">
		<div class="col-wrap">
<?php } ?>

<div class="form-wrap">
	<form method="post" action="options.php">
		<?php settings_fields( WPVC_ADD_COUNTRIES_KEY ); ?>
		
		<?php ($default) ? do_settings_sections( WPVC_ADD_COUNTRIES_KEY ) : do_settings_sections( WPVC_EDIT_COUNTRIES_KEY ); ?>
		
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php echo $button_name ?>" />
		</p>					
	</form>
</div> <!--form-wrap-->

<?php
if ($default) {
?>	

		</div> <!--col-wrap-->
	</div> <!--col-left-->
	
<?php }?>
	
</div><!--wrap-->
