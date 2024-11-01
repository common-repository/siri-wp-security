<?php
/**
 * Upgrade Functions
 *
 * @package     Secure Login
 * Copyright (C) 2013  Siri Iinnovations
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 *
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function ap_custom_login_show_upgrade_notices() {
	if ( isset( $_GET['page'] ) && $_GET['page'] == 'custom-login-upgrades' )
		return; // Don't show notices on the upgrades page
	
	$login = CUSTOMLOGIN();
	
	$old_settings = get_option( 'custom_login_settings' );
	
	/* New install */
	if ( empty( $old_settings ) )
		return;
	
	if ( !empty( $old_settings ) && !empty( $old_settings['version'] ) )
		$cl_version = $old_settings['version'];
	else
		$cl_version = $login->version;
	

}
add_action( 'admin_notices', 'ap_custom_login_show_upgrade_notices' );


function ap_custom_login_trigger_upgrades() {
	$login = CUSTOMLOGIN();
	
	$old_settings = get_option( 'custom_login_settings' );
	
	if ( !empty( $old_settings ) && !empty( $old_settings['version'] ) )
		$cl_version = $old_settings['version'];
	else
		$cl_version = $login->version;

	if ( version_compare( $cl_version, '2.0', '<' ) ) {
		ap_custom_login_v2_0_0_upgrades();
	}

	if ( DOING_AJAX )
		die( 'complete' ); // Let ajax know we are done
}
add_action( 'wp_ajax_custom_login_trigger_upgrades', 'ap_custom_login_trigger_upgrades' );


function ap_custom_login_v2_0_0_upgrades() {
	$login = CUSTOMLOGIN();
	$old_settings = get_option( 'custom_login_settings' );
	$new_settings = get_option( $login->id, array() );
		
	$new_settings['active'] = $login->version;
	$new_settings['active'] = true === $old_settings['custom'] ? 'on' : 'off';
	$new_settings['html_background_color'] = is_rgba( $old_settings['html_background_color'] ) ? rgba2hex( $old_settings['html_background_color'] ) : $old_settings['html_background_color'];
    $new_settings['html_background_color_checkbox'] = 'off';
    $new_settings['html_background_color_opacity'] = '';
    $new_settings['html_background_url'] = $old_settings['html_background_url'];
    $new_settings['html_background_position'] = 'left top';
    $new_settings['html_background_repeat'] = $old_settings['html_background_repeat'];
    $new_settings['html_background_size'] = $old_settings['html_background_size'];
	$new_settomgs['hide_wp_logo'] = 'on';
    $new_settings['logo_background_url'] = $old_settings['login_form_logo'];
    $new_settings['logo_background_position'] = 'top center';
    $new_settings['logo_background_repeat'] = '';
    $new_settings['logo_background_size'] = '';
    $new_settings['login_form_background_color'] = is_rgba( $old_settings['html_background_color'] ) ? rgba2hex( $old_settings['login_form_background_color'] ) : $old_settings['login_form_background_color'];
    $new_settings['login_form_background_color_checkbox'] = 'off';
    $new_settings['login_form_background_color_opacity'] = '';
    $new_settings['login_form_background_url'] = $old_settings['login_form_background'];
    $new_settings['login_form_background_position'] = '';
    $new_settings['login_form_background_repeat'] = '';
    $new_settings['login_form_background_size'] = $old_settings['login_form_background_size'];
    $new_settings['login_form_border_radius'] = $old_settings['login_form_border_radius'];
    $new_settings['login_form_border_size'] = $old_settings['login_form_border'];
    $new_settings['login_form_border_color'] = is_rgba( $old_settings['html_background_color'] ) ? rgba2hex( $old_settings['login_form_border_color'] ) : $old_settings['login_form_border_color'];
    $new_settings['login_form_border_color_checkbox'] = 'off';
    $new_settings['login_form_border_color_opacity'] = '';
    $new_settings['login_form_box_shadow'] = $old_settings['login_form_box_shadow_1'] . 'px ' . $old_settings['login_form_box_shadow_2'] . 'px ' . $old_settings['login_form_box_shadow_3'] . 'px';
    $new_settings['login_form_box_shadow_color'] = is_rgba( $old_settings['html_background_color'] ) ? rgba2hex( $old_settings['login_form_box_shadow_4'] ) : $old_settings['login_form_box_shadow_4'];
    $new_settings['login_form_box_shadow_color_checkbox'] = 'off';
    $new_settings['login_form_box_shadow_color_opacity'] = '';
    $new_settings['label_color'] = is_rgba( $old_settings['html_background_color'] ) ? rgba2hex( $old_settings['label_color'] ) : $old_settings['label_color'];
    $new_settings['label_color_checkbox'] = 'off';
    $new_settings['label_color_opacity'] = '';
    $new_settings['nav_color'] = '';
    $new_settings['nav_color_checkbox'] = 'off';
    $new_settings['nav_color_opacity'] = '';
    $new_settings['nav_text_shadow_color'] = '';
    $new_settings['nav_text_shadow_color_checkbox'] = 'off';
    $new_settings['nav_text_shadow_color_opacity'] = '';
    $new_settings['nav_hover_color'] = '';
    $new_settings['nav_hover_color_checkbox'] = 'off';
    $new_settings['nav_hover_color_opacity'] = '';
    $new_settings['nav_text_shadow_hover_color'] = '';
    $new_settings['nav_text_shadow_hover_color_checkbox'] = 'off';
    $new_settings['nav_text_shadow_hover_color_opacity'] = '';
    $new_settings['custom_css'] = esc_attr( $old_settings['custom_css'] );
    $new_settings['custom_html'] = wp_specialchars_decode( stripslashes( $old_settings['custom_html'] ), 1, 0, 1 );
    $new_settings['custom_jquery'] = esc_html( $old_settings['custom_jquery'] );
	
	update_option( $login->id, $new_settings );
	delete_option( 'custom_login_settings' );
	return true;
}

