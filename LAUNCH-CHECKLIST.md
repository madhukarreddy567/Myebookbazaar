# LAUNCH-CHECKLIST.md — Go-live checklist for MyEbookBazaar

Work through every item before taking real payments. Check each box only when
it is genuinely done and verified.

## Payments
- [ ] Live Razorpay keys entered in Settings (never in chat, code, or screenshots)
- [ ] Live Razorpay webhook URL set in the Razorpay dashboard
- [ ] International Payments enabled in Razorpay (required for USD orders)

## Server
- [ ] `APP_ENV=production` in `.env`
- [ ] `APP_DEBUG=false` in `.env`
- [ ] A brand-new `APP_KEY` generated on the server (`php artisan key:generate`)
- [ ] Fresh production database created; `php artisan migrate --seed` run on it
      (never import data from the sandbox/dev database)
- [ ] Admin password changed from the seeded default
- [ ] Admin 2FA enabled

## Integrations
- [ ] Turnstile keys saved in Settings
- [ ] Cloudflare SSL mode set to **Full (strict)**
- [ ] SMTP tested (send a real test email)

## End-to-end tests
- [ ] One real low-value **INR** payment tested end to end: checkout →
      payment → download works → invoice email received
- [ ] One real low-value **USD** payment tested end to end: checkout →
      payment → download works → invoice email received
- [ ] One backup restored to a **test** database successfully
- [ ] Razorpay **Test connection** in Settings → Payments returns passing

## After launch
- [ ] First real backup confirmed in `/reddy/backups`
- [ ] Update checker configured (`/reddy` → Settings → Updates)
