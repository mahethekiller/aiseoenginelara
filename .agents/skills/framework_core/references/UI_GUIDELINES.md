# UI Guidelines & Front-End Standards

The UI for the Antigravity HR Portal is built on Bootstrap 5.3, custom dark/light themes, and local javascript components.

---

## 🎨 1. Theme Configuration & Color Palettes

- The application must support both **Light Mode** and **Dark Mode** via Bootstrap 5.3's native attributes (`data-bs-theme="light"` or `data-bs-theme="dark"` on the `<html>` tag).
- Do not use raw, default HTML form elements. Use Bootstrap inputs, custom selects (`select2`), and switches.
- **Grayscale Colors**:
  - Light mode backgrounds: `#f8f9fa` (off-white), `#ffffff` (cards).
  - Dark mode backgrounds: `#0f172a` (slate-900), `#1e293b` (slate-800 for cards).
- **Primary Highlights**: Sleek corporate blue (`#0f62fe` or `#2563eb`).

---

## 📱 2. Layout & Responsiveness

- All pages must follow a standard responsive layout structure:
  - Sidebar: Hidden or collapsible on mobile viewports (`lg` breakpoint).
  - Main section: Standard `container-fluid` wrapper with a padding of `px-4 py-3`.
  - Tables: Wrapped inside a `.table-responsive` div block to allow horizontal scrolling on mobile.
- Use `row`, `col-12`, `col-md-6`, `col-lg-4` to handle multi-column layouts across desktop and mobile.

---

## 📋 3. Forms & Interactivity

- Form fields must use Bootstrap `.form-control` or `.form-select`.
- Dynamic checkboxes should use `.form-check-input` with a label for accessibility.
- Mandatory fields must be visually marked with a red asterisk `*` and handled via both front-end validation (HTML5 `required`) and server-side validation.
- Every submit action must trigger a loading state (e.g. disabling the button and showing a spinner icon) to prevent multiple form submissions.

---

## 🧊 4. Reusable Blade Components

To keep views DRY, use Laravel Blade components located under `resources/views/components/`:
- `<x-card title="Employee Details">`: Render standard Bootstrap cards.
- `<x-input name="email" label="Email Address" type="email" required="true" />`: Render input groups with validation states.
- `<x-table id="employee-table">`: Standard DataTables grid container.
- `<x-button type="submit" variant="primary">`: Standard button component.
