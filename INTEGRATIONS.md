# INTEGRATIONS.md — what's code-complete vs what the owner must do

Every integration below is **fully coded and tested**. "Owner action"
means a step only the business owner can take (their accounts, their
credentials, their OTP/KYC). Nothing here asks the owner to paste code.

## 1. Razorpay (payments)

- **Code status: COMPLETE.** Server-recalculated checkout, mandatory
  no-refund acceptance, Razorpay signature verification on return and
  webhook, idempotent webhook handling, audited manual mark-as-paid.
- **Owner action:** log in to `/reddy` → Settings → Payments. Paste the
  **test** Key ID + Key Secret first, run a test purchase (no real money),
  then switch to **live** keys. Use *Test connection* in the panel.
- Pending with Razorpay (owner): International Payments approval for
  USD cards.

## 2. Email (SMTP)

- **Code status: COMPLETE.** All mail (order confirmations, invoices,
  password resets, verification) sends through Laravel's mailer.
- **Owner action:** `/reddy` → Settings → Email. Enter SMTP host, port,
  username, password, encryption, and the "from" address. While testing,
  `MAIL_MAILER=log` in `.env` keeps mail in the log file instead of
  sending.
- Until SMTP is filled in, customers still get their downloads (delivery
  does not depend on email), they just won't receive email receipts.

## 3. Turnstile (bot protection)

- **Code status: COMPLETE.** Cloudflare Turnstile widget on login,
  register, forgot-password, and contact forms; server-side verification;
  honeypot + rate limits as backup layers.
- **Owner action:** create a free Turnstile site at cloudflare.com, then
  paste the **site key** + **secret key** into `/reddy` → Settings →
  Security, and flip the switch on. Test with the panel's test button.

## 4. Google OAuth (sign in with Google)

- **Code status: COMPLETE.** Socialite driver wired, account linking by
  verified email, tested.
- **Owner action:** Google Cloud Console → APIs & Services → Credentials →
  create an **OAuth client ID** (Web application). Add the authorized
  redirect URI shown in `/reddy` → Settings → Google login. Paste the
  Client ID + Client Secret there and use *Test connection*.

## 5. GitHub updater

- **Code status: COMPLETE.** Checks for new releases every 6 hours,
  one-click update with automatic pre-update backup, maintenance mode,
  migration run, cache clear, and automatic rollback on failure.
- **Owner action:** create a **private** GitHub repository for this code,
  then a **fine-grained personal access token** with read-only access to
  that repo. Paste owner/repo/branch + token into `/reddy` → Settings →
  Updates and use *Test connection*.

## 6. Backups

- **Code status: COMPLETE.** Manual backup/restore from `/reddy` →
  Backups, plus a **daily automatic backup** (files + database) with
  pruning. Real backup and restore were tested end to end.
- **Owner action:** none for the feature itself — but the daily automatic
  backup only runs if the **scheduler cron** is installed (see DEPLOY.md).
  Without cron, take backups manually from the panel.

## 7. Downloads

- **Code status: COMPLETE.** PDFs live in private storage (never in
  `public/`). Customers get 10-minute signed links, default limit of 5
  downloads per book, download count shown in My Library.
- **Owner action:** upload the real ebook PDF per book in
  `/reddy` → Books → edit → PDF file (max 50 MB, PDF only).

## 8. Feature switches

- **Code status: COMPLETE.** Every major feature has an on/off switch in
  `/reddy` → Settings → Features: registration, login, Google login,
  checkout, coupons, combos, offers, downloads, reviews, contact form,
  email verification, maintenance/coming-soon mode, and more.
- **Owner action:** review the switches once after deploy and turn off
  anything not wanted at launch (e.g. keep `reviews` off until real
  customers exist — never fabricate reviews).

## Quick owner checklist (in order)

1. Deploy (DEPLOY.md) and install the scheduler cron
2. Razorpay **test** keys → test purchase → **live** keys
3. SMTP credentials
4. Turnstile keys → enable
5. Google OAuth client → enable
6. GitHub repo + read-only token → enable updater
7. Upload real book cover + PDF
8. Review feature switches
9. Take a manual backup, download it, keep it somewhere safe
