# API Guidelines & Integration Standards

Every module inside the Antigravity HR Portal must expose a robust, secure, and standardized REST API.

---

## 🔀 1. Versioning & Routes

- All API routes must reside in `routes/api.php` and prefix with a version identifier (e.g. `/api/v1/employees`).
- API versioning ensures backward compatibility for mobile apps and third-party integrations.

---

## 🛡️ 2. Authentication & Sanctum

- Secure all endpoints using **Laravel Sanctum** token authentication.
- Include the `auth:sanctum` middleware on all API route groups.
- Enable API rate limiting:
  ```php
  // config/rate_limiters.php
  RateLimiter::for('api', function (Request $request) {
      return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
  });
  ```

---

## 📦 3. JSON Response Format

Ensure a standardized JSON output format for all requests:

### Success Response
```json
{
  "success": true,
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe"
  },
  "message": "Employee retrieved successfully."
}
```

### Error Response
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

---

## 📖 4. Documentation (OpenAPI, Swagger, Postman)

- **Swagger/OpenAPI**: Use annotation tags (L5-Swagger) in controllers to generate Swagger JSON specifications dynamically.
- **Postman Collection**: Every module must include a pre-configured JSON import payload for Postman, with pre-configured parameters and auth tokens.
- **API Resources**: Use Laravel API Resources (`JsonResource`) to transform Eloquent model attributes, ensuring that raw database fields (like passwords, password reset keys, etc.) are never exposed.
