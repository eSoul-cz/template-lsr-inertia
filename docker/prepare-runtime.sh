#!/usr/bin/env sh

set -eu

# temp/queue holds the RoadRunner boltdb queue file (.rr.yaml); the cache commands and the `rm` below never touch it.
mkdir -p ./logs/tracy ./temp/cache ./temp/latte ./temp/queue ./build ./upload /var/www/public/build

# Console commands bootstrap the DI container first, so stale generated files must go first.
rm -f ./temp/config.cache \
  ./temp/*.php ./temp/*.php.lock ./temp/*.php.meta

# Never expose the application root or the private Vite manifest as a static directory.
if [ ! -L /var/www/public/build/assets ]; then
  ln -s /app/build/assets /var/www/public/build/assets
fi

php bin/console orm:cache:clean
php bin/console latte:cache:clean
php bin/console routes:cache:clean
php bin/console routes:cache:compile

# Create/update the database schema (config/migrations): forward-only, never --fresh, and it seeds nothing.
# A failure does not block the deployment: it is logged loudly and the container starts anyway, so whoever
# monitors the deployment reads it and decides (fix forward or roll back).
if ! php bin/console app:install; then
  echo 'app:install failed: the container starts anyway and the schema may be behind the code. Read the output above.' >&2
fi
