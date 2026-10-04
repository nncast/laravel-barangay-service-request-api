# Contributing

Thank you for your interest in contributing to the **Barangay Service System API**!
Contributions are welcome. Please follow these guidelines to keep things smooth.

This API is used by the mobile app, [flutter-barangay-service-request-app](https://github.com/nncast/flutter-barangay-service-request-app). If you change an endpoint's request or response shape, open a matching pull request there as well and link the two.

## Development workflow

1. **Fork the repository**
   - Go to [nncast/laravel-barangay-service-request-api](https://github.com/nncast/laravel-barangay-service-request-api).
   - Click the **Fork** button in the top-right corner to create a copy under your GitHub account.
   - Clone your fork locally:
     ```bash
     git clone https://github.com/<your-username>/laravel-barangay-service-request-api.git
     cd laravel-barangay-service-request-api
     ```
   - Add the original repository as an upstream remote so you can sync changes:
     ```bash
     git remote add upstream https://github.com/nncast/laravel-barangay-service-request-api.git
     ```

2. **Create a branch** from `main` in your fork:
   ```bash
   git checkout -b feature/your-feature-name
   ```

3. **Make your changes**, then commit and push to your fork:
   ```bash
   git push origin feature/your-feature-name
   ```

4. **Open a pull request** against `nncast/laravel-barangay-service-request-api:main`.

## Before you submit

- Keep commit messages clear and descriptive.
- Avoid committing secrets, credentials, your `.env` file, `vendor/`, or database files.
- If you add or change behavior, update relevant documentation.
- If you change the database schema, add a new migration — don't edit one that has already been released.
- New endpoints that need a signed-in user go inside the `auth:sanctum` group in `routes/api.php`, and staff/admin endpoints inside the matching `role:` group.
- Run the test suite before opening a pull request:
  ```bash
  php artisan test
  ```

## Code style

- Follow the existing project conventions.
- Prefer small, reviewable changes.
- Do not add unrelated formatting changes.

## Pull requests

Pull requests should include:

- a short summary of the change
- any relevant context or motivation
- testing steps or validation performed

## Security

Do not commit sensitive values such as database credentials, `APP_KEY`, passwords, tokens, or private configuration.

For security reports, follow [SECURITY.md](https://github.com/nncast/laravel-barangay-service-request-api/blob/main/SECURITY.md).
