ARG PHP_BASE_VERSION=8.5
ARG NODE_BASE_VERSION=26-alpine
# Keep in sync with "packageManager" in package.json
ARG PNPM_VERSION=12.21.0

FROM rg.fr-par.scw.cloud/esoul-starters/php-cli-roadrunner:${PHP_BASE_VERSION} AS base

WORKDIR /app

FROM node:${NODE_BASE_VERSION} AS node_base

ARG PNPM_VERSION

WORKDIR /app

ENV PNPM_HOME="/pnpm"
ENV PATH="$PNPM_HOME:$PATH"

# Node 25+ no longer bundles corepack, pnpm comes from npm (the version "packageManager" pins)
RUN npm install --global pnpm@${PNPM_VERSION}

FROM node_base AS node_dependencies

COPY package.json pnpm-lock.yaml pnpm-workspace.yaml /app/

RUN --mount=type=cache,id=lsr-app-pnpm,target=/pnpm/store \
    pnpm config set store-dir /pnpm/store \
    && pnpm install --frozen-lockfile

FROM node_dependencies AS node_builder

COPY assets /app/assets
COPY templates /app/templates
COPY tsconfig.json vite.config.ts /app/

RUN pnpm run build

FROM base AS composer_base

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MAX_PARALLEL_HTTP=4

RUN install-php-extensions @composer

FROM composer_base AS composer_builder

COPY composer.json composer.lock /app/
COPY src /app/src
COPY include /app/include

RUN --mount=type=cache,id=lsr-app-composer,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache composer install \
        --no-dev \
        --prefer-dist \
        --optimize-autoloader \
        --no-interaction \
        --no-progress

# ---- development: bind-mounted source, Vite dev server + RoadRunner (docker-compose.yml) ----
FROM composer_base AS development

RUN apk add --no-cache libstdc++ libgcc

COPY --from=node_base /usr/local /usr/local

ENV PNPM_HOME="/pnpm"
ENV PATH="$PNPM_HOME:$PATH"

COPY --chmod=755 docker/prepare-runtime.sh /prepare-runtime
COPY --chmod=755 docker/startDev.sh /start

ENTRYPOINT ["/start"]

# ---- production: the last stage is the default build target ----
FROM base AS production

COPY bin /app/bin
COPY config /app/config
COPY include /app/include
COPY routes /app/routes
COPY src /app/src
COPY templates /app/templates
COPY .rr.yaml psr-worker.php /app/
COPY --from=composer_builder /app/vendor /app/vendor
COPY --from=node_builder /app/build /app/build

# Only compiled browser assets are public; the Vite manifest and the application stay private.
RUN mkdir -p /app/temp/cache /app/temp/latte /app/temp/queue /app/logs/tracy /app/upload /var/www/public/build \
    && ln -s /app/build/assets /var/www/public/build/assets

COPY docker/http-smoke.php /usr/local/bin/http-smoke.php
COPY --chmod=755 docker/prepare-runtime.sh /prepare-runtime
COPY --chmod=755 docker/startProd.sh /start

# GET /up answers 200 only while the database connection works (503 otherwise); `--fail` turns that into the exit code.
HEALTHCHECK --interval=10s --timeout=5s --start-period=30s --retries=3 \
    CMD ["curl", "--fail", "--silent", "--output", "/dev/null", "http://127.0.0.1:8001/up"]

ENTRYPOINT ["/start"]
