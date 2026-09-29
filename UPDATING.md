# Cutting a release for the MyEbookBazaar auto-updater

The built-in updater (Admin → Updates) pulls the tracked branch's latest
commit straight from GitHub and installs it. Follow this checklist every
time you cut a release so the updater can do its job safely.

## 1. Prepare the release

1. Make sure the working tree is clean and the full test suite is green:
   `php artisan test`.
2. Bump the version in `config/version.php` (e.g. `'version' => '1.0.1'`).
   The updater records whatever version is in the release as the
   installed version — the `0.0.1` in the dev tree is never changed by hand.
3. Build the frontend assets and commit them:
   `npm run build` → commit everything under `public/build/`.
   **The release must ship compiled assets** — the updater does not run
   `npm` on the server.
4. Run `composer install --no-dev --optimize-autoloader` locally so the
   release's file tree is complete (the updater copies files, it does not
   run composer on the server).

## 2. Migration safety

- Every schema change must be a normal Laravel migration in
  `database/migrations/` — the updater runs `php artisan migrate --force`.
- Migrations must be **backwards-compatible with a rollback**: never drop
  a column in the same release that old code still reads. If you must drop
  something, do it in two releases (release N stops reading it,
  release N+1 drops it).

## 3. Tag and push

```bash
git add -A
git commit -m "Release 1.0.1"
git tag v1.0.1
git push origin main --tags
```

For a beta: create the tag as a **pre-release** on GitHub and set the
updater channel to "Beta" in Settings → Updates.

## 4. What the updater expects

- A fine-grained personal access token with **Contents: read** on this
  repository only, entered in Settings → Updates (never in chat).
- The tarball GitHub builds for the branch tip. The updater downloads the
  archive for the exact commit SHA the API advertised and verifies the
  archive's top-level directory matches that SHA before touching anything.
- Stable channel reads the latest published **release** for its changelog;
  beta reads the newest release entry including pre-releases.

## 5. What the updater preserves

`.env` (and `.env.*`), `storage/app` (private ebooks, uploads, backups)
and `storage/logs` are never overwritten. Everything else comes from the
release archive.

## 6. If an update goes wrong

The updater takes a full backup (database + files) before every update and
restores it automatically on any failure, then brings the site back online.
Manual restore is also available any time under Admin → Backups.
