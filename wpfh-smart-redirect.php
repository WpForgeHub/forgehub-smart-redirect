<?php
/**
 * Plugin Name:       WPFH Smart Redirect & 404 Auto-Healer
 * Plugin URI:        https://wpforgehub.com/plugins/wpfh-smart-redirect/
 * Description:       Intelligent 404 auto-healing with fuzzy slug matching, outbound upgrade click tracker, and instant 301 redirect management.
 * Version:           1.1.1
 * Author:            WPForgeHub
 * Author URI:        https://wpforgehub.com
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpfh-smart-redirect
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPFH_SR_VERSION', '1.1.1' );
define( 'WPFH_SR_FILE', __FILE__ );
define( 'WPFH_SR_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPFH_SR_URL', plugin_dir_url( __FILE__ ) );

/**
 * Freemius SDK integration wrapper
 */
if ( ! function_exists( 'wpfh_sr_fs' ) ) {
	function wpfh_sr_fs() {
		global $wpfh_sr_fs;

		if ( ! isset( $wpfh_sr_fs ) ) {
			if ( file_exists( WPFH_SR_PATH . 'freemius/start.php' ) ) {
				require_once WPFH_SR_PATH . 'freemius/start.php';
			}

			if ( function_exists( 'fs_dynamic_init' ) ) {
				$wpfh_sr_fs = fs_dynamic_init( array(
					'id'             => '17964',
					'slug'           => 'wpfh-smart-redirect',
					'type'           => 'plugin',
					'public_key'     => 'pk_dummy_sr_placeholder_key',
					'is_premium'     => false,
					'has_addons'     => false,
					'has_paid_plans' => true,
					'menu'           => array(
						'slug'       => 'wpfh-smart-redirect',
						'first-path' => 'tools.php?page=wpfh-smart-redirect',
					),
				) );
			} else {
				// Fallback object for Free / Filter-based Pro distribution
				$wpfh_sr_fs = new class {
					public function can_use_premium_code() {
						// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
						return (bool) apply_filters( 'fs_can_use_premium_code__wpfh-smart-redirect', false );
					}
					public function is__premium_only() { return false; }
					public function is_plan( $plan ) {
						// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
						return (bool) apply_filters( 'fs_is_plan__wpfh-smart-redirect', false, $plan );
					}
					public function get_upgrade_url() {
						return 'https://wpforgehub.com/plugins/wpfh-smart-redirect/#pricing';
					}
				};
			}
		}

		return $wpfh_sr_fs;
	}

	wpfh_sr_fs();
}

/**
 * Main Plugin Class
 */
final class WPFH_Smart_Redirect {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ), 20 );
	}

	public function init() {
		// Note: load_plugin_textdomain is intentionally omitted as WordPress.org automatically loads translations since WP 4.6.

		require_once WPFH_SR_PATH . 'includes/class-wpfh-sr-engine.php';
		require_once WPFH_SR_PATH . 'includes/class-wpfh-sr-admin.php';

		WPFH_SR_Engine::get_instance();
		WPFH_SR_Admin::get_instance();

		// Pro Modules
		if ( function_exists( 'wpfh_sr_fs' ) && wpfh_sr_fs()->can_use_premium_code() ) {
			$pro_sentinel = WPFH_SR_PATH . 'includes/pro/class-wpfh-sr-sentinel.php';
			$pro_regex    = WPFH_SR_PATH . 'includes/pro/class-wpfh-sr-regex.php';
			$pro_alerts   = WPFH_SR_PATH . 'includes/pro/class-wpfh-sr-alerts.php';
			$pro_utm      = WPFH_SR_PATH . 'includes/pro/class-wpfh-sr-utm.php';

			if ( file_exists( $pro_sentinel ) ) {
				require_once $pro_sentinel;
				WPFH_SR_Sentinel::get_instance();
			}
			if ( file_exists( $pro_regex ) ) {
				require_once $pro_regex;
				WPFH_SR_Regex::get_instance();
			}
			if ( file_exists( $pro_alerts ) ) {
				require_once $pro_alerts;
				WPFH_SR_Alerts::get_instance();
			}
			if ( file_exists( $pro_utm ) ) {
				require_once $pro_utm;
				WPFH_SR_UTM::get_instance();
			}
		}
	}
}

WPFH_Smart_Redirect::get_instance();
