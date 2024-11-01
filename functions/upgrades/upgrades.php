<?php
/**
 * Upgrade Screen
 *
 * @package     Secure Login
 * Copyright (C) 2013  Siri Iinnovations
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 *
 */
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


function ap_custom_login_upgrades_screen() {		
	$login = CUSTOMLOGIN(); ?>
	<div class="wrap">
		<h2>//<?php  _e( 'Custom Login - Upgrades', $login->domain ); ?></h2>
		<div id="custom-login-upgrade-status">
			<p>
				<?php _e( 'The upgrade process has started, please be patient. This could take several minutes. You will be automatically redirected when the upgrade is finished.', $login->domain ); ?>
				<img src="<?php echo esc_url( admin_url( 'images/loading.gif' ) ); ?>" id="custom-login-upgrade-loader"/>
			</p>
		</div>
		<script type="text/javascript">
			jQuery( document ).ready( function() {
				// Trigger upgrades on page load
				var data = { action: 'custom_login_trigger_upgrades' };
		        jQuery.post( ajaxurl, data, function (response) {
		        	if ( response == 'complete' ) {
			        	jQuery('#custom-login-upgrade-loader').hide();
			        	document.location.href = 'index.php?page=custom-login-about'; // Redirect to the welcome page
					}
		        });
			});
		</script>
	</div>
	<?php
}


function ap_custom_login_upgrades_page() {		
	$login = CUSTOMLOGIN();
	add_submenu_page( null, __( 'Custom Login Upgrades', $login->domain ), __( 'Custom Login Upgrades', $login->domain ), 'update_plugins', 'custom-login-upgrades', 'ap_custom_login_upgrades_screen' );
}
add_action( 'admin_menu', 'ap_custom_login_upgrades_page', 10 );