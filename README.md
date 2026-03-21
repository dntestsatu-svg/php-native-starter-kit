# Native PHP Minimalist MVC Starter Kit

Starter kit MVC native PHP 8.5 dengan contoh fitur autentikasi dan dashboard CRUD user.

## 1. Gambaran Proyek

Project ini menyediakan fondasi aplikasi web native PHP yang sudah siap dipakai, dengan fokus pada struktur yang rapi:

- Routing + middleware.
- Session berbasis Redis.
- CSRF token berbasis Redis.
- Rate limiter untuk login dan register (berbasis identifier akun, bukan IP).
- ORM Eloquent (`illuminate/database`) termasuk eager loading `with(...)`.
- Migrasi database + seeder lewat `bin/console`.
- Caching konfigurasi dan environment dengan Memcached.
- Unit test dengan PHPUnit.

Contoh fitur yang sudah ada di project:

- Halaman login dan register.
- Redirect guest/auth otomatis.
- Dashboard terlindungi `AuthMiddleware`.
- CRUD user di dashboard.
- Layout `app` dan layout `dashboard`.

## 2. Langkah Instalasi

1. Install dependency Composer.

```bash
composer install
```

2. Buat file `.env` dari contoh.

```bash
cp .env.example .env
```

Jika pakai Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

3. Pastikan service berikut aktif:

- MySQL
- Redis
- Memcached

4. Isi konfigurasi penting di `.env` (bagian DB, Redis, Memcached, session, security).

5. Jalankan migrasi database.

```bash
composer run migrate
```

6. Jika ingin reset database lalu isi data awal (admin default):

```bash
composer run migrate:fresh:seed
```

## 3. Penggunaan Dasar

1. Jalankan server PHP dengan document root ke folder `public`.

```bash
php -S 127.0.0.1:8000 -t public
```

2. Buka aplikasi:

- Home: `http://127.0.0.1:8000/`
- Health check JSON: `http://127.0.0.1:8000/health`

3. Alur login/register:

- Guest bisa akses `/login` dan `/register`.
- Jika sudah login, akses `/login` atau `/register` akan diarahkan ke `/dashboard`.
- Jika belum login, akses `/dashboard` akan diarahkan ke `/login`.

4. Data awal dari seeder:

- Email: `admin@example.com`
- Password: `Password123!`

Gunakan akun ini untuk login pertama kali.

## 4. Penjelasan Fitur (Sesuai Implementasi)

### Routing

Routing didefinisikan di `config/routes.php`.

Route yang sudah tersedia:

- `GET /`
- `GET /health`
- `GET /login`
- `POST /login`
- `GET /register`
- `POST /register`
- `POST /logout`
- `GET /dashboard`
- `GET /dashboard/users/create`
- `POST /dashboard/users`
- `GET /dashboard/users/edit`
- `POST /dashboard/users/update`
- `POST /dashboard/users/delete`

Contoh menambah route sederhana:

```php
$router->get('/hello', static fn () => ['message' => 'Hello']);
```

Jika action route mengembalikan array, router akan merespons JSON otomatis.

### Middleware

Middleware yang dipakai saat ini:

- `AuthMiddleware`: hanya user login yang boleh lewat.
- `GuestMiddleware`: user login tidak boleh akses halaman guest (login/register).
- `CsrfMiddleware`: validasi CSRF untuk request mutasi.
- `RateLimiterMiddleware`: batasi percobaan login/register.

### Autentikasi dan Dashboard CRUD

- `AuthService` menangani register, login attempt, login session, logout.
- Password disimpan dengan `password_hash()` dan diverifikasi `password_verify()`.
- Dashboard memuat user + relasi `profile` menggunakan eager loading:
  `with(['profile:user_id,bio'])`.
- Operasi dashboard:
  create, read/list, update, delete user.

### Validasi Request

Project memakai `FormRequest` custom:

- `LoginRequest`
- `RegisterRequest`
- `StoreUserRequest`
- `UpdateUserRequest`
- `DeleteUserRequest`

Aturan validasi dipusatkan di `UserValidationRules` dan normalisasi input dipakai ulang lewat trait `NormalizesUserInput` agar tidak duplikasi.

### Caching (Memcached)

Memcached dipakai untuk:

- Cache snapshot environment saat bootstrap (`EnvironmentLoader`).
- Cache hasil kompilasi konfigurasi (`ConfigRepository`).

Helper cache yang tersedia:

- `cache_get($key, $default)`
- `cache_put($key, $value, $ttl)`
- `cache_remember($key, fn () => ..., $ttl)`

Catatan:

- Di alur bawaan, yang dicache otomatis adalah environment dan konfigurasi.
- Jika Memcached tidak tersedia saat bootstrap, aplikasi tetap bisa jalan (fail silently pada inisialisasi cache tertentu).

### Redis (Session, CSRF, Rate Limiting)

Redis digunakan untuk 3 hal utama:

- Session storage (`SessionManager` + `RedisSessionHandler`).
- CSRF token storage (`CsrfManager`).
- Rate limiting login/register (`RateLimiterMiddleware`).

Detail penting:

- Session driver dipaksa `redis` oleh kode.
- CSRF token bersifat one-time (token dihapus saat verifikasi sukses).
- Rate limit login berdasarkan email.
- Rate limit register berdasarkan kombinasi email + username.
- Jika Redis gagal saat rate limit, middleware bersifat fail-open agar login/register tidak mati total.

### API Response (Yang Memang Ada)

Project ini utamanya web HTML, tetapi ada response JSON yang nyata:

- `GET /health` mengembalikan status aplikasi.
- Error internal dari router dikembalikan dalam format JSON:
  `{"status":"error","message":"..."}`.

Contoh response `/health`:

```json
{
  "status": "ok",
  "php": "8.5.x",
  "database": "ok",
  "session_driver": "redis",
  "csrf_storage": "redis",
  "config_cache": "memcached"
}
```

## 5. Cara Menjalankan Project

Urutan paling aman:

1. Pastikan MySQL, Redis, dan Memcached hidup.
2. `composer install`
3. `cp .env.example .env` lalu isi koneksi.
4. `composer run migrate` atau `composer run migrate:fresh:seed`
5. `php -S 127.0.0.1:8000 -t public`
6. Buka `http://127.0.0.1:8000`

Perintah CLI database yang tersedia:

```bash
composer run migrate
composer run migrate:fresh
composer run migrate:fresh:seed
composer run seed
```

## 6. Konfigurasi Environment

File `.env` dibaca saat bootstrap. Gunakan `.env.example` sebagai acuan.

Variabel yang penting:

- App:
  - `APP_NAME`
  - `APP_ENV`
  - `APP_DEBUG`
  - `APP_URL`
  - `APP_TIMEZONE`
- Database:
  - `DB_CONNECTION` (`mysql` atau `sqlite`)
  - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
  - `DB_CHARSET`, `DB_COLLATION`
- Redis:
  - `REDIS_SCHEME`, `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`, `REDIS_DATABASE`, `REDIS_TIMEOUT`
- Session:
  - `SESSION_DRIVER` (di kode saat ini harus `redis`)
  - `SESSION_LIFETIME`, `SESSION_COOKIE`, `SESSION_PREFIX`
  - `SESSION_SECURE_COOKIE`, `SESSION_HTTP_ONLY`, `SESSION_SAME_SITE`, `SESSION_PATH`, `SESSION_DOMAIN`
- Memcached:
  - `MEMCACHED_HOST`, `MEMCACHED_PORT`, `MEMCACHED_WEIGHT`
  - `MEMCACHED_PERSISTENT_ID`, `MEMCACHED_TIMEOUT`, `MEMCACHED_RETRY_INTERVAL`
  - `CACHE_PREFIX`, `CACHE_DEFAULT_TTL`, `CACHE_ENV_TTL`, `CACHE_CONFIG_TTL`
- Security:
  - `CSRF_ENABLED`, `CSRF_TOKEN_FIELD`, `CSRF_HEADER`, `CSRF_TTL`, `CSRF_PREFIX`, `CSRF_EXCEPT`
  - `RATE_LIMIT_ENABLED`, `RATE_LIMIT_PREFIX`
  - `LOGIN_RATE_LIMIT_MAX_ATTEMPTS`, `LOGIN_RATE_LIMIT_DECAY_SECONDS`
  - `REGISTER_RATE_LIMIT_MAX_ATTEMPTS`, `REGISTER_RATE_LIMIT_DECAY_SECONDS`

Catatan tambahan:

- Nilai `CACHE_STORE` ada di `.env.example`, tetapi pemakaian cache di kode saat ini langsung melalui `MemcachedStore`.
- Jika Anda ubah `.env` dan hasil belum berubah, tunggu TTL cache environment/config atau flush Memcached.

## 7. Testing

Jalankan seluruh unit test:

```bash
composer run test:all
```

Cakupan test yang sudah ada:

- Koneksi database, Redis, Memcached.
- Migrasi dan seeder.
- Eloquent relasi + eager loading.
- Validasi form request.
- Rate limiter middleware.
- Auth service.
- CRUD model user.
- Security hardening dasar (SQL injection, CSRF, XSS output escaping).

Jalankan test per file (contoh):

```bash
vendor/bin/phpunit tests/Unit/Services/Auth/AuthServiceTest.php
```
