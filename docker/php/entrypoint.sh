#!/bin/sh
set -e
cd /var/www/app

if [ ! -f .env ]; then
  cp .env.example .env
fi

if [ "${SKIP_COMPOSER:-false}" != "true" ]; then
  if [ ! -f vendor/autoload.php ] || [ composer.json -nt vendor/autoload.php ]; then
    echo "[$SERVICE_NAME] Installing PHP packages (first start can take a few minutes)..."
    composer install --no-interaction --prefer-dist --no-progress
    touch vendor/autoload.php
  fi
else
  until [ -f vendor/autoload.php ]; do echo "[$SERVICE_NAME] waiting for PHP packages..."; sleep 3; done
fi

if ! grep -q "^APP_KEY=base64" .env; then
  php artisan key:generate --force
fi

if [ "${RUN_INSTALL:-false}" = "true" ]; then
  php artisan reelhouse:install
fi

php artisan config:clear >/dev/null 2>&1 || true

echo "[$SERVICE_NAME] ready."
exec "$@"
