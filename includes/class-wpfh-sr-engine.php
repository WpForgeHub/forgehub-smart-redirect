<?php
/**
 * Smart Redirect & 404 Auto-Healer Engine
 *
 * @package WPFH_Smart_Redirect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPFH_SR_Engine {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'template_redirect', array( $this, 'intercept_requests' ), 1 );
	}

	public function intercept_requests() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$path = trim( wp_parse_url( $uri, PHP_URL_PATH ), '/' );

		// 1. Check Outbound / Upgrade Route Tracker: /wpfh-pro/{slug} or /upgrade/{slug}
		if ( preg_match( '#^(wpfh-pro|upgrade)/([a-zA-Z0-9_-]+)$#', $path, $matches ) ) {
			$slug = sanitize_key( $matches[2] );
			$this->track_upgrade_click( $slug );

			// Redirect to target product or home
			$target_url = $this->get_upgrade_target( $slug );
			wp_safe_redirect( $target_url, 302 );
			exit;
		}

		// 2. Check Custom 301/302 Redirect Rules
		$custom_redirects = get_option( 'wpfh_sr_custom_redirects', array() );
		if ( ! empty( $custom_redirects ) && is_array( $custom_redirects ) ) {
			foreach ( $custom_redirects as $rule ) {
				$source = trim( $rule['from'], '/' );
				if ( strtolower( $source ) === strtolower( $path ) ) {
					$status = isset( $rule['status'] ) ? intval( $rule['status'] ) : 301;
					wp_safe_redirect( esc_url_raw( $rule['to'] ), $status );
					exit;
				}
			}
		}

		// 3. 404 Auto-Healing
		if ( is_404() ) {
			$this->handle_404_auto_healing( $path );
		}
	}

	/**
	 * Track upgrade and outbound clicks
	 */
	private function track_upgrade_click( $slug ) {
		$stats = get_option( 'wpfh_sr_click_stats', array() );
		if ( ! isset( $stats[ $slug ] ) ) {
			$stats[ $slug ] = array(
				'clicks'     => 0,
				'last_click' => '',
			);
		}
		$stats[ $slug ]['clicks']++;
		$stats[ $slug ]['last_click'] = current_time( 'mysql' );
		update_option( 'wpfh_sr_click_stats', $stats );
	}

	private function get_upgrade_target( $slug ) {
		// Map slugs to product URLs
		$map = array(
			'aat'           => home_url( '/product/ai-alt-text-pro/' ),
			'alt-text'      => home_url( '/product/ai-alt-text-pro/' ),
			'portal'        => home_url( '/product/wpfh-customer-portal-pro/' ),
			'checkout'      => home_url( '/product/wpfh-frictionless-checkout-pro/' ),
			'freemius'      => home_url( '/product/wpfh-freemius-bridge-pro/' ),
			'image-guard'   => home_url( '/product/wpfh-image-memory-guard-pro/' ),
		);

		return isset( $map[ $slug ] ) ? $map[ $slug ] : home_url( '/plugins/' );
	}

	/**
	 * Fuzzy matching 404 auto-healer
	 */
	private function handle_404_auto_healing( $path ) {
		$segments = explode( '/', $path );
		$target_slug = end( $segments );

		if ( empty( $target_slug ) ) {
			return;
		}

		// Clean common prefixes or suffixes
		$clean_slug = str_replace( array( 'wpfh-', '-free', '-pro', '.html', '.php' ), '', $target_slug );

		global $wpdb;
		// Fetch published post/page/product slugs
		$posts = $wpdb->get_results( "
			SELECT ID, post_name, post_type 
			FROM {$wpdb->posts} 
			WHERE post_status = 'publish' 
			AND post_type IN ('post', 'page', 'product') 
			LIMIT 200
		" );

		if ( empty( $posts ) ) {
			return;
		}

		$best_match_id   = 0;
		$highest_percent = 0;

		foreach ( $posts as $p ) {
			similar_text( $clean_slug, $p->post_name, $percent );
			if ( $percent > $highest_percent ) {
				$highest_percent = $percent;
				$best_match_id   = $p->ID;
			}
		}

		// If confidence is >= 65%, perform 301 auto-heal redirect
		if ( $highest_percent >= 65 && $best_match_id > 0 ) {
			$this->log_auto_heal( $path, get_permalink( $best_match_id ), round( $highest_percent, 1 ) );
			wp_safe_redirect( get_permalink( $best_match_id ), 301 );
			exit;
		}

		// Otherwise log as unresolved 404
		$this->log_404( $path );
	}

	private function log_auto_heal( $from, $to, $score ) {
		$log = get_option( 'wpfh_sr_heal_log', array() );
		array_unshift( $log, array(
			'from'      => $from,
			'to'        => $to,
			'score'     => $score,
			'timestamp' => current_time( 'mysql' ),
		) );
		$log = array_slice( $log, 0, 50 );
		update_option( 'wpfh_sr_heal_log', $log );
	}

	private function log_404( $path ) {
		$log = get_option( 'wpfh_sr_404_log', array() );
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		array_unshift( $log, array(
			'path'      => $path,
			'ip'        => $ip,
			'timestamp' => current_time( 'mysql' ),
		) );
		$log = array_slice( $log, 0, 50 );
		update_option( 'wpfh_sr_404_log', $log );
	}
}
