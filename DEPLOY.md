# Deployment Guide

This app ships as a set of Docker Compose services: its own nginx
(`seh-nginx`, terminating TLS), the Laravel app, a queue worker, a scheduler,
the Next.js landing app, MariaDB, and Redis.

## First-time deployment

### 1. Point DNS at your server
Add A records for your domain (and `www`) to your server's IP.

### 2. Get a TLS certificate
```bash
sudo certbot certonly --webroot -w /var/www/certbot \
  -d yourdomain.com -d www.yourdomain.com \
  --non-interactive --agree-tos --email you@example.com
```
`nginx/conf.d/smartereveryhour.conf` expects the cert at
`/etc/letsencrypt/live/<your domain>/` — update the `server_name` and
`ssl_certificate*` paths in that file to match your domain before deploying.

### 3. Clone and configure
```bash
git clone https://github.com/merrillk1818/smartereveryhour.git
cd smartereveryhour
cp .env.example .env
# Edit .env with real credentials (DB password, GEMINI_API_KEY, VAPID keys, etc.)
```

### 4. Start the stack
```bash
make setup   # creates storage/bootstrap-cache dirs, copies .env.example
make up
```

### 5. First-run setup
```bash
make first-run
# Copy the printed VAPID keys into .env, then:
make restart
```

---

## Subsequent deploys

From the server, in the project directory:
```bash
make deploy
```

This fetches `origin/main`, hard-resets to it, rebuilds all images, and
recreates all containers. A GitHub Actions workflow
(`.github/workflows/deploy.yml`) runs the same `make deploy-app` over SSH on
every push to `main` — it needs `SSH_HOST`, `SSH_USER`, and `SSH_PRIVATE_KEY`
configured as repository secrets to be active.

**After any deploy that recreates `laravel`, `queue-worker`, `scheduler`, or
`landing`:** if `nginx` wasn't *also* recreated in the same pass, restart it
(`docker compose restart nginx`). nginx resolves those service hostnames once
and caches the result — a container recreated without it gets a new internal
IP that nginx won't know about until it restarts, which otherwise shows up as
a 502 Bad Gateway on the next request.

---

## App icons

Add PNG icons to `public/icons/`:
- `icon-192.png` (192×192)
- `icon-512.png` (512×512)
- `badge-96.png` (96×96, monochrome, for Android badge)

Any icon generator (e.g. https://realfavicongenerator.net) works.
