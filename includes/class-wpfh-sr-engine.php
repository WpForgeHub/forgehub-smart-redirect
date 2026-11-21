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
		$uri      = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$raw_path = wp_parse_url( $uri, PHP_URL_PATH );
		$path     = trim( is_string( $raw_path ) ? $raw_path : '', '/' );

		// 1. Check Custom 301/302 Redirect Rules (supports both from/to/status and legacy source/target/code).
		$custom_redirects = get_option( 'wpfh_sr_custom_redirects', array() );
		if ( ! empty( $custom_redirects ) && is_array( $custom_redirects ) ) {
			foreach ( $custom_redirects as $rule ) {
				if ( ! is_array( $rule ) ) {
					continue;
				}
				$raw_from = isset( $rule['from'] ) ? (string) $rule['from'] : ( isset( $rule['source'] ) ? (string) $rule['source'] : '' );
				$raw_to   = isset( $rule['to'] ) ? (string) $rule['to'] : ( isset( $rule['target'] ) ? (string) $rule['target'] : '' );
				if ( '' === trim( $raw_from ) || '' === trim( $raw_to ) ) {
					continue;
				}
				$source = trim( $raw_from, '/' );
				// Never match empty source against homepage unless explicitly configured as '/'.
				if ( '' === $source && '/' !== trim( $raw_from ) ) {
					continue;
				}
				if ( strtolower( $source ) === strtolower( $path ) ) {
					$dest_url = esc_url_raw( $raw_to );
					if ( '' === $dest_url ) {
						continue;
					}
					// Prevent self-redirect loop.
					$dest_path = wp_parse_url( $dest_url, PHP_URL_PATH );
					$dest_host = wp_parse_url( $dest_url, PHP_URL_HOST );
					$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
					if ( ( empty( $dest_host ) || strtolower( (string) $dest_host ) === strtolower( (string) $site_host ) )
						&& strtolower( trim( is_string( $dest_path ) ? $dest_path : '', '/' ) ) === strtolower( $path )
					) {
						continue;
					}
					$status = isset( $rule['status'] ) ? intval( $rule['status'] ) : ( isset( $rule['code'] ) ? intval( $rule['code'] ) : 301 );
					if ( ! in_array( $status, array( 301, 302, 307, 308 ), true ) ) {
						$status = 301;
					}
					// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Admin-configured redirects may target external URLs.
					wp_redirect( $dest_url, $status );
					exit;
				}
			}
		}

		// 2. 404 Auto-Healing
		if ( is_404() ) {
			$this->handle_404_auto_healing( $path );
		}
	}

	/**
	 * Fuzzy matching 404 auto-healer
	 */
	private function handle_404_auto_healing( $path ) {
		// Never 301-redirect missing static asset files to HTML pages.
		$static_ext = strtolower( (string) pathinfo( $path, PATHINFO_EXTENSION ) );
		if ( in_array( $static_ext, array( 'css', 'js', 'map', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'avif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'otf', 'xml', 'txt', 'json', 'pdf', 'zip', 'mp4', 'webm', 'mp3' ), true ) ) {
			return;
		}

		$segments    = explode( '/', $path );
		$target_slug = end( $segments );

		if ( empty( $target_slug ) ) {
			return;
		}

		// Clean common file suffixes
		$clean_slug = str_replace( array( '.html', '.htm', '.php', '.asp', '.aspx' ), '', $target_slug );

		global $wpdb;
		// Fetch published post/page/product slugs
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- slug lookup on a 404 fallback path.
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
		do_action( 'wpfh_sr_on_404_recorded', $path, $ip );
	}
}
