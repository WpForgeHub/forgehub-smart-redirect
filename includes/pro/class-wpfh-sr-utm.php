<?php
/**
 * WPFH Smart Redirect — Pro UTM Query Parameter Preservation
 *
 * Automatically preserves campaign parameters (utm_*, gclid, fbclid)
 * across 301/302 redirects to ensure zero marketing analytics attribution loss.
 *
 * @package WPForgeHub\SmartRedirect\Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPFH_SR_UTM {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter( 'wp_redirect', array( $this, 'preserve_utm_parameters' ), 10, 2 );
    }

    public function preserve_utm_parameters( $location, $status ) {
        // Never modify admin or login redirects.
        if ( is_admin() || false !== strpos( (string) $location, 'wp-admin' ) || false !== strpos( (string) $location, 'wp-login.php' ) ) {
            return $location;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public UTM/referral query parameters.
        if ( empty( $_GET ) ) {
            return $location;
        }

        $tracking_keys = array(
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
            'gclid', 'fbclid', 'msclkid', 'ref', 'aff'
        );

        $params_to_forward = array();
        foreach ( $tracking_keys as $key ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ( isset( $_GET[ $key ] ) ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $params_to_forward[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
            }
        }

        if ( ! empty( $params_to_forward ) ) {
            $location = add_query_arg( $params_to_forward, $location );
        }

        return $location;
    }
}
