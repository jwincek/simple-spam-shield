# Contributing to Simple Spam Shield

Thanks for your interest in improving the plugin. This document covers the
local setup, the quality gates, and how to extend the guard pipeline.

## Requirements

- PHP 8.2+
- [Composer](https://getcomposer.org/)
- WordPress 6.2+ (only needed to run the plugin or Plugin Check, not for unit tests)

## Setup

```bash
composer install
```

This installs the development tooling only (PHP_CodeSniffer + WordPress
Coding Standards, and PHPUnit). The plugin itself ships with **no runtime
dependencies**, so nothing under `vendor/` is included in the distributed
package.

## Quality gates

All three run in CI (`.github/workflows/ci.yml`) on every push and pull
request, and should pass locally before you open a PR.

### Coding standards

```bash
composer lint        # check
composer lint:fix    # auto-fix what can be fixed
```

The ruleset is the full WordPress standard (`phpcs.xml.dist`) with **every
`WordPress.Security.*` and `WordPress.DB.PreparedSQL*` sniff enforced**. A
small set of purely stylistic sniffs is excluded to match the codebase's
deliberate modern style (short arrays, typed signatures); see the comments
in `phpcs.xml.dist` for the rationale. Direct queries against the plugin's
own custom log table are expected — keep them prepared and column-whitelisted.

### Unit tests

```bash
composer test
```

Tests live in `tests/` and run **without a WordPress install or a
database**. `tests/bootstrap.php` provides lightweight stubs for the handful
of WP functions the pure logic touches (options, transients, `WP_Error`,
sanitizers) and reuses the plugin's own autoloader. Keep tests fast and
dependency-free; if a unit needs heavy WordPress integration, prefer
refactoring the pure logic out so it can be tested in isolation (as with
`Token`, `Request`, and `Database_Manager::build_filter()`).

Every guard has a corresponding `tests/<Guard>Test.php`. New guards must
ship with tests.

### Plugin Check

The WordPress.org review tool. Run it against a distribution copy (dev
files excluded) so it sees only what ships:

```bash
# Requires the Plugin Check plugin installed in a local WordPress site.
rsync -a --exclude-from=<(grep -v '^#' .distignore) ./ /path/to/wp-content/plugins/simple-spam-shield/
wp plugin check simple-spam-shield
```

Only `WordPress.DB.DirectDatabaseQuery` warnings are expected (inherent to a
custom-table plugin); there should be no errors.

## Architecture

```
config/                JSON definitions (guard rules, default settings)
includes/core/         Infrastructure: Config, Guard_Runner, Database_Manager,
                       Assets, Admin, Request, Token
includes/guards/       One class per spam check (the "abilities" layer)
includes/integrations/ Thin consumers hooking WP Comments, WooCommerce, Jetpack
admin/                 WP_List_Table for the spam log viewer
assets/                Front-end honeypot CSS + guard JS
tests/                 PHPUnit unit tests
```

Guards are independent checks that implement `Guard_Interface` (most extend
`Abstract_Guard`). The `Guard_Runner` loads them from `config/guards.json`,
sorts by weight (highest first), and runs them as a pipeline — the first
failure short-circuits and blocks the submission. Integrations normalize
their form data into a common shape and delegate all checking to the runner,
so guards never need to know about comment arrays vs. Jetpack field data.

## Adding a new guard

1. **Create the class** in `includes/guards/class-<slug>.php`:

   ```php
   namespace Simple_Spam_Shield\Guards;

   final class My_Guard extends Abstract_Guard {
       public function check( array $data, string $context ): \WP_Error|true {
           // Return true to pass, or $this->fail( $message ) to block.
       }
   }
   ```

   The file/class naming follows the autoloader: `My_Guard` →
   `class-my-guard.php`.

2. **Declare it** in `config/guards.json` with a `label`, `description`,
   `enabled_by_default`, `weight`, and any per-guard thresholds (read in the
   guard via `$this->config[...]`).

3. **Register it** in `Guard_Runner::init()`'s `$guard_map` (slug → class).

   The on/off toggle appears on the settings page automatically (the admin
   screen reads `guards.json`). Add a numeric/text setting in
   `Admin::register_settings()` only if the guard needs one.

4. **Add tests** in `tests/My_GuardTest.php`, covering both the blocking and
   passing paths plus the Jetpack-context behavior if the guard depends on
   JS-injected fields.

## Distribution

The shipped package is runtime-only. Files listed in `.distignore` (dev
tooling, tests, source control, the GitHub-facing `README.md`) are excluded
from the WordPress.org build; `readme.txt` is the user-facing readme.

## Reporting security issues

Please do not open public issues for security vulnerabilities. Report them
privately to the maintainer so a fix can be prepared before disclosure.
