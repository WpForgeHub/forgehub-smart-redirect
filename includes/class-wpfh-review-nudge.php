<?php
/**
 * Reusable Value-Moment 5-Star Review & GitHub Star Nudge Component
 *
 * 100% generic, Zero-Nag compliant admin notice component for WordPress plugins.
 * Never displays on first install; triggers once only after real user value
 * (N completed actions or N days of trouble-free use) and can be permanently
 * dismissed in a single click (via AJAX or non-JS fallback).
 *
 * @package WPForgeHub\CodeHub
 * @version 1.1.0
 * @author  WPForgeHub <https://wpforgehub.com>
 * @license GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPFH_Review_Nudge' ) ) {

	/**
	 * Class WPFH_Review_Nudge
	 */
	class WPFH_Review_Nudge {

		/**
		 * Plugin slug (used for default wp.org review URL and screen matching).
		 *
		 * @var string
		 */
		private $plugin_slug;

		/**
		 * Human-readable plugin name.
		 *
		 * @var string
		 */
		private $plugin_name;

		/**
		 * Option / hook prefix (e.g. 'wpfh_aat').
		 *
		 * @var string
		 */
		private $option_prefix;

		/**
		 * WordPress.org review URL.
		 *
		 * @var string
		 */
		private $review_url;

		/**
		 * Optional GitHub repository URL for starring.
		 *
		 * @var string
		 */
		private $github_url;

		/**
		 * Minimum successful actions before showing the nudge.
		 *
		 * @var int
		 */
		private $min_actions;

		/**
		 * Minimum days since install before showing the nudge.
		 *
		 * @var int
		 */
		private $min_days;

		/**
		 * Trigger logic: 'or' (actions OR days) or 'and' (actions AND days).
		 *
		 * @var string
		 */
		private $trigger_mode;

		/**
		 * Allowed admin screen IDs. Empty array allows any admin screen.
		 *
		 * @var array
		 */
		private $screens;

		/**
		 * Required capability to view and dismiss the nudge.
		 *
		 * @var string
		 */
		private $capability;

		/**
		 * Number of days to snooze when user selects "Maybe later".
		 *
		 * @var int
		 */
		private $snooze_days;

		/**
		 * Seconds to suppress the nudge after an error is recorded.
		 *
		 * @var int
		 */
		private $error_cooldown;

		/**
		 * UI copy strings (plugins may pass translated overrides using their own text domain).
		 *
		 * @var array
		 */
		private $strings;

		/**
		 * Constructor.
		 *
		 * @param array $config {
		 *     Configuration parameters.
		 *
		 *     @type string $plugin_slug    Required. Plugin slug (e.g. 'forgehub-image-memory-guard').
		 *     @type string $plugin_name    Required. Display name (e.g. 'ForgeHub Image Memory Guard').
		 *     @type string $option_prefix  Optional. Prefix for wp_options & hooks (default 'wpfh_<slug>').
		 *     @type string $wporg_slug     Optional. WordPress.org plugin slug (defaults to $plugin_slug).
		 *     @type string $review_url     Optional. Explicit review URL (overrides $wporg_slug).
		 *     @type string $github_url     Optional. GitHub repository URL. Empty hides the GitHub button.
		 *     @type int    $min_actions    Optional. Completed actions threshold. Default 5.
		 *     @type int    $min_days       Optional. Days since install threshold. Default 14.
		 *     @type string $trigger_mode   Optional. 'or' or 'and'. Default 'or'.
		 *     @type array  $screens        Optional. Allowed WP_Screen IDs. Default array().
		 *     @type string $capability     Optional. Required capability. Default 'manage_options'.
		 *     @type int    $snooze_days    Optional. Days to snooze on "Maybe later". Default 14.
		 *     @type int    $error_cooldown Optional. Cooldown seconds after error. Default 86400 (24h).
		 *     @type array  $strings        Optional. Localized UI string overrides.
		 * }
		 */
		public function __construct( $config = array() ) {
			$slug_raw          = isset( $config['plugin_slug'] ) ? (string) $config['plugin_slug'] : '';
			$this->plugin_slug = sanitize_key( $slug_raw );

			$name_raw          = isset( $config['plugin_name'] ) ? (string) $config['plugin_name'] : $this->plugin_slug;
			$this->plugin_name = sanitize_text_field( $name_raw );

			$default_prefix      = 'wpfh_' . str_replace( '-', '_', $this->plugin_slug );
			$prefix_raw          = ! empty( $config['option_prefix'] ) ? (string) $config['option_prefix'] : $default_prefix;
			$this->option_prefix = sanitize_key( $prefix_raw );

			$wporg_slug = ! empty( $config['wporg_slug'] )
				? sanitize_key( (string) $config['wporg_slug'] )
				: $this->plugin_slug;

			if ( ! empty( $config['review_url'] ) ) {
				$this->review_url = esc_url_raw( (string) $config['review_url'] );
			} elseif ( '' !== $wporg_slug ) {
				$this->review_url = esc_url_raw(
					'https://wordpress.org/support/plugin/' . $wporg_slug . '/reviews/#new-post'
				);
			} else {
				$this->review_url = '';
			}

			$this->github_url = ! empty( $config['github_url'] )
				? esc_url_raw( (string) $config['github_url'] )
				: '';

			$this->min_actions = isset( $config['min_actions'] ) ? max( 0, (int) $config['min_actions'] ) : 5;
			$this->min_days    = isset( $config['min_days'] ) ? max( 0, (int) $config['min_days'] ) : 14;

			$mode               = isset( $config['trigger_mode'] ) ? strtolower( (string) $config['trigger_mode'] ) : 'or';
			$this->trigger_mode = ( 'and' === $mode ) ? 'and' : 'or';

			$this->screens = ( ! empty( $config['screens'] ) && is_array( $config['screens'] ) )
				? array_values( array_filter( array_map( 'sanitize_text_field', $config['screens'] ) ) )
				: array();

			$this->capability     = ! empty( $config['capability'] ) ? sanitize_key( (string) $config['capability'] ) : 'manage_options';
			$this->snooze_days    = isset( $config['snooze_days'] ) ? max( 1, (int) $config['snooze_days'] ) : 14;
			$this->error_cooldown = isset( $config['error_cooldown'] ) ? max( 0, (int) $config['error_cooldown'] ) : 86400;

			$defaults = array(
				'heading'     => sprintf( 'Enjoying %s?', $this->plugin_name ),
				'message'     => sprintf(
					'If %s has saved you time, a quick 5-star review helps other site owners discover it and keeps development active.',
					$this->plugin_name
				),
				'rate_cta'    => 'Rate 5 Stars on WordPress.org',
				'github_cta'  => 'Star on GitHub',
				'later_cta'   => 'Remind me in 14 days',
				'dismiss_cta' => 'Don\'t show again',
			);

			$custom_strings = ( ! empty( $config['strings'] ) && is_array( $config['strings'] ) ) ? $config['strings'] : array();
			$this->strings  = array_merge( $defaults, $custom_strings );
		}

		/**
		 * Register WordPress admin hooks and ensure install timestamp is initialized.
		 *
		 * @return void
		 */
		public function register() {
			if ( '' === $this->option_prefix ) {
				return;
			}

			$this->ensure_initialized();

			add_action( 'admin_init', array( $this, 'handle_query_action' ) );
			add_action( 'admin_notices', array( $this, 'render_notice' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_inline_script' ) );
			add_action( 'wp_ajax_' . $this->get_ajax_action(), array( $this, 'handle_ajax_action' ) );
		}

		/**
		 * Option key storing nudge state.
		 *
		 * @return string
		 */
		public function get_option_key() {
			return $this->option_prefix . '_review_nudge';
		}

		/**
		 * User meta key for per-user permanent dismissal.
		 *
		 * @return string
		 */
		public function get_user_meta_key() {
			return $this->option_prefix . '_review_nudge_dismissed';
		}

		/**
		 * Nonce action name.
		 *
		 * @return string
		 */
		public function get_nonce_action() {
			return $this->option_prefix . '_review_nudge_nonce';
		}

		/**
		 * AJAX action name.
		 *
		 * @return string
		 */
		public function get_ajax_action() {
			return $this->option_prefix . '_review_nudge_action';
		}

		/**
		 * Query parameter name for non-JS fallback actions.
		 *
		 * @return string
		 */
		public function get_query_param() {
			return $this->option_prefix . '_rn_act';
		}

		/**
		 * Read persisted state with safe defaults.
		 *
		 * @return array
		 */
		public function get_state() {
			$raw = get_option( $this->get_option_key(), array() );
			if ( ! is_array( $raw ) ) {
				$raw = array();
			}

			return array(
				'installed_at'  => isset( $raw['installed_at'] ) ? (int) $raw['installed_at'] : 0,
				'action_count'  => isset( $raw['action_count'] ) ? (int) $raw['action_count'] : 0,
				'dismissed'     => ! empty( $raw['dismissed'] ),
				'snoozed_until' => isset( $raw['snoozed_until'] ) ? (int) $raw['snoozed_until'] : 0,
				'last_error_at' => isset( $raw['last_error_at'] ) ? (int) $raw['last_error_at'] : 0,
			);
		}

		/**
		 * Persist state array.
		 *
		 * @param array $state State array.
		 * @return bool
		 */
		private function save_state( $state ) {
			return (bool) update_option( $this->get_option_key(), $state, false );
		}

		/**
		 * Initialize installed_at timestamp if not yet recorded.
		 *
		 * @param int|null $now Optional Unix timestamp for testing.
		 * @return array
		 */
		public function ensure_initialized( $now = null ) {
			$state = $this->get_state();
			if ( $state['installed_at'] <= 0 ) {
				$state['installed_at'] = ( null !== $now ) ? (int) $now : time();
				$this->save_state( $state );
			}
			return $state;
		}

		/**
		 * Record one or more completed value-moment actions.
		 *
		 * @param int      $increment Number of successful actions to add. Default 1.
		 * @param int|null $now       Optional Unix timestamp for testing.
		 * @return int Updated action count.
		 */
		public function record_success( $increment = 1, $now = null ) {
			$increment = max( 1, (int) $increment );
			$state     = $this->ensure_initialized( $now );

			$state['action_count'] += $increment;
			$this->save_state( $state );

			return $state['action_count'];
		}

		/**
		 * Static convenience helper to record a successful action by option prefix.
		 *
		 * @param string $option_prefix Option prefix used when configuring the nudge.
		 * @param int    $increment     Number of successful actions to add. Default 1.
		 * @return int Updated action count.
		 */
		public static function track_success( $option_prefix, $increment = 1 ) {
			$instance = new self(
				array(
					'plugin_slug'   => $option_prefix,
					'option_prefix' => $option_prefix,
				)
			);
			return $instance->record_success( $increment );
		}

		/**
		 * Record a plugin error so the nudge is suppressed during the cooldown window.
		 *
		 * @param int|null $now Optional Unix timestamp for testing.
		 * @return void
		 */
		public function record_error( $now = null ) {
			$state                  = $this->ensure_initialized( $now );
			$state['last_error_at'] = ( null !== $now ) ? (int) $now : time();
			$this->save_state( $state );
		}

		/**
		 * Permanently dismiss the nudge (both site-wide and for the current user).
		 *
		 * @param int|null $user_id Optional user ID. Defaults to current user.
		 * @return void
		 */
		public function dismiss( $user_id = null ) {
			$state              = $this->ensure_initialized();
			$state['dismissed'] = true;
			$this->save_state( $state );

			$uid = ( null !== $user_id ) ? (int) $user_id : (int) get_current_user_id();
			if ( $uid > 0 ) {
				update_user_meta( $uid, $this->get_user_meta_key(), 1 );
			}
		}

		/**
		 * Snooze the nudge for configured snooze_days.
		 *
		 * @param int|null $now Optional Unix timestamp for testing.
		 * @return int Timestamp until snoozed.
		 */
		public function snooze( $now = null ) {
			$ts                     = ( null !== $now ) ? (int) $now : time();
			$state                  = $this->ensure_initialized( $ts );
			$state['snoozed_until'] = $ts + ( $this->snooze_days * DAY_IN_SECONDS );
			$this->save_state( $state );

			return $state['snoozed_until'];
		}

		/**
		 * Check whether the current admin screen is allowed to display the notice.
		 *
		 * @param string|null $screen_id Optional screen ID override for testing.
		 * @return bool
		 */
		public function is_allowed_screen( $screen_id = null ) {
			if ( empty( $this->screens ) ) {
				return true;
			}

			if ( null === $screen_id && function_exists( 'get_current_screen' ) ) {
				$screen    = get_current_screen();
				$screen_id = ( is_object( $screen ) && isset( $screen->id ) ) ? (string) $screen->id : '';
			}

			return in_array( (string) $screen_id, $this->screens, true );
		}

		/**
		 * Determine whether the review nudge should be displayed right now.
		 *
		 * @param int|null    $now       Optional Unix timestamp for testing.
		 * @param int|null    $user_id   Optional user ID for testing.
		 * @param string|null $screen_id Optional screen ID for testing.
		 * @return bool
		 */
		public function should_show( $now = null, $user_id = null, $screen_id = null ) {
			if ( '' === $this->review_url ) {
				return false;
			}

			if ( ! is_admin() || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) ) {
				return false;
			}

			if ( ! current_user_can( $this->capability ) ) {
				return false;
			}

			if ( ! $this->is_allowed_screen( $screen_id ) ) {
				return false;
			}

			$ts    = ( null !== $now ) ? (int) $now : time();
			$state = $this->get_state();

			// Permanently dismissed at site level.
			if ( ! empty( $state['dismissed'] ) ) {
				return false;
			}

			// Permanently dismissed at user level.
			$uid = ( null !== $user_id ) ? (int) $user_id : (int) get_current_user_id();
			if ( $uid > 0 && get_user_meta( $uid, $this->get_user_meta_key(), true ) ) {
				return false;
			}

			// Currently snoozed.
			if ( $state['snoozed_until'] > 0 && $ts < $state['snoozed_until'] ) {
				return false;
			}

			// Recent error cooldown (never ask for a review right after an error).
			if ( $this->error_cooldown > 0 && $state['last_error_at'] > 0 && ( $ts - $state['last_error_at'] ) < $this->error_cooldown ) {
				return false;
			}

			// Safety guard: at least one threshold must be configured > 0.
			if ( $this->min_actions <= 0 && $this->min_days <= 0 ) {
				return false;
			}

			$actions_met = ( $this->min_actions > 0 && $state['action_count'] >= $this->min_actions );
			$days_met    = ( $this->min_days > 0 && $state['installed_at'] > 0 && ( $ts - $state['installed_at'] ) >= ( $this->min_days * DAY_IN_SECONDS ) );

			if ( 'and' === $this->trigger_mode ) {
				$req_actions = ( $this->min_actions > 0 ) ? $actions_met : true;
				$req_days    = ( $this->min_days > 0 ) ? $days_met : true;
				return ( $req_actions && $req_days );
			}

			return ( $actions_met || $days_met );
		}

		/**
		 * Build a nonce-protected fallback URL for non-JS dismissal or snooze.
		 *
		 * @param string $action 'dismiss' or 'snooze'.
		 * @return string
		 */
		public function get_action_url( $action ) {
			$action = ( 'snooze' === $action ) ? 'snooze' : 'dismiss';
			$base   = function_exists( 'add_query_arg' )
				? add_query_arg( $this->get_query_param(), $action )
				: '?' . $this->get_query_param() . '=' . $action;

			return wp_nonce_url( $base, $this->get_nonce_action() );
		}

		/**
		 * Build the HTML markup for the notice.
		 *
		 * @return string
		 */
		public function get_notice_html() {
			$notice_id   = 'wpfh-rn-' . $this->option_prefix;
			$nonce       = wp_create_nonce( $this->get_nonce_action() );
			$dismiss_url = $this->get_action_url( 'dismiss' );
			$snooze_url  = $this->get_action_url( 'snooze' );

			$html  = '<div id="' . esc_attr( $notice_id ) . '" ';
			$html .= 'class="notice notice-info is-dismissible wpfh-review-nudge" ';
			$html .= 'data-wpfh-rn-action="' . esc_attr( $this->get_ajax_action() ) . '" ';
			$html .= 'data-wpfh-rn-nonce="' . esc_attr( $nonce ) . '" ';
			$html .= 'style="padding:14px 16px;border-left-color:#2271b1;">';

			$html .= '<p style="margin:0 0 6px;font-weight:600;color:#1d2327;">' . esc_html( $this->strings['heading'] ) . '</p>';
			$html .= '<p style="margin:0 0 12px;color:#2c3338;">' . esc_html( $this->strings['message'] ) . '</p>';

			$html .= '<p style="margin:0;display:flex;flex-wrap:wrap;align-items:center;gap:8px;">';
			$html .= '<a href="' . esc_url( $this->review_url ) . '" target="_blank" rel="noopener noreferrer" ';
			$html .= 'class="button button-primary wpfh-rn-btn" data-wpfh-rn-do="dismiss">';
			$html .= esc_html( $this->strings['rate_cta'] );
			$html .= '</a>';

			if ( '' !== $this->github_url ) {
				$html .= '<a href="' . esc_url( $this->github_url ) . '" target="_blank" rel="noopener noreferrer" ';
				$html .= 'class="button button-secondary wpfh-rn-btn" data-wpfh-rn-do="dismiss">';
				$html .= esc_html( $this->strings['github_cta'] );
				$html .= '</a>';
			}

			$html .= '<a href="' . esc_url( $snooze_url ) . '" class="button button-link wpfh-rn-btn" data-wpfh-rn-do="snooze">';
			$html .= esc_html( $this->strings['later_cta'] );
			$html .= '</a>';

			$html .= '<a href="' . esc_url( $dismiss_url ) . '" class="button button-link wpfh-rn-btn" data-wpfh-rn-do="dismiss">';
			$html .= esc_html( $this->strings['dismiss_cta'] );
			$html .= '</a>';

			$html .= '</p></div>';

			return $html;
		}

		/**
		 * Render the admin notice when all Value-Moment gates pass.
		 *
		 * @return void
		 */
		public function render_notice() {
			if ( ! $this->should_show() ) {
				return;
			}

			echo wp_kses(
				$this->get_notice_html(),
				array(
					'div' => array(
						'id'                  => true,
						'class'               => true,
						'style'               => true,
						'data-wpfh-rn-action' => true,
						'data-wpfh-rn-nonce'  => true,
					),
					'p'   => array(
						'style' => true,
					),
					'a'   => array(
						'href'            => true,
						'target'          => true,
						'rel'             => true,
						'class'           => true,
						'data-wpfh-rn-do' => true,
					),
				)
			);
		}

		/**
		 * Attach minimal inline JS to handle one-click AJAX dismissal without page reload.
		 *
		 * @return void
		 */
		public function enqueue_inline_script() {
			if ( ! $this->should_show() ) {
				return;
			}

			static $script_added = false;
			if ( $script_added ) {
				return;
			}
			$script_added = true;

			$js = "(function(){document.addEventListener('click',function(e){"
				. "var box=e.target.closest('.wpfh-review-nudge');if(!box){return;}"
				. "var isClose=e.target.classList.contains('notice-dismiss');"
				. "var btn=e.target.closest('.wpfh-rn-btn');"
				. "if(!isClose&&!btn){return;}"
				. "var act=isClose?'dismiss':(btn.getAttribute('data-wpfh-rn-do')||'dismiss');"
				. "var isExternal=btn&&btn.getAttribute('target')==='_blank';"
				. "if(!isExternal&&!isClose){e.preventDefault();}"
				. "var fd=new FormData();"
				. "fd.append('action',box.getAttribute('data-wpfh-rn-action')||'');"
				. "fd.append('nonce',box.getAttribute('data-wpfh-rn-nonce')||'');"
				. "fd.append('rn_do',act);"
				. "if(window.ajaxurl&&window.fetch){fetch(window.ajaxurl,{method:'POST',credentials:'same-origin',body:fd});}"
				. "if(!isClose){box.remove();}"
				. "});})();";

			wp_add_inline_script( 'common', $js );
		}

		/**
		 * Handle AJAX dismiss/snooze requests.
		 *
		 * @return void
		 */
		public function handle_ajax_action() {
			if ( ! current_user_can( $this->capability ) ) {
				wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
				return;
			}

			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, $this->get_nonce_action() ) ) {
				wp_send_json_error( array( 'message' => 'invalid_nonce' ), 403 );
				return;
			}

			$do = isset( $_POST['rn_do'] ) ? sanitize_key( wp_unslash( $_POST['rn_do'] ) ) : 'dismiss';
			if ( 'snooze' === $do ) {
				$this->snooze();
			} else {
				$this->dismiss();
			}

			wp_send_json_success( array( 'status' => $do ) );
		}

		/**
		 * Handle non-JS GET fallback dismiss/snooze requests.
		 *
		 * @return bool True if an action was processed, false otherwise.
		 */
		public function handle_query_action() {
			$param = $this->get_query_param();
			if ( ! isset( $_GET[ $param ] ) ) {
				return false;
			}

			if ( ! current_user_can( $this->capability ) ) {
				return false;
			}

			$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, $this->get_nonce_action() ) ) {
				return false;
			}

			$do = sanitize_key( wp_unslash( $_GET[ $param ] ) );
			if ( 'snooze' === $do ) {
				$this->snooze();
			} elseif ( 'dismiss' === $do ) {
				$this->dismiss();
			} else {
				return false;
			}

			if ( function_exists( 'remove_query_arg' ) && function_exists( 'wp_safe_redirect' ) ) {
				wp_safe_redirect( remove_query_arg( array( $param, '_wpnonce' ) ) );
				if ( ! defined( 'WPFH_TESTING' ) ) {
					exit;
				}
			}

			return true;
		}

		/**
		 * Remove stored option during plugin uninstall.
		 *
		 * @param string $option_prefix Option prefix used by the plugin.
		 * @return bool
		 */
		public static function cleanup( $option_prefix ) {
			$prefix = sanitize_key( (string) $option_prefix );
			if ( '' === $prefix ) {
				return false;
			}
			return (bool) delete_option( $prefix . '_review_nudge' );
		}
	}
}
