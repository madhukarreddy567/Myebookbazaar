# MyEbookBazaar — Phase 1

Ebook-selling website. **Phase 1** scope: brand + layout, MySQL database,
customer auth (register with country, login, password reset, email verification),
admin auth at `/reddy/*` (separate guard), security baseline, and versioning.

**Stack:** PHP 8.2+, Laravel 11, MySQL only · Blade + Tailwind CSS + Alpine.js
(compiled assets are committed in `public/build`, so the server never needs Node).

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Create the MySQL database, then point `.env` at it (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`):

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve   # http://localhost:8000
```

The seeder inserts ~249 countries and creates the first admin from
`ADMIN_EMAIL` / `ADMIN_PASSWORD` in `.env`.
**Change the admin password right after your first login** at `/reddy/login`
(Reddy → Change Password).

## Key URLs

| URL | What |
|---|---|
| `/` | Home (public) |
| `/books` | Books placeholder (public) |
| `/register`, `/login` | Customer auth (public) |
| `/account` | My Account (login required) |
| `/reddy/login` | **Admin login — there is no `/admin` route (404)** |
| `/reddy/dashboard` | Admin dashboard (admin guard only) |

## Notes

- Document root must be the `public/` directory (standard Laravel layout).
- `config/version.php` holds the app version (`0.0.1`), shown in the admin footer.
- Email verification is toggled by `EMAIL_VERIFICATION_ENABLED` in `.env`.
- Cloudflare Turnstile keys exist in `.env` but server-side verification is **not**
  wired yet (Phase 6). The `<x-turnstile>` component renders nothing without keys.
- Books, cart, payments, coupons and the updater arrive in later phases.
