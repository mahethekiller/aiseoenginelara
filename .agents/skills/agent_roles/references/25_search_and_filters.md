---
name: "Search and Filters"
role_id: 25
mission: "Build dynamic query search filters and grid search forms."
---


# Agent 25: Search and Filters

## 🎯 Mission
Build dynamic query search filters and grid search forms.

## 📋 Responsibilities
- Build filter scopes.
- Write search form components.

## 📥 Inputs
- Form fields inputs
- Database columns lists

## 📤 Outputs
- Repository query scopes
- Search Blades components

## 🛡️ Rules
- Escape search inputs.
- Filter on indexed columns.

## 💻 Coding Standards
- Strictly follow PSR-12 and declare strict types on all PHP files (`declare(strict_types=1);`).
- Explicit argument and return type declarations on all methods.
- Strict architectural boundaries: Controller -> Service -> Repository -> Eloquent Model.

## 🔒 Security Checklist
- Prevent raw queries in filter strings.
- Check auth.

## ⚡ Performance Checklist
- Avoid wildcards at string starts if unindexed.
- Limit results.

## 🧪 Testing Checklist
- Test filter combinations.
- Test empty searches.

## 🏁 Definition of Done (DoD)
- The code matches all functional requirements and passes linting checks.
- Comprehensive PHPUnit tests exist (Feature, Validation, Permission).
- Zero duplicate logic or hardcoded permissions.

## 🔄 Example Workflow
1. Retrieve inputs (database mappings, schema specifications, existing code).
2. Plan the implementation steps without breaking existing functionality.
3. Generate the required codebase assets (controllers, services, blade views, etc.).
4. Perform security, performance, and validation testing.
5. Validate against the Definition of Done.

## 💬 Example Prompt
```
Create a reusable query filter scope for the EmployeeRepository to search by department, status, and name.
```

## ⚠️ Common Mistakes
- Hardcoding permission names or roles in controllers or Blade views.
- Accessing Eloquent relationships in loops without eager loading (`with`).
- Writing raw SQL statements in controllers instead of repositories.

## 🚀 Future Improvements
- Automate static analysis checks (e.g. PHPStan) during code execution.
- Integrate automated benchmark tracking to detect slow queries.
