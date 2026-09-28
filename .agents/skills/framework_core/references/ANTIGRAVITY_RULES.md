# Antigravity HR Portal Master Rules

These rules represent the absolute constraints of the project. Any deviation from these rules by any agent will result in a validation failure and must be rejected during code review.

---

## 🔒 Rule 1: Existing Assets Reuse
- The database schema is already loaded in `i2u2_db_laravel`.
- The Eloquent models are already generated in `laravel_files/app/Models/`.
- Do not create migrations to rebuild tables.
- If a model is missing relationships or has incorrect properties, refactor it carefully rather than rewriting it.

## 📦 Rule 2: Zero CDNs & Tailwind CSS
- All CSS, JS, and font files must be stored locally under the `public/assets/` directory.
- No remote content delivery networks (CDNs) are allowed.
- The UI framework is Bootstrap 5.3.
- Tailwind CSS, Flowbite, and Alpine.js are strictly forbidden.

## 🛡️ Rule 3: Dynamic Spatie Permissions
- Do not hardcode permissions or roles anywhere in controllers or blade files.
- Use `@can('permission.name')` in Blade views.
- Use `$this->authorize('permission.name')` or Spatie middleware in routes.
- When generating new features:
  1. Add permissions to `PermissionSeeder`.
  2. Map permissions to roles in `RoleSeeder`.
  3. Ensure a database seed updates these records safely without wiping existing user assignments.

## 💾 Rule 4: Secure File Management
- All uploads must be validated against a strict mime-type list (e.g. PDF, PNG, JPG, Docx).
- File uploads must be stored in private storage (`storage/app/private/` or secure S3/Local disks).
- Access to uploaded files must be gated by Laravel Policies (e.g. `DocumentPolicy` ensures employees can only view their own documents).
- Files must never be uploaded directly to `public/` directories without proper authorization checks.

## ♻️ Rule 5: Zero Duplicate Logic
- Business logic must reside strictly in **Services**.
- DB queries must reside strictly in **Repositories**.
- Shared functions must be placed in reusable **Traits** or **Helpers**.
- If two modules require similar components (like a profile widget or file uploader), create a reusable Blade Component instead of duplicating HTML.
