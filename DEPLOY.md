# DEPLOY.md — MyEbookBazaar production deployment

Plain-language guide to putting MyEbookBazaar (v1.0.0) on a real server.
You do NOT need Node.js on the server: the compiled CSS/JS in
`public/build/` is committed to git, so the server never builds assets.

## 1. Server requirements

- **PHP 8.2 or newer** (8.3 recommended) with extensions:
  `bcmath, ctype, curl, dom, fileinfo, json, mbstring, openssl, pdo, pdo_mysql,
  tokenizer, xml, zip, gd` (gd is used for image handling)
- **MySQL 8** (MariaDB works too, but MySQL is the tested target)
- **Composer 2**
- A web server pointing at the **`public/`** directory (Nginx or Apache)
- PHP CLI access (for `php artisan` commands)
- **Cron access** — needed for the scheduler (update checks + daily backups).
  Without cron, those two features silently never run.

## 2. First deploy — step by step

```bash
# 1. Get the code on the server
git clone <your-private-repo-url> myebookbazaar
cd myebookbazaar

# 2. Install PHP dependencies (no dev packages on production)
composer install --no-dev --optimize-autoloader

# 3. Environment file
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set at minimum:

| Key | Value |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://your-domain.com` (no trailing slash) |
| `APP_TIMEZONE` | `Asia/Kolkata` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | your MySQL details |
| `MAIL_MAILER` | `smtp` (then fill `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`) |
| `SESSION_SECURE_COOKIE` | `true` (once HTTPS works) |

```bash
# 4. Database
php artisan migrate --force

# 5. Storage link (covers are served from storage/app/public)
php artisan storage:link

# 6. Create the first admin user (via env vars + seeder)
# Add these two lines to .env first (use a strong password):
#   ADMIN_EMAIL=you@example.com
#   ADMIN_PASSWORD=choose-a-strong-password
php artisan db:seed --class=AdminSeeder --force
# Then DELETE those two lines from .env (or set a dummy value) and run:
php artisan config:cache
# After first login, change the password in /reddy and enable 2FA.

# 7. Cache everything for speed
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 3. Scheduler cron (REQUIRED)

Two background jobs keep the site healthy — they only run if cron calls
the scheduler every minute:

```cron
* * * * * cd /path/to/myebookbazaar && php artisan schedule:run >> /dev/null 2>&1
```

What the scheduler does:

| Job | Schedule | Purpose |
|---|---|---|
| `updates:check` | every 6 hours | Checks GitHub for a new release (never applies anything by itself) |
| `backups:auto` | daily | Takes a full automatic backup (files + database), prunes old ones |

Verify it works: `php artisan schedule:list`.

## 4. Queue

**No queue worker is needed.** All mail is sent synchronously and nothing
dispatches queued jobs. `QUEUE_CONNECTION` can stay `database` (unused).

## 5. After the site is live — admin panel checklist

Log in at `https://your-domain.com/reddy` and fill in
**Settings → each tab**. Nothing secret ever goes in chat or email:

1. **Site** — name, support email, Instagram/YouTube links.
2. **Payments** — paste Razorpay Key ID + Key Secret (test keys first,
   then live keys). Use *Test connection*.
3. **Email** — SMTP host/port/username/password, or keep `log` while testing.
4. **Security** — Turnstile site key + secret key (create at
   cloudflare.com), then test.
5. **Google login** — enable it, paste Google Client ID + Client Secret
   (create in Google Cloud Console → APIs & Services → Credentials),
   then *Test connection*.
6. **Updates** — enable, set repo owner/name/branch, paste a read-only
   fine-grained GitHub token, then *Test connection*.
7. **Books** — upload the real ebook cover + PDF for each book.
8. Turn **off** `APP_DEBUG` (already false in `.env`) and confirm the
   `maintenance` / coming-soon switches are as you want them.

## 6. Updating the site later (built-in updater)

1. Push new code to the GitHub repo (tag a release for the stable channel).
2. In `/reddy` → **Updates**, click *Check for updates*.
3. Review the version, then **Apply update** and confirm.
4. The updater automatically: takes a backup first, turns on maintenance
   mode, verifies the download, runs migrations, clears caches, and turns
   maintenance mode off. If anything fails it **rolls back automatically**.

You can also update the old way: `git pull`, then
`php artisan migrate --force && php artisan config:cache &&
php artisan route:cache && php artisan view:cache`.

## 7. Rollback (if an update breaks something)

1. Go to `/reddy` → **Backups**.
2. Find the automatic backup taken right before the update
   (named like `backup-20260929-103939-auto.zip`).
3. Click **Restore** and confirm. The site returns to exactly how it was:
   files + database.

## 8. Things that will NOT work on cheap shared hosting

- **Scheduler cron** — many cheap hosts don't allow real cron jobs, or only
  hourly. Without it: no 6-hour update checks, no daily automatic backups.
  (You can still back up manually from `/reddy` → Backups.)
- **`storage:link`** — needs a one-time SSH command. Some panels offer a
  "cron run once" trick; without it, uploaded covers won't display.
- **Long-running artisan commands** — restores of large backups can hit
  PHP time limits on shared hosts (ask the host to raise `max_execution_time`).
- **OPcache/file permissions** — the `storage/` and `bootstrap/cache/`
  directories must be writable by the web server user.

**Recommended shape:** a small VPS (2 GB RAM) or a Laravel-friendly host
with SSH + cron + MySQL 8. That covers everything above with room to grow.

## 9. Quick health check after deploy

- [ ] Homepage loads over HTTPS, no mixed-content warnings
- [ ] `https://your-domain.com/sitemap.xml` returns XML
- [ ] `https://your-domain.com/robots.txt` points at the sitemap
- [ ] `https://your-domain.com/admin` returns **404**
- [ ] `/reddy/login` works; wrong password is rejected
- [ ] Test purchase with Razorpay **test** keys (never real money while testing)
- [ ] A download link from My Library works and expires
- [ ] `php artisan schedule:list` shows both jobs
- [ ] Take one manual backup from `/reddy` → Backups and download it

## 10. CloudPanel deployment path

CloudPanel (free server control panel) works well for this project:

1. **Create the site**: Sites → Add PHP Site → PHP 8.3 → enter your domain.
   Set the **Document Root** to `/home/<user>/htdocs/<domain>/public`.
2. **Create the database**: Databases → Add Database (MySQL 8) → create a
   database + user. Copy the credentials into the project's `.env`.
3. **Upload the code**: use the File Manager or `git clone` over SSH into
   the site directory, then run the steps in section 2
   (`composer install --no-dev`, `cp .env.example .env`,
   `php artisan key:generate`, `php artisan migrate --seed`,
   `php artisan storage:link`, then the cache commands).
4. **SSL**: Sites → your domain → SSL → enable free Let's Encrypt certificate.
5. **Cron**: Sites → your domain → Cron Jobs → add:
   ```cron
   * * * * * cd /home/<user>/htdocs/<domain> && php artisan schedule:run >> /dev/null 2>&1
   ```
   This powers the 6-hour update checks and the daily automatic backups.

## 11. Supervisor — queue worker

**You do not need a queue worker.** This project sends all mail
synchronously and dispatches no queued jobs (`app/Jobs` does not exist;
`QUEUE_CONNECTION=database` is set but unused). Leave Supervisor
uninstalled. If a future version starts queueing mail, set
`QUEUE_CONNECTION=database` and add this Supervisor program:

```ini
[program:myebookbazaar-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/myebookbazaar/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/myebookbazaar/storage/logs/worker.log
```

## 12. Cloudflare setup

1. Point your domain's DNS to the server through Cloudflare (proxied/orange cloud).
2. **SSL/TLS → Overview → Encryption mode → Full (strict).**
   (Never use "Flexible" — it breaks secure cookies and webhooks.)
3. **Real visitor IP**: in the project's `.env`, set:
   ```
   TRUSTED_PROXIES=*
   ```
   The app then trusts Cloudflare's proxy headers and `request->ip()`
   returns the real visitor address (used by rate limiting, audit logs and
   the maintenance-mode IP bypass). Only set this when the site is actually
   behind Cloudflare/a proxy — leave it empty otherwise.
4. **Webhooks**: the Razorpay webhook URL is
   `https://your-domain.com/webhooks/razorpay` — add it in the Razorpay
   dashboard. It is CSRF-exempt by design; authenticity is proven by the
   HMAC signature check in the webhook controller.

## 13. Fresh server, fresh secrets — WARNING

- **Always generate a NEW `APP_KEY` on the production server**
  (`php artisan key:generate`). Never copy the key from your dev/sandbox
  `.env` — anyone with the old key can decrypt production secrets.
- **Always use a FRESH database** and run `php artisan migrate --seed` on
  it. **Never import data from the sandbox/dev database** — it contains
  test users, test orders and dev secrets.
- The first admin comes from `ADMIN_EMAIL` / `ADMIN_PASSWORD` in `.env`
  (used once by the seeder). Delete those two lines from `.env` right
  after, then change the password in `/reddy` and enable 2FA.

## 14. File permissions

The web-server user (often `www-data`) must be able to write to:

```bash
cd /path/to/myebookbazaar
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

Everything else should be owned by your deploy user and **not** writable
by the web server.

## 15. Restoring from a backup

Use the built-in restore — no manual SQL needed:

1. Go to `/reddy` → **Backups**.
2. Find the backup zip (automatic ones are named like
   `backup-20260929-103939-auto.zip`, or upload one via the provided button).
3. Click **Restore** and confirm. The site restores **both** files and the
   database to exactly how they were when the backup was taken, then clears
   all caches.

To restore to a **test** database first (recommended before touching
production): restore on a staging copy of the site, or unzip the backup's
`database.sql` and import it manually:

```bash
unzip -p backup-XXXX.zip database.sql | mysql -u <user> -p <test_database>
```

See `LAUNCH-CHECKLIST.md` before going live.

## 16. Fresh install from myebookbazaar-schema.sql

If you have the `myebookbazaar-schema.sql` file (shipped with the v1.0.0
release), you can set up a brand-new database without running the seeders.
The file contains the full table structure plus starter data only
(countries, default settings, the "Fit at Home: Start (Edition 1)" book
without its PDF/cover, its category, and the policy pages). It contains no
users, admins, orders, or secrets. The `migrations` table is pre-filled, so
`php artisan migrate` afterwards reports nothing pending.

### (a) Import via phpMyAdmin

1. Open phpMyAdmin and click **New** in the left sidebar.
2. Database name: `myebookbazaar`. Collation: `utf8mb4_unicode_ci`.
   Click **Create**.
3. Select the new database, then click the **Import** tab.
4. **Choose file** → select `myebookbazaar-schema.sql` → click **Go**.
5. You should see a success message. The database now has 27 tables.

### (b) .env setup

On the server, in the project folder:

```bash
cp .env.example .env
php artisan key:generate
```

Then edit `.env` and set at minimum:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=myebookbazaar
DB_USERNAME=<your-db-user>
DB_PASSWORD=<your-db-password>

APP_URL=https://your-domain.com

ADMIN_EMAIL=you@example.com
ADMIN_PASSWORD=<choose-a-strong-password>
```

### (c) Create the admin

```bash
php artisan app:create-admin
```

This creates the Super Admin from `ADMIN_EMAIL` / `ADMIN_PASSWORD`. It
refuses to run if an admin already exists, and it never prints the password.
After your first login, change the password and enable 2FA.

### (d) Verify

```bash
php artisan migrate:status   # every migration should show "Ran"
```

Then open the site: the home page loads, `/reddy/login` shows the admin
login, and `/admin` returns 404.
