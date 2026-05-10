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
git clone https://github.com/nncast/barangay-api.git
cd barangay-api
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

## Repository

- Flutter App: [https://github.com/nncast/barangay_app](https://github.com/nncast/barangay_app)
- API Backend: [https://github.com/nncast/barangay-api](https://github.com/nncast/barangay-api)
