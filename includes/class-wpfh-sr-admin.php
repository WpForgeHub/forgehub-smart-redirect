<?php
/**
 * WPFH Smart Redirect Admin Settings & Logs
 *
 * @package WPFH_Smart_Redirect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPFH_SR_Admin {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'handle_form_actions' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( WPFH_SR_FILE ), array( $this, 'add_action_links' ) );
	}

	public function add_menu_page() {
		add_management_page(
			__( 'Smart Redirect & 404', 'wpfh-smart-redirect' ),
			__( 'Smart Redirect', 'wpfh-smart-redirect' ),
			'manage_options',
			'wpfh-smart-redirect',
			array( $this, 'render_admin_page' )
		);
	}

	public function add_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'tools.php?page=wpfh-smart-redirect' ) ) . '">' . __( 'Redirects & 404s', 'wpfh-smart-redirect' ) . '</a>';
		$pro_link      = '<a href="https://wpforgehub.com/plugins/wpfh-smart-redirect/" style="color:#10b981;font-weight:700;" target="_blank">' . __( 'Upgrade to Pro', 'wpfh-smart-redirect' ) . '</a>';
		array_unshift( $links, $settings_link, $pro_link );
		return $links;
	}

	public function handle_form_actions() {
		if ( ! isset( $_POST['wpfh_sr_action'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'wpfh_sr_nonce' );

		if ( 'add_redirect' === $_POST['wpfh_sr_action'] ) {
			$from   = sanitize_text_field( wp_unslash( $_POST['redirect_from'] ) );
			$to     = sanitize_text_field( wp_unslash( $_POST['redirect_to'] ) );
			$status = intval( $_POST['redirect_status'] );

			if ( ! empty( $from ) && ! empty( $to ) ) {
				$rules = get_option( 'wpfh_sr_custom_redirects', array() );
				$rules[] = array(
					'from'   => $from,
					'to'     => $to,
					'status' => $status,
				);
				update_option( 'wpfh_sr_custom_redirects', $rules );
			}
		}
	}

	public function render_admin_page() {
		$heal_log   = get_option( 'wpfh_sr_heal_log', array() );
		$stats      = get_option( 'wpfh_sr_click_stats', array() );
		$rules      = get_option( 'wpfh_sr_custom_redirects', array() );
		?>
		<div class="wrap" style="max-width:1050px;">
			<h1>⚡ <?php esc_html_e( 'WPFH Smart Redirect & 404 Auto-Healer', 'wpfh-smart-redirect' ); ?></h1>
			<p><?php esc_html_e( 'Self-healing 404 redirection engine and SaaS outbound route tracker.', 'wpfh-smart-redirect' ); ?></p>

			<!-- Quick Stats -->
			<div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:16px;margin:24px 0;">
				<div style="background:#0f172a;border:1px solid #1e293b;border-radius:10px;padding:20px;color:#e2e8f0;">
					<div style="font-size:12px;color:#94a3b8;text-transform:uppercase;font-weight:600;">Auto-Healed 404s</div>
					<div style="font-size:28px;font-weight:800;color:#10b981;margin:6px 0;"><?php echo count( $heal_log ); ?></div>
					<div style="font-size:12px;color:#64748b;">Recovered visitors via 301</div>
				</div>
				<div style="background:#0f172a;border:1px solid #1e293b;border-radius:10px;padding:20px;color:#e2e8f0;">
					<div style="font-size:12px;color:#94a3b8;text-transform:uppercase;font-weight:600;">Custom Redirect Rules</div>
					<div style="font-size:28px;font-weight:800;color:#38bdf8;margin:6px 0;"><?php echo count( $rules ); ?></div>
					<div style="font-size:12px;color:#64748b;">Active 301/302 mappings</div>
				</div>
				<div style="background:#0f172a;border:1px solid #1e293b;border-radius:10px;padding:20px;color:#e2e8f0;">
					<div style="font-size:12px;color:#94a3b8;text-transform:uppercase;font-weight:600;">Upgrade Route Clicks</div>
					<div style="font-size:28px;font-weight:800;color:#f59e0b;margin:6px 0;">
						<?php
						$total_clicks = 0;
						foreach ( $stats as $s ) {
							$total_clicks += intval( $s['clicks'] );
						}
						echo esc_html( $total_clicks );
						?>
					</div>
					<div style="font-size:12px;color:#64748b;">Clicks through /wpfh-pro/ routes</div>
				</div>
			</div>

			<!-- Add Custom Redirect -->
			<div style="background:#0f172a;border:1px solid #1e293b;border-radius:12px;padding:24px;color:#e2e8f0;margin-bottom:24px;">
				<h3 style="margin-top:0;color:#f8fafc;">➕ <?php esc_html_e( 'Add Custom Redirect Rule', 'wpfh-smart-redirect' ); ?></h3>
				<form method="post" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
					<?php wp_nonce_field( 'wpfh_sr_nonce' ); ?>
					<input type="hidden" name="wpfh_sr_action" value="add_redirect" />
					<input type="text" name="redirect_from" placeholder="/old-path/" style="background:#1e293b;border:1px solid #334155;color:#f8fafc;padding:8px 12px;border-radius:6px;width:240px;" required />
					<span style="color:#64748b;">&rarr;</span>
					<input type="text" name="redirect_to" placeholder="https://... or /new-path/" style="background:#1e293b;border:1px solid #334155;color:#f8fafc;padding:8px 12px;border-radius:6px;width:280px;" required />
					<select name="redirect_status" style="background:#1e293b;border:1px solid #334155;color:#f8fafc;padding:8px 12px;border-radius:6px;">
						<option value="301">301 Permanent</option>
						<option value="302">302 Temporary</option>
					</select>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Add Redirect', 'wpfh-smart-redirect' ); ?></button>
				</form>
			</div>

			<!-- Auto-Healed Log Table -->
			<div style="background:#0f172a;border:1px solid #1e293b;border-radius:12px;padding:24px;color:#e2e8f0;margin-bottom:24px;">
				<h3 style="margin-top:0;color:#f8fafc;">✨ <?php esc_html_e( 'Recent Auto-Healed 404s', 'wpfh-smart-redirect' ); ?></h3>
				<?php if ( empty( $heal_log ) ) : ?>
					<p style="color:#94a3b8;"><?php esc_html_e( 'No 404 auto-heal events yet. When a visitor requests a broken URL resembling a valid page, Smart Redirect will auto-heal and log it here.', 'wpfh-smart-redirect' ); ?></p>
				<?php else : ?>
					<table class="widefat striped" style="background:#1e293b;color:#e2e8f0;border:none;">
						<thead>
							<tr>
								<th>Broken Request Path</th>
								<th>Auto-Healed Target</th>
								<th>Fuzzy Match Score</th>
								<th>Timestamp</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $heal_log as $item ) : ?>
								<tr>
									<td><code><?php echo esc_html( $item['from'] ); ?></code></td>
									<td><a href="<?php echo esc_url( $item['to'] ); ?>" target="_blank" style="color:#38bdf8;"><?php echo esc_html( $item['to'] ); ?></a></td>
									<td><span style="background:#065f46;color:#34d399;padding:2px 8px;border-radius:4px;font-weight:700;"><?php echo esc_html( $item['score'] ); ?>%</span></td>
									<td><?php echo esc_html( $item['timestamp'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<!-- Pro Upsell -->
			<div class="wpfh-pro-card" style="background:#0f172a;border:1px solid #1e293b;border-radius:12px;padding:24px;color:#e2e8f0;">
				<div style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #334155;padding-bottom:16px;margin-bottom:16px;">
					<div>
						<h3 style="margin:0;font-size:18px;color:#f8fafc;">🚀 WPFH Smart Redirect Pro</h3>
						<p style="margin:4px 0 0 0;color:#94a3b8;font-size:13px;">Advanced regex rules, 404 webhook alerts, and automated WooCommerce slug tracking.</p>
					</div>
					<a href="https://wpforgehub.com/plugins/wpfh-smart-redirect/" target="_blank" style="background:#10b981;color:#ffffff;padding:8px 16px;border-radius:6px;text-decoration:none;font-weight:700;font-size:13px;display:inline-block;">Upgrade to Pro &rarr;</a>
				</div>
				<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:13px;">
					<div style="background:#1e293b;padding:12px 16px;border-radius:8px;">
						<strong style="color:#38bdf8;">🔄 Automated Slug Change Sentinel</strong>
						<p style="margin:4px 0 0 0;color:#cbd5e1;">Never lose SEO rankings when renaming blog posts or products. Creates 301 redirects instantly upon slug update.</p>
					</div>
					<div style="background:#1e293b;padding:12px 16px;border-radius:8px;">
						<strong style="color:#38bdf8;">⚙️ Regular Expression (Regex) Rules</strong>
						<p style="margin:4px 0 0 0;color:#cbd5e1;">Map complex URL patterns and preserve query parameters dynamically.</p>
					</div>
					<div style="background:#1e293b;padding:12px 16px;border-radius:8px;">
						<strong style="color:#38bdf8;">🔔 Instant 404 Spike Webhooks</strong>
						<p style="margin:4px 0 0 0;color:#cbd5e1;">Get alerted on Slack, Discord, or Email if a sudden spike in 404 errors occurs on your store.</p>
					</div>
					<div style="background:#1e293b;padding:12px 16px;border-radius:8px;">
						<strong style="color:#38bdf8;">🌍 UTM Parameter Preservation</strong>
						<p style="margin:4px 0 0 0;color:#cbd5e1;">Preserve marketing UTM tags and affiliate query strings seamlessly across all redirects.</p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
