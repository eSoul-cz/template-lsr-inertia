# LSR app

Application on the LSR framework with RoadRunner, Latte, Inertia 3 (Vue 3), Vite 8 and Tailwind 4. This file is the operations README: how to run it, which ports and paths it uses and how to check it. Rules and decisions are in `docs/project_guidelines/` and `CONTEXT.md`.

## Requirements

- Docker with Compose v2: the development stack runs the application, NGINX and MariaDB.
- For tools outside the container: PHP 8.5 with the extensions of `composer.json` and Node 26 with the pnpm version pinned in `package.json` (`npm install --global pnpm@<version>`).

## Setup

```bash
cp .env.example .env     # optional: the dev compose file sets the application variables itself
docker compose up --build
```

The first start runs `composer install` and `pnpm install` inside the `app` container and installs the database schema (`php bin/console app:install`). The template ships no lockfiles: run `composer install` and `pnpm install` once on the host (or `docker compose exec app composer install` and `pnpm install`) and commit `composer.lock` and `pnpm-lock.yaml`; the image build needs both.

## Ports

| Port | What | Reached by |
| --- | --- | --- |
| 80 | NGINX, FastCGI to RoadRunner (the production path) | browser: <http://localhost> |
| 5173 | Vite dev server (HMR) through NGINX | browser |
| 9000 | RoadRunner FastCGI | NGINX (the host NGINX in production) |
| 8001 | RoadRunner HTTP | container health check, `docker/http-smoke.php` |
| 6001, 2112, 2114 | RoadRunner RPC, metrics, status | inside the container network only |

## Commands

```bash
composer test               # PHPUnit, debugger and coverage driver off
composer test:parallel      # the same with paratest
composer test:coverage      # PHPUnit with coverage (Xdebug)
composer phpstan            # level 10, no baseline
composer cs                 # code style check (composer cs:fix applies it)
composer mutation           # Infection on the lines changed against main (local, never a CI gate)
pnpm run eslint             # also: eslint:fix, format:check, format, tsc, test, build
php bin/console list        # application console
```

Production image and its smoke test (needs `APP_IMAGE` and the database of the CI compose file):

```bash
docker build -f docker/Dockerfile --target production -t lsr-app .
APP_IMAGE=lsr-app docker compose -f docker-compose.ci.yml up -d --wait
APP_IMAGE=lsr-app docker compose -f docker-compose.ci.yml --profile verification run --rm http-smoke
docker compose -f docker-compose.ci.yml down -v
```

## Persistent paths

In a deployed stack these are bind-mounted from the stack's run directory, next to `.env`, `services.local.neon` and `.rr.yaml`; the container filesystem is disposable and the infrastructure backups cover the run directory.

| Path | Holds |
| --- | --- |
| `./upload` | user uploads (`UPLOAD_DIR` changes it) |
| `./logs` | application and Tracy logs |
| `./temp` | caches, generated DI container, routes and sessions |
| `./temp/queue/tasks.db` | the RoadRunner `boltdb` jobs queue (`.rr.yaml`): queued tasks survive a restart |

`temp/` is cache by convention, with one exception: never delete `temp/queue/` together with the caches. `cache:clean`, the other `*:cache:clean` commands and `docker/prepare-runtime.sh` leave it alone; a manual `rm -rf temp/*` does not, and drops every queued task.

## Time

The application clock is UTC: PHP runs on `UTC` (`APP_TIMEZONE` in `include/constants.php`) and the database session is set to `+00:00` on every connection (`config/di/environment.php`). The business time zone is named in `CONTEXT.md` and applied only where a date is shown or a calendar boundary is cut.

## Troubleshooting

- `ERR_PNPM_IGNORED_BUILDS` on install: a new dependency has an install script; review it and list it in `allowBuilds` of `pnpm-workspace.yaml` (`pnpm approve-builds`).
- A page shows no styles or scripts in development: Vite must be running (`docker compose logs app`) and port 5173 reachable.
- `419` on a form post: the request must come from the application's own browser client and its `Origin` must match the host NGINX passed in `HTTP_HOST` (and `HTTPS`); `X-Forwarded-*` headers are not used.
- The `app` container is unhealthy: `GET /up` answers `503` while the database connection fails; check the `DB_*` variables and the `db` service.
