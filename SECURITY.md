# Security Policy

## Supported Versions

We do not have the resources to support every old version. ZoneMinder uses
Semantic Versioning: even minor versions are stable, odd are development. We
support the current stable release series and the current development series;
the previous stable series receives security fixes on a best-effort basis.

| Version | Supported          |
| ------- | ------------------ |
| 1.39.x (dev)    | :white_check_mark: |
| 1.38.x (stable) | :white_check_mark: |
| 1.36.x (legacy) | :warning: best-effort security fixes |
| < 1.36.x        | :x:                |

## Reporting a Vulnerability

Please report security vulnerabilities **privately** so we can fix them before
they are disclosed publicly. Two options:

1. **GitHub Private Vulnerability Reporting (preferred)** — go to the
   [Security tab](https://github.com/ZoneMinder/zoneminder/security/advisories)
   and click **Report a vulnerability**. This opens a private advisory where we
   can collaborate on a fix and issue a CVE.
2. **Email** — isaac@zoneminder.com.

Please do **not** open a public GitHub issue for a suspected vulnerability.
Non-sensitive hardening suggestions (defense-in-depth with no exploit path) are
fine as normal issues or pull requests.

We aim to acknowledge reports within a few days and to coordinate disclosure
once a fix is available.
