# Testing Guidelines & Coverage Requirements

To ensure stability across framework updates and features, automated testing is mandatory for every module.

---

## 🧪 1. PHPUnit Structure

- Tests must follow the directory structure of the application:
  - **Feature Tests**: Put inside `tests/Feature/` (testing routes, controller integrations, middleware).
  - **Unit Tests**: Put inside `tests/Unit/` (testing services, helper functions, domain math, value objects).

---

## 🔒 2. Permission & Auth Tests

Every route and action must include tests verifying that:
1. Users without authorization receive a `403 Forbidden` response.
2. Authenticated users with the correct Spatie permissions successfully access and complete the action.
3. Unauthenticated guests are redirected to the login page (`302`) or receive a `401 Unauthorized` API error.

```php
public function test_unauthorized_user_cannot_delete_employee()
{
    $user = User::factory()->create(); // without permissions
    $employee = Employee::factory()->create();

    $response = $this->actingAs($user)->delete("/employees/{$employee->id}");

    $response->assertStatus(403);
}
```

---

## 📥 3. Form Validation Tests

Write tests verifying validation rules:
- Test that missing mandatory parameters return validation errors.
- Test that invalid types (e.g. string sent for numeric fields) are blocked.
- Test that duplicate entries for unique columns (like emails) fail validation.

---

## 🌐 4. API Endpoints Tests

- Call JSON routes using `$this->json(...)` or `$this->postJson(...)`.
- Assert standard JSON structures and accurate success/error response payloads:
  ```php
  $response->assertJsonStructure([
      'success',
      'data' => ['id', 'first_name', 'last_name'],
      'message'
  ]);
  ```
