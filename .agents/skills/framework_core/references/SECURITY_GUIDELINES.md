# Security Guidelines & Checklists

Security is a primary concern for the Antigravity HR Portal. All code written must follow these rules to mitigate risks.

---

## 🔒 1. SQL Injection Prevention

- Never inject raw user input directly into SQL statements or `whereRaw()` functions.
- Always use Eloquent query bindings or PDO parameters:
  ```php
  // BAD
  Employee::whereRaw("first_name = '" . $request->input('name') . "'");

  // GOOD
  Employee::where('first_name', $request->input('name'));
  ```

---

## 🛡️ 2. XSS & CSRF Protection

- **XSS Protection**: Blade's double-curly braces `{{ $variable }}` automatically escape outputs. Avoid using unescaped tag blocks `{!! $variable !!}` unless the variable is explicitly sanitized beforehand using a HTML purifier library.
- **CSRF Protection**: All POST, PUT, PATCH, and DELETE HTML forms must include the `@csrf` token field.

---

## ⛔ 3. Mass Assignment Protection

- Keep the `$fillable` array in Eloquent models restricted to only the columns that can be safely updated by a standard user.
- Sensitive columns (like `is_admin`, `salary_amount`, or role attributes) must not be fillable, or must be protected via strict Form Request authorization gates.

---

## 📁 4. Secure File Uploads

- Validate all uploaded files for size, file extension, and MIME type:
  ```php
  $request->validate([
      'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048',
  ]);
  ```
- Store files off-public (e.g. `storage/app/private/`) and serve them via secure controller routes that check permissions before streaming contents.
- Rename uploaded files to random UUIDs or hashes to prevent directory traversal and file execution vulnerabilities.
