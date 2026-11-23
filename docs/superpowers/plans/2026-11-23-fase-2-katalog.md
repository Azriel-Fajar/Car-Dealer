# Fase 2 — Domain Katalog Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add the catalog domain (brands, categories, showrooms, cars with images, colors, options and hotspots) with seed data, plus admin CRUD for every master table and for users, in the light dashboard using a reusable "ref 5" table kit.

**Architecture:** Plain Eloquent models with PHP backed enums for every closed value set. Admin-only resource controllers under `App\Http\Controllers\Dashboard\` (route prefix `/dashboard`, name prefix `dashboard.`, middleware `auth` + `role:admin`) validate through FormRequests and render Inertia pages under `resources/js/pages/dashboard/<resource>/`. Car sub-resources (colors, options, images, hotspots) are nested, scoped routes edited from tabs on one car edit page. Catalog media lives on the disk named by `config('dealer.media_disk')`.

**Tech Stack:** Laravel 13 / PHP 8.5 (no intl), Inertia 3, React 19, TypeScript, Tailwind 4, shadcn/ui, Wayfinder (`--with-form`), Pest (class-style tests), Vite+ (`vp`).

**Spec:** `docs/superpowers/specs/2026-11-20-car-dealer-design.md` — sections "Database", "Role & hak akses", "Halaman & desain → Staff /dashboard", "Keamanan aplikasi", Fase 2 row of "Fase implementasi". Previous plan (style, constraints, existing pieces): `docs/superpowers/plans/2026-11-21-fase-1-foundation.md`.

## Global Constraints

- NEVER run `git commit` or `git push`, and never modify `.git`. The user commits manually. No commit steps in this plan.
- Prefix every bash command that runs php, composer, artisan, `npm run build` or `npm run dev` with `export PATH="/c/Users/afw14/.config/herd-lite/bin:$PATH"` (PowerShell: `$env:Path = "$env:USERPROFILE\.config\herd-lite\bin;$env:Path"`).
- PHP 8.5.13 has no `intl`: never use `NumberFormatter`, `Number::format()`, `Number::currency()`, `Str::slug` is fine (no intl needed). Money display uses existing `App\Support\Rupiah` / `resources/js/lib/format.ts` (`formatRupiah`, `formatRupiahShort`).
- Every migration must run on SQLite (tests) and MariaDB 10.4 (dev). Money columns are `unsignedBigInteger` rupiah (no decimals). Use `json` for JSON columns.
- PHP tests follow the existing class style (`namespace Tests\Feature\...`, `extends Tests\TestCase`, `use RefreshDatabase`, `public function test_*(): void`). File-upload tests use `Storage::fake('public')` and `UploadedFile::fake()->image(...)`.
- JS unit tests are `*.test.ts` next to their module, import from `vite-plus/test`, run with `npx vp test run <file>`, and must not import from `@/routes`.
- Green at the end of every task: `php artisan test`, `npm run build`, `npm run types:check`, `npm run check`, `vendor/bin/pint --test`, `vendor/bin/phpstan analyse --memory-limit=1G` (level 7; without the flag it dies at 128 MB).
- After any route change run `php artisan wayfinder:generate --with-form`. Generated `resources/js/{routes,actions,wayfinder}` are gitignored.
- UI copy Indonesian, identifiers English. Money shown as `Rp 485.000.000`. Dashboard is the light neutral surface (no Lenis, no GSAP).
- `$fillable` explicit on every model (use the `#[Fillable([...])]` attribute like `User`). `users.role` and `users.is_active` stay non-fillable — set them with `forceFill`.
- No new Composer or npm packages.
- Layouts responsive at 375 / 768 / 1280 / 1920 px with no horizontal page scroll; wide tables scroll inside their own `overflow-x-auto` wrapper.
- Flash feedback uses the existing mechanism: `Inertia::flash('toast', ['type' => 'success'|'error', 'message' => '...'])`.
- Browser checks use the Claude Browser pane at `http://localhost:8000` (`composer run dev`, XAMPP MySQL running, `php artisan migrate` + `php artisan db:seed` done). If MySQL is down, report browser steps BLOCKED. Never point `.env` at SQLite.

## Review Focus

1. **Deleting a brand, category or showroom still referenced by cars.** Expect no 500 and no orphaned cars: brand/category delete is refused with an Indonesian error toast naming the count; showroom delete sets `cars.showroom_id` to null. Test owner: Tasks 3 and 4.
2. **Uploading an SVG/HTML file renamed `.png`, or an image over the size limit.** Expect a validation error and nothing stored (SVG is never accepted, so a logo cannot carry script). Test owner: Tasks 3 and 7.
3. **Admin demoting or deactivating their own account, or deleting the last active admin.** Expect refusal with an error message; the admin keeps access. Test owner: Task 5.
4. **Two cars with the same name and year, or a name edit.** Expect unique slugs (`toyota-fortuner-2024`, `toyota-fortuner-2024-2`) and the slug stays stable when only price/specs change. Test owner: Task 6.
5. **A color, option, image or hotspot request for car A sent with car B's child id in the URL.** Expect 404 (scoped bindings), never a cross-car edit. Test owner: Task 7.

## Rulings (spec silent or in tension)

- **`cars.showroom_id` (nullable, `nullOnDelete`) is added.** The spec's card shows "kota showroom" but the `cars` table has no location; this column is the unit's showroom.
- **Extra enums** `CarStatus` (draft/published/sold_out) and `CarBadge` (new/verified/electric/classic/hot) join the spec's `FuelType` (petrol/diesel/hybrid/electric), `Transmission` (manual/automatic/cvt/dct), `CarCondition` (new/used), `OptionGroup` (wheels/interior/package). Every enum has `label(): string` (Indonesian) and uses trait `App\Enums\Concerns\HasOptions` providing `static options(): array<int, array{value: string, label: string}>`.
- **Media disk.** `config/dealer.php` gains `'media_disk' => env('MEDIA_DISK', 'public')`. All catalog uploads use `Storage::disk(config('dealer.media_disk'))`; models expose URLs via an appended `*_url` accessor. `php artisan storage:link` is part of setup (Task 3 runs it). Payment proofs (Fase 4) will use the private disk, not this one.
- **Upload rules.** Images: `image|mimes:jpg,jpeg,png,webp|max:4096` (car images, color images, option images); brand logos `image|mimes:png,webp|max:1024`. Stored with `store("<folder>")` (random hash name). Deleting a record deletes its files.
- **Car slug** = `Str::slug("{brand name} {name} {year}")`, suffixed `-2`, `-3`… when taken (including soft-deleted rows). Generated on create and regenerated only when brand, name or year changes. Brand/category slugs = `Str::slug(name)`, unique.
- **Hotspots** are stored on `cars.hotspots` as a JSON list of `{x: float, y: float, title: string, description: string}` with `x`,`y` percentages (0–100, one decimal) relative to the cover image. Max 8.
- **Cover image.** Exactly one `is_cover` per car once it has images: first upload becomes cover; setting a new cover clears the old; deleting the cover promotes the lowest `sort_order` image.
- **Authorization** for master data is `role:admin` route middleware (the whole group is admin-only, so policies add nothing in this phase). Sales get 403 on every `/dashboard/{brands,categories,showrooms,cars,users}` route.
- **Deletes.** Cars soft-delete (index filter "Terhapus" + restore action; no force delete). Brands/categories: FK `restrictOnDelete`, controller pre-checks and refuses. Showrooms: hard delete, cars keep `showroom_id = null`. Users: hard delete only when the user has no future relations — in this phase always allowed except self and last active admin.
- **Seed images.** The seeder creates no image files (no downloads). The UI shows a neutral placeholder (car silhouette icon) when a car has no cover. Real photos are added via the dashboard or in Fase 3.
- **List/Cards toggle** is only on the cars index (query `view=cards|list`, default `list`). Other indexes are list only.
- **Index query params:** `q` (search), resource filters (cars: `brand`, `category`, `status`, `trashed=1`; users: `role`, `active`), `page`. Paginate 10 per page with `->withQueryString()`. Search uses `where like` on name (cars: name or brand name; users: name or email; showrooms: name or city).

---

## File Structure

| Area | Files |
|---|---|
| Enums | `app/Enums/{FuelType,Transmission,CarCondition,OptionGroup,CarStatus,CarBadge}.php`, `app/Enums/Concerns/HasOptions.php` |
| Schema & models | `database/migrations/2026_11_23_0000{01..07}_create_{brands,categories,showrooms,cars,car_images,car_colors,car_options}_table.php`, `app/Models/{Brand,Category,Showroom,Car,CarImage,CarColor,CarOption}.php`, `database/factories/*Factory.php` |
| Seed | `database/seeders/CatalogSeeder.php`, `database/seeders/DatabaseSeeder.php` |
| Support | `app/Support/CarSlug.php`, `config/dealer.php` (`media_disk`) |
| Controllers | `app/Http/Controllers/Dashboard/{BrandController,CategoryController,ShowroomController,UserController,CarController,CarColorController,CarOptionController,CarImageController,CarHotspotController}.php` |
| Requests | `app/Http/Requests/Dashboard/*Request.php` (one Store/Update pair per resource, or a single `XRequest` when rules are identical) |
| Routes | `routes/dashboard.php` (required from `routes/web.php`) |
| Table kit | `resources/js/components/ui/table.tsx`, `resources/js/components/dashboard/{data-toolbar,data-table-shell,status-pill,row-actions,pagination-bar,confirm-delete-dialog,empty-state,image-input,money-input}.tsx`, `resources/js/types/pagination.ts`, `resources/js/lib/query.ts` (+ test) |
| Pages | `resources/js/pages/dashboard/{brands,categories,showrooms,users}/index.tsx` (create/edit in a Dialog), `resources/js/pages/dashboard/cars/{index,create,edit}.tsx`, `resources/js/components/dashboard/cars/{info-fields,spec-fields,colors-tab,options-tab,images-tab,hotspots-tab}.tsx`, `resources/js/lib/hotspots.ts` (+ test) |
| Nav | `resources/js/components/app-sidebar.tsx` |
| Tests | `tests/Feature/Catalog/{CatalogSchemaTest,CatalogSeederTest}.php`, `tests/Feature/Dashboard/{BrandCrudTest,CategoryCrudTest,ShowroomCrudTest,UserCrudTest,CarCrudTest,CarMediaTest,CarHotspotTest,MasterDataAccessTest}.php` |
| Docs | `docs/DOKUMENTASI.md`, `docs/FLOWCHART.md` |

---

### Task 1: Catalog enums, schema, models & factories

**Files:**
- Create: the enums, migrations, models and factories listed above; `app/Support/CarSlug.php`
- Test: `tests/Feature/Catalog/CatalogSchemaTest.php`, `tests/Unit/EnumOptionsTest.php`

**Interfaces:**
- Produces:
  - `Brand` (name, slug, logo_path; `logo_url` appended; `cars(): HasMany`), `Category` (name, slug; `cars()`), `Showroom` (name, city, address, phone, lat `decimal(10,7)`, lng `decimal(10,7)`; `cars()`).
  - `Car` with `SoftDeletes`; fillable: brand_id, category_id, showroom_id, name, slug, year, base_price, fuel_type, transmission, engine, power_hp, torque_nm, accel_0_100 (`decimal(4,1)` nullable), top_speed, range_km (nullable), seats, mileage_km, condition, stock, badge (nullable), status, description (text nullable), hotspots (json nullable, cast `array`), is_featured. Casts: enums for fuel_type/transmission/condition/badge/status, `is_featured` bool. Relations: `brand()`, `category()`, `showroom()`, `images()` (ordered by `sort_order`), `cover(): HasOne` (`ofMany` where `is_cover`), `colors()`, `options()`. Scope `published()`.
  - `CarImage` (car_id, path, sort_order, is_cover; `url` appended), `CarColor` (car_id, name, hex `char(7)`, price_delta `bigInteger` signed default 0, image_path nullable; `image_url`), `CarOption` (car_id, group `OptionGroup`, name, description nullable, price unsigned bigint, image_path nullable; `image_url`). Child FKs `cascadeOnDelete`.
  - `Car` FKs: brand_id & category_id `restrictOnDelete`, showroom_id nullable `nullOnDelete`.
  - `CarSlug::for(string $brandName, string $name, int $year, ?int $ignoreId = null): string` — unique across `cars` including trashed.
  - Factories: `BrandFactory`, `CategoryFactory`, `ShowroomFactory`, `CarFactory` (states `published()`, `draft()`, `soldOut()`), `CarImageFactory` (state `cover()`), `CarColorFactory`, `CarOptionFactory`.
  - `HasOptions::options()` returns `[['value' => 'petrol', 'label' => 'Bensin'], ...]` in case order.

- [ ] **Step 1: Write failing tests**

`EnumOptionsTest::test_fuel_type_options_are_value_label_pairs`: `FuelType::options()[0] === ['value' => 'petrol', 'label' => 'Bensin']` and count 4. Labels: FuelType Bensin/Diesel/Hybrid/Listrik; Transmission Manual/Otomatis/CVT/DCT; CarCondition Baru/Bekas; OptionGroup Velg/Interior/Paket; CarStatus Draft/Tayang/Terjual habis; CarBadge NEW/VERIFIED/ELECTRIC/CLASSIC/HOT.

`CatalogSchemaTest`:
- `test_car_relations_and_casts`: a factory car with 2 colors, 2 options, 3 images (second is cover) → `$car->cover->id` is the second image, `$car->fuel_type instanceof FuelType`, `$car->hotspots` round-trips `[['x' => 12.5, 'y' => 40.0, 'title' => 'LED', 'description' => 'Lampu']]`.
- `test_deleting_car_soft_deletes_and_keeps_children`: `$car->delete()` → `assertSoftDeleted`, colors count unchanged.
- `test_brand_with_cars_cannot_be_hard_deleted_at_db_level`: expects `QueryException` on `$brand->delete()` (enable FK on sqlite — Laravel does by default).
- `test_deleting_showroom_nulls_car_showroom`.
- `test_car_slug_is_unique_including_trashed`: create + trash `toyota-fortuner-2024`; `CarSlug::for('Toyota','Fortuner',2024)` → `toyota-fortuner-2024-2`; with `$ignoreId` of the existing row → `toyota-fortuner-2024`.
- `test_published_scope`.

- [ ] **Step 2: Run** `php artisan test --filter='CatalogSchemaTest|EnumOptionsTest'` → FAIL (classes missing).
- [ ] **Step 3: Implement** enums, trait, migrations (order: brands, categories, showrooms, cars, car_images, car_colors, car_options), models, factories, `CarSlug`. Add PHPDoc `@property` blocks like `User` so phpstan level 7 passes. Indexes: `cars(status)`, `cars(brand_id)`, `cars(category_id)`, unique `cars.slug`, `brands.slug`, `categories.slug`.
- [ ] **Step 4: Run** the filter again → PASS; then the full green list from Global Constraints; `php artisan migrate` on MariaDB succeeds.

---

### Task 2: Catalog seeder

**Files:**
- Create: `database/seeders/CatalogSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php` (call after `DemoUserSeeder`)
- Test: `tests/Feature/Catalog/CatalogSeederTest.php`

**Interfaces:**
- Consumes: Task 1 models.
- Produces: seeded data later phases rely on: categories `SUV, Sedan, MPV, Hatchback, EV, Pickup`; brands `Toyota, Honda, Hyundai, Mitsubishi, BYD, Wuling, Mazda`; showrooms `Pemecut Motors Jakarta Selatan` (Jakarta), `Pemecut Motors Bandung` (Bandung), `Pemecut Motors Surabaya` (Surabaya) with real-ish lat/lng and dummy addresses/phones.

Cars (10, all `published` except the last which is `draft`; at least 3 `is_featured`): Toyota Fortuner 2024 (SUV, Rp 615.000.000, diesel), Toyota Innova Zenix Hybrid 2024 (MPV, Rp 495.000.000, hybrid), Honda Civic RS 2024 (Sedan, Rp 597.000.000), Honda Brio RS 2024 (Hatchback, Rp 237.000.000), Hyundai Ioniq 5 2024 (EV, Rp 782.000.000, electric, range_km set), Hyundai Creta 2024 (SUV, Rp 389.000.000), Mitsubishi Triton 2024 (Pickup, Rp 487.000.000, diesel, manual), BYD Seal 2024 (EV, Rp 719.000.000, electric), Wuling Binguo EV 2024 (EV, Rp 349.000.000, electric), Mazda CX-5 2021 (SUV, used, Rp 425.000.000, mileage 38.000 km, `draft`). Specs plausible; each car 2–4 colors (`hex`, `price_delta` 0 or 2–8 juta), options: 2 wheels, 2 interior, 2 package with prices; 2–3 hotspots each; `stock` 1–8; badges mixed (EVs `electric`, used `verified`, one `hot`, new ones `new`).

- [ ] **Step 1: Write failing tests**: `test_seeds_expected_catalog` (6 categories, 7 brands, 3 showrooms, 10 cars, 9 published, ≥3 featured, every car has 2–4 colors and ≥1 option of each `OptionGroup`); `test_seeder_is_idempotent` (run twice → still 10 cars, 7 brands).
- [ ] **Step 2: Run** `php artisan test --filter=CatalogSeederTest` → FAIL.
- [ ] **Step 3: Implement** with `updateOrCreate` keyed by slug; replace children per car on re-run (delete + recreate colors/options).
- [ ] **Step 4: Run** → PASS; `php artisan db:seed` on MariaDB succeeds; full green list.

---

### Task 3: Dashboard table kit, admin route group & Brands CRUD

**Files:**
- Create: `routes/dashboard.php`; `app/Http/Controllers/Dashboard/BrandController.php`; `app/Http/Requests/Dashboard/BrandRequest.php`; `resources/js/components/ui/table.tsx` (standard shadcn table primitives); the `resources/js/components/dashboard/*` kit files; `resources/js/types/pagination.ts`; `resources/js/lib/query.ts` + `query.test.ts`; `resources/js/pages/dashboard/brands/index.tsx`
- Modify: `routes/web.php` (require `dashboard.php`), `config/dealer.php` (`media_disk`), `resources/js/components/app-sidebar.tsx`
- Test: `tests/Feature/Dashboard/BrandCrudTest.php`, `tests/Feature/Dashboard/MasterDataAccessTest.php`

**Interfaces:**
- Produces:
  - Route group in `routes/dashboard.php`: `Route::middleware(['auth', 'role:admin'])->prefix('dashboard')->name('dashboard.')->group(...)`. Brands: `Route::resource('brands', BrandController::class)->except(['create','show','edit'])` plus `POST /dashboard/brands/{brand}` handled by spoofed `PUT` for multipart (forms send `_method=PUT`).
  - Index Inertia props shape used by every list page: `{ items: Paginated<T>, filters: Record<string,string|null> }`; `Paginated<T>` = Laravel paginator JSON (`data`, `current_page`, `last_page`, `per_page`, `total`, `from`, `to`, `links: {url: string|null, label: string, active: boolean}[]`).
  - Kit components (props are the contract):
    - `DataToolbar({ search: string, onSearch(q: string), filters?: ReactNode, actions?: ReactNode })` — debounced (300 ms) search input with icon, filter slot, primary action slot; wraps on mobile.
    - `DataTableShell({ children })` — rounded card + `overflow-x-auto`.
    - `StatusPill({ tone: 'success'|'warning'|'danger'|'neutral'|'info', children })`.
    - `RowActions({ items: {label: string, onSelect(): void, destructive?: boolean, icon?: LucideIcon}[] })` — "⋯" dropdown.
    - `PaginationBar({ meta: Paginated<unknown> })` — "Menampilkan 1–10 dari 34" + prev/next + page links via Inertia `Link` with `preserveScroll`.
    - `ConfirmDeleteDialog({ open, onOpenChange, title, description, onConfirm, processing })`.
    - `EmptyState({ icon, title, description, action? })`.
    - `ImageInput({ name, currentUrl?: string|null, accept: string, error?: string })` — preview + replace.
    - `MoneyInput({ name, defaultValue?: number, error?: string })` — shows `Rp` grouping while typing, submits digits only (hidden input).
  - `lib/query.ts`: `cleanQuery(params: Record<string, string|number|boolean|null|undefined>): Record<string, string>` drops empty/null/false, stringifies numbers, `true` → `'1'`.
  - Sidebar: group label "Master Data" (admin only) with Mobil (`Car`), Brand (`Tags`), Kategori (`Shapes`), Showroom (`MapPin`), Pengguna (`Users`) — add each link in the task that creates its page; Brand now.
  - Brand controller flashes `toast` success messages: "Brand ditambahkan.", "Brand diperbarui.", "Brand dihapus.", and error "Brand masih dipakai {n} mobil." on blocked delete.

- [ ] **Step 1: Write failing tests**
  - `query.test.ts`: `cleanQuery({ q: '', brand: 3, trashed: true, status: null })` → `{ brand: '3', trashed: '1' }`.
  - `MasterDataAccessTest::test_sales_and_customers_are_forbidden_from_master_data`: for `dashboard.brands.index` (extend in later tasks with categories, showrooms, cars, users) → sales 403, customer 403, guest redirect to login, admin 200.
  - `BrandCrudTest`: `test_index_lists_and_searches` (`?q=toy` returns only Toyota, prop `items.data` count 1, component `dashboard/brands/index`); `test_store_creates_brand_with_logo` (png → file exists on fake `public` disk under `brands/`, slug `toyota`); `test_store_rejects_svg_and_duplicate_name` (svg upload → `logo` error; duplicate name → `name` error); `test_update_replaces_logo_and_deletes_old_file`; `test_destroy_blocked_when_brand_has_cars` (brand kept, session flash toast type error); `test_destroy_deletes_brand_and_logo`.
- [ ] **Step 2: Run** `php artisan test --filter='BrandCrudTest|MasterDataAccessTest'` and `npx vp test run resources/js/lib/query.test.ts` → FAIL.
- [ ] **Step 3: Implement** route group, controller, request (`name` required max 60 unique ignoring self; `logo` nullable per Rulings), kit, page (table: logo thumb, name, slug, cars count via `withCount`, actions; create/edit in a `Dialog` using Wayfinder `.form()`), sidebar link, `media_disk` config. Run `php artisan storage:link` and `php artisan wayfinder:generate --with-form`.
- [ ] **Step 4: Run** tests → PASS; full green list.
- [ ] **Step 5: Browser check** as admin: create brand with logo, search, edit, delete blocked for Toyota (toast), delete fresh brand; 375 px width shows no page-level horizontal scroll.

---

### Task 4: Categories & Showrooms CRUD

**Files:**
- Create: `Dashboard/CategoryController.php`, `Dashboard/ShowroomController.php`, their requests, `pages/dashboard/categories/index.tsx`, `pages/dashboard/showrooms/index.tsx`
- Modify: `routes/dashboard.php`, `app-sidebar.tsx`, `MasterDataAccessTest`
- Test: `tests/Feature/Dashboard/{CategoryCrudTest,ShowroomCrudTest}.php`

**Interfaces:**
- Consumes: Task 3 kit, route group, toast messages pattern.
- Produces: `dashboard.categories.*` and `dashboard.showrooms.*` (resource `except create/show/edit`), same index props shape.

Validation: category `name` required max 40 unique. Showroom `name` required max 80 unique, `city` required max 60, `address` required max 255, `phone` required `regex:/^[0-9+\-\s()]{6,20}$/`, `lat` required numeric between -90..90, `lng` required numeric between -180..180.

- [ ] **Step 1: Failing tests**: category store/update/search, `test_destroy_blocked_when_category_has_cars` (error toast "Kategori masih dipakai {n} mobil."); showroom store with lat/lng, `test_rejects_out_of_range_coordinates` (lat 91 → error), `test_destroy_nulls_cars_showroom` (car still exists, `showroom_id` null); access test extended to both indexes.
- [ ] **Step 2: Run** → FAIL. **Step 3: Implement** (showroom table columns: name, city, phone, coordinates mono, cars count). **Step 4: Run** → PASS; full green list.

---

### Task 5: Users CRUD (admin)

**Files:**
- Create: `Dashboard/UserController.php`, `Requests/Dashboard/{StoreUserRequest,UpdateUserRequest}.php`, `pages/dashboard/users/index.tsx`
- Modify: `routes/dashboard.php`, `app-sidebar.tsx`, `MasterDataAccessTest`
- Test: `tests/Feature/Dashboard/UserCrudTest.php`

**Interfaces:**
- Produces: `dashboard.users.*` (resource `except create/show/edit`). Index filters `role` (admin/sales/customer), `active` (`1`/`0`). Rows: name, email, phone, role pill, active pill (Aktif/Nonaktif), created date (`d M Y`).

Rules: store — name required max 100; email required email unique; phone nullable same regex as showroom; role required `Rule::enum(Role::class)`; is_active boolean; password required `Password::defaults()` confirmed. Update — same but password nullable (only changed when filled). Role and is_active set via `forceFill`. Admin-created users get `email_verified_at = now()`.

Guards (controller, Indonesian error toast, no change saved): cannot change own role, cannot deactivate self, cannot delete self ("Tidak bisa menghapus akun sendiri."), cannot demote/deactivate/delete the last active admin ("Minimal harus ada satu admin aktif.").

- [ ] **Step 1: Failing tests**: `test_admin_creates_sales_account` (role sales, verified, can log in); `test_store_ignores_nothing_but_validated_fields` (extra `email_verified_at` in payload ignored); `test_update_without_password_keeps_password`; `test_admin_cannot_deactivate_or_demote_self`; `test_cannot_delete_self`; `test_last_active_admin_is_protected` (second admin inactive → demoting the only active admin refused); `test_filter_by_role_and_active`; deactivated user's next request is logged out (reuse Fase 1 `EnsureUserIsActive`, just assert `assertGuest` after a request).
- [ ] **Step 2: Run** → FAIL. **Step 3: Implement.** **Step 4: Run** → PASS; full green list.

---

### Task 6: Cars index, create & edit (Info + Spesifikasi tabs), soft delete & restore

**Files:**
- Create: `Dashboard/CarController.php`, `Requests/Dashboard/CarRequest.php`, `pages/dashboard/cars/{index,create,edit}.tsx`, `components/dashboard/cars/{info-fields,spec-fields}.tsx`
- Modify: `routes/dashboard.php`, `app-sidebar.tsx`, `MasterDataAccessTest`
- Test: `tests/Feature/Dashboard/CarCrudTest.php`

**Interfaces:**
- Consumes: `CarSlug::for`, enums `options()`, kit, `MoneyInput`.
- Produces:
  - Routes: `Route::resource('cars', CarController::class)->except('show')` + `PATCH /dashboard/cars/{car}/restore` → `dashboard.cars.restore` (`withTrashed()` binding).
  - `create`/`edit` props: `car` (edit only; with colors, options, images ordered, hotspots), `options: { brands: {id,name}[], categories: {id,name}[], showrooms: {id,name,city}[], fuelTypes, transmissions, conditions, statuses, badges }` (enum `options()`), `tab: 'info'|'specs'|'colors'|'options'|'images'|'hotspots'` from query `?tab=` (default `info`). Tasks 7–8 add tab bodies; this task renders the tab bar with the later tabs disabled on create (car must exist first) and showing "Segera" placeholders on edit.
  - Store redirects to `dashboard.cars.edit` with `tab=colors` and toast "Mobil ditambahkan. Lanjutkan isi warna & opsi."

Validation (`CarRequest`): brand_id/category_id required exists; showroom_id nullable exists; name required max 80; year integer 1990..(current year+1); base_price required integer min 10_000_000 max 50_000_000_000; fuel_type/transmission/condition/status `Rule::enum`; badge nullable enum; engine nullable max 40; power_hp, torque_nm, top_speed, seats (2..9), stock (0..999), mileage_km (0..2_000_000) integers; range_km nullable integer required_if fuel_type=electric; accel_0_100 nullable numeric 1..30; description nullable max 5000; is_featured boolean. `condition=new` forces mileage_km ≤ 100. Publishing (`status=published`) requires the car to have ≥1 image and ≥1 color — on update only (create cannot publish: `status` must be `draft` on store, error "Simpan sebagai draft dulu, tambahkan foto & warna sebelum tayang.").

Index: filters brand, category, status, `trashed=1` (shows only trashed with "Pulihkan" action), search q (name or brand name), `view=list|cards`. List columns: cover thumb, name + `brand · year`, category, price `formatRupiah`, stock, status pill (Tayang=success, Draft=neutral, Terjual habis=warning), featured star, actions (Edit, Hapus). Cards: cover, badge, name, price, status pill.

- [ ] **Step 1: Failing tests**: `test_index_filters_and_searches` (brand filter, status filter, `q=civic`, trashed view); `test_store_creates_draft_with_unique_slug` (two `Toyota Fortuner 2024` → second slug ends `-2`; redirect to edit with `tab=colors`); `test_store_rejects_published_status`; `test_update_keeps_slug_when_only_price_changes` and `test_update_regenerates_slug_when_name_changes`; `test_cannot_publish_without_image_and_color`; `test_electric_requires_range`; `test_destroy_soft_deletes_and_restore_brings_back`; access test extended.
- [ ] **Step 2: Run** → FAIL. **Step 3: Implement.** **Step 4: Run** → PASS; full green list.
- [ ] **Step 5: Browser check**: create a draft car, edit specs, toggle list/cards, delete, view trashed, restore; 375 px no page-level horizontal scroll.

---

### Task 7: Car colors, options & images tabs

**Files:**
- Create: `Dashboard/{CarColorController,CarOptionController,CarImageController}.php`, requests, `components/dashboard/cars/{colors-tab,options-tab,images-tab}.tsx`
- Modify: `routes/dashboard.php`, `pages/dashboard/cars/edit.tsx`
- Test: `tests/Feature/Dashboard/CarMediaTest.php`

**Interfaces:**
- Consumes: `ImageInput`, `MoneyInput`, `ConfirmDeleteDialog`, `edit` props from Task 6.
- Produces (all under the admin group, `->scopeBindings()`):
  - `POST cars/{car}/colors` `dashboard.cars.colors.store`, `PUT cars/{car}/colors/{color}` `.update`, `DELETE ...` `.destroy`. Rules: name required max 40 unique per car; hex `regex:/^#[0-9A-Fa-f]{6}$/`; price_delta integer -100_000_000..500_000_000; image nullable (image rules).
  - `POST cars/{car}/options`, `PUT/DELETE cars/{car}/options/{option}` (`dashboard.cars.options.*`). Rules: group enum; name required max 60; description nullable max 300; price integer 0..500_000_000; image nullable.
  - `POST cars/{car}/images` (`images[]` up to 10 per request, each image rule; total per car ≤ 20), `PATCH cars/{car}/images/{image}/cover` (`dashboard.cars.images.cover`), `PATCH cars/{car}/images/order` (`dashboard.cars.images.order`, body `ids: int[]` — must be exactly the car's image ids), `DELETE cars/{car}/images/{image}`.
  - All redirect back to `dashboard.cars.edit` with the matching `tab` and a toast.
  - Cover rules per Rulings. Deleting a color/option/image deletes its file. If deleting the last image or last color of a `published` car, the car is set to `draft` and the toast says "Mobil dikembalikan ke draft karena tidak punya foto/warna."

UI: colors tab = swatch list (hex circle, name, `+ Rp 5.000.000` delta, thumb) with add/edit dialog; options tab grouped by Velg / Interior / Paket; images tab = grid with cover badge, "Jadikan cover", move left/right buttons (keyboard accessible, posts the new order), delete, multi-file upload input.

- [ ] **Step 1: Failing tests**: color store/update/delete with file cleanup; invalid hex rejected; options grouped store with enum validation; images multi-upload (first becomes cover), set cover clears previous, reorder with wrong id set → 422, delete cover promotes next; `test_child_of_other_car_returns_404` (color/option/image of car B via car A URL → 404 for update, destroy, cover); `test_rejects_svg_and_oversize_images` (svg disguised `fake()->create('x.png', 10, 'image/svg+xml')` and 5 MB image → errors, nothing stored); `test_published_car_reverts_to_draft_when_last_image_deleted`.
- [ ] **Step 2: Run** → FAIL. **Step 3: Implement.** **Step 4: Run** → PASS; full green list.
- [ ] **Step 5: Browser check**: add 2 colors with images, 3 options, upload 3 photos, reorder, change cover, publish the car.

---

### Task 8: Hotspot editor tab

**Files:**
- Create: `Dashboard/CarHotspotController.php`, `Requests/Dashboard/CarHotspotRequest.php`, `components/dashboard/cars/hotspots-tab.tsx`, `resources/js/lib/hotspots.ts` + `hotspots.test.ts`
- Modify: `routes/dashboard.php`, `pages/dashboard/cars/edit.tsx`
- Test: `tests/Feature/Dashboard/CarHotspotTest.php`

**Interfaces:**
- Produces:
  - `PUT /dashboard/cars/{car}/hotspots` → `dashboard.cars.hotspots.update`, body `hotspots: {x,y,title,description}[]` replaces the whole list. Rules: array max 8; x,y required numeric 0..100; title required max 40; description required max 200. Stored rounded to 1 decimal. Redirect to edit `tab=hotspots`, toast "Hotspot disimpan."
  - `lib/hotspots.ts`: `pointFromClick(rect: {left: number, top: number, width: number, height: number}, clientX: number, clientY: number): {x: number, y: number}` — percentage, clamped 0–100, rounded to 1 decimal.

UI: cover image shown at its natural aspect ratio; clicking adds a numbered "+" marker at the click point and opens a side form for title/description; markers draggable is NOT required — edit via selecting a marker and nudging x/y number inputs; delete marker; "Simpan hotspot" submits all. No cover → EmptyState "Unggah foto & pilih cover dulu" linking to the images tab.

- [ ] **Step 1: Failing tests**: `hotspots.test.ts` — click at center of `{left:100, top:50, width:400, height:200}` (`300,150`) → `{x:50, y:50}`; outside the rect → clamped `{x:100, y:0}`; `(233,117)` → `{x:33.3, y:33.5}`. PHP: `test_replaces_hotspots_and_rounds`, `test_rejects_more_than_eight_and_out_of_range`, `test_empty_list_clears_hotspots`.
- [ ] **Step 2: Run** → FAIL. **Step 3: Implement.** **Step 4: Run** → PASS; full green list.
- [ ] **Step 5: Browser check**: place 3 hotspots on a seeded car, save, reload, markers persist at the same positions at 375 px and 1280 px widths.

---

### Task 9: Documentation update & end-to-end check

**Files:**
- Modify: `docs/DOKUMENTASI.md`, `docs/FLOWCHART.md`

- [ ] **Step 1:** Update DOKUMENTASI sections: 1 (CRUD requirement evidence for master data + users), 4 (Dashboard controllers / kit components), 7 Database (the 7 catalog tables as implemented incl. `showroom_id`, enums and values), 9 Routes (regenerate from `php artisan route:list --path=dashboard`), 10 (fitur per halaman: dashboard master data pages + CRUD table), 13 Keamanan (upload rules, no SVG, scoped bindings, last-admin guard), 3 Setup (`php artisan storage:link`, `db:seed` creates the catalog). Remove the `_(Fase 2)_` planned markers that are now done.
- [ ] **Step 2:** Update FLOWCHART: ERD with the catalog tables as implemented; admin flowchart (kelola master data, mobil draft → foto/warna → tayang).
- [ ] **Step 3:** Validate mermaid with `npx -y @mermaid-js/mermaid-cli@11 -i docs/FLOWCHART.md -o <scratchpad>/flowchart-check/out.md` (outputs outside the repo). Grep every named route/class in the docs exists.
- [ ] **Step 4: Full verification:** the green list; `php artisan migrate:fresh --seed` on MariaDB; Browser pass as admin through all five master-data pages at 375 and 1280 px; as sales confirm the sidebar hides "Master Data" and `/dashboard/cars` is 403.
