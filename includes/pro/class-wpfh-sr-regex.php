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
        add_action( 'admin_init', array( $this, 'handle_admin_actions' ) );
        add_action( 'wpfh_sr_admin_pro_sections', array( $this, 'render_admin_section' ) );
    }

    public function intercept_regex_redirects() {
        $regex_rules = get_option( 'wpfh_sr_regex_redirects', array() );
        if ( empty( $regex_rules ) || ! is_array( $regex_rules ) ) {
            return;
        }

        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        $path = wp_parse_url( $request_uri, PHP_URL_PATH );
        if ( ! is_string( $path ) || '' === $path ) {
            return;
        }

        $site_host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );

        foreach ( $regex_rules as $rule ) {
            $pattern = isset( $rule['pattern'] ) ? (string) $rule['pattern'] : '';
            $target  = isset( $rule['target'] ) ? (string) $rule['target'] : '';
            $code    = absint( $rule['code'] ?? 301 );
            if ( ! in_array( $code, array( 301, 302, 307, 308 ), true ) ) {
                $code = 301;
            }

            if ( '' === $pattern || '' === $target ) {
                continue;
            }

            // Ensure valid delimiter and syntax.
            $regex = '#' . str_replace( '#', '\#', trim( $pattern, '#' ) ) . '#i';
            if ( false === @preg_match( $regex, '' ) ) {
                continue;
            }

            if ( @preg_match( $regex, $path ) ) {
                $destination = preg_replace( $regex, $target, $path );
                if ( ! is_string( $destination ) || '' === $destination ) {
                    continue;
                }

                // Prevent self-redirect loop.
                $dest_host = strtolower( (string) wp_parse_url( $destination, PHP_URL_HOST ) );
                $dest_path = (string) wp_parse_url( $destination, PHP_URL_PATH );
                if ( ( '' === $dest_host || $dest_host === $site_host ) && trim( $dest_path, '/' ) === trim( $path, '/' ) ) {
                    continue;
                }

                // Preserve query string if exists.
                if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
                    $query_args = array();
                    wp_parse_str( sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ), $query_args );
                    if ( ! empty( $query_args ) ) {
                        $destination = add_query_arg( $query_args, $destination );
                    }
                }

                // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Admin-configured regex rules may target external URLs.
                wp_redirect( esc_url_raw( $destination ), $code );
                if ( ! defined( 'WPFH_TEST_RUN' ) ) {
                    exit;
                }
                return;
            }
        }
    }

    public static function add_regex_rule( $pattern, $target, $code = 301 ) {
        $pattern = trim( (string) $pattern );
        $target  = trim( (string) $target );
        if ( '' === $pattern || '' === $target ) {
            return false;
        }
        $regex = '#' . str_replace( '#', '\#', trim( $pattern, '#' ) ) . '#i';
        if ( false === @preg_match( $regex, '' ) ) {
            return false;
        }
        $code = in_array( (int) $code, array( 301, 302, 307, 308 ), true ) ? (int) $code : 301;
        $rules = get_option( 'wpfh_sr_regex_redirects', array() );
        if ( ! is_array( $rules ) ) {
            $rules = array();
        }
        $rules[] = array(
            'id'      => 'regex_' . wp_generate_password( 8, false, false ),
            'pattern' => $pattern,
            'target'  => $target,
            'code'    => $code,
            'hits'    => 0,
        );
        update_option( 'wpfh_sr_regex_redirects', $rules, false );
        return true;
    }

    public function handle_admin_actions() {
        if ( ! isset( $_POST['wpfh_sr_pro_action'] ) || ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $action = sanitize_key( wp_unslash( $_POST['wpfh_sr_pro_action'] ) );
        if ( 'add_regex' === $action ) {
            check_admin_referer( 'wpfh_sr_pro_regex_nonce' );
            $pattern = isset( $_POST['regex_pattern'] ) ? sanitize_text_field( wp_unslash( $_POST['regex_pattern'] ) ) : '';
            $target  = isset( $_POST['regex_target'] ) ? sanitize_text_field( wp_unslash( $_POST['regex_target'] ) ) : '';
            $code    = isset( $_POST['regex_code'] ) ? absint( $_POST['regex_code'] ) : 301;
            self::add_regex_rule( $pattern, $target, $code );
        } elseif ( 'delete_regex' === $action && isset( $_POST['regex_index'] ) ) {
            check_admin_referer( 'wpfh_sr_pro_regex_nonce' );
            $idx   = absint( $_POST['regex_index'] );
            $rules = get_option( 'wpfh_sr_regex_redirects', array() );
            if ( is_array( $rules ) && isset( $rules[ $idx ] ) ) {
                unset( $rules[ $idx ] );
                update_option( 'wpfh_sr_regex_redirects', array_values( $rules ), false );
            }
        }
    }

    public function render_admin_section() {
        $rules = get_option( 'wpfh_sr_regex_redirects', array() );
        ?>
        <div style="background:#0f172a;border:1px solid #1e293b;border-radius:12px;padding:24px;color:#e2e8f0;margin-bottom:24px;">
            <h3 style="margin-top:0;color:#f8fafc;">⚙️ <?php esc_html_e( 'Pro Regex Redirect Rules', 'forgehub-smart-redirect' ); ?></h3>
            <form method="post" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                <?php wp_nonce_field( 'wpfh_sr_pro_regex_nonce' ); ?>
                <input type="hidden" name="wpfh_sr_pro_action" value="add_regex" />
                <input type="text" name="regex_pattern" placeholder="^/blog/([0-9]{4})/(.*)$" style="background:#1e293b;border:1px solid #334155;color:#f8fafc;padding:8px 12px;border-radius:6px;width:260px;" required />
                <span style="color:#64748b;">&rarr;</span>
                <input type="text" name="regex_target" placeholder="/articles/$2" style="background:#1e293b;border:1px solid #334155;color:#f8fafc;padding:8px 12px;border-radius:6px;width:240px;" required />
                <select name="regex_code" style="background:#1e293b;border:1px solid #334155;color:#f8fafc;padding:8px 12px;border-radius:6px;">
                    <option value="301">301</option>
                    <option value="302">302</option>
                </select>
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Add Regex Rule', 'forgehub-smart-redirect' ); ?></button>
            </form>
            <?php if ( ! empty( $rules ) && is_array( $rules ) ) : ?>
                <table class="widefat striped" style="background:#1e293b;color:#e2e8f0;border:none;margin-top:16px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Pattern', 'forgehub-smart-redirect' ); ?></th>
                            <th><?php esc_html_e( 'Target', 'forgehub-smart-redirect' ); ?></th>
                            <th><?php esc_html_e( 'Code', 'forgehub-smart-redirect' ); ?></th>
                            <th><?php esc_html_e( 'Action', 'forgehub-smart-redirect' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $rules as $idx => $rule ) : ?>
                            <tr>
                                <td><code><?php echo esc_html( (string) ( $rule['pattern'] ?? '' ) ); ?></code></td>
                                <td><code><?php echo esc_html( (string) ( $rule['target'] ?? '' ) ); ?></code></td>
                                <td><?php echo absint( $rule['code'] ?? 301 ); ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <?php wp_nonce_field( 'wpfh_sr_pro_regex_nonce' ); ?>
                                        <input type="hidden" name="wpfh_sr_pro_action" value="delete_regex" />
                                        <input type="hidden" name="regex_index" value="<?php echo absint( $idx ); ?>" />
                                        <button type="submit" class="button button-small button-link-delete"><?php esc_html_e( 'Delete', 'forgehub-smart-redirect' ); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
}
