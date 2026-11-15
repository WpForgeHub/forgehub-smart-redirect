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

        // Avoid duplicate
        foreach ( $rules as $rule ) {
            if ( rtrim( $rule['source'], '/' ) === rtrim( $old_path, '/' ) ) {
                return;
            }
        }

        $rules[] = array(
            'id'          => 'auto_' . time() . '_' . $post_id,
            'source'      => rtrim( $old_path, '/' ),
            'target'      => rtrim( $new_path, '/' ),
            'code'        => 301,
            'hits'        => 0,
            'created_at'  => current_time( 'mysql' ),
            'auto_slug'   => true,
            'post_id'     => $post_id,
        );

        update_option( 'wpfh_sr_custom_redirects', $rules, false );
    }
}
