# ForgeHub Smart Redirect

<p align="center">
  <img src="assets/banner-1544x500.png" alt="Smart Redirect & 404 Auto-Healer banner" width="100%" />
</p>

[![License](https://img.shields.io/badge/License-GPL%20v2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-v1.2.0-purple.svg)](https://github.com/WpForgeHub/forgehub-smart-redirect/releases/tag/v1.2.0)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://php.net/)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b.svg)](https://wordpress.org/)
[![Tested](https://img.shields.io/badge/Tested-WP%207.1-brightgreen.svg)](https://wordpress.org/)
[![wp.org](https://img.shields.io/badge/wp.org-pending%20review-orange.svg)](https://wordpress.org/plugins/)
[![Freemius](https://img.shields.io/badge/Freemius-Pro%20available-0d9488.svg)](https://wpforgehub.com/)
[![Architecture](https://img.shields.io/badge/Architecture-Zero%20Bloat-06b6d4.svg)](https://wpforgehub.com/)
[![WCAG](https://img.shields.io/badge/WCAG-2.1%20AA%20ready-2563eb.svg)](https://wpforgehub.com/)



**Smart Redirect** transforms dead-end 404 error pages into self-healing, revenue-retaining 301 redirects using fuzzy slug similarity matching.

---

## ⚡ How 404 Auto-Healing Works

When a user visits a mistyped or outdated URL (e.g. `/product/smart-redrect/` instead of `/product/smart-redirect/`):
1. Smart Redirect intercepts the 404 event.
2. It strips known prefixes and compares slug distance (`similar_text`) against all published store products, pages, and blog posts.
3. If confidence is >= 65%, it immediately issues an SEO-friendly 301 Permanent Redirect to the exact matching resource!
4. The auto-heal event is logged in your admin dashboard for audit.

---

## 🚀 Pro Features

* **Automated Slug Change Sentinel**: Detects when posts/products are renamed and auto-creates 301 rules.
* **Regex Wildcard Matching**: Route entire URL structures dynamically.
* **404 Spike Webhooks**: Slack and email notifications when 404s spike.
* **Query Parameter & UTM Preservation**: Carries UTM tags across redirects.

---

## 🔗 Links & Resources

- **Plugin Page:** [https://wpforgehub.com/product/smart-redirect/](https://wpforgehub.com/product/smart-redirect/)
- **Documentation & Help:** [wpforgehub.com/docs](https://wpforgehub.com/docs/)
- **More Free WordPress Plugins:** [wpforgehub.com](https://wpforgehub.com/#products)

---

## 📄 License

GPLv2 or later &copy; [WPForgeHub](https://wpforgehub.com).

---

## 📸 Admin Preview

<p align="center">
  <img src="assets/screenshot-1.png" alt="ForgeHub Smart Redirect WordPress Admin Settings" width="100%" />
</p>
