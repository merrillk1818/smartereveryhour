# Smarter Every Hour

A Laravel-based micro-learning PWA. You define curricula and topics, and the app delivers
bite-sized lessons on a schedule, then lets you chat with an AI tutor (Google Gemini) about
each one and turn the conversation into new topics for your curriculum.

## Features

- **Curricula & topics** — organize what you want to learn into curricula, each with an
  ordered list of topics and skill levels.
- **Scheduled delivery** — a queued job dispatches learning sessions on a recurring
  schedule you configure per-user.
- **AI tutor chat** — each learning session opens a chat with Gemini that can answer
  questions and suggest/save new topics based on the conversation.
- **Multi-channel notifications** — lessons can be delivered via Web Push (VAPID),
  Pushover, or SMS (Vonage).
- **PWA** — installable, with a service worker and web app manifest.
- **Admin panel** — basic user management for admins.

## Stack

- PHP 8.3, Laravel 13
- MySQL/MariaDB, Redis (cache/queue)
- Vite for frontend assets
- Google Gemini API for the AI tutor
- Web Push, Pushover, and Vonage SMS for notifications
- Cloudflare Turnstile for bot protection on auth forms

## Setup

```bash
composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate
```

Edit `.env` and set at minimum:

- Database credentials (`DB_*`)
- `GEMINI_API_KEY` — from [Google AI Studio](https://aistudio.google.com/)
- `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` — generate with `php artisan vapid:generate`
- Whichever notification channel(s) you want: `PUSHOVER_APP_TOKEN`, or
  `VONAGE_API_KEY` / `VONAGE_API_SECRET` / `VONAGE_FROM_NUMBER`
- `TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET_KEY` — from Cloudflare, if using Turnstile

Then:

```bash
php artisan migrate
php artisan serve
```

Run the queue worker and scheduler for notification delivery:

```bash
php artisan queue:work redis --queue=notifications,default
php artisan schedule:work
```

## License

MIT
