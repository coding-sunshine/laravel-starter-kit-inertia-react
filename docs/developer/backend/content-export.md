# Content & Export

Tags, PDF generation, and Excel/CSV export (Step 4 of the feature roadmap). All integrated with the Inertia kit and Filament admin.

## Tags (spatie/laravel-tags)

- **Model**: `User` is taggable via `HasTags`; tags live in `tags` and `taggables` tables.
- **Filament**: User resource form has a **Tags** field (`TagsInput` as `tag_names`) with suggestions from existing tags. Create/Edit pages sync tags on save; list table and view infolist show tags. Table query eager-loads `tags`.
- **Usage**: Attach/detach/sync via `$user->attachTag('name')`, `$user->syncTags([...])`, `$user->tags`, `User::withAnyTags([...])`, etc. See [spatie/laravel-tags](https://github.com/spatie/laravel-tags) for full API.

## PDF (spatie/laravel-pdf)

- **Blade view**: `resources/views/pdf/profile.blade.php` — profile summary (name, email, verified at, generated date).
- **Controller**: `ProfileExportPdfController` — GET `profile/export-pdf` (auth + verified); returns PDF download. See [ProfileExportPdfController](./controllers/ProfileExportPdfController.md).
- **Inertia**: Dashboard has an "Export profile (PDF)" button linking to `/profile/export-pdf`.
- **Requirements**: Browsershot (Node/Puppeteer) for real PDF generation; tests use `Pdf::fake()`.

## Excel / CSV export (maatwebsite/excel + pxlrbt/filament-excel)

- **Filament User resource**: Table has **header** Export action (XLSX and CSV options) and **bulk** Export action. Columns are derived from the table; model `$hidden` (e.g. password) is respected.
- **Location**: `app/Filament/Resources/Users/Tables/UsersTable.php` — `ExportAction` in `headerActions`, `ExportBulkAction` in `toolbarActions`.
- **Import**: Not implemented; can be added with a dedicated import package or custom action.

## For coding agents

- **Tags**: User form key `tag_names` (array of strings); sync in `CreateUser` / `EditUser` after save.
- **PDF**: Route `profile.export-pdf`; controller uses `pdf('pdf.profile', ['user' => $user])->name(...)->download()`.
- **Export**: Filament table uses `pxlrbt\FilamentExcel\Actions\ExportAction` and `ExportBulkAction` with `ExcelExport::make()->fromTable()`.
