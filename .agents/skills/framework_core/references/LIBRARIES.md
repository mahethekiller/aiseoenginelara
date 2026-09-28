# Local Front-End Libraries Specification

This document lists the mandatory UI and utility libraries that must be downloaded locally. Remote CDNs are strictly forbidden.

---

## 📦 Required Libraries & Versions

All assets must be saved inside `public/assets/vendor/` under their respective folders (e.g. `public/assets/vendor/bootstrap/`).

| Library | Version | Description | Target Path |
| :--- | :--- | :--- | :--- |
| **Bootstrap** | 5.3.x | Core styling and grid layout | `vendor/bootstrap/` |
| **jQuery** | 3.7.x | Helper library for DOM & AJAX | `vendor/jquery/` |
| **Font Awesome** | 6.x | CSS Icon set | `vendor/font-awesome/` |
| **DataTables** | 1.13.x | Dynamic tables with searches/filters | `vendor/datatables/` |
| **ApexCharts** | 3.x | Visual dashboard widgets & analytics | `vendor/apexcharts/` |
| **Chart.js** | 4.x | Alternate simple chart engine | `vendor/chartjs/` |
| **SweetAlert2** | 11.x | Confirm modals & action dialogs | `vendor/sweetalert2/` |
| **Toastr** | 2.x | Pop-up activity notifications | `vendor/toastr/` |
| **Flatpickr** | 4.6.x | Date & time picker input field | `vendor/flatpickr/` |
| **Select2** | 4.1.x | Searchable drop-down fields | `vendor/select2/` |
| **FullCalendar**| 6.x | Holiday & shifts calendars | `vendor/fullcalendar/` |
| **Dropzone** | 5.x | Drag-and-drop secure file uploader | `vendor/dropzone/` |
| **SortableJS** | 1.15.x | Drag-and-drop item sort lists | `vendor/sortablejs/` |
| **Inputmask** | 5.x | Form input filters (PAN/Phone formats) | `vendor/inputmask/` |
| **PDF.js** | 3.x | Secure document PDF viewer | `vendor/pdfjs/` |
| **Day.js** | 1.11.x | Lightweight date formatting utility | `vendor/dayjs/` |

---

## 📥 How to Download and Serve

1. Run an asset collector job to download minified `.css`, `.js`, and font files directly from NPM/unpkg source endpoints.
2. Store the assets:
   - CSS files: `public/assets/css/` or `public/assets/vendor/[library]/css/`
   - JS files: `public/assets/js/` or `public/assets/vendor/[library]/js/`
3. Serve in layouts using standard local helper tags:
   ```html
   <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
   <script src="{{ asset('assets/vendor/jquery/jquery.min.js') }}"></script>
   <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
   ```
