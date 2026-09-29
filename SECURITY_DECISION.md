# SECURITY_DECISION.md — Laravel 11 vs 12 (owner decision)

## Decision: stay on Laravel 11 for the 1.0.0 launch

Laravel 11 is the current framework version of this project
(`laravel/framework ^11.31`, PHP `^8.2`). There is **no known
unpatched vulnerability affecting this application's code paths** that
forces an upgrade before launch.

## What was reviewed

- **CR/LF-related advisories**: the forms that accept free text (contact,
  reviews where enabled, admin text fields) strip or reject control
  characters at validation; responses set headers through Laravel's
  response objects, which do not pass raw user input into headers.
  **Mitigated in this codebase.**
- **Signed-URL path-confusion**: Laravel 12 changed how signed routes
  normalise paths. The fix exists **only in Laravel 12** — it cannot be
  backported by us without forking framework code.
- Our signed download URLs (`/download/{order}/{book}`, `signed`
  middleware, 10-minute expiry) additionally check order ownership and
  paid status in the controller, so a forged or confused signature alone
  buys an attacker nothing: the controller still aborts 404/403 for
  anyone who does not own a paid order containing the book.

## Why not upgrade silently

- Laravel 12 requires PHP 8.2+ (fine) but changes several framework
  behaviours; a silent upgrade risks breaking checkout, webhooks, and
  the updater with no test coverage for the new behaviour.
- The upgrade is a planned, tested migration — not a patch to sneak in
  at launch.

## Owner's explicit choice (pick one before or after launch)

1. **Launch on Laravel 11** (recommended): ship 1.0.0 now; schedule the
   Laravel 12 upgrade as a tested 1.x maintenance release.
2. **Upgrade to Laravel 12 first**: delays launch by the time needed to
   upgrade, re-run the full suite, and re-verify checkout/webhooks/
   updater end to end.

Either way, the upgrade path is `composer update laravel/framework`
plus the Laravel 12 upgrade guide steps — never an unattended change.
