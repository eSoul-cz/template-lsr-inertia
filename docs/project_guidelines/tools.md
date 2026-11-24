<!-- esoul-guidelines:begin tools -->
# Project guidelines - Tools

This file lists the tools the project uses and how to run them.

## Versions

| Area                 | Tool                                                                                             |
| -------------------- | ------------------------------------------------------------------------------------------------ |
| PHP                  | 8.5                                                                                              |
| Framework            | LSR packages (`lsr/*`) at the versions locked in `composer.lock`                                 |
| Runtime              | RoadRunner (`rr`), PSR-7 worker                                                                  |
| Static analysis      | PHPStan level 10 (`phpstan.neon`); the level MUST NOT be lowered                                 |
| Code style           | PHP-CS-Fixer (`.php-cs-fixer.php` is the PHP formatting contract)                                |
| PHP tests            | PHPUnit 13, paratest                                                                             |
| Node                 | 26                                                                                               |
| Package manager      | pnpm 12 (the latest release, `packageManager`)                                              |
| Frontend build       | Vite 8, Vue 3, Inertia 3, TypeScript, Tailwind CSS 4                                             |
| Frontend checks      | ESLint (flat config), Prettier (printWidth 150, tabWidth 4), `vue-tsc`, Vitest                   |

- `composer.json`, `composer.lock`, `package.json` and `pnpm-lock.yaml` are authoritative for installed versions.
- New LSR applications start at PHPStan level 10. An existing application below it raises the level when it can, fixing findings in code; a baseline is not a way to get there.
- Use API documentation and installed source that match the locked `lsr/*` versions and the PHPUnit major/minor, even when linked documentation is newer. Read the installed `vendor/lsr/*` source before concluding that a feature is unavailable.
- When chaining a method on a newly instantiated class, write `new Foo()->bar()`; only the constructor call's parentheses are needed.
- A project created from the template has no lock files until `composer install` and `pnpm install` have run once. Commit `composer.lock` and `pnpm-lock.yaml` before the first Docker build, which fails without them.

<!-- from: package/mutation-testing -->
Mutation testing adds this row:

| Area | Tool | Source |
|---|---|---|
| Mutation testing | Infection (local, optional) | `infection.json5` |

## Where tools run

- Run tools through the `composer` and `pnpm` scripts, never through globally installed binaries, so local runs and CI use the same versions and configuration.
- PHP tools run in the project's PHP container or a matching local PHP; prefix PHPUnit with `XDEBUG_MODE=off` unless you need the debugger or coverage.
- RoadRunner runs the application in development and production (`rr serve -c .rr.yaml`); the container start scripts prepare the runtime and start it ([deployment.md](deployment.md)).

## Tool caches

Tool caches live under `temp/tools/` (ignored with the rest of `temp/`):

| Tool         | Cache                              |
| ------------ | ---------------------------------- |
| PHPStan      | `temp/tools/phpstan`               |
| PHPUnit      | `temp/tools/phpunit`               |
| PHP-CS-Fixer | `temp/tools/php-cs-fixer.cache`    |

The rest of `temp/` is the application runtime cache (DI container, Latte, models, catalogs, sessions); do not clear one when you mean the other. Clear the generated DI container with `php bin/console container:clean` after changing DI configuration. `temp/queue/` is not cache: it holds the jobs queue file ([Background work](architecture.md#background-work)), and no cache clean removes it.

<!-- from: package/mutation-testing -->
On LSR, Infection's cache is `temp/tools/infection`, next to the other tool caches.

## Read-only verification

These commands do not rewrite tracked files.

### PHP

```bash
composer phpstan
composer cs
composer test
```

`composer test:parallel` runs the same suite with paratest.

### Frontend

```bash
pnpm eslint
pnpm format:check
pnpm tsc
pnpm test
pnpm build
```

### Guidelines

```bash
vendor/bin/esoul-guidelines check  # drift of the rendered guidelines, the AGENTS.md block and the lock; never writes
```

CI runs it as the stage `Guidelines check` of the shared pipeline, from the project's own `vendor/` (see [deployment.md](deployment.md#delivery-path)); a failure is fixed with `esoul-guidelines sync` after the content was moved out of the managed block, not by editing the block.

## Formatting and lint autofixes

Mutating commands are explicit developer actions: run them deliberately, inspect the diff, then rerun the read-only checks.

### PHP

```bash
composer cs:fix
```

`composer cs:fix` rewrites PHP files; `composer cs` is its read-only `--dry-run --diff` counterpart.

### Frontend

```bash
pnpm eslint:fix
pnpm format
```

`pnpm eslint:fix` (ESLint `--fix`) and `pnpm format` (Prettier `--write`) rewrite files. Formatting is required before committing: `composer cs:fix` for PHP and `pnpm format` for the frontend.

## Runtime commands

```bash
php ./bin/console list              # console entrypoint
php ./bin/console app:install       # apply database installation and migrations
php ./bin/console container:clean   # drop the generated DI container
pnpm dev                            # Vite dev server
pnpm build                          # production frontend build
```

`app:install --fresh` drops all tables; use it only on a disposable database ([architecture.md](architecture.md#migrations)).

## Documentation checks

When the repository ships a documentation launcher (it requires Docker for the build):

```bash
docs/documentation validate
docs/documentation build
```

## Mutation testing (local, optional)

<!-- from: package/mutation-testing -->
```bash
composer mutation
```

`composer mutation` runs Infection on your machine before you push. It is optional, never a CI gate and sets no score threshold. How to read the output and handle escaped mutants is described in the mutation testing section of [testing.md](testing.md#mutation-testing).

> Local additions go below this managed block. Content outside the block is preserved when the guidelines are synced.
<!-- esoul-guidelines:end tools -->
