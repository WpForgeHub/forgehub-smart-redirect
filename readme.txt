=== ForgeHub Smart Redirect ===
Contributors: wpforgehub
Tags: 404 redirect, smart redirect, 301 redirect, 404 healer, fuzzy matching
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Intelligent 404 auto-healing with fuzzy slug matching, unresolved 404 logging, and custom 301/302 redirect management.

== Description ==

Broken links and 404 errors destroy user experience and drain search engine ranking signals. Typoed URLs from social media or old inbound links typically result in a dead end.

**ForgeHub Smart Redirect** solves this through intelligent self-healing:

* **Fuzzy 404 Auto-Healing**: When a visitor hits a 404 page, the algorithm computes slug similarity against published posts, pages, and WooCommerce products. If a high-confidence match is detected (>= 65%), it automatically issues a seamless 301 redirect to the intended destination while safely skipping static asset requests.
* **Custom 301/302 Redirect Manager**: Set up and delete explicit redirects for moved pages directly within WordPress, with built-in self-redirect loop protection.
* **Unresolved 404 Activity Log**: Monitor recent unresolved 404 requests with path, IP, and timestamp, and clear logs in one click.
* **404 Auto-Heal Activity Log**: Review recovered visitors, target URLs, and fuzzy match confidence scores directly from the dashboard.

### Key Features (Free Version)
* **Automated 404 Recovery**: Saves lost traffic without manual configuration.
* **Custom 301, 302, 307 & 308 Rules**: Fast, lightweight redirect router with loop protection.
* **Unresolved & Auto-Healed Logs**: Full visibility into broken URLs and recovered visits.
* **Zero Database Bloat**: Clean options storage capped at recent entries, with complete cleanup on uninstall.

### Pro Features (Separate Commercial Plugin)
Need automated slug monitoring or regex routing? The separate commercial Pro edition is available at [WPForgeHub](https://wpforgehub.com/product/smart-redirect/) and is not bundled in this free plugin:
* **Automated Slug Change Sentinel**: Auto-generates 301 redirects whenever an existing post or product slug is modified.
* **Regex Rule Engine**: Handle complex wildcard routing with backreference substitution.
* **Instant 404 Spike Alerts**: Email and webhook notifications for sudden 404 floods.
* **Query Parameter Preservation**: Keep UTM tracking tags and marketing parameters intact across redirects.

== Installation ==

1. Upload the `forgehub-smart-redirect` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access **Tools > Smart Redirect** to manage custom rules and view 404 and auto-healed logs.

== Frequently Asked Questions ==

= How does fuzzy 404 auto-healing work? =
When a visitor lands on a 404 URL, the plugin strips common legacy extensions (such as `.html` or `.php`) and compares the requested slug against published posts, pages, and products using PHP's `similar_text()` algorithm. If the similarity score is 65% or higher, it automatically performs a 301 redirect and logs the recovery.

= Will it accidentally redirect missing images, CSS, or JS files? =
No. Static asset extensions (CSS, JS, images, fonts, archives, media, XML, JSON, etc.) are explicitly skipped so missing static files never trigger HTML redirects.

= Does this plugin require any external API or service? =
No. ForgeHub Smart Redirect runs entirely locally on your WordPress server with zero external dependencies.

== Screenshots ==

1. Smart Redirect admin page — 404 auto-heal statistics, custom 301/302 redirect manager, unresolved 404 log, and auto-healed redirect log.

== Changelog ==

= 1.1.5 =
* Compliance: removed all premium/license wrapper code from the WordPress.org free distribution and added uninstall cleanup hook for commercial builds.
* Fix: standardized Upgrade to Pro links to canonical `#pricing` product URL and excluded directory banner assets from the plugin package.
* Docs: updated readme.txt description, FAQ, and screenshots.

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

= 1.0.0 =
* Initial public release.
* Fuzzy 404 auto-healing algorithm.
* Custom 301/302 redirect manager.
