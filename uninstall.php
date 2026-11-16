<?php
/**
 * Smart Redirect Uninstall
 *
 * @package WPFH_Smart_Redirect
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wpfh_sr_custom_redirects' );
delete_option( 'wpfh_sr_heal_log' );
delete_option( 'wpfh_sr_404_log' );
delete_option( 'wpfh_sr_click_stats' );
