.PHONY: setup first-run up dev down build restart logs logs-app logs-queue logs-scheduler \
        shell shell-db migrate key-generate vapid-generate deploy ps

# ── First-time setup ──────────────────────────────────────────────────────────

setup:
	@[ -f .env ] && echo ".env already exists — skipping" || (cp .env.example .env && echo "Created .env — edit it before continuing")
	@mkdir -p storage/app/public
	@mkdir -p storage/framework/cache/data
	@mkdir -p storage/framework/sessions
	@mkdir -p storage/framework/views
	@mkdir -p storage/logs
	@mkdir -p bootstrap/cache
	@echo ""
	@echo "Next steps:"
	@echo "  1. Edit .env with your passwords and API keys"
	@echo "  2. make up"
	@echo "  3. make first-run"

first-run:
	docker compose exec laravel php artisan key:generate --force
	docker compose exec laravel php artisan migrate --force
	docker compose exec laravel php artisan storage:link 2>/dev/null || true
	docker compose exec laravel php artisan vapid:generate
	docker compose exec laravel php artisan config:cache
	docker compose exec laravel php artisan route:cache
	@echo ""
	@echo "IMPORTANT: Copy the VAPID keys above into your .env file, then restart:"
	@echo "  make restart"

# ── Start / stop ──────────────────────────────────────────────────────────────

up:
	docker compose up -d

dev:
	docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d

down:
	docker compose down

build:
	docker compose build --no-cache

restart:
	docker compose restart

# ── Logs ─────────────────────────────────────────────────────────────────────

logs:
	docker compose logs -f --tail=100

logs-app:
	docker compose logs -f laravel nginx

logs-landing:
	docker compose logs -f landing

logs-queue:
	docker compose logs -f queue-worker

logs-scheduler:
	docker compose logs -f scheduler

ps:
	docker compose ps

# ── Shells ────────────────────────────────────────────────────────────────────

shell:
	docker compose exec laravel sh

shell-db:
	docker compose exec mariadb bash -c 'mariadb -u $$MYSQL_USER -p$$MYSQL_PASSWORD $$MYSQL_DATABASE'

# ── Database / maintenance ────────────────────────────────────────────────────

migrate:
	docker compose exec laravel php artisan migrate --force

key-generate:
	docker compose exec laravel php artisan key:generate

vapid-generate:
	docker compose exec laravel php artisan vapid:generate

config-cache:
	docker compose exec laravel php artisan config:cache
	docker compose exec laravel php artisan route:cache

# ── Deploy ────────────────────────────────────────────────────────────────────

deploy:
	git fetch origin main
	git reset --hard origin/main
	$(MAKE) deploy-app

deploy-app:
	docker compose build
	docker compose up -d --force-recreate --remove-orphans
	docker run --rm -v $(CURDIR)/public:/mnt alpine sh -c "rm -rf /mnt/build"
	docker cp seh-laravel:/var/www/html/public/build public/build
	docker compose exec -T laravel php artisan storage:link --force 2>/dev/null || true
	docker compose exec -T laravel php artisan migrate --force
	docker compose exec -T laravel php artisan view:clear
	docker compose exec -T laravel php artisan config:cache
	docker compose exec -T laravel php artisan route:cache
