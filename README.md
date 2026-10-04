<p align="center">
  <img src="public/img/BSR_Logo_1.svg" width="400" alt="Barangay Service Request logo">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/status-stable-2772BD?style=flat-square" alt="status">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/auth-Sanctum-FF2D20?style=flat-square" alt="Sanctum">
  <img src="https://img.shields.io/badge/MySQL%20%7C%20SQLite-supported-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL or SQLite">
</p>

<p align="center">
  <a href="#quick-setup"><strong>Quick Setup</strong></a> ·
  <a href="#api-endpoints">API Endpoints</a> ·
  <a href="https://github.com/nncast/flutter-barangay-service-request-app">Mobile App</a> ·
  <a href="AUTHORS.md">Authors</a>
</p>

The **Barangay Service System API** is the REST backend for the [Barangay Service System mobile app](https://github.com/nncast/flutter-barangay-service-request-app). It handles sign-in, service requests and their status history, notifications to residents, dashboard statistics, and user management for residents, staff and administrators.

<p align="center">
  <img src="https://raw.githubusercontent.com/nncast/flutter-barangay-service-request-app/main/docs/screenshots/request-detail.png" width="200" alt="Resident view of a request">
  <img src="https://raw.githubusercontent.com/nncast/flutter-barangay-service-request-app/main/docs/screenshots/admin-dashboard.png" width="200" alt="Staff dashboard">
  <img src="https://raw.githubusercontent.com/nncast/flutter-barangay-service-request-app/main/docs/screenshots/admin-request-details.png" width="200" alt="Request details for staff">
</p>
<p align="center"><sub>The mobile app running against this API. More screenshots are in the <a href="https://github.com/nncast/flutter-barangay-service-request-app#screenshots">app's README</a>.</sub></p>

## How a request flows

1. A resident submits a request. It starts as **Pending**, with a tracking code like `BSR-2026-01588`.
2. The resident can cancel it while it is still **Pending**.
3. Staff move it through **In Review → Approved → Processing → Completed**, or **Rejected**, adding remarks for the resident.
4. Every change is written to the request's status history, and the resident gets a notification.

Requests a resident cancelled can't be changed by staff.

## Development environment

| Category | Details |
| --- | --- |
| Language | PHP 8.3+ |
| Framework | Laravel 13 |
| Authentication | Laravel Sanctum bearer tokens |
| Database | MySQL / MariaDB (Laragon or XAMPP) or SQLite |
| Tests | PHPUnit (`php artisan test`) |

## Requirements

| Tool | Download |
| --- | --- |
| PHP 8.3 or later (Laragon or XAMPP with PHP 8.3+) | [Laragon](https://laragon.org/download/) · [XAMPP](https://www.apachefriends.org/download.html) |
| Composer | [getcomposer.org](https://getcomposer.org/download/) |
| MySQL / MariaDB (included in Laragon and XAMPP), or SQLite | — |
| Git (optional, for cloning) | [git-scm.com](https://git-scm.com/downloads) |

> **Note:** Laravel 13 needs **PHP 8.3 or later**. Check with `php -v`. Node.js / npm are **not** needed.

## Quick Setup

### 1. Start MySQL
- **Laragon:** click **Start All**
- **XAMPP:** start **MySQL** in the Control Panel

(Skip this if you use SQLite.)

### 2. Clone the project
```bash
git clone https://github.com/nncast/laravel-barangay-service-request-api.git
cd laravel-barangay-service-request-api
```

### 3. Install dependencies
```bash
composer install
```

### 4. Set up the environment
```bash
# Windows (CMD)
copy .env.example .env

# Linux / macOS
cp .env.example .env
```

Generate the app key:

```bash
php artisan key:generate
```

### 5. Configure the database

For **MySQL**, edit `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=barangay_system
DB_USERNAME=root
DB_PASSWORD=
```

<details>
<summary>Using SQLite instead of MySQL</summary>

Keep `DB_CONNECTION=sqlite` (the default in `.env.example`) and create an empty database file:

```bash
# Windows (CMD)
type nul > database\database.sqlite

# Linux / macOS
touch database/database.sqlite
```
</details>

### 6. Run migrations and seed

```bash
php artisan migrate --seed
```

If you use MySQL and are asked:

```
WARN  The database 'barangay_system' does not exist on the 'mysql' connection.

Would you like to create it? (yes/no) [yes]
```

type `yes` and press `Enter`.

The seeder creates the demo accounts below and the eight service categories.

### 7. Start the API
```bash
php artisan serve
```

The API runs at `http://localhost:8000/api`.

To reach it from a **physical phone** on the same Wi-Fi, listen on all interfaces instead and point the app at your computer's IP address (see the [app's setup](https://github.com/nncast/flutter-barangay-service-request-app#4-run-the-app)):

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

## Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@barangay.gov.ph | Admin1234 |
| Staff | staff@barangay.gov.ph | Staff1234 |
| Resident | maria@example.com | User1234 |

**Change these passwords before using the system for real.**

## API Endpoints

All endpoints are under `/api`. Send `Accept: application/json`, and for authenticated endpoints `Authorization: Bearer <token>` (the token comes from `/login` or `/register`).

**Public**

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `/register` | Create a resident account. Body: `name`, `email`, `password`, `password_confirmation` (min 8), optional `phone`, `address` |
| POST | `/login` | Sign in. Body: `email`, `password`. Deactivated accounts get `403` |
| GET | `/categories` | Active service categories |

`/register` and `/login` are limited to 10 attempts per minute.

**Any signed-in user**

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `/logout` | Revoke the current token |
| GET | `/me` | The signed-in user |
| GET | `/requests` | Your own requests, with category and status history |
| POST | `/requests` | Submit a request. Body: `category_id`, `title`, `description`, optional `priority` (`low`, `normal`, `high`, `urgent`) |
| GET | `/requests/{id}` | One of your requests |
| DELETE | `/requests/{id}` | Cancel one of your requests (pending only) |
| GET | `/notifications` | Your notifications, newest first |
| PUT | `/notifications/{id}/read` | Mark one notification read |
| PUT | `/notifications/read-all` | Mark all notifications read |

**Staff and admins**

| Method | Endpoint | Description |
| --- | --- | --- |
| GET | `/admin/requests` | All requests, with requester, category and history |
| PUT | `/admin/requests/{id}/status` | Change status. Body: `status`, optional `remarks`. Notifies the resident |
| GET | `/admin/dashboard` | Counts per status, plus today, this week, this month |
| GET | `/admin/users` | Users. Admins also see deactivated accounts |

**Admins only**

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `/admin/users` | Create a user with any role |
| PUT | `/admin/users/{id}` | Update `name`, `phone`, `address`, `role`, `is_active`. Deactivating signs the user out everywhere |
| DELETE | `/admin/users/{id}` | Permanently delete a user and their own requests. Status history they wrote on other requests is kept |

Errors come back as JSON with a `message`, and validation errors (`422`) also include an `errors` object per field.

## Upgrading

After pulling a new version, run:

```bash
composer install
php artisan migrate
```

## Running tests

```bash
php artisan test
```

The tests use an in-memory SQLite database, so they don't touch your data.

## Contributing

Contributions are welcome. Fork the repository, work on a branch from `main`, and open a pull request describing what changed and why. See [CONTRIBUTING.md](CONTRIBUTING.md) for the workflow and code style, and [AUTHORS.md](AUTHORS.md) for the people who built it.

## Security

Please don't report vulnerabilities in public issues. Use the repository's **Security → Report a vulnerability** tab instead. See [SECURITY.md](SECURITY.md) for details.

## Repository

- Flutter App: [flutter-barangay-service-request-app](https://github.com/nncast/flutter-barangay-service-request-app)
- API Backend: [laravel-barangay-service-request-api](https://github.com/nncast/laravel-barangay-service-request-api)

---

*Barangay Service System API · Laravel 13 · Sanctum · MySQL / SQLite*
