<?php
/**
 * Plugin Name:       WPFH Smart Redirect & 404 Auto-Healer
 * Plugin URI:        https://wpforgehub.com/plugins/wpfh-smart-redirect/
 * Description:       Intelligent 404 auto-healing with fuzzy slug matching, outbound upgrade click tracker, and instant 301 redirect management.
 * Version:           1.0.0
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

define( 'WPFH_SR_VERSION', '1.0.0' );
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
			}
		}

		return $wpfh_sr_fs;
	}

	if ( file_exists( WPFH_SR_PATH . 'freemius/start.php' ) ) {
		wpfh_sr_fs();
	}
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
		load_plugin_textdomain( 'wpfh-smart-redirect', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		require_once WPFH_SR_PATH . 'includes/class-wpfh-sr-engine.php';
		require_once WPFH_SR_PATH . 'includes/class-wpfh-sr-admin.php';

		WPFH_SR_Engine::get_instance();
		WPFH_SR_Admin::get_instance();
	}
}

WPFH_Smart_Redirect::get_instance();
