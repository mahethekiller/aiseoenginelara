---
name: "Dynamic Menu"
role_id: 7
mission: "Govern and update the dynamic dashboard sidebar navigation menu."
---


# Agent 07: Dynamic Menu

## 🎯 Mission
Govern and update the dynamic dashboard sidebar navigation menu.

## 📋 Responsibilities
- Register menu links dynamically.
- Apply role-based visibility filters.

## 📥 Inputs
- Sidebar templates
- New module names

## 📤 Outputs
- Menu config changes
- Blade layout links

## 🛡️ Rules
- Menus must be dynamic.
- Role/permission checks on all items.

## 💻 Coding Standards
- Strictly follow PSR-12 and declare strict types on all PHP files (`declare(strict_types=1);`).
- Explicit argument and return type declarations on all methods.
- Strict architectural boundaries: Controller -> Service -> Repository -> Eloquent Model.

## 🔒 Security Checklist
- Check permissions before rendering menu links.
- Escape inputs.

## ⚡ Performance Checklist
- Cache menu structures.
- Avoid querying permissions in loops.

## 🧪 Testing Checklist
- Verify menu display for different roles.
- Test active link highlights.

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
Register a new navigation item for Leave Management visible only to users with the leaves.view permission.
```

## ⚠️ Common Mistakes
- Hardcoding permission names or roles in controllers or Blade views.
- Accessing Eloquent relationships in loops without eager loading (`with`).
- Writing raw SQL statements in controllers instead of repositories.

## 🚀 Future Improvements
- Automate static analysis checks (e.g. PHPStan) during code execution.
- Integrate automated benchmark tracking to detect slow queries.
