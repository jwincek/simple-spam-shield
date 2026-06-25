# Changelog

All notable changes to Simple Spam Shield are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

The user-facing changelog shipped to WordPress.org lives in the
`== Changelog ==` section of `readme.txt`; keep the two in sync.

## [Unreleased]

## [1.0.0] - 2026-06-24

Initial release.

### Added
- Guard pipeline that runs independent spam checks in weighted order and
  short-circuits on the first block: honeypot, duplicate detection, time
  gate, signature (HMAC-signed token), link limit, keyword block, and
  optional behavioral analysis.
- Integrations for WordPress comments, WooCommerce product reviews, and
  Jetpack contact form blocks, each normalizing its data into a shared
  shape consumed by the guard pipeline.
- Server-signed form token driving both the time gate (tamper-proof issue
  time) and the signature guard (proof the form came from this site).
  Because the HMAC does not expire, protection stays valid under full-page
  caching without producing false positives.
- Allowlist supporting exact IPs, CIDR ranges, email addresses, and email
  domains, with an optional trusted-proxy mode for IP detection (off by
  default; direct connection IP is used otherwise).
- Blocked comments and reviews routed to the spam queue by default
  (recoverable), with an option to reject them outright with an error.
- Database-backed logging with a paginated admin viewer: filter by guard
  and context, a user-agent column, and a cached 7-day "blocked / most
  active guard" summary.
- Configurable log retention with a daily auto-purge (default 30 days;
  0 keeps entries indefinitely).
- Suggested privacy-policy content describing what is logged, and a clean
  uninstall routine that removes the table, options, scheduled task, and
  transients.

[Unreleased]: https://github.com/jwincek/simple-spam-shield/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/jwincek/simple-spam-shield/releases/tag/v1.0.0
