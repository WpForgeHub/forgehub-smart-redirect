<?php
/**
 * WPFH Smart Redirect — Pro 404 Spike Real-Time Alerts
 *
 * Tracks high-velocity 404 error spikes indicating broken links,
 * missing assets, or vulnerability scanners, triggering email & webhook alerts.
 *
 * @package WPForgeHub\SmartRedirect\Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPFH_SR_Alerts {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wpfh_sr_on_404_recorded', array( $this, 'track_404_rate' ), 10, 2 );
    }

    public function track_404_rate( $uri, $ip ) {
        $key = 'wpfh_sr_404_spike_counter';
        $count = (int) get_transient( $key ) ?: 0;
        $count++;
        set_transient( $key, $count, 300 ); // 5 minute window

        $threshold = absint( get_option( 'wpfh_sr_404_alert_threshold', 20 ) );

        if ( $count >= $threshold ) {
            // Check if alert already sent in this cooldown period
            $cooldown_key = 'wpfh_sr_spike_alert_sent';
            if ( ! get_transient( $cooldown_key ) ) {
                set_transient( $cooldown_key, 1, 1800 ); // 30 minute cooldown
                self::dispatch_spike_alert( $count, $uri, $ip );
            }
        }
    }

    public static function dispatch_spike_alert( $count, $last_uri, $last_ip ) {
        $admin_email = get_option( 'admin_email' );
        $site_name   = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
        $subject     = sprintf( '[%s] 404 Spike Alert (%d hits in 5m)', $site_name, $count );
        $message     = sprintf(
            "Hello Admin,\n\nA high-volume 404 spike was detected on your site.\n\nTotal 404s: %d\nLast Requested URI: %s\nClient IP: %s\nTime: %s\n\nPlease check your Smart Redirect dashboard: %s\n",
            $count,
            $last_uri,
            $last_ip,
            current_time( 'mysql' ),
            admin_url( 'tools.php?page=forgehub-smart-redirect' )
        );

        wp_mail( $admin_email, $subject, $message );

        $webhook_url = get_option( 'wpfh_sr_alert_webhook_url' );
        if ( ! empty( $webhook_url ) && filter_var( $webhook_url, FILTER_VALIDATE_URL ) ) {
            wp_remote_post( $webhook_url, array(
                'blocking' => false,
                'headers'  => array( 'Content-Type' => 'application/json' ),
                'body'     => wp_json_encode( array(
                    'event'     => '404_spike',
                    'count'     => $count,
                    'last_uri'  => $last_uri,
                    'timestamp' => current_time( 'mysql' ),
                ) ),
            ) );
        }
    }
}
