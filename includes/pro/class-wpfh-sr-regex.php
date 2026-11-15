<?php
/**
 * WPFH Smart Redirect — Pro Regex Wildcard Redirect Engine
 *
 * Evaluates dynamic regular expressions with backreference substitutions ($1, $2)
 * for advanced URL structure migrations and directory rerouting.
 *
 * @package WPForgeHub\SmartRedirect\Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPFH_SR_Regex {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'template_redirect', array( $this, 'intercept_regex_redirects' ), 2 );
    }

    public function intercept_regex_redirects() {
        $regex_rules = get_option( 'wpfh_sr_regex_redirects', array() );
        if ( empty( $regex_rules ) || ! is_array( $regex_rules ) ) {
            return;
        }

        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = wp_parse_url( $request_uri, PHP_URL_PATH );
        if ( empty( $path ) ) {
            return;
        }

        foreach ( $regex_rules as $rule ) {
            $pattern = $rule['pattern'] ?? '';
            $target  = $rule['target'] ?? '';
            $code    = absint( $rule['code'] ?? 301 );

            if ( empty( $pattern ) || empty( $target ) ) {
                continue;
            }

            // Ensure valid delimiter
            $regex = '#' . trim( $pattern, '#' ) . '#i';

            if ( @preg_match( $regex, $path ) ) {
                $destination = preg_replace( $regex, $target, $path );

                // Preserve query string if exists
                if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
                    $destination = add_query_arg( array(), $destination );
                }

                wp_safe_redirect( $destination, $code );
                if ( ! defined( 'WPFH_TEST_RUN' ) ) {
                    exit;
                }
            }
        }
    }

    public static function add_regex_rule( $pattern, $target, $code = 301 ) {
        $rules = get_option( 'wpfh_sr_regex_redirects', array() );
        $rules[] = array(
            'id'      => 'regex_' . time(),
            'pattern' => $pattern,
            'target'  => $target,
            'code'    => $code,
            'hits'    => 0,
        );
        update_option( 'wpfh_sr_regex_redirects', $rules, false );
    }
}
