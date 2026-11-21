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
        add_action( 'admin_init', array( $this, 'handle_admin_actions' ) );
        add_action( 'wpfh_sr_admin_pro_sections', array( $this, 'render_admin_section' ) );
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

    public function handle_admin_actions() {
        if ( ! isset( $_POST['wpfh_sr_pro_action'] ) || 'save_alert_settings' !== sanitize_key( wp_unslash( $_POST['wpfh_sr_pro_action'] ) ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        check_admin_referer( 'wpfh_sr_pro_alerts_nonce' );
        $threshold = isset( $_POST['wpfh_sr_404_alert_threshold'] ) ? max( 5, absint( $_POST['wpfh_sr_404_alert_threshold'] ) ) : 20;
        $webhook   = isset( $_POST['wpfh_sr_alert_webhook_url'] ) ? esc_url_raw( wp_unslash( $_POST['wpfh_sr_alert_webhook_url'] ) ) : '';
        update_option( 'wpfh_sr_404_alert_threshold', $threshold );
        update_option( 'wpfh_sr_alert_webhook_url', $webhook );
    }

    public function render_admin_section() {
        $threshold = absint( get_option( 'wpfh_sr_404_alert_threshold', 20 ) );
        $webhook   = (string) get_option( 'wpfh_sr_alert_webhook_url', '' );
        ?>
        <div style="background:#0f172a;border:1px solid #1e293b;border-radius:12px;padding:24px;color:#e2e8f0;margin-bottom:24px;">
            <h3 style="margin-top:0;color:#f8fafc;">🔔 <?php esc_html_e( 'Pro 404 Spike Alert Settings', 'forgehub-smart-redirect' ); ?></h3>
            <form method="post" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                <?php wp_nonce_field( 'wpfh_sr_pro_alerts_nonce' ); ?>
                <input type="hidden" name="wpfh_sr_pro_action" value="save_alert_settings" />
                <label style="font-size:13px;color:#cbd5e1;"><?php esc_html_e( '5-Min Threshold:', 'forgehub-smart-redirect' ); ?>
                    <input type="number" name="wpfh_sr_404_alert_threshold" min="5" value="<?php echo absint( $threshold ); ?>" style="background:#1e293b;border:1px solid #334155;color:#f8fafc;padding:6px 10px;border-radius:6px;width:80px;margin-left:6px;" />
                </label>
                <label style="font-size:13px;color:#cbd5e1;"><?php esc_html_e( 'Webhook URL (Slack/Discord):', 'forgehub-smart-redirect' ); ?>
                    <input type="url" name="wpfh_sr_alert_webhook_url" value="<?php echo esc_attr( $webhook ); ?>" placeholder="https://hooks.slack.com/..." style="background:#1e293b;border:1px solid #334155;color:#f8fafc;padding:6px 10px;border-radius:6px;width:300px;margin-left:6px;" />
                </label>
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Alert Settings', 'forgehub-smart-redirect' ); ?></button>
            </form>
        </div>
        <?php
    }
}
