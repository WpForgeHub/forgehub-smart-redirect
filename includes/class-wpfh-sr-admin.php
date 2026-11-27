<?php
/**
 * Smart Redirect Admin Settings & Logs
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

		if ( function_exists( 'is_admin' ) && is_admin() && class_exists( 'WPFH_Review_Nudge' ) ) {
			$nudge = new WPFH_Review_Nudge(
				array(
					'plugin_slug'   => 'forgehub-smart-redirect',
					'plugin_name'   => 'ForgeHub Smart Redirect',
					'option_prefix' => 'wpfh_sr',
					'wporg_slug'    => 'forgehub-smart-redirect',
					'github_url'    => 'https://github.com/WpForgeHub/forgehub-smart-redirect',
					'min_actions'   => 5,
					'min_days'      => 14,
					'trigger_mode'  => 'or',
					'screens'       => array( 'tools_page_forgehub-smart-redirect' ),
					'strings'       => array(
						/* translators: %s: Plugin name. */
						'heading'     => sprintf( __( 'Enjoying %s?', 'forgehub-smart-redirect' ), 'ForgeHub Smart Redirect' ),
						/* translators: %s: Plugin name. */
						'message'     => sprintf( __( 'If %s has helped recover broken URLs and manage redirects, a quick 5-star review helps other site owners discover it and keeps development active.', 'forgehub-smart-redirect' ), 'ForgeHub Smart Redirect' ),
						'rate_cta'    => __( 'Rate 5 Stars on WordPress.org', 'forgehub-smart-redirect' ),
						'github_cta'  => __( 'Star on GitHub', 'forgehub-smart-redirect' ),
						'later_cta'   => __( 'Remind me in 14 days', 'forgehub-smart-redirect' ),
						'dismiss_cta' => __( "Don't show again", 'forgehub-smart-redirect' ),
					),
				)
			);
			$nudge->register();
		}
	}

	private function get_upgrade_url() {
		$url = 'https://wpforgehub.com/product/smart-redirect/#pricing';
		// <wpfh-pro-only>
		if ( function_exists( 'wpfh_sr_fs' ) ) {
			$url = wpfh_sr_fs()->get_upgrade_url();
		}
		// </wpfh-pro-only>
		return $url;
	}

	public function add_menu_page() {
		add_management_page(
			__( 'Smart Redirect & 404', 'forgehub-smart-redirect' ),
			__( 'Smart Redirect', 'forgehub-smart-redirect' ),
			'manage_options',
			'forgehub-smart-redirect',
			array( $this, 'render_admin_page' )
		);
	}

	public function add_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'tools.php?page=forgehub-smart-redirect' ) ) . '">' . esc_html__( 'Settings', 'forgehub-smart-redirect' ) . '</a>';
		$is_pro        = false;
		// <wpfh-pro-only>
		$is_pro        = function_exists( 'wpfh_sr_fs' ) && wpfh_sr_fs()->can_use_premium_code();
		// </wpfh-pro-only>
		if ( $is_pro ) {
			array_unshift( $links, $settings_link );
		} else {
			$pro_link = '<a href="' . esc_url( $this->get_upgrade_url() ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Priority Support & Pro', 'forgehub-smart-redirect' ) . '</a>';
			array_unshift( $links, $settings_link, $pro_link );
		}
		return $links;
	}

	public function handle_form_actions() {
		if ( ! isset( $_POST['wpfh_sr_action'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'wpfh_sr_nonce' );

		$action = sanitize_key( wp_unslash( $_POST['wpfh_sr_action'] ) );

		if ( 'add_redirect' === $action ) {
			$from   = isset( $_POST['redirect_from'] ) ? sanitize_text_field( wp_unslash( $_POST['redirect_from'] ) ) : '';
			$to     = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
			$status = isset( $_POST['redirect_status'] ) ? intval( $_POST['redirect_status'] ) : 301;
			if ( ! in_array( $status, array( 301, 302, 307, 308 ), true ) ) {
				$status = 301;
			}

			if ( ! empty( $from ) && ! empty( $to ) ) {
				$rules = get_option( 'wpfh_sr_custom_redirects', array() );
				if ( ! is_array( $rules ) ) {
					$rules = array();
				}
				$rules[] = array(
					'from'   => $from,
					'to'     => $to,
					'status' => $status,
				);
				update_option( 'wpfh_sr_custom_redirects', $rules );
				if ( class_exists( 'WPFH_Review_Nudge' ) ) {
					WPFH_Review_Nudge::track_success( 'wpfh_sr' );
				}
			}
		} elseif ( 'delete_redirect' === $action && isset( $_POST['rule_index'] ) ) {
			$idx   = absint( $_POST['rule_index'] );
			$rules = get_option( 'wpfh_sr_custom_redirects', array() );
			if ( is_array( $rules ) && isset( $rules[ $idx ] ) ) {
				unset( $rules[ $idx ] );
				update_option( 'wpfh_sr_custom_redirects', array_values( $rules ) );
			}
		} elseif ( 'clear_404_log' === $action ) {
			delete_option( 'wpfh_sr_404_log' );
		}
	}

	public function render_admin_page() {
		$heal_log  = get_option( 'wpfh_sr_heal_log', array() );
		$log_404   = get_option( 'wpfh_sr_404_log', array() );
		$rules     = get_option( 'wpfh_sr_custom_redirects', array() );
		$is_pro    = false;
		// <wpfh-pro-only>
		$is_pro    = function_exists( 'wpfh_sr_fs' ) && wpfh_sr_fs()->can_use_premium_code();
		// </wpfh-pro-only>
		?>
		<div class="wrap" style="max-width:1050px;">
			<h1>⚡ <?php esc_html_e( 'Smart Redirect & 404 Auto-Healer', 'forgehub-smart-redirect' ); ?></h1>
			<p><?php esc_html_e( 'Self-healing 404 redirection engine and custom 301/302 redirect manager.', 'forgehub-smart-redirect' ); ?></p>

			<!-- Quick Stats -->
			<div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:16px;margin:24px 0;">
				<div style="background:#0f172a;border:1px solid #1e293b;border-radius:10px;padding:20px;color:#e2e8f0;">
					<div style="font-size:12px;color:#94a3b8;text-transform:uppercase;font-weight:600;"><?php esc_html_e( 'Auto-Healed 404s', 'forgehub-smart-redirect' ); ?></div>
					<div style="font-size:28px;font-weight:800;color:#10b981;margin:6px 0;"><?php echo absint( is_array( $heal_log ) ? count( $heal_log ) : 0 ); ?></div>
					<div style="font-size:12px;color:#64748b;"><?php esc_html_e( 'Recovered visitors via 301', 'forgehub-smart-redirect' ); ?></div>
				</div>
				<div style="background:#0f172a;border:1px solid #1e293b;border-radius:10px;padding:20px;color:#e2e8f0;">
					<div style="font-size:12px;color:#94a3b8;text-transform:uppercase;font-weight:600;"><?php esc_html_e( 'Custom Redirect Rules', 'forgehub-smart-redirect' ); ?></div>
					<div style="font-size:28px;font-weight:800;color:#38bdf8;margin:6px 0;"><?php echo absint( is_array( $rules ) ? count( $rules ) : 0 ); ?></div>
					<div style="font-size:12px;color:#64748b;"><?php esc_html_e( 'Active 301/302 mappings', 'forgehub-smart-redirect' ); ?></div>
				</div>
				<div style="background:#0f172a;border:1px solid #1e293b;border-radius:10px;padding:20px;color:#e2e8f0;">
					<div style="font-size:12px;color:#94a3b8;text-transform:uppercase;font-weight:600;"><?php esc_html_e( 'Unresolved 404s', 'forgehub-smart-redirect' ); ?></div>
					<div style="font-size:28px;font-weight:800;color:#f59e0b;margin:6px 0;"><?php echo absint( is_array( $log_404 ) ? count( $log_404 ) : 0 ); ?></div>
					<div style="font-size:12px;color:#64748b;"><?php esc_html_e( 'Recent logged 404 requests', 'forgehub-smart-redirect' ); ?></div>
				</div>
			</div>

			<!-- Add Custom Redirect -->
			<div style="background:#0f172a;border:1px solid #1e293b;border-radius:12px;padding:24px;color:#e2e8f0;margin-bottom:24px;">
				<h3 style="margin-top:0;color:#f8fafc;">➕ <?php esc_html_e( 'Add Custom Redirect Rule', 'forgehub-smart-redirect' ); ?></h3>
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
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Add Redirect', 'forgehub-smart-redirect' ); ?></button>
				</form>

				<?php if ( ! empty( $rules ) && is_array( $rules ) ) : ?>
					<table class="widefat striped" style="background:#1e293b;color:#e2e8f0;border:none;margin-top:20px;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'From Path', 'forgehub-smart-redirect' ); ?></th>
								<th><?php esc_html_e( 'Destination URL', 'forgehub-smart-redirect' ); ?></th>
								<th><?php esc_html_e( 'Status', 'forgehub-smart-redirect' ); ?></th>
								<th><?php esc_html_e( 'Action', 'forgehub-smart-redirect' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $rules as $idx => $rule ) : ?>
								<?php
								$r_from   = isset( $rule['from'] ) ? $rule['from'] : ( isset( $rule['source'] ) ? $rule['source'] : '' );
								$r_to     = isset( $rule['to'] ) ? $rule['to'] : ( isset( $rule['target'] ) ? $rule['target'] : '' );
								$r_status = isset( $rule['status'] ) ? intval( $rule['status'] ) : ( isset( $rule['code'] ) ? intval( $rule['code'] ) : 301 );
								?>
								<tr>
									<td><code><?php echo esc_html( $r_from ); ?></code></td>
									<td><a href="<?php echo esc_url( $r_to ); ?>" target="_blank" style="color:#38bdf8;"><?php echo esc_html( $r_to ); ?></a></td>
									<td><?php echo absint( $r_status ); ?></td>
									<td>
										<form method="post" style="display:inline;">
											<?php wp_nonce_field( 'wpfh_sr_nonce' ); ?>
											<input type="hidden" name="wpfh_sr_action" value="delete_redirect" />
											<input type="hidden" name="rule_index" value="<?php echo absint( $idx ); ?>" />
											<button type="submit" class="button button-small button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Delete this redirect rule?', 'forgehub-smart-redirect' ) ); ?>');"><?php esc_html_e( 'Delete', 'forgehub-smart-redirect' ); ?></button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<!-- Unresolved 404 Log -->
			<div style="background:#0f172a;border:1px solid #1e293b;border-radius:12px;padding:24px;color:#e2e8f0;margin-bottom:24px;">
				<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
					<h3 style="margin:0;color:#f8fafc;">🔍 <?php esc_html_e( 'Recent Unresolved 404s', 'forgehub-smart-redirect' ); ?></h3>
					<?php if ( ! empty( $log_404 ) ) : ?>
						<form method="post" style="margin:0;">
							<?php wp_nonce_field( 'wpfh_sr_nonce' ); ?>
							<input type="hidden" name="wpfh_sr_action" value="clear_404_log" />
							<button type="submit" class="button button-small"><?php esc_html_e( 'Clear Log', 'forgehub-smart-redirect' ); ?></button>
						</form>
					<?php endif; ?>
				</div>
				<?php if ( empty( $log_404 ) || ! is_array( $log_404 ) ) : ?>
					<p style="color:#94a3b8;"><?php esc_html_e( 'No unresolved 404 requests recorded yet.', 'forgehub-smart-redirect' ); ?></p>
				<?php else : ?>
					<table class="widefat striped" style="background:#1e293b;color:#e2e8f0;border:none;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Requested Path', 'forgehub-smart-redirect' ); ?></th>
								<th><?php esc_html_e( 'IP Address', 'forgehub-smart-redirect' ); ?></th>
								<th><?php esc_html_e( 'Timestamp', 'forgehub-smart-redirect' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $log_404 as $item ) : ?>
								<tr>
									<td><code>/<?php echo esc_html( ltrim( (string) ( $item['path'] ?? '' ), '/' ) ); ?></code></td>
									<td><code><?php echo esc_html( (string) ( $item['ip'] ?? '' ) ); ?></code></td>
									<td><?php echo esc_html( (string) ( $item['timestamp'] ?? '' ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<!-- Auto-Healed Log Table -->
			<div style="background:#0f172a;border:1px solid #1e293b;border-radius:12px;padding:24px;color:#e2e8f0;margin-bottom:24px;">
				<h3 style="margin-top:0;color:#f8fafc;">✨ <?php esc_html_e( 'Recent Auto-Healed 404s', 'forgehub-smart-redirect' ); ?></h3>
				<?php if ( empty( $heal_log ) ) : ?>
					<p style="color:#94a3b8;"><?php esc_html_e( 'No 404 auto-heal events yet. When a visitor requests a broken URL resembling a valid page, Smart Redirect will auto-heal and log it here.', 'forgehub-smart-redirect' ); ?></p>
				<?php else : ?>
					<table class="widefat striped" style="background:#1e293b;color:#e2e8f0;border:none;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Broken Request Path', 'forgehub-smart-redirect' ); ?></th>
								<th><?php esc_html_e( 'Auto-Healed Target', 'forgehub-smart-redirect' ); ?></th>
								<th><?php esc_html_e( 'Fuzzy Match Score', 'forgehub-smart-redirect' ); ?></th>
								<th><?php esc_html_e( 'Timestamp', 'forgehub-smart-redirect' ); ?></th>
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

			<?php if ( $is_pro ) : ?>
				<?php do_action( 'wpfh_sr_admin_pro_sections' ); ?>
			<?php else : ?>
			<p class="wpfh-sr-footer-note" style="margin-top:20px;color:#50575e;font-size:13px;">
				<?php
				printf(
					/* translators: %s: link to optional Pro edition and priority support */
					esc_html__( 'Need automated slug-change tracking, regex rules, 404 spike webhooks, or priority support? %s', 'forgehub-smart-redirect' ),
					'<a href="' . esc_url( $this->get_upgrade_url() ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Priority Support & Pro', 'forgehub-smart-redirect' ) . '</a>'
				);
				?>
			</p>
			<?php endif; ?>

			<?php
			if ( class_exists( 'WPFH_Help_Tools_Card' ) ) {
				$help_card = new WPFH_Help_Tools_Card(
					array(
						'plugin_name' => 'ForgeHub Smart Redirect',
						'docs_url'    => 'https://wpforgehub.com/docs/',
						'support_url' => 'https://wordpress.org/support/plugin/forgehub-smart-redirect/',
						'catalog_url' => 'https://wpforgehub.com/#products',
						'theme'       => 'dark',
						'tools'       => array(
							array(
								'name'    => 'ForgeHub Image Memory Guard',
								'tagline' => __( 'Prevent PHP fatal memory errors on large media library uploads.', 'forgehub-smart-redirect' ),
								'url'     => 'https://wpforgehub.com/product/image-memory-guard/',
							),
							array(
								'name'    => 'ForgeHub Broken Links Checker',
								'tagline' => __( 'Scan posts and pages for dead outbound and internal URLs.', 'forgehub-smart-redirect' ),
								'url'     => 'https://wpforgehub.com/product/broken-links/',
							),
							array(
								'name'    => 'ForgeHub SEO Meta & Schema',
								'tagline' => __( 'Lightweight meta tags, OpenGraph, and JSON-LD schema with zero bloat.', 'forgehub-smart-redirect' ),
								'url'     => 'https://wpforgehub.com/product/seo-meta-schema/',
							),
						),
						'strings'     => array(
							'help_heading'  => __( 'Help & Documentation', 'forgehub-smart-redirect' ),
							/* translators: %s: Plugin name. */
							'help_desc'     => sprintf( __( 'Need setup guides, 404 auto-healing tips, or answers for %s?', 'forgehub-smart-redirect' ), 'ForgeHub Smart Redirect' ),
							'docs_cta'      => __( 'Read Documentation', 'forgehub-smart-redirect' ),
							'support_cta'   => __( 'WordPress.org Support', 'forgehub-smart-redirect' ),
							'tools_heading' => __( 'Other Free ForgeHub Tools', 'forgehub-smart-redirect' ),
							'tools_cta'     => __( 'Browse All Free Tools', 'forgehub-smart-redirect' ),
							'tool_link_cta' => __( 'Explore tool', 'forgehub-smart-redirect' ),
						),
					)
				);
				$help_card->render();
			}
			?>
		</div>
		<?php
	}
}
