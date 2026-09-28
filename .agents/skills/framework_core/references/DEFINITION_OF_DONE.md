# Definition of Done (DoD)

A module is considered fully complete and ready for code review when it satisfies all of the following requirements:

---

## 📋 1. Core Implementation Checklist

- [ ] **PSR-12 Compliance**: Code contains no styling errors, uses strict types, and correct casing.
- [ ] **Architecture Layers**: Controllers are skinny; Repository is loaded for queries; Business logic is inside the Service layer.
- [ ] **No CDN Usage**: Any external assets are downloaded locally into `public/assets/vendor/`.
- [ ] **No Migrations**: Exclusively uses existing tables in `i2u2_db_laravel` and models in `laravel_files/app/Models/`.

---

## 🔒 2. Security & Gates

- [ ] **Spatie Permissions**: Permissions are registered in `PermissionSeeder` and roles updated.
- [ ] **Gated Controller/Routes**: Access checks exist via Spatie middleware and Policies.
- [ ] **Form Requests**: Every writable route uses a validated Form Request. No raw input updates are present.
- [ ] **File Gates**: Uploaded files are kept off the public root, and downloads run through gate validation.

---

## 🌐 3. API & Docs

- [ ] **REST API endpoints**: Operational CRUD operations are exposed.
- [ ] **Swagger**: OpenAPI annotations are added to controller files, and Swagger docs compiled.
- [ ] **Postman Collection**: A collection JSON is generated and updated for endpoint testing.

---

## 🧪 4. Testing & Verification

- [ ] **PHPUnit Feature Tests**: Feature tests cover web endpoints.
- [ ] **API Endpoint Tests**: API tests cover JSON request/response formats.
- [ ] **Validation Tests**: Ensure invalid payloads are safely caught and rejected.
- [ ] **Permission Tests**: Ensure unauthorized roles cannot edit or access data.
- [ ] **No N+1 Queries**: Eager loading is utilized on all lists.
