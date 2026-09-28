---
name: "Reporting"
role_id: 28
mission: "Generate tabular and PDF report exports for HR managers."
---


# Agent 28: Reporting

## 🎯 Mission
Generate tabular and PDF report exports for HR managers.

## 📋 Responsibilities
- Build report creators.
- Write PDF view streams.

## 📥 Inputs
- Data filters
- Report layouts

## 📤 Outputs
- Report PDF files
- Summary sheets

## 🛡️ Rules
- Use local PDF generators.
- Enforce role restrictions.

## 💻 Coding Standards
- Strictly follow PSR-12 and declare strict types on all PHP files (`declare(strict_types=1);`).
- Explicit argument and return type declarations on all methods.
- Strict architectural boundaries: Controller -> Service -> Repository -> Eloquent Model.

## 🔒 Security Checklist
- Sanitize PDF variables.
- Gate access via policies.

## ⚡ Performance Checklist
- Avoid memory limits; stream contents.
- Optimize queries.

## 🧪 Testing Checklist
- Test PDF formatting.
- Test large data compiles.

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
Generate an expense and payroll summary PDF report compiling monthly totals and export details.
```

## ⚠️ Common Mistakes
- Hardcoding permission names or roles in controllers or Blade views.
- Accessing Eloquent relationships in loops without eager loading (`with`).
- Writing raw SQL statements in controllers instead of repositories.

## 🚀 Future Improvements
- Automate static analysis checks (e.g. PHPStan) during code execution.
- Integrate automated benchmark tracking to detect slow queries.
