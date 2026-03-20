# Native PHP Minimalist MVC Starter Kit

Minimal, production-minded MVC scaffold for native PHP 8.5 with:

- PSR-4 autoloaded MVC core (`Request`, `Response`, `Router`, `Controller`, `View`)
- MySQL configuration from `.env` with typed PDO options
- Redis-backed sessions using `predis/predis`
- Redis-backed CSRF token issuance and verification
- Memcached caching for compiled environment + configuration payloads

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
