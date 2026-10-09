# Security Policy

## Reporting a Vulnerability

**Please do not open a public GitHub issue for security vulnerabilities.** Publicly disclosing a vulnerability before it's fixed gives attackers a head start against anyone running this project.

Instead, report it privately through **GitHub Private Vulnerability Reporting**: go to this repository's **Security** tab → **Report a vulnerability** ([direct link](https://github.com/nncast/laravel-barangay-service-request-api/security/advisories/new)). This opens a private conversation visible only to the maintainer, and lets you track the fix without exposing details publicly. ([GitHub's guide to reporting a vulnerability](https://docs.github.com/en/code-security/security-advisories/guidance-on-reporting-and-writing/privately-reporting-a-security-vulnerability))

When reporting, please include:
- A description of the vulnerability and its potential impact
- Steps to reproduce it (a minimal example is ideal)
- The affected version/commit, if known
- Any suggested fix, if you have one — optional, but appreciated

## What to Expect

This is a small, single-maintainer project (a student project, not a funded security team), so please have reasonable patience — but every report will get a response acknowledging receipt, and a fix or mitigation plan once the issue is understood. Credit is happily given in the fix's release notes unless you'd prefer to stay anonymous.

## Scope

This covers the Barangay Service System API in this repo — registration and login, Sanctum token handling, the `role:admin` / `role:admin,staff` route groups in `routes/api.php`, and access to service requests, notifications and user records.

Of particular interest: anything that lets a resident view or delete another resident's requests or notifications, lets a resident or staff member reach admin-only endpoints, lets someone register themselves as staff or admin, or reuses a token after logout.

Out of scope: the demo accounts listed in the README (they are seed data for local testing), issues that require an attacker to already have admin access, and vulnerabilities in third-party dependencies themselves (please report those upstream — e.g. Laravel, Sanctum). Issues in the mobile client belong in [flutter-barangay-service-request-app](https://github.com/nncast/flutter-barangay-service-request-app/security).

## Supported Versions

As a single-track project without parallel maintained release branches, only the **latest version on `main`** receives security fixes. If you're running an older version, please update before reporting an issue that's already fixed.
