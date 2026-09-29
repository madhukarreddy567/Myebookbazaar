# RELEASING.md — How to publish a new version of MyEbookBazaar

## How the built-in updater works (read this first)

The updater in `/reddy/updates` does **not** download the release ZIP from a
GitHub Release. It asks the GitHub API for the latest commit SHA on your
tracked branch, then downloads the **tarball for that exact SHA** from
codeload and verifies the payload directory matches the SHA. There is no
separate checksum step in the updater — the SHA-embedded download URL plus
the payload-directory check is the integrity guarantee.

The release ZIP + `.sha256` built by `.github/workflows/release.yml` are for
**manual** installs and for anyone who wants to verify a download by hand:

```bash
sha256sum -c myebookbazaar-v1.0.1.zip.sha256
```

## Publishing a new version

1. **Bump the version** in `config/version.php`, e.g. `'version' => '1.0.1'`.
2. **Run the full test suite** — it must be 100% green:
   ```bash
   php artisan test
   ```
3. **Commit** everything:
   ```bash
   git add -A
   git commit -m "Release v1.0.1"
   ```
4. **Tag** the release (tags must look like `v1.0.1`):
   ```bash
   git tag v1.0.1
   ```
5. **Push** the commit and the tag:
   ```bash
   git push origin main
   git push origin v1.0.1
   ```
6. **Confirm the Release**: pushing the tag triggers the `Build release ZIP`
   workflow. On github.com, open your repo → **Actions** → check the run is
   green, then **Releases** → confirm `v1.0.1` exists with
   `myebookbazaar-v1.0.1.zip` and `myebookbazaar-v1.0.1.zip.sha256` attached.
7. **Update the live site** from `/reddy/updates` → *Check for updates* →
   confirm the SHA → enter your admin password → *Apply update*. A backup is
   taken automatically first, and the site rolls back to it if anything fails.

## Rules

- Never tag a version unless `php artisan test` is fully green.
- Tag names must start with `v` (e.g. `v1.0.1`) or the workflow will not run.
- The workflow builds the ZIP from the **tagged commit**, so commit before tagging.
