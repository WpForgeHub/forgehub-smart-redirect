<?php
/**
 * WPFH Smart Redirect — Pro Automated Slug Change Sentinel
 *
 * Automatically detects changes to post, page, and WooCommerce product slugs,
 * immediately creating 301 permanent redirects from old URLs to new URLs.
 *
 * @package WPForgeHub\SmartRedirect\Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPFH_SR_Sentinel {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'post_updated', array( $this, 'detect_slug_change' ), 10, 3 );
    }

    public function detect_slug_change( $post_id, $post_after, $post_before ) {
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        if ( $post_after->post_status !== 'publish' || $post_before->post_status !== 'publish' ) {
            return;
        }

        if ( $post_after->post_name === $post_before->post_name ) {
            return;
        }

        $old_permalink = get_permalink( $post_before );
        $new_permalink = get_permalink( $post_after );

        if ( empty( $old_permalink ) || empty( $new_permalink ) || $old_permalink === $new_permalink ) {
            return;
        }

        $old_path = wp_parse_url( $old_permalink, PHP_URL_PATH );
        $new_path = wp_parse_url( $new_permalink, PHP_URL_PATH );

        if ( $old_path && $new_path && $old_path !== $new_path ) {
            self::add_auto_redirect( $old_path, $new_path, $post_id );
        }
    }

    public static function add_auto_redirect( $old_path, $new_path, $post_id = 0 ) {
        $rules = get_option( 'wpfh_sr_custom_redirects', array() );
        if ( ! is_array( $rules ) ) {
            $rules = array();
        }

        $norm_old = trim( (string) $old_path, '/' );
        if ( '' === $norm_old ) {
            return;
        }

        // Avoid duplicate (supports both from and legacy source keys).
        foreach ( $rules as $rule ) {
            if ( ! is_array( $rule ) ) {
                continue;
            }
            $existing_from = isset( $rule['from'] ) ? (string) $rule['from'] : ( isset( $rule['source'] ) ? (string) $rule['source'] : '' );
            if ( strtolower( trim( $existing_from, '/' ) ) === strtolower( $norm_old ) ) {
                return;
            }
        }

        $rules[] = array(
            'id'         => 'auto_' . time() . '_' . absint( $post_id ),
            'from'       => '/' . $norm_old,
            'to'         => home_url( '/' . trim( (string) $new_path, '/' ) . '/' ),
            'status'     => 301,
            'hits'       => 0,
            'created_at' => current_time( 'mysql' ),
            'auto_slug'  => true,
            'post_id'    => absint( $post_id ),
        );

        update_option( 'wpfh_sr_custom_redirects', $rules, false );
    }
}
