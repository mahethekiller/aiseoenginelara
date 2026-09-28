# Workspace Agent Guidelines

## Theme & Dark/Light Mode Compliance
- Always ensure all Blade views, components, tables, forms, badges, select2 dropdowns (single & multi-select), WYSIWYG editors, and cards are 100% compatible with both Light Mode and Dark Mode (`data-bs-theme="dark"`).
- Never use hardcoded light backgrounds (e.g. `bg-white`, `bg-light`, `#F1F5F9`) or hardcoded dark text colors (`color: #0F172A`, `text-gray-900`) without corresponding `[data-bs-theme="dark"]` override rules or theme variables (`bg-body`, `bg-body-tertiary`, `text-body-emphasis`, `text-body-secondary`).
- Ensure Select2 multi-select pills (`.select2-selection__choice`) and WYSIWYG canvas elements (`.wysiwyg-canvas`) dynamically invert background and text colors under `[data-bs-theme="dark"]` for 100% high contrast.
- Ensure all `.table`, `.table th`, `.table td`, and `.card` elements maintain high-contrast legibility in both Light and Dark modes.

---

# Antigravity HR Portal Master Rules

These rules represent the absolute constraints of the project. Any deviation from these rules by any agent will result in a validation failure and must be rejected during code review.

---

## 🔒 Rule 1: Existing Assets Reuse
- The database schema is already loaded in `i2u2_db_laravel`.
- The Eloquent models are already generated in `laravel_files/app/Models/`.
- Do not create migrations to rebuild tables.
- If a model is missing relationships or has incorrect properties, refactor it carefully rather than rewriting it.

## 📦 Rule 2: Zero CDNs & Modern Component Architecture (Tailwind CSS 4 & daisyUI 5)
- All CSS, JS, and font files must be built locally via Vite (`resources/css/app.css`) or stored locally under `public/assets/`.
- No remote content delivery networks (CDNs) are allowed.
- The primary UI framework is Tailwind CSS v4 paired with the daisyUI v5 component library (replacing legacy Bootstrap 5.3).
- Flowbite and remote CDN imports remain strictly forbidden.

## 🛡️ Rule 3: Dynamic Spatie Permissions
- Do not hardcode permissions or roles anywhere in controllers or blade files.
- Use `@can('permission.name')` in Blade views.
- Use `$this->authorize('permission.name')` or Spatie middleware in routes.
- When generating new features:
  1. Add permissions to `PermissionSeeder`.
  2. Map permissions to roles in `RoleSeeder`.
  3. Ensure a database seed updates these records safely without wiping existing user assignments.

## 💾 Rule 4: Secure File Management & Descriptive Naming
- All uploads must be validated against a strict mime-type list (e.g. PDF, PNG, JPG, Docx).
- All uploaded files must use structured, human-readable file naming with `storeAs()` (e.g. `Resume_Rahul_Sharma_20260826_124950.pdf` or `Clearance_Emp102_20260826.pdf`) instead of random hash strings, enabling easy manual and programmatic file identification.
- File uploads must be stored in secure storage (`storage/app/private/` or dedicated storage disks).
- Access to uploaded files must be gated by Laravel Policies (e.g. `DocumentPolicy` ensures employees can only view authorized documents).
- Files must never be uploaded directly to `public/` root directories without proper authorization checks.

## ♻️ Rule 5: Zero Duplicate Logic
- Business logic must reside strictly in **Services**.
- DB queries must reside strictly in **Repositories**.
- Shared functions must be placed in reusable **Traits** or **Helpers**.
- If two modules require similar components (like a profile widget or file uploader), create a reusable Blade Component instead of duplicating HTML.

## 🧹 Rule 6: Mandatory Content Sanitization & Clean Text Rendering (WYSIWYG Standard)
- **Mandatory WYSIWYG Editor Component**: All rich text inputs across the portal (Notification Email Templates, Company Announcements, Job Postings/Requisitions, Offer Letters, Ticket Resolutions/Responses, Policy Documents) must use the reusable `<x-wysiwyg-editor>` component instead of raw `<textarea>` tags.
  - Usage: `<x-wysiwyg-editor name="field_name" :value="$model->content" :tokens="['{token1}', '{token2}']" height="300px" />`
  - The component provides a full formatting toolbar (bold, italic, underline, strike, headings, lists, justify, links), HTML source code view toggle, dynamic placeholder token chips, and dark/light mode compatibility.
  - Plain `<textarea>` inputs must strictly be limited to single-line or short plain-text inputs (e.g. Leave reason, brief decline comment, postal address) where HTML formatting is disallowed.
- **Sanitization on Save**: All ticket descriptions, comments, resolution remarks, announcements, and user rich text inputs must use `App\Traits\HasCleanContent` or `HasCleanContent::sanitizeContent($value, false)` before saving to the database.
- **Clean Display & Decoding**: In Blade views, never output raw database strings containing un-decoded HTML entities (`&lt;p data-start=...`) or raw HTML tags (`<p><br></p>`) inside `<textarea>` inputs or plain text display blocks.
- Use `{!! $ticket->clean_description !!}` / `{!! $ticket->clean_remarks !!}` for rich view display and `{{ $ticket->plain_remarks }}` / `{{ $ticket->plain_description }}` for `<textarea>` form controls and truncated table snippets.
- When resetting or populating HTML templates into WYSIWYG canvas elements (`contenteditable="true"`), always decode HTML entities (e.g. using `textarea` element decoding in JS: `txt.innerHTML = rawTemplate; decodedHtml = txt.value;`) before setting `.html()`, preventing raw `&lt;p&gt;` entity strings from rendering as plain text.

## 🚀 Rule 7: Server Deployment Guide Maintenance
- Whenever creating or modifying features that require database seeders, configuration changes, permission updates, or background commands:
  1. Ensure all database changes are 100% non-destructive to existing production database records.
  2. Maintain and document the exact step-by-step deployment instructions in `SERVER_DEPLOYMENT_GUIDE.md` in the project root.
  3. Never drop, truncate, or overwrite existing production database tables or user records.

## ⚡ Rule 8: First-Column Icon-Only Action Buttons & Explicit Spacing
- All data tables across Blade views (e.g. Clearance Hub, Employee Directory, Resignations, Leave Management, Payroll, Recruitment Candidates, Scheduled Interviews) must place Action Buttons in **Column 1** (the very first table column on the left with `<th>Actions</th>`).
- **Icon-Only Controls**: Action buttons inside table rows must be clean, compact **icon-only buttons** (e.g. `<button class="btn btn-sm btn-outline-primary px-2.5 rounded-2" title="View Profile"><i class="fa-solid fa-eye"></i></button>`) equipped with descriptive HTML `title` tooltips. Do not use plain text labels inside table row action buttons.
- **Explicit Button Spacing**: Never wrap action buttons in merged `.btn-group` containers (which force buttons to glue together without margins). Always wrap action buttons in `<div class="d-inline-flex align-items-center" style="gap: 6px;">` so every button maintains individual rounded borders (`rounded-2`) and distinct 6px spacing between icons.

## 🔍 Rule 9: Searchable Select Dropdowns & Multi-Company Disambiguation
- All modal forms and complex selection fields (e.g. Job Requisition, Candidate, Employee, Department, Designation, Role, Leave Type) must include `class="select-search"` or `data-control="select2"` and a blank default option (`<option value=""></option>`).
- All Department and Designation select dropdowns must load with `with('company')` and display the associated Company name (`$dept->name_with_company` / `{$dept->department_name} (Company: {$dept->company->name})`), enabling multi-company HR disambiguation.
- Whenever a Department select dropdown is present alongside a Designation dropdown, the Designation `<option>`s must include `data-department-id="{{ $desig->department_id }}"`, and selecting a Department must dynamically filter available Designation options.
- This automatically activates live search filtering via Select2, ensuring users can instantly search and filter options.

## 🛑 Rule 10: In-Modal Error Alerts & Input State Retention
- All modal forms across all Blade views must automatically remain/re-open open upon validation error or exception (`@if($errors->any() || session('error'))` script auto-trigger).
- All form inputs inside modals must retain user-entered values using `value="{{ old('field_name') }}"`, retain select option states (`old('field_name') == $val ? 'selected' : ''`), and retain checkbox states (`old('field_name', '1') == '1' ? 'checked' : ''`).
- Form validation/exception errors must be rendered directly inside the modal body (`@if(session('error'))` / `@if($errors->any())` alert banners) and inline using `@error('field_name')` directives and `is-invalid` classes.

## 📐 Rule 11: Strict Modal Markup & daisyUI Dialog Compliance
- All modal dialogs must use standard daisyUI HTML `<dialog class="modal">` hierarchy or compliant wrappers:
  - `<dialog id="..." class="modal">` MUST have `<div class="modal-box">` as its direct structural child.
  - Complex modal forms containing email previews, WYSIWYG editors, or multi-step fields must use `.modal-box.max-w-5xl` with a clean 2-Column Side-by-Side Widescreen Studio Layout (`col-span-5` form parameters on left, `col-span-7` preview/studio canvas on right).
  - During the migration bridge, any legacy Bootstrap modals must maintain `.modal-dialog > .modal-content` without intermediate unstyled `<form>` tags.

## ⏳ Rule 12: Mandatory Form Submit Disabling & Loading Spinner State
- All form submissions across all Blade views and modal dialogs must automatically disable the primary submit button (`disabled="disabled"`) and display a visual loading spinner indicator (`<i class="fa-solid fa-circle-notch fa-spin me-1"></i> Processing...`).
- Enforced globally via `<head>` helper `onclick="submitWithLoader(this)"` or by adding `class="btn-loader"` / `class="submit-loader"` to submit buttons, preventing duplicate form submissions and double API calls.

## 🎨 Rule 13: Portal View Benchmark & Unified Button Styling Language (`/recruitment-applications` Benchmark)
All index, roster, listing, and dashboard views across the portal must strictly adhere to the visual hierarchy, layout architecture, metric card design, and standardized button color palette established in `/recruitment-applications` (`resources/views/recruitment/index.blade.php`):

### 1. Page Header Blueprint
- **Badge Pill**: Top-left category identifier `<span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 font-monospace small"><i class="fa-solid fa-... me-1"></i> Subtitle</span>`.
- **Title & Subtitle**: `h4 class="fw-bold mb-1 text-body-emphasis"` paired with descriptive subtitle `p class="text-body-secondary small mb-0"`.
- **Header Action Buttons**:
  - **Secondary Navigation / Links**: `btn btn-sm btn-body border text-body-emphasis shadow-xs fw-semibold px-3 py-2 rounded-2`.
  - **Primary Action CTA**: `btn btn-sm btn-primary fw-semibold px-3 py-2 rounded-2 shadow-xs`.

### 2. Telemetry / KPI Metric Cards Blueprint
- Encased in responsive 4-column grid (`col-sm-6 col-xl-3`).
- **Card Container**: `card border-0 shadow-sm rounded-3 p-3 bg-body-tertiary h-100 position-relative overflow-hidden`.
- **Bottom Accent Border**: `<div class="position-absolute bottom-0 start-0 end-0 bg-{variant}" style="height: 3px;"></div>` where `{variant}` matches metric context (`primary`, `info`, `warning`, `success`, `danger`).
- **Metric Header**: Contextual pill `<span class="badge bg-{variant}-subtle text-{variant} border border-{variant}-subtle rounded-pill px-2 py-0.5 small">{Label}</span>`.
- **Metric Value**: Bold number `<h3 class="fw-bold mb-0 text-body-emphasis">{{ $metric }}</h3>`.
- **Icon Square**: `<div class="rounded-3 bg-{variant}-subtle text-{variant} d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;"><i class="fa-solid fa-... fa-lg"></i></div>`.

### 3. Filter Toolbar Blueprint
- Encased in `card border-0 shadow-sm rounded-3 bg-body-tertiary mb-4`.
- Input fields use semantic theme classes: `form-control form-control-sm bg-body text-body` / `form-select form-select-sm bg-body text-body`.
- **Filter Action Buttons**:
  - **Apply / Filter**: `btn btn-sm btn-primary fw-semibold px-3`.
  - **Reset**: `btn btn-sm btn-outline-secondary px-3`.

### 4. Standardized Button Styling & Color Palette Across the Portal
- **Column 1 Table Action Buttons (Rule 8 Compliance)**:
  - Container: Always wrapped in `<div class="d-inline-flex align-items-center" style="gap: 6px;">`.
  - Buttons: Clean icon-only buttons with `px-2.5 rounded-2` (never plain text, never `btn-light-*` or un-bordered light buttons):
    - **View / Details**: `btn btn-sm btn-outline-primary px-2.5 rounded-2` (Blue)
    - **Edit / Re-schedule / Action**: `btn btn-sm btn-outline-warning px-2.5 rounded-2` (Amber/Orange)
    - **Status / History / Notes / Codes**: `btn btn-sm btn-outline-info px-2.5 rounded-2` (Cyan/Sky)
    - **Download / Export / Secondary**: `btn btn-sm btn-outline-secondary px-2.5 rounded-2` (Slate/Gray)
    - **Approve / Hire / Confirm**: `btn btn-sm btn-outline-success px-2.5 rounded-2` (Emerald/Green)
    - **Reject / Delete / Cancel**: `btn btn-sm btn-outline-danger px-2.5 rounded-2` (Rose/Red)
- **Modal Dialog Action Buttons**:
  - **Cancel / Close**: `btn btn-sm btn-body border text-body-emphasis`
  - **Save / Create**: `btn btn-sm btn-primary fw-semibold px-3`
  - **Update / Modify**: `btn btn-sm btn-warning fw-semibold px-3`
  - **Delete / Destroy**: `btn btn-sm btn-danger fw-semibold px-3`
  - Always enforce Rule 12 submit loader (`onclick="submitWithLoader(this)"` or `class="btn-loader"`).

## 🏷️ Rule 14: Compact, Concise Table Headings & Standardized Typography
Across all Blade data tables, rosters, and listings portal-wide:
- **Concise Headings (No Long/Verbose Labels)**: Table column titles must be short, clear, and punchy. Never use unnecessarily verbose labels:
  - Use `ID` or `Emp ID` (instead of `Employee ID`)
  - Use `Employee` or `Name` (instead of `Employee Name` / `Staff Full Name`)
  - Use `Dept / Role` (instead of `Department & Designation`)
  - Use `Manager` (instead of `Reporting Manager`)
  - Use `Email` (instead of `Official Email` / `Email Address`)
  - Use `Company` (instead of `Company Name` / `Corporate Legal Entity`)
  - Use `Status` (instead of `Current Employment Status`)
  - Use `Actions` (instead of `Quick Action Buttons`)
- **Standardized `<thead class="...">` Hierarchy**:
  - Always style table headers with: `<thead class="bg-body-secondary text-body-secondary border-bottom text-uppercase fs-9 fw-bold tracking-wider">`.
  - Never use raw `<thead class="table-light">` without theme overrides.
  - Apply `class="text-nowrap"` to header cells (`<th>`) to eliminate awkward multi-line text wrapping.
- **Table Container & Typography**:
  - Add `fs-8` to the table: `<table class="table table-hover align-middle mb-0 fs-8 border-top">`.
  - Wrap tables inside `<div class="table-responsive">` to ensure smooth horizontal scroll resilience across devices.
