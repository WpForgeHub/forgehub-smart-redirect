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
delete_option( 'wpfh_sr_regex_redirects' );
delete_option( 'wpfh_sr_404_alert_threshold' );
delete_option( 'wpfh_sr_alert_webhook_url' );
delete_option( 'wpfh_sr_review_nudge' );
delete_metadata( 'user', 0, 'wpfh_sr_review_nudge_dismissed', '', true );
delete_transient( 'wpfh_sr_404_spike_counter' );
delete_transient( 'wpfh_sr_spike_alert_sent' );
