<!-- esoul-guidelines:begin architecture -->
# Project guidelines - Architecture

The application is an LSR Framework application served by RoadRunner workers. This file describes the structure that new code MUST fit into; the frontend layout is in [frontend.md](frontend.md). Optional LSR modules (for example `lsr/cqrs`) add their own rules through their own layers.

## Runtime and entrypoints

- A pool of long-lived PSR-7 workers serves HTTP (`psr-worker.php`, started by `rr serve -c .rr.yaml`); background jobs and the scheduler run under the same RoadRunner server as separate pools/services. The console entrypoint is `bin/console` (Symfony Console).
- `include/load.php` bootstraps the application once per process: constants, configuration, the DI container and the database connection. `psr-worker.php` and `bin/console` MUST stay thin; application logic does not live in entrypoints.
- Each request is handled as "one request, one response", but inside a worker process that is **reused** across requests. Read [Long-running worker state](#long-running-worker-state) before adding caches, statics or singletons.
- Server-side rendering of the Inertia frontend is disabled by default and is described in [frontend.md](frontend.md#inertia-on-the-server); the architecture below applies with or without it.

## Directory layout

Application code is grouped by **technical layer** under `src/` (PSR-4 namespace `App\` maps to `src/`). Do not introduce a domain or feature top-level structure unless a decision records it.

| Path                    | Holds                                                                       |
| ----------------------- | --------------------------------------------------------------------------- |
| `src/Http/Controllers`  | HTTP controllers                                                            |
| `src/Http/Middleware`   | route middleware                                                            |
| `src/Http/Templates`    | page and template parameter DTOs (Inertia props, Latte parameters)          |
| `src/Console/Commands`  | console commands                                                            |
| `src/Core`              | application kernel (`App`, `Loader`, `Controller` base, key-value `Info`)   |
| `src/Install`           | database installation and migration runner                                  |
| `config/`               | `services.php`, `di/*.neon`, `migrations/migrations.neon`                   |
| `routes/`               | route files, loaded in file-name order (`01-middleware.php`, `10-public.php`) |
| `templates/`            | Latte templates (the layout and the Inertia page shell)                     |
| `assets/`               | frontend sources ([frontend.md](frontend.md))                               |
| `tests/`                | PHPUnit tests ([testing.md](testing.md))                                    |

- A layer the template does not ship is created next to these when it is first needed, named after its technical role and mirrored by the namespace: request DTOs in `src/Http/Requests`, ORM models in `src/Models`, services in `src/Services`, job tasks in `src/Tasks`. A new kind of layer needs a decision.
- Every PHP file declares `strict_types` and exactly one class, interface, trait or enum (PSR-4).
- Public service boundaries MUST return typed DTOs, ORM models or collections of them; they MUST NOT expose `Dibi\Row` or unstructured associative arrays.
- Closed sets (status, mode, source, reason, type) are PHP backed enums, not strings or class constants.

## Dependency injection

- Services are wired with **Nette DI** NEON files. `config/services.php` returns the list of root files; shared wiring lives in `config/di/services-common.neon`, per-environment differences in thin environment files, and local overrides in an uncommitted `services.local.neon`.
- Keep NEON modular: root files are indexes that `include` focused files (`config/di/extensions/*.neon` for framework extensions, `config/di/configs/*.neon` for application concerns). Do not grow one application-wide file.
- Register an `lsr/*` extension only for an installed package, and each extension name once across the whole include graph. Read the installed extension's config schema before adding keys; LSR packages are versioned independently.
- Broad technical layers are discovered with the Nette `search:` extension (controllers and console commands in the template); register such a directory once. Specific or named services are defined explicitly.
- Constructor injection is the default. `App::getService()` / `App::getServiceByType()` are allowed only at bootstrap seams (the worker entrypoint, the console entrypoint, the loader); controllers, services and handlers MUST NOT call them. When a lookup by name or type is unavoidable, assert the returned type before use.
- After changing DI configuration, clear the generated container (`php bin/console container:clean`) before runtime checks.

## Routing and controllers

- Routes are defined in `routes/*.php`. The files are loaded in file-name order, so the prefix is the order: `01-middleware.php` declares the middleware groups and loads first, `10-public.php` holds the public routes, later files take later numbers. Keep each file focused.
- Every route has a `->name(...)`; names are globally unique and redirects and links use them.
- Shared behaviour is attached through **named middleware groups** declared in `routes/01-middleware.php`: `web` (the Inertia middleware, for page routes) and `csrf` (the browser CSRF check for unsafe methods). A new group is added there with a decision.
- Controllers extend `Lsr\Core\Controllers\Controller` (the template's `App\Core\Controller` base); Inertia controllers add `Lsr\Inertia\Http\WithInertia`. A controller action is thin: it maps the request, calls a service or ORM model, and builds the response.
- Action arguments are resolved by the route handler: route parameters for scalars and models, the container for services, and `#[MapRequest]` for request DTOs. Controllers MUST NOT read `$_GET`/`$_POST` directly.
- Business rules and persistence do not live in controllers.
- Authorization is enforced on the server by middleware or in the code the controller calls; the frontend only reflects it.
- When adding a route, check the whole chain together: route file, controller signature, request DTO, response DTO and the frontend page name.

## Request DTOs and template parameters

- Every endpoint that accepts input maps it to a **request DTO** (`src/Http/Requests`) with `Lsr\ObjectValidation\Attributes`. Validation errors are returned or flashed per field; raw request arrays do not travel past the controller.
- Page and template data are **typed parameter DTOs** in `src/Http/Templates` (extending `Lsr\Core\Controllers\TemplateParameters`), not plain arrays. DTOs carry only what the page needs and never expose sensitive attributes.

## Persistence

- Database access goes through `Lsr\Db\DB` (the Dibi-based fluent query builder) and `lsr/orm` models. Models extend the project's base model.
- App-owned writes use ORM `save()`. A raw DB write MUST clear the affected cache tags explicitly ([Caching](#caching)), unless it is install/migration code.
- Reads through the DB facade use fluent queries with explicit cache tags and `fetchDto()` / `fetchAllDto()` for projections; simple one-table reads use `Model::query()`.
- SQL table and column names are `snake_case`, ORM properties `camelCase`. Tables use plural `snake_case` names with an explicit `TABLE` constant and primary keys `id_{model}` declared with `#[PrimaryKey(...)]`. Models declare every mapped field and every relation as an attribute.

### Migrations

- Schema lives in NEON migration files applied by the installer (`php bin/console app:install`). The root index is `config/migrations.neon` or `config/migrations/migrations.neon` (the template uses the latter); it stays a small index that `includes:` focused files per domain. Include paths follow the process working directory (the project root); keep the include graph a tree, because a file loaded twice is rejected as a cyclic include.
- A change to a model that needs new columns changes the migration in the same pull request. The table definition stays equal to a fresh install, and the change is added as a new `modifications` version.
- A released `modifications` version key MUST NOT be rewritten; add a new version instead.
- `php bin/console app:install --fresh` drops all tables. Run it only on a disposable database, never on a shared, staging or production one.
- After a migration change, install into a disposable database and inspect the resulting columns, indexes and foreign keys; a successful command does not prove the schema is right.

## Caching

- Cache storage is file-based (`lsr/caching`, `temp/`) by default. A Valkey cache is a project choice: the Redis-compatible `RedisStorage` talks to the stack's own Valkey service (`REDIS_HOST=valkey`, [deployment.md](deployment.md#cache)). Configure it explicitly as a storage service, never assume it.
- Valkey storage and journal share an application-specific namespace prefix, so two environments or applications that ever reach the same instance cannot touch each other's keys.
- Code MUST NOT clear the whole cache or the whole Valkey database (no `FLUSHALL`, no global clean to make a test or a bug go away). Fix the key or the invalidation instead; maintenance cleans by tag (`php bin/console cache:clean --tag=<tag>`).
- Cached values are tagged with every table or model they depend on, and a write invalidates those tags (ORM `save()` does; a raw DB write does it explicitly). A key includes the user, tenant, locale or permission scope the value varies by.

## Templates and frontend shell

- Latte templates render the HTML shell; the application UI is Vue + Inertia ([frontend.md](frontend.md)). Controllers do not build HTML fragments.
- Shared document structure (head, SEO tags, asset tags, Inertia outlet) lives in one layout template; reusable presentation lives in partials or Latte extensions registered as DI services.
- Output is escaped by Latte at the rendering boundary. Do not pre-mark database, request or translated text as safe HTML.

## Long-running worker state

RoadRunner workers do **not** reset memoized or static state between requests or jobs.

- Request- or job-dependent memoization MUST live on request- or job-local objects.
- Persistent objects (services, singletons), static properties and global calls MUST NOT hold per-request data (the current user, locale, request, tenant); if they must, the lifecycle clearing is explicit and tested.
- Workers are recycled by `max_jobs` and `max_worker_memory` in `.rr.yaml`; correctness MUST NOT depend on that recycling.
- State that must outlive or be shared between workers (sessions, cache) lives in the configured storage (file storage under `temp/` or the database by default, Valkey when the project chose it), never in worker memory.

## Background work

- Jobs run in the RoadRunner jobs pool; scheduled work runs in the scheduler service (`scheduler:run`). A task is a dispatcher class implementing `Lsr\Roadrunner\Tasks\TaskDispatcherInterface`, queued through `TaskProducer` with a payload implementing `TaskPayloadInterface`.
- Jobs are durable by default: the jobs pool uses the RoadRunner `boltdb` driver, which needs no extra service and keeps its queue file in `/app/temp/queue/`, inside the stack's bind-mounted `temp/` run directory ([deployment.md](deployment.md#stack-runtime)), so queued work survives a restart. Cache cleaning (`cache:clean` and the generated-cache cleanup of `/prepare-runtime`) MUST leave `temp/queue/` alone: clearing it drops queued work. The `memory` driver is used only for work that may be lost (a cache warm-up, a best-effort notification), and the decision is recorded. Another driver (`amqp` or any other broker) needs an ADR.
- `getDiName()` of a dispatcher MUST equal the name of its DI service definition, because the jobs worker resolves the task by that name.
- A payload carries identifiers, enums, timestamps and small DTO data only: never closures, streams, PDO/Dibi connections, DI services or loaded ORM graphs. The task loads fresh state from the identifiers.
- A task MUST be idempotent or guard against double execution, because jobs can be retried. Guard the payload type at the start of `process()`.
- A successful push proves the job was queued, not that it was processed. A task acknowledges only after its durable state change succeeded; a thrown failure is nacked.

> Local additions go below this managed block. Content outside the block is preserved when the guidelines are synced.
<!-- esoul-guidelines:end architecture -->
