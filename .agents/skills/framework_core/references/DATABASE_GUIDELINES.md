# Database Guidelines & Optimization

This document outlines standard guidelines to maintain query speed, data integrity, and Eloquent performance in the Antigravity HR Portal.

---

## ⚡ 1. Eager Loading (Avoiding N+1)

- **Rule**: Never access relation properties (e.g. `$employee->user->email`) inside loops without eager loading.
- Eager load relations in the repository or controller query using `with(...)`:
  ```php
  // CORRECT: Eager loads the user relationship in 1 query
  $employees = Employee::with('user')->get();
  ```
- Use `$model->load(...)` for lazy eager loading when a model has already been retrieved.
- Utilize the `double-loop check` during code review: check if any relation property is referenced inside a loop without being declared in the `with` array.

---

## 📈 2. Indexing & Searches

- Ensure all columns used in `WHERE`, `ORDER BY`, or `JOIN` clauses have indexes in the database.
- For text search filters, avoid running `LIKE '%search%'` queries on large tables without indices or full-text indexes.
- Utilize standard keys when referencing relations: e.g. `user_id` should point to `employees.user_id` or `users.id` depending on the mappings.

---

## 🔒 3. DB Transactions

- Any service method that updates multiple tables or runs multiple database write operations must be wrapped in a transaction block:
  ```php
  use Illuminate\Support\Facades\DB;

  DB::transaction(function () use ($employeeData, $bankData) {
      $employee = $this->employeeRepo->create($employeeData);
      $this->bankRepo->createForEmployee($employee->id, $bankData);
  });
  ```
- Avoid executing external HTTP requests, file uploads, or email dispatches inside database transaction blocks. Perform these actions *after* the transaction commits.

---

## 📅 4. Date & Time Formats

- Standard MySQL dates use `Y-m-d H:i:s` (for timestamps) and `Y-m-d` (for dates).
- Standardize all date fields using Eloquent attribute casts inside models:
  ```php
  protected $casts = [
      'date_of_joining' => 'date',
      'last_login_date' => 'datetime',
  ];
  ```
- When importing date values from legacy files or API payloads, use `Carbon` to parse them into standard formats:
  ```php
  $parsedDate = Carbon::createFromFormat('d-m-Y H:i:s', $legacyDate)->format('Y-m-d H:i:s');
  ```
