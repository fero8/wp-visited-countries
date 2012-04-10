<div class="wrap nosubsub">
	<div id="icon-tools" class="icon32"></div><h2><?php _e( 'General Settings', 'wpvc-countries' ) ?></h2>	
	<form method="post" action="options.php">
		<?php 
		settings_fields( WPVC_SETTINGS_KEY ); 
		do_settings_sections( WPVC_SETTINGS_KEY ); 
		?>
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'wpvc-countries' ) ?>" />
			<input type="submit" class="button-secondary" name="wpvc_settings[reset]" value="<?php _e( 'Reset Defaults', 'wpvc-countries' ) ?>" />
		</p>					
	</form>
</div>