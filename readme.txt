=== Simple Spam Shield ===
Contributors: jeromewincek
Tags: spam, antispam, comments, honeypot, woocommerce
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Config-driven spam protection for comments, WooCommerce reviews, and Jetpack contact forms — no external services, API keys, or CAPTCHAs.

== Description ==

Simple Spam Shield blocks spam on the forms your visitors actually use — WordPress comments, WooCommerce product reviews, and Jetpack contact form blocks — without sending anything to a third-party service, requiring an API key, or putting a CAPTCHA in front of your users.

Protection is built from a pipeline of independent **guards**. Each guard is a small, focused check (a hidden honeypot field, a submit-speed gate, a keyword filter, and so on). Guards run in priority order, and the first one to fail blocks the submission. Every guard can be toggled and tuned from a single settings page, and every block can be logged for review.

= Spam guards =

* **Honeypot** — a hidden field that bots fill in but humans never see.
* **Duplicate detection** — rejects identical submissions sent within a short window.
* **Time gate** — rejects submissions completed faster than a human could plausibly type.
* **Nonce** — validates a WordPress nonce to deter automated cross-site posting.
* **Link limit** — flags submissions that contain too many URLs.
* **Keyword block** — rejects submissions matching a configurable blocklist of words or phrases.
* **Behavioral analysis** (optional) — scores mouse movement, clicks, and time on page to spot bot-like interaction.

= Why you might choose it =

* **No external services.** Nothing leaves your site. No accounts, no API keys, no per-submission fees.
* **No CAPTCHA.** Protection is invisible to legitimate visitors.
* **Allowlist.** Trusted IPs, CIDR ranges, email addresses, and email domains bypass every guard.
* **Logging with retention.** Blocked submissions are recorded in a dedicated table with a paginated admin viewer, and old entries are pruned automatically on a schedule you control.
* **Privacy-aware.** The plugin registers suggested privacy-policy text describing exactly what it records.
* **Modern, dependency-free code.** PHP 8.1+, vanilla front-end JavaScript (no jQuery), and no runtime third-party libraries.

= Works with =

* WordPress comments (always).
* WooCommerce product reviews (when WooCommerce is active).
* Jetpack contact form blocks (when Jetpack is active).

== Installation ==

1. Upload the `simple-spam-shield` folder to `/wp-content/plugins/`, or install it through **Plugins → Add New**.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Spam Shield → Settings** to choose which form types to protect and to enable or tune individual guards.
4. Review anything that gets blocked under **Spam Shield → Spam Logs**.

No further configuration is required — sensible defaults are applied on activation.

== Frequently Asked Questions ==

= Does this send my data to any external service? =

No. Every check runs on your own server. Nothing about a submission is sent anywhere outside your site.

= Will legitimate visitors see a CAPTCHA or extra step? =

No. All protection is invisible. The honeypot field is hidden, and the timing and behavioral checks happen in the background.

= What does it store, and for how long? =

When a submission is blocked (and logging is enabled), the plugin records the guard that blocked it, the form context, the reason, a short excerpt of the content, the visitor IP address, and the browser user-agent. Entries older than the retention window (default 30 days, configurable; set to 0 to keep them indefinitely) are pruned automatically. The plugin also registers suggested privacy-policy text you can add to your site's policy.

= I'm behind Cloudflare or a load balancer and the wrong IP is logged. =

By default the plugin uses the direct connection IP, because forwarded headers can be spoofed to bypass the allowlist. If your site sits behind a trusted reverse proxy, enable **Trust proxy headers for IP detection** under **Spam Shield → Settings → Allowlist**.

= A legitimate submission was blocked. What do I do? =

Open **Spam Shield → Spam Logs** to see which guard blocked it and why, then loosen that guard on the settings page — for example, raise the link limit, lower the behavioral threshold, or add the sender to the allowlist.

= Does it work with caching plugins? =

Yes. The honeypot, timing, and behavioral fields are injected by JavaScript at page load rather than baked into cached HTML, so full-page caching does not break them.

= Does removing the plugin clean up after itself? =

Yes. Deleting the plugin (not just deactivating it) drops its database table, removes all of its options, clears its scheduled task, and purges its transients.

== Screenshots ==

1. The settings page: protection targets, individual guard toggles, and per-guard thresholds.
2. The allowlist and trusted-proxy options.
3. The Spam Logs viewer with per-row and bulk delete actions.

== Changelog ==

= 1.0.0 =
* Initial release.
* Guard pipeline: honeypot, duplicate detection, time gate, nonce, link limit, keyword block, and optional behavioral analysis.
* Integrations for WordPress comments, WooCommerce product reviews, and Jetpack contact form blocks.
* Allowlist supporting IPs, CIDR ranges, email addresses, and email domains, with an optional trusted-proxy mode for IP detection.
* Database-backed logging with a paginated admin viewer and a configurable auto-purge retention window.
* Suggested privacy-policy content and a clean uninstall routine.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
