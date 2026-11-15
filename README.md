# WPFH Smart Redirect & 404 Auto-Healer

[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://php.net/)
[![Release](https://img.shields.io/badge/Release-v1.1.0-purple.svg)](https://github.com/WpForgeHub/wpfh-smart-redirect/releases/tag/v1.1.0)

**WPFH Smart Redirect** transforms dead-end 404 error pages into self-healing, revenue-retaining 301 redirects using fuzzy slug similarity matching.

---

## ⚡ How 404 Auto-Healing Works

When a user visits a mistyped or outdated URL (e.g. `/product/wpfh-ai-alt-text-free/` instead of `/product/ai-alt-text-free/`):
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

## 📄 License

GPL-2.0+ &copy; [WPForgeHub](https://wpforgehub.com).
