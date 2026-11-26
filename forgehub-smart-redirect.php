<?php
/**
 * Plugin Name:       ForgeHub Smart Redirect
 * Plugin URI:        https://wpforgehub.com/product/smart-redirect/
 * Description:       Intelligent 404 auto-healing with fuzzy slug matching, unresolved 404 logging, and custom 301/302 redirect management.
 * Version:           1.1.5
 * Author:            WPForgeHub
 * Author URI:        https://wpforgehub.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       forgehub-smart-redirect
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPFH_SR_VERSION', '1.1.5' );
define( 'WPFH_SR_FILE', __FILE__ );
define( 'WPFH_SR_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPFH_SR_URL', plugin_dir_url( __FILE__ ) );

// <wpfh-pro-only>
/**
 * Freemius SDK integration wrapper
 */
if ( ! function_exists( 'wpfh_sr_fs' ) ) {
	function wpfh_sr_fs() {
		global $wpfh_sr_fs;

		if ( ! isset( $wpfh_sr_fs ) ) {
			$fs_path = WPFH_SR_PATH . 'freemius/start.php';
			if ( file_exists( $fs_path ) ) {
				require_once $fs_path;
				$wpfh_sr_fs = fs_dynamic_init( array(
					'id'             => '45084',
					'slug'           => 'forgehub-smart-redirect',
					'type'           => 'plugin',
					'public_key'     => 'pk_af5c1cdaff59df492e19650419acc',
					'is_premium'     => false,
					'has_addons'     => false,
					'has_paid_plans' => true,
					'menu'           => array(
						'slug'       => 'forgehub-smart-redirect',
						'first-path' => 'tools.php?page=forgehub-smart-redirect',
						'parent'     => array(
							'slug' => 'tools.php',
						),
					),
				) );
				// Freemius forbids uninstall.php in premium builds; use its after_uninstall hook there.
				$wpfh_sr_fs->add_action( 'after_uninstall', 'wpfh_sr_uninstall_cleanup' );
			} else {
				// Fallback object for Free / Filter-based Pro distribution
				$wpfh_sr_fs = new class {
					public function can_use_premium_code() {
						// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
						return (bool) apply_filters( 'fs_can_use_premium_code__forgehub-smart-redirect', false );
					}
					public function is__premium_only() { return false; }
					public function is_plan( $plan ) {
						// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
						return (bool) apply_filters( 'fs_is_plan__forgehub-smart-redirect', false, $plan );
					}
					public function get_upgrade_url() {
						return 'https://wpforgehub.com/product/smart-redirect/#pricing';
					}
				};
			}
		}

		return $wpfh_sr_fs;
	}

	wpfh_sr_fs();
}

/**
 * Removes plugin options (Freemius premium build; free build uses uninstall.php).
 */
function wpfh_sr_uninstall_cleanup() {
	delete_option( 'wpfh_sr_custom_redirects' );
	delete_option( 'wpfh_sr_heal_log' );
	delete_option( 'wpfh_sr_404_log' );
	delete_option( 'wpfh_sr_click_stats' );
	delete_option( 'wpfh_sr_regex_redirects' );
	delete_option( 'wpfh_sr_404_alert_threshold' );
	delete_option( 'wpfh_sr_alert_webhook_url' );
	delete_transient( 'wpfh_sr_404_spike_counter' );
	delete_transient( 'wpfh_sr_spike_alert_sent' );
}
// </wpfh-pro-only>

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

		// <wpfh-pro-only>
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
		// </wpfh-pro-only>
	}
}

WPFH_Smart_Redirect::get_instance();
