<p align="center">
  <img src="public/img/BSR_Logo_1.svg" width="400" alt="Barangay Service System Logo">
</p>

## About

The Barangay Service System API is a RESTful backend service for managing barangay service requests. It handles user authentication, service requests, notifications, and administrative functions for residents, staff, and administrators.

## Quick Setup

### 1. Start Laragon
- Open Laragon
- Click "Start All"

### 2. Clone to Laragon www folder
```bash
cd C:\laragon\www
git clone https://github.com/nncast/laravel-barangay-service-request-api.git
cd laravel-barangay-service-request-api
```

### 3. Install dependencies
```bash
composer install
```

### 4. Setup environment
```bash
# Windows (CMD)
copy .env.example .env

# Linux / macOS
cp .env.example .env
```

Generate app key:

```bash
php artisan key:generate
```

### 5. Configure database

Edit `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=barangay_system
DB_USERNAME=root
DB_PASSWORD=
```

### 6. Run migrations and seed

```bash
php artisan migrate --seed
```

If prompted with:

```bash
WARN  The database 'barangay_system' does not exist on the 'mysql' connection.

Would you like to create it? (yes/no) [yes]
```

Type:

```bash
yes
```

Then press `Enter`.

### 7. Start API
```bash
php artisan serve
```

API running at: `http://localhost:8000`

---

## Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@barangay.gov.ph | Admin1234 |
| Staff | staff@barangay.gov.ph | Staff1234 |
| Resident | maria@example.com | User1234 |

---

## Contributing

Contributions are welcome. Fork the repository, work on a branch from `main`, and open a pull request describing what changed and why. See [CONTRIBUTING.md](CONTRIBUTING.md) for the workflow and code style.

## Security

Please don't report vulnerabilities in public issues. Use the repository's **Security → Report a vulnerability** tab instead. See [SECURITY.md](SECURITY.md) for details.

## Repository

- Flutter App: [flutter-barangay-service-request-app](https://github.com/nncast/flutter-barangay-service-request-app)
- API Backend: [laravel-barangay-service-request-api](https://github.com/nncast/laravel-barangay-service-request-api)
