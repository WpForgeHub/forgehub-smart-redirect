=== ForgeHub Smart Redirect ===
Contributors: wpforgehub
Tags: 404 redirect, smart redirect, 301 redirect, 404 healer, fuzzy matching
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Intelligent 404 auto-healing with fuzzy slug matching, outbound upgrade click tracker, and instant 301 redirect management.

== Description ==

Broken links and 404 errors destroy user experience and drain search engine ranking signals. Typoed URLs from social media or old inbound links typically result in a dead end.

**Smart Redirect** solves this through intelligent self-healing:

* **Fuzzy 404 Auto-Healing**: When a visitor hits a 404 page, the algorithm computes slug similarity against published posts, pages, and WooCommerce products. If a high-confidence match is detected (>= 65%), it automatically issues a seamless 301 redirect to the intended destination.
* **Outbound SaaS & Upgrade Tracker**: Track clicks through clean shortlinks like `/wpfh-pro/{plugin}` with click counts and last-clicked timestamps.
* **Custom 301/302 Redirect Manager**: Set up explicit redirects for moved pages directly within WordPress.
* **404 Auto-Heal Activity Log**: Review recovered visitors and auto-healed paths directly from the dashboard.

### Key Features (Free Version)
* **Automated 404 Recovery**: Saves lost traffic without manual configuration.
* **Custom 301 & 302 Rules**: Fast, lightweight redirect router.
* **Outbound Click Analytics**: Monitor upgrade conversions across plugins.
* **Zero Database Bloat**: Clean transients and lightweight options storage.

### Pro Features
* **Automated Slug Change Sentinel**: Auto-generates 301 redirects whenever an existing post or product slug is modified.
* **Regex Rule Engine**: Handle complex wildcard routing.
* **Instant 404 Spike Alerts**: Webhook notifications for sudden 404 floods.
* **Query Parameter Preservation**: Keep UTM tracking tags and marketing parameters intact.

== Installation ==

1. Upload plugin files to `/wp-content/plugins/forgehub-smart-redirect`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access **Tools > Smart Redirect** to manage custom rules and view auto-healed logs.

== Changelog ==

= 1.1.4 =
* Remove hardcoded /upgrade routes, unify Sentinel schema, add rule deletion & 404 log UI, wire Pro Regex/404 Alert UIs, scope Pro UTM to frontend.


= 1.1.3 =
* Rename: plugin is now "ForgeHub Smart Redirect" (ForgeHub naming standard).
* Fix: Plugins screen now shows a consistent "Settings" link.
= 1.1.2 =
* Rename: plugin slug and text domain are now forgehub-smart-redirect (WordPress.org naming rules).
* Docs: README and readme.txt updated to the unified repository standard.

= 1.1.0 =
* Feature (Pro): Automated Post & Product Slug Change Sentinel.
* Feature (Pro): Regex wildcard redirect engine with backreference substitution.
* Feature (Pro): 404 Spike alerts with email notifications and webhook triggers.
* Feature (Pro): UTM and analytics query parameter preservation across redirects.
* Enhancement: Freemius license filter integration (fs_can_use_premium_code__forgehub-smart-redirect).

= 1.0.0 =
* Initial public release.
* Fuzzy 404 auto-healing algorithm.
* Outbound `/wpfh-pro/` click tracker.
* Custom 301/302 redirect manager.
