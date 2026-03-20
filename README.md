# Native PHP Minimalist MVC Starter Kit

Minimal, production-minded MVC scaffold for native PHP 8.5 with:

- PSR-4 autoloaded MVC core (`Request`, `Response`, `Router`, `Controller`, `View`)
- MySQL configuration from `.env` with typed PDO options
- Redis-backed sessions using `predis/predis`
- Redis-backed CSRF token issuance and verification
- Redis-backed credential-aware rate limiting for login and registration
- Memcached caching for compiled environment + configuration payloads
- Standalone Eloquent ORM support (including eager loading via `with(...)`)
- Built-in migration runner (`migrate`, `migrate:fresh`, optional `--seed`)
- Auth example with `guest` and `auth` route middleware
- Reusable page layouts: `app` and `dashboard`
- Dashboard-protected user CRUD example
- Reusable request validation layer (`app/Http/Requests` + `Validation`)
- PHPUnit CRUD unit tests (`tests/Unit/Models/UserModelCrudTest.php`)

## Requirements

- PHP 8.5+
- MySQL
- Redis
- Memcached
- Composer dependencies installed (`composer install`)

## Quick Start

1. Copy `.env.example` to `.env` and fill your secrets.
2. Set MySQL, Redis, and Memcached connection details in `.env`.
3. Start your web server with document root at `public/`.
4. Open `/` for the demo page and `/health` for a JSON health check.

## Auth Example Routes

- Guest-only:
  - `GET /login`, `POST /login`
  - `GET /register`, `POST /register`
- Auth-only:
  - `GET /dashboard`
  - `GET /dashboard/users/create`, `POST /dashboard/users`
  - `GET /dashboard/users/edit`, `POST /dashboard/users/update`
  - `POST /dashboard/users/delete`
  - `POST /logout`

## Project Structure

```text
app/
  Config/            # Typed configuration from environment
  Core/              # MVC kernel (app, container, request/response, router, view)
  Http/
    Controllers/
    Middlewares/
  Services/          # Redis, session, CSRF, database, cache, config loaders
  Views/             # Plain PHP views
bootstrap/
  app.php            # Application wiring and service bindings
config/
  routes.php         # Route definitions
public/
  index.php          # Front controller
```

## Security Notes

- Keep `.env` private and never commit it.
- Sessions are configured to run on Redis only.
- CSRF token checks run for mutating requests (`POST`, `PUT`, `PATCH`, `DELETE`).
- CSRF tokens are one-time and TTL-bound in Redis.
- Login and registration are rate-limited by account identifiers (email/username), not IP address.

## Run Tests

```bash
composer run test:all
```

## Database Workflow

```bash
composer run migrate
composer run migrate:fresh
composer run migrate:fresh -- --seed
composer run migrate:fresh:seed
```

Some Composer versions require forwarding flags with `--` before script arguments.
