# Simple Spam Shield

Config-driven spam prevention for WordPress Comments, WooCommerce Product Reviews, and Jetpack Contact Form blocks — no external services, no API keys, no CAPTCHA.

## Architecture

This plugin follows the **config-driven, layered architecture** pioneered by [vcpahumane-petstablished-sync](https://github.com/jwincek/vcpahumane-petstablished-sync), adapted for spam prevention:

```
config/                  → JSON definitions (guard rules, default settings)
includes/core/           → Reusable infrastructure (Config loader, Guard Runner, Assets, Admin)
includes/guards/         → Individual spam checks (analogous to "abilities")
includes/integrations/   → Thin hooks into WP Comments, WooCommerce, Jetpack
assets/css/              → Honeypot field styling
assets/js/               → Front-end guard injection script
```

**Guards** are the equivalent of the reference plugin's **abilities** — thin, testable operations with clear inputs/outputs. Each guard implements `Guard_Interface` and is registered from `config/guards.json`. The `Guard_Runner` loads them, sorts by weight, and runs them as a pipeline.

**Integrations** are thin consumers that hook into WordPress, WooCommerce, and Jetpack lifecycle events and delegate all spam-checking to the shared guard pipeline.

## Requirements

- WordPress 6.9+
- PHP 8.1+
- Optional: WooCommerce (for review protection)
- Optional: Jetpack (for contact form protection)

## Installation

1. Download or clone into `wp-content/plugins/simple-spam-shield/`.
2. Activate in **Plugins → Installed Plugins**.
3. Configure at **Settings → Spam Shield**.

## Spam Guards

| Guard | Description |
|---|---|
| **Honeypot** | Hidden field that bots fill in but humans never see |
| **Time gate** | Rejects submissions completed faster than a human could type |
| **Nonce** | Validates a WordPress nonce to prevent cross-site forgeries |
| **Link limit** | Flags submissions containing too many URLs |
| **Keyword block** | Rejects submissions matching blocked keywords or phrases |

All guards are enabled by default and can be toggled individually from the admin settings page. Guard definitions (weights, defaults, field names) live in `config/guards.json`.

## How It Works

1. **Front-end injection**: `guard.js` automatically finds comment forms, WooCommerce review forms, and Jetpack contact form blocks on the page. It injects a hidden honeypot field, a nonce, and a timestamp into each form.

2. **Server-side pipeline**: When a form is submitted, the relevant integration class (Comments, WooCommerce, or Jetpack) normalizes the data and passes it to `Guard_Runner::run()`. The runner executes each enabled guard in weight order. The first failure short-circuits and blocks the submission.

3. **Logging**: Blocked submissions are optionally logged with timestamp, guard name, context, and IP — viewable on the settings page.

## Improvements Over the Reference Architecture

- **Guard pipeline pattern**: Where the reference plugin registers abilities individually, this plugin runs guards as an ordered pipeline with short-circuit logic and weighted priority.
- **Normalized data layer**: Each integration normalizes its form data into a common format before passing to guards, so guards never need to know about WP comment arrays vs. Jetpack form arrays.
- **Front-end auto-injection**: A single JS file protects all form types via CSS selectors and MutationObserver, including dynamically-loaded forms.
- **Block log with admin UI**: A simple but effective log of blocked submissions, viewable directly on the settings page.

## Linting

```bash
composer install
composer lint        # Check
composer lint:fix    # Auto-fix
```

## License

GPL-2.0-or-later.
