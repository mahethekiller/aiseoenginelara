# Folder Structure Map

Every module added to the project must place its source files strictly within these standard directories.

---

## 📂 Directory Layout

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Web/                # Web view controllers
│   │   └── Api/V1/             # API versioned controllers
│   ├── Requests/               # Form validation requests
│   └── Resources/V1/           # JSON translation resources
├── Services/                   # Pure business logic files
├── Repositories/               # Database query abstractions
├── Models/                     # Eloquent entities (pre-existing)
└── Policies/                   # Spatie auth/action policies
database/
├── seeders/                    # Spatie permissions and role updates
├── factories/                  # Testing faker seed templates
public/
└── assets/
    ├── css/                    # Custom stylesheets
    ├── js/                     # Custom scripts
    └── vendor/                 # Local UI dependencies (Bootstrap, DataTables, etc.)
resources/
├── views/
│   ├── layouts/                # Master Blade templates
│   ├── components/             # Reusable Blade components
│   └── modules/                # Module view subfolders
│       ├── employees/
│       ├── attendance/
│       └── ...
routes/
├── web.php                     # Gated portal routes
└── api.php                     # Sanctum token API routes
tests/
├── Feature/                    # Integration, Route, and Policy tests
└── Unit/                       # Calculation and Domain logic tests
```

---

## 🔀 Module Layout Principle

To prevent files from becoming disorganized, each module must follow the standard naming structure:
- **Routes**: Grouped in `routes/web.php` and `routes/api.php` under distinct comments/groups.
- **Controllers**:
  - `app/Http/Controllers/Web/EmployeeController.php`
  - `app/Http/Controllers/Api/V1/EmployeeApiController.php`
- **Business Layer**:
  - `app/Services/EmployeeService.php`
  - `app/Repositories/EmployeeRepository.php`
- **Security & Validation**:
  - `app/Http/Requests/StoreEmployeeRequest.php`
  - `app/Http/Requests/UpdateEmployeeRequest.php`
  - `app/Policies/EmployeePolicy.php`
- **JSON Transforms**:
  - `app/Http/Resources/V1/EmployeeResource.php`
  - `app/Http/Resources/V1/EmployeeCollection.php`
