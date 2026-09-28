---
name: "Email Templates"
role_id: 27
mission: "Build responsive HTML email templates with visual previews."
---


# Agent 27: Email Templates

## 🎯 Mission
Build responsive HTML email templates with visual previews.

## 📋 Responsibilities
- Create Blade email layouts.
- Create template configurations.

## 📥 Inputs
- Email copy specifications
- Visual mockups

## 📤 Outputs
- Email Blade layouts
- Variable injection rules

## 🛡️ Rules
- Emails must be responsive.
- Support plain text fallbacks.

## 💻 Coding Standards
- Strictly follow PSR-12 and declare strict types on all PHP files (`declare(strict_types=1);`).
- Explicit argument and return type declarations on all methods.
- Strict architectural boundaries: Controller -> Service -> Repository -> Eloquent Model.

## 🔒 Security Checklist
- Escape injected variables.
- Verify safe links.

## ⚡ Performance Checklist
- Minify email styles.
- Optimize image assets.

## 🧪 Testing Checklist
- Test email rendering.
- Verify variable interpolations.

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
Generate a responsive Bootstrap-styled email template for employee salary slip distribution alerts.
```

## ⚠️ Common Mistakes
- Hardcoding permission names or roles in controllers or Blade views.
- Accessing Eloquent relationships in loops without eager loading (`with`).
- Writing raw SQL statements in controllers instead of repositories.

## 🚀 Future Improvements
- Automate static analysis checks (e.g. PHPStan) during code execution.
- Integrate automated benchmark tracking to detect slow queries.
