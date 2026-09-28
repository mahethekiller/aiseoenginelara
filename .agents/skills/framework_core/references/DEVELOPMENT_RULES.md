# Development Rules & Boundaries

Every module added to the portal must adhere to this architectural flow to ensure long-term maintainability.

---

## 🚦 1. Routing & Requests

- **Web Routes (`routes/web.php`)**: Gated by `auth` and Spatie permission middleware.
- **API Routes (`routes/api.php`)**: Gated by `auth:sanctum` and rate-limiters.
- **Form Requests**: 
  - Every POST, PUT, or PATCH route must have a dedicated Form Request class (e.g. `StoreEmployeeRequest`).
  - Validation rules must be fully specified (including array structures and size/type constraints).
  - Business logic checks must not be done in validation rule files; keep it strictly structural.

---

## 🎮 2. Controllers

- Keep controllers "skinny." Their only jobs are:
  1. Capture input from the request.
  2. Call the appropriate Service method.
  3. Return a Web View (with data) or an API Resource (JSON).
- Never write SQL, raw queries, or `DB::transaction` directly inside a controller.
- Use explicit dependency injection to load services in the constructor.

---

## ⚙️ 3. Services

- The core engine of business rules.
- Services must handle:
  - Database Transactions (`DB::transaction(...)` or database locks).
  - Calculation algorithms.
  - Verification policies.
  - Event dispatching (e.g. `event(new EmployeeRegistered($employee))`).
  - Notification triggers.
- Services should return standard DTOs (Data Transfer Objects), arrays, or Eloquent models.

---

## 🗃️ 4. Repositories

- Shield your services from complex SQL and Eloquent logic.
- Repositories must handle:
  - Query filters and scopes.
  - Cache wrapping (storing/retrieving values).
  - Eager loading (preventing N+1 queries).
  - Aggregations.
- Custom raw SQL queries are allowed only in repositories when standard Eloquent cannot execute them performantly.

---

## 📑 5. Blade & View Components

- UI layouts must use Blade templates with reusable **Blade Components** (e.g. `<x-bootstrap.card>`, `<x-forms.input>`).
- Dynamic page tables must use **DataTables** with AJAX data loading.
- Avoid writing large inline scripts. Put Javascript logic in dedicated assets files or custom Blade stack components (`@push('js')`).
