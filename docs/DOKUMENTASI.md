# Dokumentasi Teknis — Pemecut Motors

> Draf Fase 1 (Foundation). Dokumen ini diperbarui di akhir setiap fase dan difinalisasi di Fase 7. Bagian yang dikerjakan di fase berikutnya ditandai `_(Direncanakan — Fase N)_` beserta ringkasan dari spesifikasi (`docs/superpowers/specs/2026-11-20-car-dealer-design.md`). Diagram ada di [FLOWCHART.md](FLOWCHART.md).

## Ringkasan Proyek & Pemetaan Syarat Tugas

Pemecut Motors adalah website dealer mobil lengkap (tugas akhir pemrograman web, tema kelompok Dealer Mobil). Guest bebas melihat katalog, filter, compare, dan configurator tanpa login; login baru diminta saat checkout, test drive, dan wishlist. Tampilan publik berupa dark cinematic dengan animasi scroll halus; dashboard staf berupa light neutral. Seluruh teks antarmuka berbahasa Indonesia, kode dan identifier berbahasa Inggris.

Pemetaan ke syarat tugas dosen:

| No    | Syarat                                                      | Bukti / fitur                                                                                                                                                                                                          | Lokasi kode                                                                                                              |
| ----- | ----------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| 1     | Laravel (framework PHP)                                     | Laravel 13.43 di PHP 8.5.13                                                                                                                                                                                            | `composer.json`, `bootstrap/app.php`, `app/`                                                                             |
| 2     | MySQL via phpMyAdmin                                        | MariaDB 10.4 (XAMPP), database `car_dealer`, bisa dilihat di phpMyAdmin                                                                                                                                                | `.env` (`DB_CONNECTION=mysql`, `DB_DATABASE=car_dealer`), `database/migrations/`                                         |
| 3     | Minimal 2 role berbeda                                      | 3 role: `admin`, `sales`, `customer`                                                                                                                                                                                   | `app/Enums/Role.php`, kolom `users.role`                                                                                 |
| 4     | Autentikasi session; user lain tidak boleh punya akses sama | Fortify + `SESSION_DRIVER=database` + tabel `sessions` + middleware `EnsureRole` (`role:customer`, `role:admin,sales`)                                                                                                 | `app/Providers/FortifyServiceProvider.php`, `config/session.php`, `app/Http/Middleware/EnsureRole.php`, `routes/web.php` |
| 5     | Semua fitur CRUD                                            | Fase 1 baru fondasi. Rencana CRUD: Cars (+warna/opsi/gambar/hotspot), Brands, Categories, Showrooms, Users (full CRUD oleh admin); Orders; Payments; Test drives; Inquiries; Wishlist _(Direncanakan — Fase 2 s.d. 5)_ | Lihat bagian "Fitur per Halaman & Daftar CRUD"                                                                           |
| 6     | Sesuai tema                                                 | Dealer mobil: katalog, configurator, checkout booking fee, test drive, AI advisor                                                                                                                                      | Spesifikasi, bagian "Halaman & desain"                                                                                   |
| 7     | Tampilan + fungsionalitas sesuai alur bisnis                | Tiga layout (public, account, dashboard), alur order dengan state machine _(Direncanakan — Fase 4/5)_                                                                                                                  | `resources/js/layouts/`, [FLOWCHART.md](FLOWCHART.md)                                                                    |
| 8     | Kode di GitHub                                              | `github.com/Azriel-Fajar/Car-Dealer` (repo ini)                                                                                                                                                                        | `.git` (commit dilakukan manual oleh user)                                                                               |
| Bonus | Deploy                                                      | Vercel + MySQL cloud _(Direncanakan — Fase 8)_                                                                                                                                                                         | Bagian "Deploy"                                                                                                          |

## Tech Stack & Alasan Pemilihan

Versi diambil dari `composer.json`, `package.json`, dan `php artisan --version`.

| Lapisan         | Teknologi                                                       | Versi                                                                                   | Alasan                                                      |
| --------------- | --------------------------------------------------------------- | --------------------------------------------------------------------------------------- | ----------------------------------------------------------- |
| Runtime         | PHP                                                             | 8.5.13 (syarat `^8.3`), tanpa ekstensi `intl`                                           | Laravel 13 membutuhkan PHP 8.3 atau lebih baru              |
| Backend         | Laravel                                                         | 13.43.0 (`laravel/framework ^13.17`)                                                    | Framework wajib dari tugas                                  |
| Auth            | Laravel Fortify                                                 | v1.43.0 (`^1.37.2`), fitur: registrasi, reset password, verifikasi email, 2FA, passkeys | Auth berbasis session, bawaan starter kit                   |
| Bridge          | Inertia (`inertiajs/inertia-laravel`)                           | v3.7.0                                                                                  | Halaman React tanpa REST API terpisah                       |
| Route helper    | Laravel Wayfinder                                               | `^0.1.14`                                                                               | Fungsi route bertipe untuk TypeScript                       |
| Frontend        | React + TypeScript                                              | React `^19.2.0`, TypeScript `^5.7.2`, `@inertiajs/react ^3.0.0`                         | Starter kit resmi Laravel 13                                |
| Styling         | Tailwind CSS + shadcn/ui                                        | Tailwind `^4.0.0`                                                                       | Token desain mudah dikunci per permukaan                    |
| Animasi         | gsap, lenis, motion                                             | `^3.15.1`, `^1.3.27`, `^14.14.0`                                                        | Smooth scroll, ScrollTrigger/SplitText, micro-interaction   |
| Font            | `@fontsource-variable` Big Shoulders Display, Geist, Geist Mono | `^5.3.0` masing-masing                                                                  | Display condensed, body bersih, label spek mono             |
| Toolchain       | Vite+ (`vp`)                                                    | `vite-plus 1.4.0`                                                                       | `npm run build`, `check`, test JS                           |
| Database        | MariaDB (XAMPP) untuk dev                                       | 10.4                                                                                    | Didukung Laravel 13 (MariaDB 10.3+), terlihat di phpMyAdmin |
| Test            | Pest                                                            | `^5.3`                                                                                  | Menjalankan test PHPUnit bergaya kelas                      |
| Analisis statis | Larastan (PHPStan)                                              | `^3.9`, level 7 (`phpstan.neon`)                                                        | Bagian dari `composer ci:check`                             |

## Setup Lokal

Asumsi: Windows, XAMPP terpasang. Perintah di bawah memakai Git Bash.

### 1. PHP 8.5 dan Composer

1. Buka PowerShell sebagai Administrator, lalu jalankan installer resmi (php.new):
    ```powershell
    Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
    ```
2. Hapus `C:\xampp\php` dari **System PATH** (Settings, System, About, Advanced system settings, Environment Variables, System variables, Path). Apache dan phpMyAdmin XAMPP tetap jalan karena memakai modul PHP-nya sendiri.
3. Pada setiap sesi Git Bash yang menjalankan `php`, `composer`, `artisan`, atau `npm run build`/`dev`, awali dengan:
    ```bash
    export PATH="$HOME/.config/herd-lite/bin:$PATH"
    ```
    Alasannya: composer dari ComposerSetup di PATH crash di PHP 8.5, dan plugin Wayfinder untuk Vite memanggil `php artisan wayfinder:generate`. Di PowerShell: `$env:Path = "$env:USERPROFILE\.config\herd-lite\bin;$env:Path"`.
4. Cek: `php -v` (8.5.x), `composer -V`.

### 2. XAMPP MySQL, database, dan `.env`

1. Di XAMPP Control Panel start **MySQL** (dan Apache bila ingin memakai phpMyAdmin).
2. Di phpMyAdmin buat database `car_dealer` dengan collation `utf8mb4_unicode_ci`.
3. Salin `.env.example` menjadi `.env` (atau jalankan `composer setup`) lalu pastikan nilai berikut:
    ```ini
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=car_dealer
    DB_USERNAME=root
    DB_PASSWORD=
    SESSION_DRIVER=database
    QUEUE_CONNECTION=sync
    CACHE_STORE=database
    MAIL_MAILER=log
    ```
    `.env` tidak boleh di-commit (sudah di `.gitignore`).

### 3. Migrasi, seed, dan menjalankan aplikasi

```bash
export PATH="$HOME/.config/herd-lite/bin:$PATH"
composer install
npm install
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
composer run dev
```

Aplikasi berjalan di `http://localhost:8000` (tidak bentrok dengan Apache XAMPP di port 80).

### 4. Akun demo

Dibuat oleh `Database\Seeders\DemoUserSeeder` (idempoten, dipanggil dari `DatabaseSeeder`). Semua akun aktif dan email-nya sudah terverifikasi.

| Role     | Nama          | Email                   | Password   | Mendarat di  |
| -------- | ------------- | ----------------------- | ---------- | ------------ |
| admin    | Admin Pemecut | `admin@pemecut.test`    | `password` | `/dashboard` |
| sales    | Sales Pemecut | `sales@pemecut.test`    | `password` | `/dashboard` |
| customer | Customer Demo | `customer@pemecut.test` | `password` | `/account`   |

Akun ini hanya untuk pengembangan lokal dan harus dihapus atau diganti sebelum deploy.

## Struktur Folder & Arsitektur

```text
app/
  Enums/Role.php                         enum role (admin, sales, customer)
  Http/Controllers/RoleHomeController.php  dispatcher GET /home
  Http/Middleware/                       EnsureRole, EnsureUserIsActive, SecurityHeaders, HandleInertiaRequests, HandleAppearance
  Http/Responses/RoleRedirectResponse.php  redirect pasca login/registrasi/2FA/passkey
  Models/User.php
  Providers/FortifyServiceProvider.php   auth, view Inertia, rate limiter
  Support/Rupiah.php                     format Rupiah tanpa intl
config/dealer.php                        konfigurasi bisnis dealer
database/{migrations,factories,seeders}/
resources/
  css/app.css                            token desain
  views/app.blade.php                    root template Inertia
  js/
    app.tsx                              pemetaan prefix halaman ke layout
    layouts/                             public, account, dashboard, role, auth, settings
    pages/{public,account,dashboard,auth,settings}/
    components/public/                   header, footer, wordmark, menu mobile
    lib/{format,roles,dashboard-nav}.ts  helper
    lib/motion/                          modul animasi
    hooks/use-public-surface.ts
routes/{web,settings}.php
tests/{Feature,Unit}/
```

### Laravel, Inertia, React

Laravel menangani routing, middleware, Eloquent, dan session. Route halaman memakai `Route::inertia(path, component)` (atau controller yang mengembalikan `Inertia::render`). Inertia mengirim nama komponen (mis. `public/home`) beserta props, lalu React me-render file di `resources/js/pages/`. Props yang dibagikan ke semua halaman (`HandleInertiaRequests::share`): `name` (dari `config('dealer.brand_name')`), `auth.user`, dan `sidebarOpen`. Navigasi antar halaman memakai Inertia tanpa reload penuh.

### Peta layout (`resources/js/app.tsx`)

| Prefix komponen | Layout                         | Lenis           | Permukaan                     |
| --------------- | ------------------------------ | --------------- | ----------------------------- |
| `public/`       | `PublicLayout`                 | Ya (`useLenis`) | `.surface-public` (gelap)     |
| `auth/`         | `AuthLayout`                   | Tidak           | `.surface-public` (gelap)     |
| `account/`      | `AccountLayout`                | Tidak           | `.surface-public` (gelap)     |
| `dashboard/`    | `DashboardLayout`              | Tidak           | `:root` (terang netral)       |
| `settings/`     | `[RoleLayout, SettingsLayout]` | Tidak           | tergantung role (lihat bawah) |
| lainnya         | tanpa layout                   | Tidak           | `:root`                       |

`RoleLayout` memilih `DashboardLayout` untuk staf dan `AccountLayout` untuk customer, sehingga halaman pengaturan (profil, keamanan, tampilan) tetap berfungsi untuk semua role. Menu "Tampilan" disembunyikan untuk customer.

### Dua permukaan token (`resources/css/app.css`)

- **`:root`** adalah token terang netral untuk dashboard staf. Mode gelap lewat kelas `.dark` tetap tersedia (toggle appearance). Default appearance adalah `light` (`HandleAppearance`).
- **`.surface-public`** adalah token gelap sinematik dengan aksen oranye-merah (`--primary: oklch(0.668 0.222 35)`). Dipakai oleh halaman `public/`, `auth/`, `account/`, dan `settings/` untuk customer, apa pun pengaturan appearance-nya. Kelas ini diterapkan dua kali: di server oleh `resources/views/app.blade.php` (variabel `$publicSurface`) agar tidak berkedip saat load pertama, dan di klien oleh hook `usePublicSurface` saat navigasi Inertia.
- Font: `--font-display` (Big Shoulders Display), `--font-sans` (Geist), `--font-mono` (Geist Mono) untuk label spek.

## Autentikasi & Session

- **Fortify** menyediakan login, registrasi, reset password, verifikasi email, 2FA, dan passkeys (`config/fortify.php`). View-nya adalah halaman Inertia `auth/*` (didaftarkan di `FortifyServiceProvider::configureViews`). Login memakai email (`username => email`) dan `lowercase_usernames => true`.
- **Session** disimpan di database: `SESSION_DRIVER=database`, tabel `sessions` (dibuat oleh migrasi `0001_01_01_000000_create_users_table.php`), `SESSION_LIFETIME=120` menit. Tabel ini terlihat di phpMyAdmin sebagai bukti penggunaan session.
- **Regenerasi session saat login**: Fortify memanggil `$request->session()->regenerate()` di `PrepareAuthenticatedSession` untuk mencegah session fixation.
- **Autentikasi kustom**: `Fortify::authenticateUsing` mengecek password lalu `is_active`. Bila akun nonaktif, dilempar `ValidationException` dengan pesan `EnsureUserIsActive::MESSAGE` ("Akun Anda telah dinonaktifkan. Silakan hubungi admin dealer."). Password salah tetap menghasilkan error generik, sehingga status akun tidak bocor ke penyerang.

### Redirect berdasarkan role

`App\Http\Responses\RoleRedirectResponse` didaftarkan sebagai singleton untuk empat kontrak Fortify/Passkeys: `LoginResponse`, `RegisterResponse`, `TwoFactorLoginResponse`, dan `PasskeyLoginResponse`. Aturannya:

| Pengguna            | Tujuan setelah login/registrasi                                                                                                                                            |
| ------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Staf (admin, sales) | Selalu `/dashboard`. `url.intended` dihapus dari session (`session()->forget('url.intended')`) sehingga staf tidak pernah dilempar ke 403 karena URL tujuan milik customer |
| Customer            | `redirect()->intended(route('account'))`: kembali ke URL yang dituju sebelum login (mis. checkout) bila ada, selain itu `/account`                                         |

Untuk permintaan JSON, respons berisi `{"two_factor": false, "redirect": "<url>"}`.

`GET /home` (`role-home`, `RoleHomeController`) mengarahkan ke `User::homePath()`: `/dashboard` untuk staf, `/account` untuk customer. Route ini dipakai sebagai `fortify.home` (`config/fortify.php`, `'home' => '/home'`) dan `redirectUsersTo('/home')` di `bootstrap/app.php`. Dengan begitu pengguna yang sudah login lalu membuka `/login`, `/register`, atau `/email/verify` (middleware `guest`) dikirim ke halaman role-nya sendiri, bukan ke `/dashboard` yang akan menolak customer dengan 403.

### Pemblokiran akun nonaktif

`EnsureUserIsActive` ditambahkan ke grup middleware `web` (`bootstrap/app.php`). Pada setiap request, bila pengguna login tetapi `is_active = false`, ia di-logout, session di-invalidate dan token CSRF diregenerasi, lalu diarahkan ke `/login` dengan pesan di field `email` (untuk request JSON: 403 dengan pesan yang sama). Ini menutup kasus akun dinonaktifkan saat session, cookie remember-me, atau login passkey masih aktif.

### Konfigurasi mobil guest di session _(Direncanakan — Fase 4)_

Konfigurasi mobil (warna, velg, interior, paket) yang dibuat guest di configurator disimpan di session sehingga tetap utuh setelah login/registrasi lalu kembali ke checkout. Belum ada kodenya di Fase 1.

## Role & Hak Akses

Tiga role, disimpan di kolom `users.role` (enum PHP `App\Enums\Role`: `Admin`, `Sales`, `Customer`). Registrasi publik selalu menghasilkan `customer`; akun admin/sales dibuat lewat seeder atau menu Users oleh admin _(menu Users: Fase 2)_.

Matriks hak akses (dari spesifikasi):

| Fitur                                                                   |      Guest       | Customer | Sales | Admin |
| ----------------------------------------------------------------------- | :--------------: | :------: | :---: | :---: |
| Lihat katalog, detail, compare, configurator, kirim inquiry             |        ✅        |    ✅    |  ✅   |  ✅   |
| Checkout, upload bukti transfer, wishlist, booking test drive           |     → login      |    ✅    |  ❌   |  ❌   |
| Verifikasi bukti transfer (approve/reject)                              |        ❌        |    ❌    |  ✅   |  ✅   |
| `/account` (order & test drive milik sendiri)                           |        ❌        |    ✅    |  ❌   |  ❌   |
| `/dashboard` overview, Orders, Test drives, Inquiries, Customers (read) |        ❌        |    ❌    |  ✅   |  ✅   |
| Master data (Cars, Brands, Categories, Showrooms), Users, Reports       |        ❌        |    ❌    |  ❌   |  ✅   |
| Pakai AI Car Advisor                                                    | ✅ (limit ketat) |    ✅    |  ✅   |  ✅   |
| Log & statistik AI Advisor, kill switch, budget token                   |        ❌        |    ❌    |  ❌   |  ✅   |

Matriks ini ditegakkan oleh middleware, Policies, dan feature test untuk tiap baris. Di Fase 1 baru baris `/account` dan `/dashboard` yang ada kodenya.

### Middleware `role:`

`App\Http\Middleware\EnsureRole` didaftarkan sebagai alias `role` di `bootstrap/app.php`. Parameter berupa daftar nilai role yang diizinkan, dipisah koma; bila pengguna tidak ada atau role-nya tidak termasuk daftar, hasilnya `abort(403)`. Contoh di `routes/web.php`:

```php
Route::inertia('/account', 'account/index')->middleware(['auth', 'role:customer'])->name('account');
Route::inertia('/dashboard', 'dashboard/index')->middleware(['auth', 'role:admin,sales'])->name('dashboard');
```

Keputusan desain: `/account` dan `/dashboard` memakai `auth` + `role:` tanpa `verified`, agar alur registrasi lalu kembali ke checkout tidak terhambat verifikasi email selama `MAIL_MAILER=log`. Verifikasi email Fortify tetap aktif dan route pengaturan tetap memakai `verified`.

### Penunjang

- `EnsureUserIsActive`: lihat bagian Autentikasi & Session.
- `User::isStaff()` dan `Role::isStaff()` menentukan staf (admin atau sales). Sisi klien memakai `isStaff()` di `resources/js/lib/roles.ts` dan `navItemsFor()` di `resources/js/lib/dashboard-nav.ts` untuk memfilter menu sidebar per role.
- Policies untuk kepemilikan data (customer hanya melihat order miliknya, termasuk file bukti transfer) _(Direncanakan — Fase 2+)_.

## Database

### Tabel `users` (Fase 1, sesuai implementasi)

| Kolom                                                                       | Tipe                             | Keterangan                                                                       |
| --------------------------------------------------------------------------- | -------------------------------- | -------------------------------------------------------------------------------- |
| `id`                                                                        | bigint                           | PK                                                                               |
| `name`                                                                      | string                           |                                                                                  |
| `email`                                                                     | string                           | unik                                                                             |
| `phone`                                                                     | string(20), nullable             | ditambah migrasi `2026_11_21_000000_add_role_phone_is_active_to_users_table.php` |
| `role`                                                                      | enum(`admin`,`sales`,`customer`) | default `customer`                                                               |
| `is_active`                                                                 | boolean                          | default `true`                                                                   |
| `email_verified_at`                                                         | timestamp, nullable              |                                                                                  |
| `password`                                                                  | string                           | di-hash (cast `hashed`)                                                          |
| `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at` | kolom 2FA Fortify                |                                                                                  |
| `remember_token`, `created_at`, `updated_at`                                |                                  |                                                                                  |

`$fillable` model `User` hanya `name`, `email`, `password`, `phone` (atribut `#[Fillable]`). `role` dan `is_active` tidak pernah mass-assignable; nilai default diatur lewat `$attributes` (`role = customer`, `is_active = true`) dan seeder memakai `forceFill`. Tabel bawaan lain: `sessions`, `cache`, `jobs`, `password_reset_tokens`, dan `passkeys`.

### Tabel lain dari spesifikasi

Semua tabel di bawah belum dibuat di Fase 1. ERD lengkap ada di [FLOWCHART.md](FLOWCHART.md#erd).

| Tabel                    | Kolom penting                                                                                                                                                                                                                                                                                                 | Fase         |
| ------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------ |
| `brands`                 | name, slug, logo_path                                                                                                                                                                                                                                                                                         | _(Fase 2)_   |
| `categories`             | name, slug (SUV, Sedan, MPV, Hatchback, EV, Pickup)                                                                                                                                                                                                                                                           | _(Fase 2)_   |
| `showrooms`              | name, city, address, phone, lat, lng                                                                                                                                                                                                                                                                          | _(Fase 2)_   |
| `cars`                   | brand_id, category_id, name, slug, year, base_price (bigint rupiah), fuel_type, transmission, engine, power_hp, torque_nm, accel_0_100, top_speed, range_km?, seats, mileage_km, condition (new/used), stock, badge?, status (draft/published/sold_out), description, hotspots JSON, is_featured, softDeletes | _(Fase 2)_   |
| `car_images`             | car_id, path, sort_order, is_cover                                                                                                                                                                                                                                                                            | _(Fase 2)_   |
| `car_colors`             | car_id, name, hex, price_delta, image_path                                                                                                                                                                                                                                                                    | _(Fase 2)_   |
| `car_options`            | car_id, group (wheels/interior/package), name, description, price, image_path                                                                                                                                                                                                                                 | _(Fase 2)_   |
| `configurations`         | user_id?, car_id, car_color_id, total_price                                                                                                                                                                                                                                                                   | _(Fase 4)_   |
| `configuration_option`   | configuration_id, car_option_id, price (snapshot)                                                                                                                                                                                                                                                             | _(Fase 4)_   |
| `wishlists`              | user_id, car_id (pasangan unik)                                                                                                                                                                                                                                                                               | _(Fase 4)_   |
| `orders`                 | code (mis. `PM-2611-0001`), user_id, configuration_id, snapshot nama mobil/warna, subtotal, booking_fee, payment_scheme (cash/credit), dp_amount?, tenor_months?, monthly_installment?, delivery_method (pickup/delivery), showroom_id, delivery_address?, status, payment_due_at, sales_id?, notes           | _(Fase 4)_   |
| `order_status_histories` | order_id, status, note, changed_by                                                                                                                                                                                                                                                                            | _(Fase 4/5)_ |
| `payments`               | order_id, type (booking_fee/remaining), amount, bank_destination, sender_name, sender_bank, transfer_date, proof_path (jpg/png/pdf, maks 2 MB), status (submitted/approved/rejected), verified_by?, verified_at?, rejection_note?                                                                             | _(Fase 4/5)_ |
| `test_drives`            | user_id, car_id, showroom_id, scheduled_at, status (requested/confirmed/completed/cancelled), sales_id?, notes                                                                                                                                                                                                | _(Fase 5)_   |
| `inquiries`              | name, email, phone, service, message, status (new/replied), handled_by?                                                                                                                                                                                                                                       | _(Fase 3)_   |
| `ai_chat_logs`           | user_id?, ip_hash, session_id_hash, question, reply_excerpt, recommended_car_ids JSON, prompt_tokens, completion_tokens, status, block_reason?, created_at                                                                                                                                                    | _(Fase 6)_   |
| `settings`               | key, value (mis. `ai_advisor_enabled`, `ai_daily_token_budget`)                                                                                                                                                                                                                                               | _(Fase 6)_   |

## Workflow Bisnis & State Machine

Ringkasan per role (detail visual di [FLOWCHART.md](FLOWCHART.md)). Semua alur di bawah adalah rencana kecuali login dan redirect per role.

- **Guest ke customer** ([diagram](FLOWCHART.md#alur-guest--customer)) _(Direncanakan — Fase 3/4)_: menjelajah katalog, filter, compare; configure mobil (disimpan di session); checkout memaksa login/registrasi lalu kembali ke checkout; isi kontak, skema bayar (cash atau kredit dengan DP dan tenor), pickup atau delivery; order dibuat dengan status `pending_payment`; transfer booking fee lalu upload bukti, status menjadi `waiting_verification`; setelah sales approve menjadi `booking_paid`, bila ditolak customer upload ulang; customer memantau timeline di `/account`.
- **Sales** ([diagram](FLOWCHART.md#verifikasi-pembayaran--proses-order-sales)) _(Direncanakan — Fase 5)_: antrean Verifikasi Pembayaran (approve/reject dengan alasan), assign order `booking_paid` ke diri sendiri, `processing`, `ready`, catat pelunasan manual, `completed`; kelola test drive dan balas inquiry.
- **Admin** ([diagram](FLOWCHART.md#alur-admin)) _(Direncanakan — Fase 2/5/6)_: semua yang bisa sales, ditambah master data mobil, user dan role, laporan, serta kontrol AI Advisor.
- **Test drive** ([diagram](FLOWCHART.md#booking-test-drive)) _(Direncanakan — Fase 5)_: customer memilih showroom dan slot, sales mengonfirmasi, menjadwal ulang, atau menyelesaikan.
- **Status order** ([diagram](FLOWCHART.md#state-diagram-status-order)) _(Direncanakan — Fase 4/5)_: `pending_payment` ke `waiting_verification` ke `booking_paid` ke `processing` ke `ready` ke `completed`. Reject pembayaran mengembalikan ke `pending_payment`. Transisi hanya lewat `OrderStatusService` yang menulis `order_status_histories`. Edge `cancelled` pada diagram masih draf dan difinalisasi di Fase 4/5.
- **Status pembayaran** ([diagram](FLOWCHART.md#state-diagram-status-pembayaran)) _(Direncanakan — Fase 4/5)_: `submitted` menjadi `approved` atau `rejected`.
- **Autentikasi** ([diagram](FLOWCHART.md#sequence-autentikasi--session)): sudah berjalan di Fase 1.

## Daftar Route

Sumber: `php artisan route:list --except-vendor` (route aplikasi) dan route Fortify/Passkeys. Kolom Middleware tidak menyebut grup `web` yang berlaku untuk semua.

### Route aplikasi

| Method   | URL                              | Name                                    | Middleware                            | Role                                               |
| -------- | -------------------------------- | --------------------------------------- | ------------------------------------- | -------------------------------------------------- |
| GET      | `/`                              | `home`                                  | -                                     | Publik                                             |
| GET      | `/home`                          | `role-home`                             | `auth`                                | Semua role login (redirect sesuai role)            |
| GET      | `/account`                       | `account`                               | `auth`, `role:customer`               | customer                                           |
| GET      | `/dashboard`                     | `dashboard`                             | `auth`, `role:admin,sales`            | admin, sales                                       |
| ANY      | `/settings`                      | -                                       | `auth`                                | Semua role login (redirect ke `/settings/profile`) |
| GET      | `/settings/profile`              | `profile.edit`                          | `auth`                                | Semua role login                                   |
| PATCH    | `/settings/profile`              | `profile.update`                        | `auth`                                | Semua role login                                   |
| DELETE   | `/settings/profile`              | `profile.destroy`                       | `auth`, `verified`                    | Semua role login                                   |
| GET      | `/settings/security`             | `security.edit`                         | `auth`, `verified`, `RequirePassword` | Semua role login                                   |
| PUT      | `/settings/password`             | `user-password.update`                  | `auth`, `verified`, `throttle:6,1`    | Semua role login                                   |
| GET      | `/settings/appearance`           | `appearance.edit`                       | `auth`, `verified`                    | Semua role login                                   |
| GET      | `/.well-known/passkey-endpoints` | `well-known.passkeys`                   | -                                     | Publik                                             |
| GET      | `/up`                            | -                                       | -                                     | Publik (health check)                              |
| GET, PUT | `/storage/{path}`                | `storage.local`, `storage.local.upload` | -                                     | Publik (disk `local` bawaan Laravel)               |

Di lingkungan dev juga terdaftar `GET /_inertia/devtools/entries` dan `/_inertia/devtools/entries/{id}` dari paket Inertia DevTools.

### Route autentikasi (Fortify dan Passkeys)

| Method | URL                                         | Name                                   | Middleware                                          | Role             |
| ------ | ------------------------------------------- | -------------------------------------- | --------------------------------------------------- | ---------------- |
| GET    | `/login`                                    | `login`                                | `guest:web`                                         | Guest            |
| POST   | `/login`                                    | `login.store`                          | `guest:web`, `throttle:login`                       | Guest            |
| POST   | `/logout`                                   | `logout`                               | `auth:web`                                          | Semua role login |
| GET    | `/register`                                 | `register`                             | `guest:web`                                         | Guest            |
| POST   | `/register`                                 | `register.store`                       | `guest:web`                                         | Guest            |
| GET    | `/forgot-password`                          | `password.request`                     | `guest:web`                                         | Guest            |
| POST   | `/forgot-password`                          | `password.email`                       | `guest:web`                                         | Guest            |
| GET    | `/reset-password/{token}`                   | `password.reset`                       | `guest:web`                                         | Guest            |
| POST   | `/reset-password`                           | `password.update`                      | `guest:web`                                         | Guest            |
| GET    | `/email/verify`                             | `verification.notice`                  | `auth:web`                                          | Semua role login |
| GET    | `/email/verify/{id}/{hash}`                 | `verification.verify`                  | `auth:web`, `signed`, `throttle:6,1`                | Semua role login |
| POST   | `/email/verification-notification`          | `verification.send`                    | `auth:web`, `throttle:6,1`                          | Semua role login |
| GET    | `/user/confirm-password`                    | `password.confirm`                     | `auth:web`                                          | Semua role login |
| POST   | `/user/confirm-password`                    | `password.confirm.store`               | `auth:web`                                          | Semua role login |
| GET    | `/user/confirmed-password-status`           | `password.confirmation`                | `auth:web`                                          | Semua role login |
| GET    | `/two-factor-challenge`                     | `two-factor.login`                     | `guest:web`                                         | Guest            |
| POST   | `/two-factor-challenge`                     | `two-factor.login.store`               | `guest:web`, `throttle:two-factor`                  | Guest            |
| POST   | `/user/two-factor-authentication`           | `two-factor.enable`                    | `auth:web`, `password.confirm`                      | Semua role login |
| DELETE | `/user/two-factor-authentication`           | `two-factor.disable`                   | `auth:web`, `password.confirm`                      | Semua role login |
| POST   | `/user/confirmed-two-factor-authentication` | `two-factor.confirm`                   | `auth:web`, `password.confirm`                      | Semua role login |
| GET    | `/user/two-factor-qr-code`                  | `two-factor.qr-code`                   | `auth:web`, `password.confirm`                      | Semua role login |
| GET    | `/user/two-factor-secret-key`               | `two-factor.secret-key`                | `auth:web`, `password.confirm`                      | Semua role login |
| GET    | `/user/two-factor-recovery-codes`           | `two-factor.recovery-codes`            | `auth:web`, `password.confirm`                      | Semua role login |
| POST   | `/user/two-factor-recovery-codes`           | `two-factor.regenerate-recovery-codes` | `auth:web`, `password.confirm`                      | Semua role login |
| GET    | `/passkeys/login/options`                   | `passkey.login-options`                | `guest:web`, `throttle:passkeys`                    | Guest            |
| POST   | `/passkeys/login`                           | `passkey.login`                        | `guest:web`, `throttle:passkeys`                    | Guest            |
| GET    | `/passkeys/confirm/options`                 | `passkey.confirm-options`              | `auth:web`, `throttle:passkeys`                     | Semua role login |
| POST   | `/passkeys/confirm`                         | `passkey.confirm`                      | `auth:web`, `throttle:passkeys`                     | Semua role login |
| GET    | `/user/passkeys/options`                    | `passkey.registration-options`         | `auth:web`, `password.confirm`, `throttle:passkeys` | Semua role login |
| POST   | `/user/passkeys`                            | `passkey.store`                        | `auth:web`, `password.confirm`, `throttle:passkeys` | Semua role login |
| DELETE | `/user/passkeys/{passkey}`                  | `passkey.destroy`                      | `auth:web`, `password.confirm`                      | Semua role login |

Route fase berikutnya (katalog, checkout, order, dashboard CRUD, `POST /ai-advisor/messages`) belum ada _(Direncanakan — Fase 2 s.d. 6)_. Setelah mengubah route, jalankan `php artisan wayfinder:generate --with-form` agar fungsi route TypeScript ikut diperbarui.

## Fitur per Halaman & Daftar CRUD

### Halaman Fase 1 (sudah ada)

| Halaman                                                                                                                                  | Komponen          | Layout                          | Isi                                                                                                                                                                                                       |
| ---------------------------------------------------------------------------------------------------------------------------------------- | ----------------- | ------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `/`                                                                                                                                      | `public/home`     | `PublicLayout`                  | Halaman placeholder untuk peragaan sistem animasi: hero dengan headline reveal per baris, parallax, kata menyala saat scroll, section pinned horizontal (hanya desktop), reveal kartu, dengan data contoh |
| `/login`, `/register`, `/forgot-password`, `/reset-password/{token}`, `/email/verify`, `/two-factor-challenge`, `/user/confirm-password` | `auth/*`          | `AuthLayout`                    | Halaman auth starter kit, berbahasa Indonesia, tampil gelap                                                                                                                                               |
| `/account`                                                                                                                               | `account/index`   | `AccountLayout`                 | Placeholder area customer dengan header dan footer situs, tanpa Lenis                                                                                                                                     |
| `/dashboard`                                                                                                                             | `dashboard/index` | `DashboardLayout`               | Placeholder area staf, sidebar difilter per role, tanpa Lenis                                                                                                                                             |
| `/settings/profile`, `/settings/security`, `/settings/appearance`                                                                        | `settings/*`      | `RoleLayout` + `SettingsLayout` | Pengaturan starter kit untuk semua role. Label navigasi sudah berbahasa Indonesia, isi dalam halaman masih Inggris sampai Fase 7                                                                          |

Pembantu format uang: `App\Support\Rupiah` (PHP) dan `resources/js/lib/format.ts` (TypeScript) menghasilkan format yang identik: `Rp 485.000.000` dan bentuk singkat `Rp 9,8 jt` (juta, `jt`) atau `M` (miliar) dengan satu desimal pembulatan half-up memakai aritmetika bilangan bulat. Nilai di bawah 1 juta memakai format penuh; angka negatif diawali `-`. Nilai bisnis (booking fee `5_000_000`, batas bayar 24 jam, rekening bank dummy BCA/Mandiri/BNI, bunga flat 5%/tahun, opsi DP `[20, 25, 30, 40, 50]`, tenor `[12, 24, 36, 48, 60]`, dan `brand_name` "Pemecut Motors") ada di `config/dealer.php`.

### Daftar CRUD dan halaman rencana

| Area                                                                                                                            | Operasi                                                               | Fase                        |
| ------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------- | --------------------------- |
| Cars (+warna, opsi, gambar, hotspot), Brands, Categories, Showrooms, Users                                                      | Full CRUD oleh admin                                                  | _(Direncanakan — Fase 2)_   |
| Katalog `/cars`, Detail `/cars/{slug}`, Compare `/compare` (maks 3), Kontak `/contact` (peta Leaflet)                           | Baca; Inquiries: create oleh guest                                    | _(Direncanakan — Fase 3)_   |
| Configurator `/cars/{slug}/configure`, Checkout `/checkout`                                                                     | Create konfigurasi dan order oleh customer                            | _(Direncanakan — Fase 4)_   |
| Orders                                                                                                                          | Create oleh customer; baca, ubah status, batalkan oleh staf           | _(Direncanakan — Fase 4/5)_ |
| Payments                                                                                                                        | Upload dan upload ulang bukti oleh customer; approve/reject oleh staf | _(Direncanakan — Fase 4/5)_ |
| Wishlist                                                                                                                        | Tambah/hapus oleh customer                                            | _(Direncanakan — Fase 4)_   |
| Test drives                                                                                                                     | Create oleh customer; kelola oleh staf                                | _(Direncanakan — Fase 5)_   |
| Inquiries (kelola)                                                                                                              | Kelola dan balas oleh staf                                            | _(Direncanakan — Fase 5)_   |
| `/account` penuh: overview, orders, test drives, wishlist                                                                       | Baca milik sendiri                                                    | _(Direncanakan — Fase 4)_   |
| `/dashboard` penuh: KPI, grafik penjualan (Recharts), Verifikasi Pembayaran, Orders, Test drives, Inquiries, Customers, Reports | Operasional staf                                                      | _(Direncanakan — Fase 5)_   |
| Email notifikasi (order dibuat, bukti diterima/ditolak, status berubah, test drive dikonfirmasi)                                | Laravel Mail + Notifications                                          | _(Direncanakan — Fase 5)_   |

## Sistem Animasi

Berlaku untuk situs publik saja. Modul ada di `resources/js/lib/motion/`.

### API

| File             | Export                                                                                                                          | Fungsi                                                                                                                                             |
| ---------------- | ------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| `core.ts`        | `gsap`, `ScrollTrigger`, `SplitText`, `DESKTOP_QUERY` (`(min-width: 1024px)`), `REDUCED_MOTION_QUERY`, `prefersReducedMotion()` | Registrasi plugin GSAP dan konstanta bersama                                                                                                       |
| `lenis.ts`       | `startLenis()`, `stopLenis()`, `getLenis()`                                                                                     | Smooth scroll (`lerp: 0.1`), disinkronkan dengan ticker GSAP dan `ScrollTrigger.update()`. `startLenis()` mengembalikan `null` bila reduced motion |
| `reveal.ts`      | `revealFadeUp(targets, { y, stagger, start })`, `revealClip(target)`                                                            | Fade-up bertahap dan reveal gambar `clip-path`                                                                                                     |
| `split-lines.ts` | `splitLines(target, { stagger, start })`                                                                                        | Headline dipecah per baris (mask slide-up), kelas `split-line`                                                                                     |
| `scrub-words.ts` | `scrubWords(target)`                                                                                                            | Kata menyala mengikuti scroll (scrub), kelas `split-word`                                                                                          |
| `parallax.ts`    | `parallax(target, { distance })`                                                                                                | Parallax gambar terikat scroll (`distance` default 15)                                                                                             |
| `hooks.ts`       | `useLenis()`, `useGsapContext(setup, deps)`, `useReveal()`                                                                      | Hook React; lihat di bawah                                                                                                                         |

Hook:

- `useLenis()` memulai Lenis saat mount, mengembalikan scroll ke atas dan me-refresh ScrollTrigger pada setiap event Inertia `navigate`, serta menghentikan Lenis saat unmount. Hanya `PublicLayout` yang memanggilnya.
- `useGsapContext(setup, deps)` menjalankan `setup({ mm, reduced })` di dalam `gsap.context` yang dibatasi ref yang dikembalikan, dengan `gsap.matchMedia()` baru. Semua yang dibuat otomatis di-revert saat unmount atau saat `deps` berubah.
- `useReveal()` memberi efek reveal pada setiap elemen turunan ber-atribut `data-reveal`.

### Cara menambah animasi

1. Reveal sederhana: pasang ref dari `useReveal()` pada elemen pembungkus halaman, lalu beri atribut `data-reveal` pada elemen yang ingin dimunculkan (`data-reveal="clip"` untuk reveal gambar clip-path).
2. Animasi kustom: panggil `useGsapContext(({ mm, reduced }) => { ... })`, pasang ref yang dikembalikan pada elemen akar, lalu panggil `splitLines`, `scrubWords`, `parallax`, atau `revealFadeUp` dari dalam callback. Jangan membuat tween atau ScrollTrigger di luar context agar cleanup otomatis berlaku.
3. Pin atau scroll horizontal didaftarkan lewat `mm.add(...)` (lihat `resources/js/pages/public/home.tsx`).

### Aturan wajib

- **Reduced motion**: bila `prefers-reduced-motion: reduce`, Lenis mati dan setiap animasi menjadi fade sederhana (helper di atas sudah menangani ini; `splitLines` hanya fade, `scrubWords` dan `parallax` tidak melakukan apa pun).
- **Pin hanya pada layar >= 1024 px**: pin dan scroll horizontal didaftarkan dengan `DESKTOP_QUERY` (dan `prefers-reduced-motion: no-preference`) lewat `gsap.matchMedia()`; di bawah itu menjadi layout biasa (carousel swipe di Fase 3).
- **Cleanup Inertia**: Lenis hanya berjalan di `PublicLayout`, dan dihentikan saat layout itu unmount (mis. pindah ke halaman auth atau dashboard). Semua `gsap.context` di-revert saat halaman diganti, sehingga pin tidak terduplikasi saat kembali dan scroll mulai dari atas.
- `AccountLayout`, `DashboardLayout`, dan `AuthLayout` tidak pernah memulai Lenis.
- Efek tambahan di spesifikasi (cursor "Lihat", tombol magnetic, transisi halaman curtain wipe dengan Motion, count-up) _(Direncanakan — Fase 3)_.

## AI Car Advisor

_(Direncanakan — Fase 6)_ Belum ada kode di Fase 1. Ringkasan dari spesifikasi:

Chatbot rekomendasi mobil memakai OpenAI `gpt-4o-mini` lewat Laravel AI SDK, dengan jawaban hanya dari katalog database. Endpoint `POST /ai-advisor/messages`. Lapisan proteksi:

- Rate limit berlapis (`throttle:ai-advisor`): guest 5/menit dan 20/hari, user login 10/menit dan 60/hari, ditambah batas global harian; respons 429 dengan pesan ramah.
- Validasi FormRequest, input maksimum 500 karakter, riwayat maksimum 10 pesan.
- Kill switch dan budget token harian (tabel `settings`), cek lockout abuse.
- Cloudflare Turnstile untuk pesan pertama guest (opsional, fallback honeypot dan rate limit), `Cache::lock` satu request aktif per sesi.
- `AdvisorInputFilter`: normalisasi Unicode, allowlist karakter, denylist pola (HTML, SQL, kode, URL), deteksi prompt injection yang dihitung sebagai flag.
- OpenAI Moderation untuk input; 3 flag dalam 1 jam memicu lockout 24 jam.
- System prompt ketat, pesan user sebagai data di dalam delimiter, canary token, structured output `{topic, reply, car_ids}`, tanpa tools atau function calling.
- `topic = off_topic` dijawab penolakan baku.
- `AdvisorOutputSanitizer`: validasi skema ketat, hapus HTML/URL/code block, batas 1.200 karakter, cek canary; `car_ids` disaring ke mobil published.
- Frontend merender balasan sebagai teks biasa (tanpa `dangerouslySetInnerHTML`); Content-Security-Policy di produksi.
- Privasi: IP dan session di-hash, pertanyaan dipotong, log (`ai_chat_logs`) bisa dihapus admin; halaman admin berisi grafik, log, kill switch, dan budget.

Pipeline lengkap 14 gerbang ada di [FLOWCHART.md](FLOWCHART.md#pipeline-ai-advisor).

## Keamanan

### Sudah diterapkan (Fase 1)

| Kontrol              | Detail                                                                                                                                                                                                                                                                                 | Lokasi                                                                 |
| -------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------- |
| Security headers     | `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()`. Dipasang ke semua respons (halaman, redirect, error) lewat middleware global | `app/Http/Middleware/SecurityHeaders.php`, `bootstrap/app.php`         |
| Throttling Fortify   | Login 5 per menit per kombinasi email dan IP; 2FA 5 per menit per sesi login; passkeys 10 per menit. Verifikasi email dan ganti password `throttle:6,1`                                                                                                                                | `FortifyServiceProvider::configureRateLimiting`, `routes/settings.php` |
| Regenerasi session   | `session()->regenerate()` saat login (Fortify)                                                                                                                                                                                                                                         | `Laravel\Fortify\Actions\PrepareAuthenticatedSession`                  |
| Cookie session       | `http_only` = `true` (default `SESSION_HTTP_ONLY`), `same_site` = `lax` (default `SESSION_SAME_SITE`); `secure` mengikuti `SESSION_SECURE_COOKIE`                                                                                                                                      | `config/session.php`                                                   |
| Anti mass-assignment | `$fillable` eksplisit (`name`, `email`, `password`, `phone`); `role` dan `is_active` tidak pernah mass-assignable. Registrasi (`CreateNewUser`) hanya memakai `name`, `email`, `password` sehingga payload `role=admin` diabaikan                                                      | `app/Models/User.php`, `app/Actions/Fortify/CreateNewUser.php`         |
| Guard akun nonaktif  | Login ditolak dan session aktif diputus untuk `is_active = false`                                                                                                                                                                                                                      | `EnsureUserIsActive`, `FortifyServiceProvider`                         |
| Otorisasi role       | Middleware `role:` mengembalikan 403                                                                                                                                                                                                                                                   | `EnsureRole`                                                           |
| Anti-XSS             | React meng-escape output secara default. Satu-satunya `dangerouslySetInnerHTML` ada di `components/two-factor-setup-modal.tsx` untuk SVG kode QR 2FA dari server (bukan input pengguna)                                                                                                | `resources/js/`                                                        |
| Anti SQL injection   | Query lewat Eloquent dengan parameter binding                                                                                                                                                                                                                                          | seluruh `app/`                                                         |
| Rahasia              | `.env` di `.gitignore`; `.env.example` hanya berisi placeholder                                                                                                                                                                                                                        | `.gitignore`, `.env.example`                                           |

### Direncanakan

- Content-Security-Policy (`script-src 'self'` dan domain Turnstile) _(Fase 6)_.
- Aturan upload (`mimes`, ukuran maksimum 2 MB, nama file acak, bukti transfer di disk private dengan route ber-policy) _(Fase 4)_.
- Cloudflare Turnstile dan honeypot untuk form publik dan chatbot _(Fase 3/6)_.
- Policies kepemilikan data _(Fase 2+)_ dan feature test untuk setiap baris matriks hak akses.

### Wajib di produksi

- `APP_DEBUG=false`.
- `SESSION_SECURE_COOKIE=true` (cookie hanya lewat HTTPS).
- Hapus atau ganti akun demo, dan isi `OPENAI_API_KEY` hanya lewat environment hosting _(Fase 6)_.

## Testing

Test PHP memakai gaya kelas (`class XTest extends Tests\TestCase`, `use RefreshDatabase`, metode `test_*`) dan dijalankan oleh Pest. Test berjalan pada SQLite `:memory:` (`phpunit.xml`: `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `SESSION_DRIVER=array`, `CACHE_STORE=array`, `MAIL_MAILER=array`), jadi tidak menyentuh database `car_dealer`.

Perintah (awali dengan `export PATH="$HOME/.config/herd-lite/bin:$PATH"` untuk yang memakai PHP):

| Perintah                     | Fungsi                                                                                        |
| ---------------------------- | --------------------------------------------------------------------------------------------- |
| `php artisan test`           | Seluruh test PHP                                                                              |
| `npx vp test run`            | Test unit JS (`*.test.ts` di samping modulnya)                                                |
| `npm run build`              | Build produksi Vite+                                                                          |
| `npm run types:check`        | `tsc --noEmit`                                                                                |
| `npm run check`              | Lint dan format (`vp check`)                                                                  |
| `vendor/bin/pint --test`     | Cek gaya kode PHP                                                                             |
| `vendor/bin/phpstan analyse` | Analisis statis level 7                                                                       |
| `composer ci:check`          | Dipakai CI (`.github/workflows/tests.yml`): `npm run check`, `npm run types:check`, lalu test |

Cakupan test:

| File                                         | Mencakup                                                                                                                                                                                                                   |
| -------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `tests/Feature/Access/RoleAccessTest.php`    | Guest bisa lihat home; guest dilempar ke login dari area terproteksi; customer boleh `/account` tapi 403 di `/dashboard`; staf sebaliknya; customer belum terverifikasi tetap bisa `/account`; `/home` memilah per role    |
| `tests/Feature/Auth/RoleRedirectTest.php`    | Redirect login per role, `intended` untuk customer, staf mengabaikan `intended`, registrasi mengabaikan payload `role`, 2FA staf ke dashboard, respons JSON, pengguna login membuka halaman guest dikirim ke home role-nya |
| `tests/Feature/Auth/InactiveUserTest.php`    | Akun nonaktif tidak bisa login, password salah tetap error generik, akun dinonaktifkan di tengah session di-logout                                                                                                         |
| `tests/Feature/Auth/*` lainnya               | Login, logout, rate limit, registrasi, reset password, verifikasi email, konfirmasi password, tantangan 2FA                                                                                                                |
| `tests/Feature/Users/UserRoleTest.php`       | Default customer aktif, `role`/`is_active` tidak mass-assignable, `phone` boleh, hanya admin dan sales yang staf                                                                                                           |
| `tests/Feature/Users/DemoUserSeederTest.php` | Seeder akun demo idempoten                                                                                                                                                                                                 |
| `tests/Feature/SecurityHeadersTest.php`      | Empat header ada di halaman, redirect, dan error                                                                                                                                                                           |
| `tests/Feature/SurfaceTest.php`              | Halaman publik, auth, dan customer memakai `.surface-public`; halaman staf terang                                                                                                                                          |
| `tests/Feature/SettingsShellTest.php`        | Pengaturan profil terbuka untuk semua role                                                                                                                                                                                 |
| `tests/Feature/SharedPropsTest.php`          | Props `auth.user` memuat role tanpa rahasia                                                                                                                                                                                |
| `tests/Feature/DealerConfigTest.php`         | `config/dealer.php` sesuai spesifikasi; `brand_name` dibagikan ke Inertia                                                                                                                                                  |
| `tests/Feature/Settings/*`                   | Profil dan keamanan (update, hapus akun, ganti password)                                                                                                                                                                   |
| `tests/Unit/Support/RupiahTest.php`          | Format dan bentuk singkat Rupiah                                                                                                                                                                                           |
| `resources/js/lib/format.test.ts`            | Padanan TypeScript format Rupiah                                                                                                                                                                                           |
| `resources/js/lib/dashboard-nav.test.ts`     | Filter menu dashboard per role                                                                                                                                                                                             |

Test yang direncanakan (akses per baris matriks, `PricingService`, `CreditSimulator`, transisi status, upload bukti, dataset `AdvisorInputFilter`, dll.) _(Direncanakan — Fase 2 s.d. 6)_.

## Deploy

_(Direncanakan — Fase 8, bonus)_ Ringkasan dari spesifikasi: Vercel bersifat serverless (filesystem tidak permanen, tidak ada MySQL bawaan).

- **App**: panduan resmi Vercel "Deploy Laravel on Vercel with Docker" (container function + FrankenPHP, image `php8.5`); fallback runtime komunitas `vercel-php@0.9.0`.
- **MySQL**: TiDB Cloud Starter (kompatibel MySQL, wajib TLS) atau Railway MySQL. Saat demo ke dosen tetap memakai XAMPP dan phpMyAdmin lokal.
- **Upload foto dan bukti transfer**: Vercel Blob atau Cloudinary lewat penggantian disk di env; bukti transfer tetap di bucket private. Upload selalu lewat `Storage::disk(config('filesystems.default'))`.
- **Cache bersama** untuk rate limiter dan lock AI Advisor: `CACHE_STORE=database`.
- **Env produksi**: `SESSION_DRIVER=database`, `CACHE_STORE=database`, `LOG_CHANNEL=stderr`, `QUEUE_CONNECTION=sync`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`.
- Fallback paling sederhana: Railway (app dan MySQL satu project).

## Troubleshooting

| Masalah                                                                                     | Penyebab dan solusi                                                                                                                                                                                                                                                                                                                              |
| ------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `php -v` menampilkan 8.2 (XAMPP)                                                            | `C:\xampp\php` masih ada di System PATH dan menang atas PHP 8.5. Hapus dari PATH (lihat Setup Lokal), buka terminal baru. Sementara itu gunakan prefix PATH herd-lite                                                                                                                                                                            |
| `composer` crash di PHP 8.5                                                                 | Composer dari ComposerSetup di PATH tidak kompatibel. Awali perintah dengan `export PATH="$HOME/.config/herd-lite/bin:$PATH"` (PowerShell: `$env:Path = "$env:USERPROFILE\.config\herd-lite\bin;$env:Path"`). Berlaku juga untuk `npm run build`, `npm run dev`, dan `composer run dev` karena plugin Wayfinder memanggil `php artisan` |
| Error karena `intl` tidak ada (`NumberFormatter`, `Number::format()`, `Number::currency()`) | PHP 8.5.13 di setup ini tanpa ekstensi `intl`. Itulah alasan `App\Support\Rupiah` dibuat; jangan memakai API Laravel yang membutuhkan `intl`                                                                                                                                                                                                     |
| Port bentrok                                                                                | `composer run dev` memakai port 8000, sedangkan Apache XAMPP di port 80. Buka `http://localhost:8000` untuk aplikasi dan `http://localhost/phpmyadmin` untuk phpMyAdmin                                                                                                                                                                          |
| Gambar upload tidak tampil                                                                  | Jalankan `php artisan storage:link` agar `public/storage` mengarah ke `storage/app/public` _(relevan mulai Fase 2)_                                                                                                                                                                                                                              |
| Fungsi route TypeScript tidak ditemukan atau usang                                          | Jalankan `php artisan wayfinder:generate --with-form` setelah mengubah route. Hasilnya (`resources/js/{routes,actions,wayfinder}`) tidak di-commit                                                                                                                                                                                               |
| `SQLSTATE[HY000] [2002]` atau koneksi database ditolak                                      | MySQL belum jalan. Start MySQL di XAMPP Control Panel dan pastikan database `car_dealer` ada                                                                                                                                                                                                                                                     |
| Test gagal karena database                                                                  | Test memakai SQLite `:memory:` lewat `phpunit.xml`, bukan MySQL. Jangan mengarahkan `.env` ke SQLite                                                                                                                                                                                                                                             |
