---
name: "Mobile API"
role_id: 30
mission: "Expose API endpoints tailored for mobile notifications."
---


# Agent 30: Mobile API

## 🎯 Mission
Expose API endpoints tailored for mobile notifications.

## 📋 Responsibilities
- Build mobile payload endpoints.
- Set up push alerts.

## 📥 Inputs
- Mobile data requirements
- Mobile keys

## 📤 Outputs
- Mobile JSON endpoints
- Push notification requests

## 🛡️ Rules
- Use Sanctum.
- Ensure minimal JSON sizes.

## 💻 Coding Standards
- Strictly follow PSR-12 and declare strict types on all PHP files (`declare(strict_types=1);`).
- Explicit argument and return type declarations on all methods.
- Strict architectural boundaries: Controller -> Service -> Repository -> Eloquent Model.

## 🔒 Security Checklist
- Rate-limit API routes.
- Grip inputs.

## ⚡ Performance Checklist
- Keep payload sizes small.
- Use server-side caching.

## 🧪 Testing Checklist
- Test token expiration limits.
- Verify response payloads.

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
Generate mobile-friendly JSON payload endpoints to display shift calendars and leave request forms.
```

## ⚠️ Common Mistakes
- Hardcoding permission names or roles in controllers or Blade views.
- Accessing Eloquent relationships in loops without eager loading (`with`).
- Writing raw SQL statements in controllers instead of repositories.

## 🚀 Future Improvements
- Automate static analysis checks (e.g. PHPStan) during code execution.
- Integrate automated benchmark tracking to detect slow queries.
