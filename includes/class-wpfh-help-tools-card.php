<?php
/**
 * Reusable Help & Other Free Tools Card Component
 *
 * 100% generic, Zero-Nag, WCAG 2.1 AA compliant settings-page footer card for
 * WordPress plugins. Displays a direct Documentation/Help link alongside a
 * clean, non-intrusive grid of companion free tools passed by the host plugin.
 *
 * @package WPForgeHub\CodeHub
 * @version 1.0.0
 * @author  WPForgeHub <https://wpforgehub.com>
 * @license GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPFH_Help_Tools_Card' ) ) {

	/**
	 * Class WPFH_Help_Tools_Card
	 */
	class WPFH_Help_Tools_Card {

		/**
		 * Human-readable plugin name.
		 *
		 * @var string
		 */
		private $plugin_name;

		/**
		 * Direct documentation / help URL for the current plugin.
		 *
		 * @var string
		 */
		private $docs_url;

		/**
		 * Optional community support forum URL.
		 *
		 * @var string
		 */
		private $support_url;

		/**
		 * Optional full catalog URL for browsing all free tools.
		 *
		 * @var string
		 */
		private $catalog_url;

		/**
		 * Curated list of companion free tools.
		 * Each item: array( 'name' => string, 'tagline' => string, 'url' => string ).
		 *
		 * @var array
		 */
		private $tools;

		/**
		 * Visual theme: 'light' or 'dark'.
		 *
		 * @var string
		 */
		private $theme;

		/**
		 * UI copy strings (host plugins pass translated strings with their own text domain).
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
		 *     @type string $plugin_name Required. Display name of the current plugin.
		 *     @type string $docs_url    Optional. Direct Help / Documentation URL.
		 *     @type string $support_url Optional. WordPress.org support forum URL.
		 *     @type string $catalog_url Optional. URL to browse all free tools.
		 *     @type array  $tools       Optional. Companion free tools list (max 4 shown).
		 *     @type string $theme       Optional. 'light' or 'dark'. Default 'light'.
		 *     @type array  $strings     Optional. Localized UI copy overrides.
		 * }
		 */
		public function __construct( $config = array() ) {
			$this->plugin_name = isset( $config['plugin_name'] )
				? sanitize_text_field( (string) $config['plugin_name'] )
				: '';

			$this->docs_url = ! empty( $config['docs_url'] )
				? esc_url_raw( (string) $config['docs_url'] )
				: '';

			$this->support_url = ! empty( $config['support_url'] )
				? esc_url_raw( (string) $config['support_url'] )
				: '';

			$this->catalog_url = ! empty( $config['catalog_url'] )
				? esc_url_raw( (string) $config['catalog_url'] )
				: '';

			$this->tools = array();
			if ( ! empty( $config['tools'] ) && is_array( $config['tools'] ) ) {
				foreach ( $config['tools'] as $item ) {
					if ( ! is_array( $item ) ) {
						continue;
					}
					$t_name = isset( $item['name'] ) ? sanitize_text_field( (string) $item['name'] ) : '';
					$t_desc = isset( $item['tagline'] ) ? sanitize_text_field( (string) $item['tagline'] ) : '';
					$t_url  = isset( $item['url'] ) ? esc_url_raw( (string) $item['url'] ) : '';
					if ( '' !== $t_name && '' !== $t_url ) {
						$this->tools[] = array(
							'name'    => $t_name,
							'tagline' => $t_desc,
							'url'     => $t_url,
						);
					}
					if ( count( $this->tools ) >= 4 ) {
						break;
					}
				}
			}

			$theme_raw   = isset( $config['theme'] ) ? strtolower( (string) $config['theme'] ) : 'light';
			$this->theme = ( 'dark' === $theme_raw ) ? 'dark' : 'light';

			$defaults = array(
				'help_heading'  => 'Help & Documentation',
				'help_desc'     => '' !== $this->plugin_name
					? sprintf( 'Need setup guides, troubleshooting tips, or answers for %s?', $this->plugin_name )
					: 'Need setup guides or troubleshooting tips?',
				'docs_cta'      => 'Read Documentation',
				'support_cta'   => 'Community Support',
				'tools_heading' => 'More Free Tools',
				'tools_cta'     => 'Browse All Free Tools',
				'tool_link_cta' => 'Learn more',
			);

			$custom_strings = ( ! empty( $config['strings'] ) && is_array( $config['strings'] ) ) ? $config['strings'] : array();
			$this->strings  = array_merge( $defaults, $custom_strings );
		}

		/**
		 * Whether there is any meaningful content to render.
		 *
		 * @return bool
		 */
		public function has_content() {
			return ( '' !== $this->docs_url || '' !== $this->support_url || ! empty( $this->tools ) );
		}

		/**
		 * Build WCAG 2.1 AA compliant HTML markup for the Help & Tools card.
		 *
		 * @return string
		 */
		public function get_html() {
			if ( ! $this->has_content() ) {
				return '';
			}

			$is_dark = ( 'dark' === $this->theme );

			// WCAG 2.1 AA verified color palette (>= 4.5:1 contrast for body text).
			$box_style   = $is_dark
				? 'background:#0f172a;border:1px solid #1e293b;border-radius:12px;padding:20px 24px;margin-top:24px;color:#e2e8f0;'
				: 'background:#ffffff;border:1px solid #dcdcde;border-radius:8px;padding:20px 24px;margin-top:24px;color:#1d2327;';
			$h3_style    = $is_dark
				? 'margin:0 0 6px;font-size:15px;font-weight:700;color:#f8fafc;'
				: 'margin:0 0 6px;font-size:15px;font-weight:700;color:#1d2327;';
			$desc_style  = $is_dark
				? 'margin:0 0 12px;font-size:13px;color:#cbd5e1;'
				: 'margin:0 0 12px;font-size:13px;color:#3c434a;';
			$hr_style    = $is_dark
				? 'border:0;border-top:1px solid #1e293b;margin:18px 0;'
				: 'border:0;border-top:1px solid #e2e4e7;margin:18px 0;';
			$card_style  = $is_dark
				? 'background:#1e293b;border:1px solid #334155;border-radius:8px;padding:12px 14px;display:flex;flex-direction:column;justify-content:space-between;gap:6px;'
				: 'background:#f6f7f7;border:1px solid #dcdcde;border-radius:6px;padding:12px 14px;display:flex;flex-direction:column;justify-content:space-between;gap:6px;';
			$title_style = $is_dark
				? 'font-weight:600;font-size:13px;color:#f8fafc;'
				: 'font-weight:600;font-size:13px;color:#1d2327;';
			$tag_style   = $is_dark
				? 'font-size:12px;color:#cbd5e1;line-height:1.45;'
				: 'font-size:12px;color:#3c434a;line-height:1.45;';
			$link_style  = $is_dark
				? 'font-size:12px;font-weight:600;color:#7dd3fc;text-decoration:underline;'
				: 'font-size:12px;font-weight:600;color:#135e96;text-decoration:underline;';

			$html = '<div class="wpfh-help-tools-card" style="' . esc_attr( $box_style ) . '">';

			// Section 1: Direct Help & Documentation links.
			if ( '' !== $this->docs_url || '' !== $this->support_url ) {
				$html .= '<div class="wpfh-help-section">';
				$html .= '<h3 style="' . esc_attr( $h3_style ) . '">' . esc_html( $this->strings['help_heading'] ) . '</h3>';
				$html .= '<p style="' . esc_attr( $desc_style ) . '">' . esc_html( $this->strings['help_desc'] ) . '</p>';
				$html .= '<p style="margin:0;display:flex;flex-wrap:wrap;gap:10px;align-items:center;">';

				if ( '' !== $this->docs_url ) {
					$html .= '<a href="' . esc_url( $this->docs_url ) . '" target="_blank" rel="noopener noreferrer" class="button button-secondary">';
					$html .= esc_html( $this->strings['docs_cta'] );
					$html .= '</a>';
				}

				if ( '' !== $this->support_url ) {
					$html .= '<a href="' . esc_url( $this->support_url ) . '" target="_blank" rel="noopener noreferrer" class="button button-secondary">';
					$html .= esc_html( $this->strings['support_cta'] );
					$html .= '</a>';
				}

				$html .= '</p></div>';
			}

			// Divider when both sections exist.
			if ( ( '' !== $this->docs_url || '' !== $this->support_url ) && ! empty( $this->tools ) ) {
				$html .= '<hr style="' . esc_attr( $hr_style ) . '" />';
			}

			// Section 2: Curated Companion Free Tools.
			if ( ! empty( $this->tools ) ) {
				$html .= '<div class="wpfh-tools-section">';
				$html .= '<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:12px;">';
				$html .= '<h3 style="' . esc_attr( $h3_style ) . 'margin:0;">' . esc_html( $this->strings['tools_heading'] ) . '</h3>';
				if ( '' !== $this->catalog_url ) {
					$html .= '<a href="' . esc_url( $this->catalog_url ) . '" target="_blank" rel="noopener noreferrer" style="' . esc_attr( $link_style ) . '">';
					$html .= esc_html( $this->strings['tools_cta'] ) . ' &rarr;';
					$html .= '</a>';
				}
				$html .= '</div>';

				$html .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;">';
				foreach ( $this->tools as $tool ) {
					$html .= '<div style="' . esc_attr( $card_style ) . '">';
					$html .= '<div style="' . esc_attr( $title_style ) . '">' . esc_html( $tool['name'] ) . '</div>';
					if ( '' !== $tool['tagline'] ) {
						$html .= '<div style="' . esc_attr( $tag_style ) . '">' . esc_html( $tool['tagline'] ) . '</div>';
					}
					$html .= '<div><a href="' . esc_url( $tool['url'] ) . '" target="_blank" rel="noopener noreferrer" style="' . esc_attr( $link_style ) . '">';
					$html .= esc_html( $this->strings['tool_link_cta'] ) . ' &rarr;';
					$html .= '</a></div>';
					$html .= '</div>';
				}
				$html .= '</div></div>';
			}

			$html .= '</div>';

			return $html;
		}

		/**
		 * Render the Help & Tools card safely.
		 *
		 * @return void
		 */
		public function render() {
			$html = $this->get_html();
			if ( '' === $html ) {
				return;
			}

			echo wp_kses(
				$html,
				array(
					'div' => array(
						'class' => true,
						'style' => true,
					),
					'h3'  => array(
						'style' => true,
					),
					'p'   => array(
						'style' => true,
					),
					'hr'  => array(
						'style' => true,
					),
					'a'   => array(
						'href'   => true,
						'target' => true,
						'rel'    => true,
						'class'  => true,
						'style'  => true,
					),
				)
			);
		}
	}
}
