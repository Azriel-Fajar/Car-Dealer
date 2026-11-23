# Fase 1 — Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn the Laravel 13 React starter kit into the Pemecut Motors foundation: three roles with session auth and role-aware redirects, a route skeleton with placeholder pages in three layouts, a locked design system, the `lib/motion` module, money formatting, security headers, and draft documentation.

**Architecture:** Laravel + Fortify handles session auth. A `role` column (PHP enum) is enforced by an `EnsureRole` middleware and by a single Fortify response class that redirects by role. Inertia + React render pages under `public/`, `account/`, `dashboard/`, `auth/` and `settings/`, and `app.tsx` maps each prefix to its layout. Public, auth and customer surfaces always use a scoped dark token set (`.surface-public`). The dashboard uses the light neutral `:root` tokens. Only PublicLayout runs Lenis and the GSAP motion helpers.

**Tech Stack:** Laravel 13.43 / PHP 8.5.13 (no intl), Fortify (passkeys + 2FA), Inertia 3, React 19, TypeScript, Tailwind 4, shadcn/ui, Wayfinder, Pest (class-style tests), Vite+ (`vp`), gsap 3.15, lenis 1.3, motion 14, @fontsource-variable fonts, MariaDB 10.4 (XAMPP) for dev, SQLite in-memory for tests.

**Spec:** `docs/superpowers/specs/2026-11-20-car-dealer-design.md` (Fase 1 row of "Fase implementasi", plus Arsitektur, Role & hak akses, Halaman & desain, Sistem animasi, Keamanan aplikasi, Dokumentasi & flowchart).

## Global Constraints

- NEVER run `git commit` or `git push`, and never modify `.git`. The user commits manually. This plan has no commit steps; the controller snapshots progress itself.
- Shell is Windows with Git Bash or PowerShell. Prefix every bash command that runs php, composer or artisan with `export PATH="/c/Users/afw14/.config/herd-lite/bin:$PATH"`, because the ComposerSetup composer on PATH crashes on PHP 8.5. This includes `npm run build`, `npm run dev` and `composer run dev`, since the Wayfinder Vite plugin calls `php artisan wayfinder:generate`. In PowerShell use `$env:Path = "$env:USERPROFILE\.config\herd-lite\bin;$env:Path"`.
- PHP 8.5.13 has no `intl` extension. Never use `NumberFormatter`, `Number::format()`, `Number::currency()` or anything else that needs intl.
- Dev DB is MariaDB 10.4 via XAMPP, DB `car_dealer`. `.env` is already configured (SESSION_DRIVER=database, QUEUE_CONNECTION=sync, CACHE_STORE=database, MAIL_MAILER=log); do not change its DB/session settings. Tests use `phpunit.xml`: `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `SESSION_DRIVER=array`, `CACHE_STORE=array`, `MAIL_MAILER=array`. Every migration must run on both SQLite and MariaDB.
- PHP tests follow the existing class style: `namespace Tests\Feature\...`, `class XTest extends Tests\TestCase`, `use RefreshDatabase`, and `public function test_*(): void` methods. The Pest runner executes them. Pure unit tests may extend `PHPUnit\Framework\TestCase`.
- JS unit tests are `*.test.ts` files next to their module. They import from `vite-plus/test` and run with `npx vp test run <file>`. A tested module must not import from `@/routes`; type-only `@/types` imports are fine.
- Verification commands must all be green at the end of every task: `php artisan test`, `npm run build`, `npm run types:check`, `npm run check`, `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` (level 7; CI's `composer ci:check` runs these).
- After any route change, run `php artisan wayfinder:generate --with-form`. The generated `resources/js/{routes,actions,wayfinder}` are gitignored.
- UI copy is Indonesian; code and identifiers are English. The brand name is "Pemecut Motors". Money is shown as `Rp 485.000.000`, and monthly amounts as `Rp 9,8 jt / bln`.
- Layouts must be fully responsive at 375, 768, 1280 and 1920 px with no horizontal page scroll (`document.documentElement.scrollWidth <= window.innerWidth`).
- `prefers-reduced-motion: reduce` turns Lenis off and reduces every animation to a simple fade.
- Lenis runs only in PublicLayout. AccountLayout, DashboardLayout and AuthLayout never start it.
- Starter-kit settings pages (profile, password, 2FA, passkeys) keep working for every role. Existing tests change only where this plan changes a redirect target.
- `$fillable` stays explicit. `role` and `is_active` are never mass-assignable.
- No new Composer packages. The only new npm packages are `gsap@^3.15.1`, `lenis@^1.3.27`, `motion@^14.14.0`, `@fontsource-variable/big-shoulders-display@^5.3.0`, `@fontsource-variable/geist@^5.3.0` and `@fontsource-variable/geist-mono@^5.3.0`.
- Browser checks use the Claude Browser pane at `http://localhost:8000` with `composer run dev`. They need XAMPP MySQL running and the Task 1 `php artisan migrate` and `php artisan db:seed`. If MySQL is down, report the browser steps as BLOCKED. Never point `.env` at SQLite.

## Review Focus

1. **Staff login after a guest-side intended URL.** For example, a guest hits `/account` (later `/checkout`) and then logs in as admin or sales. They must land on `/dashboard`, never on a 403. Test owner: Task 2.
2. **Registration POST carrying `role=admin` or `is_active=0`.** The new user must be an active customer, with no privilege escalation through mass assignment. Test owner: Tasks 1 and 2.
3. **Account deactivated while a session, remember-me cookie or passkey login is alive.** The next request must log the user out with the Indonesian inactive message. Test owner: Task 2.
4. **Already-authenticated user opening `/login`, `/register` or `/email/verify`.** They must land on their own role home, not the starter kit's `/dashboard`, which would 403 a customer. Test owner: Task 2.
5. **Inertia navigation public → auth or dashboard → public.** Lenis must be torn down outside PublicLayout, pins must not be duplicated on return, and scroll must start at the top. Check owner: Task 7 (scripted Browser check).

## Rulings (spec silent or in tension)

- **No `verified` on `/account` and `/dashboard`.** These routes use `auth` + `role:` without `verified`, so the spec's flow (register → back to the intended checkout) is not blocked by email verification while MAIL_MAILER=log. Fortify email verification stays enabled, and the settings routes keep `verified`.
- **Staff ignore `url.intended`.** They always go to `/dashboard`, as the spec says. The customer-only intended URL is dropped from their session.
- **`GET /home` (`role-home`) dispatches by role.** It is used as `fortify.home` and as `redirectUsersTo`. This covers Fortify's static redirects (email verification, password-confirmation fallback, the `guest` middleware, the passkey default), which would otherwise send customers to `/dashboard` and a 403.
- **Appearance default is now `light` instead of `system`.** The spec fixes the dashboard as light neutral; the appearance toggle stays. Public, auth and customer (account plus customer settings) surfaces are always dark through `.surface-public`, whatever the appearance setting.
- **AccountLayout uses the public dark surface.** It shares the site header and footer but does not run Lenis. Settings pages render inside AccountLayout for customers and DashboardLayout for staff (`RoleLayout`). The "Tampilan" settings item is hidden for customers.
- **Translation scope.** Auth pages and settings nav labels are translated now. The inner copy of the starter-kit settings pages stays English until the Fase 7 polish.
- **`config/dealer.php` values the spec does not fix.** Flat interest is 5%/year (`0.05`). DP options are `[20, 25, 30, 40, 50]`. Tenor options are `[12, 24, 36, 48, 60]`. The bank accounts are dummy BCA, Mandiri and BNI accounts. `brand_name` is a literal and becomes the Inertia `name` prop.
- **Short Rupiah form.** Amounts of at least 1 juta use `jt` and amounts of at least 1 miliar use `M`. Values have 1 decimal, rounded half-up with integer arithmetic (identical in PHP and TS), and `,0` is dropped. Amounts under 1 juta fall back to the full format. Negatives are prefixed with `-`.
- **Security header values.** `X-Frame-Options: SAMEORIGIN` (Inertia devtools may frame same-origin), `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()`. `publickey-credentials-*` keeps its default so passkeys work. CSP is deferred to Fase 6, as the spec places it.
- **Fonts.** Big Shoulders Display (display), Geist (body) and Geist Mono (spec labels), all from `@fontsource-variable`. The Bunny font plugin and the `@fonts` directive are removed.
- **Order-cancel edges in the state diagram.** The draft shows cancel from every non-terminal state. This is marked as a draft, to be finalized in Fase 4/5.

---

## File Structure

| Area | Files |
|---|---|
| Roles & users | `app/Enums/Role.php`, `app/Models/User.php`, `database/migrations/2026_11_21_000000_add_role_phone_is_active_to_users_table.php`, `database/factories/UserFactory.php`, `database/seeders/{DemoUserSeeder,DatabaseSeeder}.php` |
| HTTP layer | `app/Http/Middleware/{EnsureRole,EnsureUserIsActive,SecurityHeaders}.php`, `app/Http/Responses/RoleRedirectResponse.php`, `app/Http/Controllers/RoleHomeController.php`, `bootstrap/app.php`, `app/Providers/FortifyServiceProvider.php`, `config/fortify.php`, `routes/web.php` |
| Money & config | `config/dealer.php`, `app/Support/Rupiah.php`, `resources/js/lib/format.ts` |
| Design system | `resources/css/app.css`, `resources/views/app.blade.php`, `vite.config.ts`, `resources/js/hooks/use-public-surface.ts`, appearance defaults |
| Layouts | `resources/js/layouts/{public-layout,account-layout,dashboard-layout,role-layout}.tsx`, `resources/js/layouts/auth/auth-simple-layout.tsx`, `resources/js/components/public/*`, `resources/js/components/app-sidebar.tsx`, `resources/js/lib/{roles,dashboard-nav}.ts`, `resources/js/app.tsx` |
| Pages | `resources/js/pages/public/home.tsx`, `resources/js/pages/account/index.tsx`, `resources/js/pages/dashboard/index.tsx` (`welcome.tsx` and `dashboard.tsx` are deleted) |
| Motion | `resources/js/lib/motion/{core,lenis,reveal,split-lines,parallax,scrub-words,hooks}.ts` |
| Docs | `docs/DOKUMENTASI.md`, `docs/FLOWCHART.md` |

---

### Task 1: Roles, user columns & demo accounts

**Suggested implementer model:** sonnet

**Files:**
- Create: `app/Enums/Role.php`
- Create: `database/migrations/2026_11_21_000000_add_role_phone_is_active_to_users_table.php`
- Modify: `app/Models/User.php`
- Modify: `database/factories/UserFactory.php`
- Create: `database/seeders/DemoUserSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php` (replace the "Test User" factory call with `$this->call(DemoUserSeeder::class)`)
- Test: `tests/Feature/Users/UserRoleTest.php`, `tests/Feature/Users/DemoUserSeederTest.php`

**Interfaces:**
- Consumes: none.
- Produces:
  - `enum App\Enums\Role: string` with cases `Admin = 'admin'`, `Sales = 'sales'`, `Customer = 'customer'`. `label(): string` returns `'Admin'`, `'Sales'` or `'Pelanggan'`. `isStaff(): bool` is true for Admin and Sales.
  - New `users` columns: `phone` (string 20, nullable, after `email`), `role` (enum of `Role` values, default `'customer'`, after `phone`), `is_active` (boolean, default true, after `role`).
  - `User`:
    - `#[Fillable(['name', 'email', 'password', 'phone'])]`
    - casts `'role' => Role::class` and `'is_active' => 'boolean'`
    - `protected $attributes = ['role' => 'customer', 'is_active' => true]`, so the in-memory model matches the DB defaults right after `create()`
    - `isStaff(): bool`
    - phpdoc `@property Role $role`, `@property string|null $phone`, `@property bool $is_active`
  - `UserFactory` definition adds `'role' => Role::Customer`, `'is_active' => true` and `'phone' => null`. It gets four states returning `static`: `admin()`, `sales()`, `customer()` and `inactive()`.
  - `DemoUserSeeder`. It is idempotent: for each email it calls `User::query()->firstOrNew(['email' => …])->forceFill([...])->save()`. It does not use factories or faker, because production installs without dev deps. Each account gets password `'password'`, `email_verified_at = now()` and `is_active = true`:

    | name | email | role | phone |
    |---|---|---|---|
    | Admin Pemecut | admin@pemecut.test | admin | 081200000001 |
    | Sales Pemecut | sales@pemecut.test | sales | 081200000002 |
    | Customer Demo | customer@pemecut.test | customer | 081200000003 |

- [ ] **Step 1: Write the failing tests**

```php
// tests/Feature/Users/UserRoleTest.php
public function test_users_created_without_role_default_to_active_customer(): void
{
    $user = User::create(['name' => 'Budi', 'email' => 'budi@example.com', 'password' => 'password']);
    $this->assertSame(Role::Customer, $user->role);          // before refresh
    $this->assertTrue($user->is_active);
    $this->assertSame(Role::Customer, $user->fresh()->role); // DB default
    $this->assertTrue($user->fresh()->is_active);
}

public function test_role_and_is_active_are_not_mass_assignable(): void
{
    $user = User::create(['name' => 'X', 'email' => 'x@example.com', 'password' => 'password',
        'role' => 'admin', 'is_active' => false]);
    $this->assertSame(Role::Customer, $user->fresh()->role);
    $this->assertTrue($user->fresh()->is_active);
}

public function test_phone_is_mass_assignable(): void
{
    $user = User::create(['name' => 'Y', 'email' => 'y@example.com', 'password' => 'password', 'phone' => '081234567890']);
    $this->assertSame('081234567890', $user->fresh()->phone);
}

public function test_only_admin_and_sales_are_staff(): void
{
    $this->assertTrue(User::factory()->admin()->create()->isStaff());
    $this->assertTrue(User::factory()->sales()->create()->isStaff());
    $this->assertFalse(User::factory()->customer()->create()->isStaff());
    $this->assertFalse(User::factory()->inactive()->create()->is_active);
    $this->assertSame('Pelanggan', Role::Customer->label());
}

// tests/Feature/Users/DemoUserSeederTest.php
public function test_demo_accounts_are_seeded_idempotently(): void
{
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);
    $this->assertSame(3, User::count());
    foreach (['admin@pemecut.test' => Role::Admin, 'sales@pemecut.test' => Role::Sales,
              'customer@pemecut.test' => Role::Customer] as $email => $role) {
        $user = User::where('email', $email)->firstOrFail();
        $this->assertSame($role, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('password', $user->password));
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test tests/Feature/Users`
Expected: FAIL (`Class "App\Enums\Role" not found`).

- [ ] **Step 3: Implement the `Role` enum, the migration, `User` changes, the factory states and `DemoUserSeeder`, as specified in Interfaces**

The migration builds the enum values with `array_column(Role::cases(), 'value')`. Its `down()` drops `phone`, `role` and `is_active`.

- [ ] **Step 4: Run the tests to verify they pass, then run the full suite**

Run: `php artisan test`
Expected: all PASS. Existing tests still pass because the factory default is customer and `/dashboard` is not role-gated yet.

- [ ] **Step 5: Apply to the dev DB**

Run: `php artisan migrate`, then `php artisan db:seed`.
Expected: the migration runs on MariaDB without error, and `php artisan tinker --execute="dump(App\Models\User::whereIn('email',['admin@pemecut.test','sales@pemecut.test','customer@pemecut.test'])->count());"` prints `3`.

- [ ] **Step 6: Run the global verification commands (Global Constraints)**

Expected: all green.

---

### Task 2: Access control, role-aware redirects, security headers & route skeleton

**Suggested implementer model:** sonnet

**Files:**
- Create: `app/Http/Middleware/EnsureRole.php`, `app/Http/Middleware/EnsureUserIsActive.php`, `app/Http/Middleware/SecurityHeaders.php`
- Create: `app/Http/Responses/RoleRedirectResponse.php`, `app/Http/Controllers/RoleHomeController.php`
- Modify: `bootstrap/app.php`, `app/Providers/FortifyServiceProvider.php`, `config/fortify.php` (`'home' => '/home'`), `routes/web.php`, `app/Models/User.php` (add `homePath()`)
- Create (placeholders, restyled in Tasks 5–7): `resources/js/pages/public/home.tsx`, `resources/js/pages/account/index.tsx`, `resources/js/pages/dashboard/index.tsx` (content moved from `pages/dashboard.tsx`, breadcrumb title `'Ringkasan'`)
- Delete: `resources/js/pages/welcome.tsx`, `resources/js/pages/dashboard.tsx`
- Modify: `resources/js/app.tsx`. Replace `case name === 'welcome': return null;` with `case name.startsWith('public/'): return null;`. Other pages keep `AppLayout` for now.
- Test: create `tests/Feature/Access/RoleAccessTest.php`, `tests/Feature/Auth/RoleRedirectTest.php`, `tests/Feature/Auth/InactiveUserTest.php` and `tests/Feature/SecurityHeadersTest.php`. Modify the existing tests listed in Step 3.

**Interfaces:**
- Consumes (Task 1): `Role`, `User::isStaff()`, factory states `admin()`, `sales()`, `customer()`, `inactive()`, `withTwoFactor()` and `unverified()`.
- Produces:
  - Routes:
    - `home`: GET `/`, `Route::inertia('/', 'public/home')`.
    - `role-home`: GET `/home`, `RoleHomeController`, middleware `auth`.
    - `account`: GET `/account`, Inertia `account/index`, middleware `['auth', 'role:customer']`.
    - `dashboard`: GET `/dashboard`, Inertia `dashboard/index`, middleware `['auth', 'role:admin,sales']`.
  - Wayfinder (after regenerating): `home()`, `account()` and `dashboard()` from `@/routes`.
  - `User::homePath(): string` returns `route('dashboard', absolute: false)` for staff and `route('account', absolute: false)` otherwise.
  - `EnsureRole` is aliased `role`. Its signature is `handle(Request $request, Closure $next, string ...$roles): Response`. It returns 403 unless the user's `role->value` is in `$roles`.
  - `EnsureUserIsActive` is appended to the `web` group. It has `public const MESSAGE = 'Akun Anda telah dinonaktifkan. Silakan hubungi admin dealer.'`. For an authenticated inactive user it logs out the `web` guard, invalidates the session and regenerates the token. JSON requests get `abort(403, MESSAGE)`. All other requests get `redirect()->route('login')->withErrors(['email' => MESSAGE])`.
  - `SecurityHeaders` is appended globally (`$middleware->append`). It has `public const HEADERS` (array<string,string>) holding the four values from the Rulings and sets each of them on every response.
  - `RoleRedirectResponse` implements `Laravel\Fortify\Contracts\{LoginResponse, RegisterResponse, TwoFactorLoginResponse}` and `Laravel\Passkeys\Contracts\PasskeyLoginResponse`. All four are bound as singletons in `FortifyServiceProvider::register()`. Behaviour:
    - Staff: forget `url.intended` and target `route('dashboard')`.
    - Customer: target `redirect()->intended(route('account'))->getTargetUrl()`.
    - `wantsJson()` returns `JsonResponse(['two_factor' => false, 'redirect' => $target])`, which `passkey-verify.tsx` reads as `response.redirect`. Otherwise it returns `redirect($target)`.
  - `RoleHomeController::__invoke(Request $request): RedirectResponse` redirects to `$user->homePath()`.
  - `bootstrap/app.php` adds `$middleware->alias(['role' => EnsureRole::class])` and `$middleware->redirectUsersTo('/home')`.
  - `FortifyServiceProvider` gets a new private `configureAuthentication()`, called from `boot()`. It uses `Fortify::authenticateUsing()`: find the user by `Fortify::username()`; return null unless `Hash::check` passes; if the user is not active, throw `ValidationException::withMessages([Fortify::username() => EnsureUserIsActive::MESSAGE])`; otherwise return the user. The inactive state is revealed only after a correct password.

- [ ] **Step 1: Write the failing tests**

```php
// tests/Feature/Access/RoleAccessTest.php   (use Inertia\Testing\AssertableInertia as Assert)
public function test_guests_can_view_home(): void
{ $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('public/home')); }

public function test_guests_are_redirected_to_login_from_protected_areas(): void
{ $this->get(route('account'))->assertRedirect(route('login'));
  $this->get(route('dashboard'))->assertRedirect(route('login')); }

public function test_customer_can_view_account_but_not_dashboard(): void
{ $c = User::factory()->customer()->create();
  $this->actingAs($c)->get(route('account'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('account/index'));
  $this->actingAs($c)->get(route('dashboard'))->assertForbidden(); }

public function test_staff_can_view_dashboard_but_not_account(): void
{ foreach ([User::factory()->admin()->create(), User::factory()->sales()->create()] as $staff) {
      $this->actingAs($staff)->get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('dashboard/index'));
      $this->actingAs($staff)->get(route('account'))->assertForbidden(); } }

public function test_unverified_customer_can_still_reach_account(): void
{ $this->actingAs(User::factory()->customer()->unverified()->create())->get(route('account'))->assertOk(); }

public function test_role_home_dispatches_by_role(): void
{ $this->get(route('role-home'))->assertRedirect(route('login'));
  $this->actingAs(User::factory()->customer()->create())->get(route('role-home'))->assertRedirect(route('account'));
  $this->actingAs(User::factory()->sales()->create())->get(route('role-home'))->assertRedirect(route('dashboard')); }

// tests/Feature/Auth/RoleRedirectTest.php
public function test_customer_login_redirects_to_account(): void            // post login.store → assertRedirect(route('account'))
public function test_customer_login_returns_to_intended_url(): void
{ $c = User::factory()->customer()->create();
  $this->withSession(['url.intended' => route('profile.edit')])
       ->post(route('login.store'), ['email' => $c->email, 'password' => 'password'])
       ->assertRedirect(route('profile.edit')); }
public function test_staff_login_ignores_intended_url_and_lands_on_dashboard(): void   // Review Focus 1
{ $a = User::factory()->admin()->create();
  $this->withSession(['url.intended' => route('account')])
       ->post(route('login.store'), ['email' => $a->email, 'password' => 'password'])
       ->assertRedirect(route('dashboard'))->assertSessionMissing('url.intended'); }
public function test_registration_lands_customer_on_account_and_ignores_role_payload(): void   // Review Focus 2
{ $this->post(route('register.store'), ['name' => 'Ani', 'email' => 'ani@example.com', 'password' => 'password',
      'password_confirmation' => 'password', 'role' => 'admin', 'is_active' => '0'])
       ->assertRedirect(route('account'));
  $u = User::where('email', 'ani@example.com')->firstOrFail();
  $this->assertSame(Role::Customer, $u->role); $this->assertTrue($u->is_active); }
public function test_two_factor_login_redirects_staff_to_dashboard(): void
{ // same Features::twoFactorAuthentication([...]) + skipUnlessFortifyHas preamble as AuthenticationTest
  $s = User::factory()->sales()->withTwoFactor()->create();
  $this->post(route('login.store'), ['email' => $s->email, 'password' => 'password'])->assertRedirect(route('two-factor.login'));
  $this->post(route('two-factor.login.store'), ['recovery_code' => 'recovery-code-1'])->assertRedirect(route('dashboard')); }
public function test_json_login_returns_redirect_target(): void
{ $c = User::factory()->customer()->create();
  $this->postJson(route('login.store'), ['email' => $c->email, 'password' => 'password'])
       ->assertOk()->assertJson(['two_factor' => false, 'redirect' => route('account')]); }
public function test_authenticated_users_opening_guest_pages_are_sent_to_role_home(): void   // Review Focus 4
{ $c = User::factory()->customer()->create();
  $this->actingAs($c)->get(route('login'))->assertRedirect(route('role-home'));
  $this->actingAs($c)->get(route('register'))->assertRedirect(route('role-home')); }
public function test_verified_customer_visiting_verification_prompt_goes_to_role_home(): void
{ $this->actingAs(User::factory()->customer()->create())->get(route('verification.notice'))->assertRedirect(route('role-home')); }

// tests/Feature/Auth/InactiveUserTest.php
public function test_inactive_user_cannot_log_in(): void
{ $u = User::factory()->inactive()->create();
  $this->post(route('login.store'), ['email' => $u->email, 'password' => 'password'])
       ->assertSessionHasErrors(['email' => EnsureUserIsActive::MESSAGE]);
  $this->assertGuest(); }
public function test_inactive_user_with_wrong_password_gets_generic_error(): void
{ $u = User::factory()->inactive()->create();
  $r = $this->post(route('login.store'), ['email' => $u->email, 'password' => 'salah']);
  $r->assertSessionHasErrors('email');
  $this->assertNotSame(EnsureUserIsActive::MESSAGE, session('errors')->first('email')); $this->assertGuest(); }
public function test_user_deactivated_mid_session_is_logged_out_on_next_request(): void   // Review Focus 3
{ $u = User::factory()->sales()->create(); $this->actingAs($u);
  $u->forceFill(['is_active' => false])->save();
  $this->get(route('dashboard'))->assertRedirect(route('login'))->assertSessionHasErrors(['email' => EnsureUserIsActive::MESSAGE]);
  $this->assertGuest(); }

// tests/Feature/SecurityHeadersTest.php — assert literal values, not the const
private const EXPECTED = [
    'X-Frame-Options' => 'SAMEORIGIN',
    'X-Content-Type-Options' => 'nosniff',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
];
public function test_security_headers_are_sent_on_pages_redirects_and_errors(): void
{ $responses = [$this->get(route('home')), $this->get(route('dashboard')) /* 302 */,
      $this->get('/halaman-tidak-ada') /* 404 */,
      $this->actingAs(User::factory()->customer()->create())->get(route('dashboard')) /* 403 */];
  foreach ($responses as $r) foreach (self::EXPECTED as $h => $v) $r->assertHeader($h, $v); }
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test tests/Feature/Access tests/Feature/Auth/RoleRedirectTest.php tests/Feature/Auth/InactiveUserTest.php tests/Feature/SecurityHeadersTest.php`
Expected: FAIL (route `account` not defined, missing headers, and so on).

- [ ] **Step 3: Update the existing tests whose redirect target changes (and nothing else)**

- `tests/Feature/DashboardTest.php`: `test_authenticated_users_can_visit_the_dashboard` creates `User::factory()->admin()`.
- `tests/Feature/Auth/AuthenticationTest.php:31`: change to `route('account', absolute: false)`.
- `tests/Feature/Auth/RegistrationTest.php`: change to `route('account', absolute: false)`.
- `tests/Feature/Auth/EmailVerificationTest.php`:
  - lines 59 and 123: change to `route('role-home', absolute: false).'?verified=1'`
  - line 107: change to `route('role-home', absolute: false)`

- [ ] **Step 4: Implement the middleware, response, controller, provider bindings, config, routes and placeholder pages as specified in Interfaces**

Placeholder pages are one heading each, in Indonesian: `Beranda`, `Akun Saya`, `Ringkasan`. Then run `php artisan wayfinder:generate --with-form`.

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test`
Expected: all PASS, including every starter-kit test.

- [ ] **Step 6: Run the global verification commands**

Expected: all green. In particular, `npm run types:check` must find no leftover import of `pages/welcome` or `pages/dashboard`.

---

### Task 3: Dealer config & Rupiah formatting (PHP + TS)

**Suggested implementer model:** sonnet

**Files:**
- Create: `config/dealer.php`, `app/Support/Rupiah.php`, `resources/js/lib/format.ts`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php` (`'name' => config('dealer.brand_name')`)
- Test: `tests/Unit/Support/RupiahTest.php` (extends `PHPUnit\Framework\TestCase`, `#[DataProvider]`), `tests/Feature/DealerConfigTest.php`, `resources/js/lib/format.test.ts`

**Interfaces:**
- Consumes: none.
- Produces:
  - `config('dealer')`:
    ```php
    'brand_name' => 'Pemecut Motors',
    'booking_fee' => 5_000_000,            // rupiah, int
    'payment_due_hours' => 24,             // display only
    'bank_accounts' => [                   // dummy data
        ['bank' => 'BCA',     'account_number' => '8730123456',    'account_name' => 'PT Pemecut Motors Indonesia'],
        ['bank' => 'Mandiri', 'account_number' => '1370012345678', 'account_name' => 'PT Pemecut Motors Indonesia'],
        ['bank' => 'BNI',     'account_number' => '0912345678',    'account_name' => 'PT Pemecut Motors Indonesia'],
    ],
    'credit' => [
        'flat_interest_rate' => 0.05,                    // per year, flat
        'down_payment_percents' => [20, 25, 30, 40, 50],
        'tenor_months' => [12, 24, 36, 48, 60],
    ],
    ```
  - `App\Support\Rupiah::format(int $amount): string` and `Rupiah::short(int $amount): string` (final class with static methods).
  - `resources/js/lib/format.ts` exports `formatRupiah(amount: number): string` and `formatRupiahShort(amount: number): string`. It applies `Math.round()` first, has no imports, and uses no `Intl` or `toLocaleString`, so its output matches PHP exactly.

Algorithm (the same in both languages):
- `format`: sign prefix `-` for negatives, then `Rp `, then `|amount|` with `.` thousands separators.
- `short`: let `a = |amount|`.
  - If `a < 1_000_000`, return `format(amount)`.
  - Otherwise set `unit = a >= 1_000_000_000 ? 1e9 ('M') : 1e6 ('jt')` and compute `tenths = floor((a*10 + unit/2) / unit)` with integer math (PHP `intdiv`).
  - If the unit is `jt` and `tenths >= 10_000`, switch to unit `M` and recompute.
  - The text is `whole` (with `.` thousands separators) plus `,d` when `tenths % 10 != 0`. Add the sign prefix and `Rp `, then a space and the unit.

- [ ] **Step 1: Write the failing tests (the same vector table in PHP and TS)**

| amount | `format` | `short` |
|---|---|---|
| 0 | `Rp 0` | `Rp 0` |
| 1500 | `Rp 1.500` | `Rp 1.500` |
| 950000 | `Rp 950.000` | `Rp 950.000` |
| 1000000 | `Rp 1.000.000` | `Rp 1 jt` |
| 9750000 | `Rp 9.750.000` | `Rp 9,8 jt` |
| 9840000 | `Rp 9.840.000` | `Rp 9,8 jt` |
| 10000000 | `Rp 10.000.000` | `Rp 10 jt` |
| 485000000 | `Rp 485.000.000` | `Rp 485 jt` |
| 999960000 | `Rp 999.960.000` | `Rp 1 M` |
| 1250000000 | `Rp 1.250.000.000` | `Rp 1,3 M` |
| 2500000000000 | `Rp 2.500.000.000.000` | `Rp 2.500 M` |
| -1500 | `-Rp 1.500` | `-Rp 1.500` |
| -9750000 | `-Rp 9.750.000` | `-Rp 9,8 jt` |

The TS test adds `formatRupiah(485000000.4) === 'Rp 485.000.000'`.

```php
// tests/Feature/DealerConfigTest.php
public function test_dealer_config_matches_spec(): void
{
    $this->assertSame('Pemecut Motors', config('dealer.brand_name'));
    $this->assertSame(5_000_000, config('dealer.booking_fee'));
    $this->assertSame(24, config('dealer.payment_due_hours'));
    $this->assertSame(0.05, config('dealer.credit.flat_interest_rate'));
    $this->assertSame([20, 25, 30, 40, 50], config('dealer.credit.down_payment_percents'));
    $this->assertSame([12, 24, 36, 48, 60], config('dealer.credit.tenor_months'));
    $this->assertCount(3, config('dealer.bank_accounts'));
    foreach (config('dealer.bank_accounts') as $acc) {
        $this->assertSame(['bank', 'account_number', 'account_name'], array_keys($acc));
    }
}
public function test_brand_name_is_shared_with_inertia(): void
{ $this->get(route('home'))->assertInertia(fn (Assert $p) => $p->where('name', 'Pemecut Motors')); }
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test tests/Unit/Support/RupiahTest.php tests/Feature/DealerConfigTest.php` and `npx vp test run resources/js/lib/format.test.ts`
Expected: FAIL (class, config and module missing).

- [ ] **Step 3: Implement `config/dealer.php`, `Rupiah`, `format.ts` and the `name` share as specified**

- [ ] **Step 4: Run the tests to verify they pass**

Run the same commands as Step 2.
Expected: PASS, with identical vectors in both languages.

- [ ] **Step 5: Run the global verification commands**

Expected: all green.

---

### Task 4: Design tokens, fonts & surfaces

**Suggested implementer model:** opus (load the `frontend-design` skill)

**Files:**
- Modify: `package.json` (`npm i @fontsource-variable/big-shoulders-display@^5.3.0 @fontsource-variable/geist@^5.3.0 @fontsource-variable/geist-mono@^5.3.0`)
- Modify: `vite.config.ts` (remove the `fonts: [bunny(...)]` option and the `bunny` import)
- Modify: `resources/views/app.blade.php`:
  - remove `@fonts`
  - add `surface-public` to the `<html>` `@class` when the component starts with `public/`, `auth/` or `account/`, or starts with `settings/` and `auth()->user()?->isStaff() !== true`
  - add the inline style `html.surface-public { background-color: oklch(0.145 0.004 50); color-scheme: dark !important; }`. `!important` is needed because `use-appearance` writes an inline `color-scheme` on `<html>`.
- Modify: `resources/css/app.css`
- Modify: `app/Http/Middleware/HandleAppearance.php` and `resources/js/hooks/use-appearance.tsx` (every `'system'` default becomes `'light'`; the `Appearance` type is unchanged)
- Create: `resources/js/hooks/use-public-surface.ts`
- Test: `tests/Feature/SurfaceTest.php`

**Interfaces:**
- Consumes (Task 2): routes and page names. Consumes `User::isStaff()`.
- Produces:
  - The `@theme` color/radius mapping becomes `@theme inline`. This is required: with a non-inline `@theme`, `--color-*` resolves at `:root` and per-element overrides (`.surface-public`, a `.dark` wrapper) would not cascade to utilities.
  - Tailwind tokens:
    - `font-sans`: `'Geist Variable', ui-sans-serif, system-ui, sans-serif`
    - `font-display`: `'Big Shoulders Display Variable', 'Arial Narrow', ui-sans-serif, sans-serif`
    - `font-mono`: `'Geist Mono Variable', ui-monospace, monospace`. Confirm the three family names in `node_modules/@fontsource-variable/*/index.css`.
    - `text-display-xl`: `clamp(3.25rem, 11vw, 10rem)`, line-height `0.88`
    - `text-display-lg`: `clamp(2.5rem, 6.5vw, 5.75rem)`, line-height `0.92`
    - colors `ember` = `oklch(0.668 0.222 35)` (#FF4D1C) and `ember-foreground` = `oklch(0.145 0.004 50)`
    - `@utility label-spec`: mono, `0.6875rem/1rem`, `letter-spacing: 0.14em`, uppercase
  - Token sets. Names are fixed. Values may be tuned by at most ±0.02 L for WCAG AA contrast.

    | token | `:root` (dashboard, light neutral) | `.surface-public` (dark cinematic) |
    |---|---|---|
    | `--background` | `oklch(0.975 0.002 90)` | `oklch(0.145 0.004 50)` ink |
    | `--foreground` | `oklch(0.185 0.004 50)` | `oklch(0.965 0.006 85)` bone |
    | `--card`, `--popover` | `oklch(1 0 0)` | `oklch(0.19 0.004 50)` |
    | `--primary` / `--primary-foreground` | ink / `oklch(0.985 0 0)` | ember / ink |
    | `--secondary`, `--muted`, `--accent` | `oklch(0.945 0.002 90)` | `oklch(0.235 0.004 50)` |
    | `--muted-foreground` | `oklch(0.52 0.006 60)` | `oklch(0.70 0.01 70)` |
    | `--border`, `--input` | `oklch(0.91 0.003 90)` | `oklch(1 0 0 / 10%)`, `oklch(1 0 0 / 14%)` |
    | `--ring` | ember `/ 50%` | ember `/ 55%` |
    | `--chart-1..5` | ember, `oklch(0.78 0.15 55)`, ink, `oklch(0.70 0.01 70)`, `oklch(0.88 0.005 80)` | inherit |
    | `--sidebar` / `--sidebar-accent` | `oklch(0.955 0.002 90)` / `oklch(1 0 0)` | n/a |
    | `--sidebar-primary` | ember | n/a |
    | `--radius` | `0.875rem` | `0.875rem` |

    `.dark` (dashboard with dark appearance) reuses the ink/bone neutrals with `--primary` = bone. The `.surface-public` block comes after `.dark` in `app.css`, so it wins on an element carrying both classes.
  - `usePublicSurface(): void` (`@/hooks/use-public-surface`) adds `surface-public` to `document.documentElement` on mount and removes it on unmount. PublicLayout, AuthLayout and AccountLayout use it. A public layout root always carries `className="surface-public dark …"`.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/SurfaceTest.php — inspect only the <html> tag (the <style> also mentions the class)
private function htmlTag(TestResponse $r): string { preg_match('/<html[^>]*>/', $r->getContent(), $m); return $m[0]; }
public function test_public_auth_and_customer_pages_use_public_surface(): void
{ $this->assertStringContainsString('surface-public', $this->htmlTag($this->get(route('home'))));
  $this->assertStringContainsString('surface-public', $this->htmlTag($this->get(route('login'))));
  $c = User::factory()->customer()->create();
  $this->assertStringContainsString('surface-public', $this->htmlTag($this->actingAs($c)->get(route('account'))));
  $this->assertStringContainsString('surface-public', $this->htmlTag($this->actingAs($c)->get(route('profile.edit')))); }
public function test_staff_pages_are_light_by_default(): void
{ $a = User::factory()->admin()->create();
  foreach ([route('dashboard'), route('profile.edit')] as $url) {
      $tag = $this->htmlTag($this->actingAs($a)->get($url));
      $this->assertStringNotContainsString('surface-public', $tag);
      $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bdark\b/', $tag); }
  // no appearance cookie → the inline theme script receives 'light', not 'system'
  $this->actingAs($a)->get(route('dashboard'))->assertSee("const appearance = 'light'", false); }
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test tests/Feature/SurfaceTest.php`
Expected: FAIL.

- [ ] **Step 3: Install the fonts and implement the tokens, blade, Vite config, appearance defaults and `usePublicSurface` as specified**

- [ ] **Step 4: Run the test to verify it passes, then run the global verification commands**

Expected: all green. `public/build/manifest.json` references `.woff2` files for geist, geist-mono and big-shoulders-display.

- [ ] **Step 5: Browser check**

- On `/`, the `<html>` background is dark and `getComputedStyle(document.body).fontFamily` contains `Geist`.
- `read_network_requests` shows no request to `fonts.bunny.net`.
- As admin on `/dashboard`, the background is light.

---

### Task 5: Public layout & dark auth pages

**Suggested implementer model:** opus (load the `frontend-design` skill)

**Files:**
- Modify: `package.json` (`npm i motion@^14.14.0`)
- Create: `resources/js/layouts/public-layout.tsx`
- Create: `resources/js/components/public/site-header.tsx`, `site-footer.tsx`, `mobile-menu.tsx`, `brand-wordmark.tsx`, `nav-items.ts`
- Create: `resources/js/lib/roles.ts`
- Modify: `resources/js/types/auth.ts`
- Modify: `resources/js/app.tsx` (`public/` → `PublicLayout`; `progress.color` = `'#FF4D1C'`)
- Modify: `resources/js/layouts/auth/auth-simple-layout.tsx`, all 7 `resources/js/pages/auth/*.tsx`, `resources/js/components/passkey-verify.tsx` (copy only)
- Modify: `resources/js/pages/public/home.tsx` (static hero; motion is added in Task 7)
- Test: `tests/Feature/SharedPropsTest.php`

**Interfaces:**
- Consumes:
  - Task 2: `home()`, `account()` and `dashboard()`; `login()`, `register()` and `logout()` from `@/routes`.
  - Task 3: the `name` prop.
  - Task 4: `usePublicSurface()`, `font-display`, `label-spec` and `.surface-public`.
- Produces:
  - `export type Role = 'admin' | 'sales' | 'customer'` from `@/types`. `User` gains `role: Role`, `phone: string | null` and `is_active: boolean`.
  - `@/lib/roles`: `isStaff(role: Role): boolean` and `roleLabels: Record<Role, string>` (`Admin`, `Sales`, `Pelanggan`).
  - `PublicLayout({ children }: { children: React.ReactNode })` (default export). Its root is `div.surface-public.dark` with a skip link `Lewati ke konten`, `<SiteHeader />`, `<main id="konten">` and `<SiteFooter />`. Task 7 adds `useLenis()` here.
  - `SiteHeader()`, `SiteFooter()` and `BrandWordmark({ className }: { className?: string })` (the brand text comes from the `name` prop and renders in `font-display`).
  - `publicNavItems: { title: string; href: NonNullable<InertiaLinkProps['href']> }[]`, which contains only `Beranda` → `home()` in Fase 1. Later phases append to it, and only routes that exist may be listed.
  - Header auth area:
    - guest: `Masuk`, plus `Daftar` as the primary/ember button
    - customer: `Akun Saya` → `account()`
    - staff: `Dashboard` → `dashboard()`
  - Below `md`, a menu button (`aria-label` `Buka menu` / `Tutup menu`) opens `MobileMenu`. It is built on the shadcn `Sheet` (focus trap, Esc) with a Motion stagger on the links. `useReducedMotion()` turns the stagger off.
  - The header is transparent at the top and `bg-background/80 backdrop-blur` after 24px of scroll.
  - Footer:
    - tagline `Dealer mobil baru & bekas pilihan — jual beli, kredit, servis & trade-in.`
    - dummy contact `Jl. Teuku Umar No. 88, Denpasar, Bali` · `(0361) 123 456` · `halo@pemecut.test`
    - `© {tahun berjalan} Pemecut Motors. Hak cipta dilindungi.`
- Auth copy (all visible strings Indonesian; key strings fixed):
  - login: `Masuk ke akun Anda` / `Masukkan email dan kata sandi untuk melanjutkan`. Labels `Email`, `Kata sandi`, `Lupa kata sandi?`, `Ingat saya`; button `Masuk`; footer `Belum punya akun?` `Daftar`.
  - register: `Buat akun` / `Isi data di bawah untuk membuat akun pelanggan`. Labels `Nama lengkap`, `Konfirmasi kata sandi`; button `Buat akun`; footer `Sudah punya akun?` `Masuk`.
  - forgot-password: `Lupa kata sandi`, button `Kirim tautan reset`.
  - reset-password: `Atur ulang kata sandi`.
  - verify-email: `Verifikasi email`, buttons `Kirim ulang email verifikasi` and `Keluar`.
  - confirm-password: `Konfirmasi kata sandi`, button `Konfirmasi`.
  - two-factor-challenge: `Kode autentikasi` / `Kode pemulihan`.
- The auth layout keeps its props and structure. Its root becomes `surface-public dark`, it calls `usePublicSurface()`, the wordmark links to `home()`, and the title uses `font-display uppercase`.
- Home hero (static):
  - eyebrow `label-spec` `01 — Pemecut Motors`
  - h1 `Temukan Mobil Impianmu` in `font-display text-display-xl uppercase`
  - sub-copy `Mobil baru & bekas terkurasi, simulasi kredit transparan, dan test drive tanpa ribet.`
  - CTA `Cari Mobil Impianmu ↗` with `href="#sorotan"`, pointing to an empty `<section id="sorotan">` that Task 7 fills
  - a dark gradient background with an ember glow and no photos

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/SharedPropsTest.php
public function test_authenticated_user_props_expose_role_without_secrets(): void
{
    $u = User::factory()->customer()->create(['phone' => '081234567890']);
    $this->actingAs($u)->get(route('account'))->assertInertia(fn (Assert $p) => $p
        ->where('name', 'Pemecut Motors')
        ->where('auth.user.role', 'customer')
        ->where('auth.user.is_active', true)
        ->where('auth.user.phone', '081234567890')
        ->missing('auth.user.password')
        ->missing('auth.user.two_factor_secret')
        ->missing('auth.user.remember_token'));
}
```

- [ ] **Step 2: Run it**

Run: `php artisan test tests/Feature/SharedPropsTest.php`
Expected: PASS already, because the backend landed in Tasks 1–3. This test pins the contract the TS `User` type relies on. If it fails, fix the backend before writing UI.

- [ ] **Step 3: Implement the types, roles helper, public components, layout, auth restyle and copy, home hero and `app.tsx` mapping as specified**

- [ ] **Step 4: Run the global verification commands**

Expected: all green.

- [ ] **Step 5: Browser check at 375, 768, 1280 and 1920**

Check `/`, `/login`, `/register` and `/forgot-password`:
- The surface is dark with Big Shoulders headings.
- `document.documentElement.scrollWidth <= innerWidth`.
- The header shows nav at ≥768 and the menu button below it.
- The menu opens; Esc closes it and focus returns to the button.
- Logged in as `customer@pemecut.test`, the header shows `Akun Saya`.
- Logging in from `/login` as customer lands on `/account`.
- There are no console errors.

---

### Task 6: Account & dashboard shells, role-filtered sidebar, settings shell

**Suggested implementer model:** opus (load the `frontend-design` skill)

**Files:**
- Rename: `resources/js/layouts/app-layout.tsx` → `resources/js/layouts/dashboard-layout.tsx` (default export `DashboardLayout`, still wrapping `layouts/app/app-sidebar-layout.tsx`)
- Create: `resources/js/layouts/account-layout.tsx`, `resources/js/layouts/role-layout.tsx`, `resources/js/lib/dashboard-nav.ts`, `resources/js/lib/dashboard-nav.test.ts`
- Modify:
  - `resources/js/components/app-sidebar.tsx`: role-filtered `mainNavItems: DashboardNavItem[]`; the footer starter links are replaced by `Lihat Situs` → `home()`
  - `resources/js/components/nav-main.tsx`: group label `Menu`
  - `resources/js/components/app-logo.tsx`: brand name
  - `resources/js/app.tsx`: final resolver
  - `resources/js/layouts/settings/layout.tsx`: heading `Pengaturan`, items `Profil`, `Keamanan`, `Tampilan`; `Tampilan` is shown to staff only
  - `resources/js/pages/account/index.tsx`, `resources/js/pages/dashboard/index.tsx`
- Test: `resources/js/lib/dashboard-nav.test.ts`, `tests/Feature/SettingsShellTest.php`

**Interfaces:**
- Consumes:
  - Task 5: `SiteHeader`, `SiteFooter`, `Role`, `isStaff` and `roleLabels`.
  - Task 4: `usePublicSurface()`.
  - Task 2: `account()`, `dashboard()` and `home()`; `edit()` from `@/routes/profile`; `logout()`.
- Produces:
  - `DashboardLayout({ breadcrumbs = [], children }: { breadcrumbs?: BreadcrumbItem[]; children: React.ReactNode })`.
  - `AccountLayout({ children }: { breadcrumbs?: BreadcrumbItem[]; children: React.ReactNode })`:
    - root `surface-public dark`, calls `usePublicSurface()`
    - `SiteHeader`, then a container with a sub-nav (`Ringkasan` → `account()`, `Pengaturan` → profile `edit()`, `Keluar` → `logout()` as a POST button), then `SiteFooter`
    - no Lenis
  - `RoleLayout(props: { breadcrumbs?: BreadcrumbItem[]; children: React.ReactNode })` renders `DashboardLayout` when `isStaff(auth.user.role)` and `AccountLayout` otherwise.
  - `type DashboardNavItem = NavItem & { roles: Role[] }` and `navItemsFor(role: Role, items: DashboardNavItem[]): DashboardNavItem[]` in `@/lib/dashboard-nav`. The module has no `@/routes` import. The sidebar's `mainNavItems` (which later phases append to) is `[{ title: 'Ringkasan', href: dashboard(), icon: LayoutGrid, roles: ['admin', 'sales'] }]`.
  - The final `app.tsx` resolver: `public/` → `PublicLayout`, `auth/` → `AuthLayout`, `account/` → `AccountLayout`, `dashboard/` → `DashboardLayout`, `settings/` → `[RoleLayout, SettingsLayout]`, default → `null`.
- Page copy:
  - Dashboard (ref 3 style):
    - greeting `Selamat datang, {nama depan}` with a role badge
    - four KPI cards with `label-spec` labels `Revenue booking · bulan ini`, `Pesanan`, `Menunggu verifikasi` and `Test drive hari ini`. Each value is `—` in `font-display`, captioned `Belum ada data`. One card uses the black-accent style.
    - two panels, `Penjualan per bulan` (`Belum ada data penjualan.`) and `Pesanan terbaru` (`Belum ada pesanan.`)
    - breadcrumb `Ringkasan`
  - Account:
    - `Halo, {nama depan}` / `Pantau pesanan dan jadwal test drive Anda di sini.`
    - card `Pesanan aktif`: `Belum ada pesanan.`, CTA `Jelajahi mobil` → `home()`
    - card `Test drive terdekat`: `Belum ada jadwal test drive.`

- [ ] **Step 1: Write the failing tests**

```ts
// resources/js/lib/dashboard-nav.test.ts
import { describe, expect, it } from 'vite-plus/test';
import { navItemsFor, type DashboardNavItem } from './dashboard-nav';
const items: DashboardNavItem[] = [
    { title: 'Ringkasan', href: '/dashboard', roles: ['admin', 'sales'] },
    { title: 'Pengguna', href: '/dashboard/users', roles: ['admin'] },
];
describe('navItemsFor', () => {
    it('shows admin every item', () => expect(navItemsFor('admin', items).map((i) => i.title)).toEqual(['Ringkasan', 'Pengguna']));
    it('hides admin-only items from sales', () => expect(navItemsFor('sales', items).map((i) => i.title)).toEqual(['Ringkasan']));
    it('shows customers nothing', () => expect(navItemsFor('customer', items)).toEqual([]));
});
```

```php
// tests/Feature/SettingsShellTest.php
public function test_settings_profile_opens_for_every_role(): void
{ foreach (['admin', 'sales', 'customer'] as $state) {
      $this->actingAs(User::factory()->{$state}()->create())->get(route('profile.edit'))->assertOk(); } }
```

- [ ] **Step 2: Run them to verify the JS test fails**

Run: `npx vp test run resources/js/lib/dashboard-nav.test.ts`
Expected: FAIL (module missing). The PHP test passes already; it guards against regressions.

- [ ] **Step 3: Implement the layouts, nav filter, sidebar changes, settings labels, resolver and both pages as specified**

- [ ] **Step 4: Run the tests and the global verification commands**

Expected: all green.

- [ ] **Step 5: Browser check**

- Log in as each demo account. Customer lands on `/account` (dark); sales and admin land on `/dashboard` (light, sidebar `Ringkasan` active).
- `/settings/profile` renders in AccountLayout for the customer, without `Tampilan`. For admin it renders in DashboardLayout, with `Tampilan`.
- `/settings/security` (2FA, passkeys) loads after password confirmation.
- At 375 the sidebar becomes a sheet.
- There is no horizontal scroll at 375, 768, 1280 or 1920, and no console errors.

---

### Task 7: Motion module & home demo

**Suggested implementer model:** opus (load the `frontend-design` skill)

**Files:**
- Modify: `package.json` (`npm i gsap@^3.15.1 lenis@^1.3.27`)
- Create: `resources/js/lib/motion/core.ts`, `lenis.ts`, `reveal.ts`, `split-lines.ts`, `parallax.ts`, `scrub-words.ts`, `hooks.ts`
- Modify: `resources/js/layouts/public-layout.tsx` (call `useLenis()`), `resources/js/pages/public/home.tsx`, `resources/css/app.css` (reduced-motion base rules)

**Interfaces:**
- Consumes:
  - Task 5: `PublicLayout` and the hero markup.
  - Task 3: `formatRupiah` and `formatRupiahShort` (demo price on the cards).
  - Task 4: tokens.
- Produces (Fase 3 builds on these exact names):
  - `core.ts`: re-exports `gsap`, `ScrollTrigger` and `SplitText` (`gsap.registerPlugin` called once). Constants `DESKTOP_QUERY = '(min-width: 1024px)'` and `REDUCED_MOTION_QUERY = '(prefers-reduced-motion: reduce)'`. `prefersReducedMotion(): boolean`, which returns false when `window` is undefined.
  - `lenis.ts`:
    - It imports `lenis/dist/lenis.css`.
    - `startLenis(): Lenis | null` returns null under reduced motion. It uses options `{ lerp: 0.1, autoRaf: false }`, calls `lenis.on('scroll', ScrollTrigger.update)`, drives `lenis.raf` from `gsap.ticker` and sets `gsap.ticker.lagSmoothing(0)`.
    - `stopLenis(): void` destroys Lenis, removes the ticker callback and restores `lagSmoothing(500, 33)`.
    - `getLenis(): Lenis | null`.
  - `reveal.ts`:
    - `revealFadeUp(targets: gsap.DOMTarget, options?: { y?: number; stagger?: number; start?: string }): gsap.core.Tween`. Defaults are `y: 32`, `stagger: 0.08`, `start: 'top 85%'`, `once: true`. Reduced motion gives an opacity-only, 0.4s tween.
    - `revealClip(target: gsap.DOMTarget): gsap.core.Tween` animates `clip-path: inset(12%)` to `inset(0%)` plus scale 1.08 → 1. Reduced motion gives opacity only.
  - `split-lines.ts`: `splitLines(target: HTMLElement, options?: { stagger?: number; start?: string }): SplitText | null`. It uses `SplitText.create` with `type: 'lines'`, `mask: 'lines'`, `linesClass: 'split-line'`, `autoSplit: true`, and an `onSplit` that returns `gsap.from(self.lines, { yPercent: 100, stagger: 0.08, duration: 0.9, ease: 'expo.out', scrollTrigger: { trigger: target, start: 'top 85%', once: true } })`. Under reduced motion it runs an opacity fade and returns null.
  - `parallax.ts`: `parallax(target: HTMLElement, options?: { distance?: number }): gsap.core.Tween | null` (default distance 15, `yPercent: -distance`, `scrub: true`). Returns null under reduced motion.
  - `scrub-words.ts`: `scrubWords(target: HTMLElement): SplitText | null`. It splits words (`wordsClass: 'split-word'`) and scrubs opacity 0.15 → 1 from `top 80%` to `bottom 40%`. Under reduced motion it returns null and the text stays fully visible.
  - `hooks.ts`:
    - `useLenis(): void`, used only by PublicLayout. On mount it calls `startLenis()` and subscribes `router.on('navigate', …)` to call `getLenis()?.scrollTo(0, { immediate: true, force: true })` and then `ScrollTrigger.refresh()` on the next frame. Cleanup unsubscribes and calls `stopLenis()`.
    - `useGsapContext<T extends HTMLElement = HTMLDivElement>(setup: (tools: { mm: gsap.MatchMedia; reduced: boolean }) => void, deps?: React.DependencyList): React.RefObject<T | null>`. It runs in `useLayoutEffect`, so `gsap.from` sets its start state before paint. It runs `setup` inside `gsap.context(…, ref.current)` with a fresh `gsap.matchMedia()`. Cleanup is `mm.revert()` followed by `ctx.revert()`, which must be safe under React StrictMode double-invoke.
    - `useReveal<T extends HTMLElement = HTMLDivElement>(): React.RefObject<T | null>` animates each descendant with `[data-reveal]` using `revealFadeUp`, or `revealClip` when `data-reveal="clip"`.
  - Rules:
    - Pins and horizontal scroll exist only inside `mm.add(`${DESKTOP_QUERY} and (prefers-reduced-motion: no-preference)`, …)`.
    - Every tween is created inside `useGsapContext`.
    - Content is never hidden by CSS before JS runs.
- Home demo (Indonesian, replaced in Fase 3):
  1. Hero: the h1 uses `splitLines`, the decorative background layer uses `parallax`, and the CTA calls `getLenis()?.scrollTo('#sorotan')`, falling back to the native anchor.
  2. Statement with `scrubWords`: `Setiap unit kami pilih dengan teliti, supaya Anda cukup memikirkan satu hal: ke mana perjalanan berikutnya.`
  3. `#sorotan` (`Sorotan Minggu Ini`): three placeholder cards (`01 PICK OF THE WEEK`, `02`, `03`).
     - Each card has `label-spec` rows `0–100`, `POWER` and `ENGINE` with values `—`, plus `Contoh harga` with `formatRupiah(485000000)` and `formatRupiahShort(9800000) + ' / bln'`.
     - At ≥1024 with no reduced motion, the section pins and the cards scroll horizontally.
     - Otherwise it is a CSS `overflow-x-auto snap-x` carousel inside the container, so the page itself never scrolls horizontally.
  4. Services `01 Jual Beli`, `02 Kredit`, `03 Servis & Trade-in`, each with `data-reveal`.

No JS unit tests in this task: the behaviour depends on the DOM and scrolling, so it is pinned by the scripted Browser checks in Step 3.

- [ ] **Step 1: Install the packages and implement the motion module and the home demo as specified**

- [ ] **Step 2: Run the global verification commands**

Expected: all green.

- [ ] **Step 3: Scripted Browser checks**

Use `javascript_tool` and `resize_window`.
- (a) At 1280×800 on `/`: `document.documentElement.classList.contains('lenis') === true`, `document.querySelectorAll('.pin-spacer').length === 1` and `document.querySelectorAll('h1 .split-line').length > 0`. Scroll to the bottom: the statement words reach opacity 1 and the console has no errors.
- (b) Mobile preset, reload: `.pin-spacer` count is `0`, the carousel swipes, and `scrollWidth <= innerWidth`. Repeat at 768 and 1920.
- (c) Review Focus 5:
  - On `/`, scroll down, then click `Masuk` (an Inertia link). On `/login`, `classList.contains('lenis') === false`.
  - Click the wordmark back to `/`. `.pin-spacer` count is `1` (not 2) and `window.scrollY === 0`.
  - Log in as admin. On `/dashboard`, the `lenis` class is absent and there are no console errors.
- (d) Reduced motion:
  - On `/login`, run `const mq = window.matchMedia.bind(window); window.matchMedia = (q) => mq(q.replace('(prefers-reduced-motion: reduce)', 'all').replace('(prefers-reduced-motion: no-preference)', 'not all'));`. The rewrite yields real `MediaQueryList` objects, and invalid combined queries evaluate to no-match.
  - Navigate to `/` through the wordmark link. Expect no `lenis` class, `.pin-spacer` count `0`, `h1 .split-line` count `0`, and all text visible.

---

### Task 8: Documentation drafts (DOKUMENTASI + FLOWCHART)

**Suggested implementer model:** sonnet

**Files:**
- Create: `docs/DOKUMENTASI.md`, `docs/FLOWCHART.md`

**Interfaces:**
- Consumes: the code from Tasks 1–7, which is the source of truth for names, routes and values; read it, do not copy from this plan. Also consumes the spec sections Database, User flow, AI Car Advisor, Keamanan, Deploy.
- Produces: the two docs. Later phases update them at the end of each phase.

**DOKUMENTASI.md** is in Indonesian, with exactly these 16 `##` headings in this order. Any section, or part of one, belonging to a later phase is marked `_(Direncanakan — Fase N)_` and gets a short summary from the spec.
1. `Ringkasan Proyek & Pemetaan Syarat Tugas`: a table mapping each syarat to its evidence and code location: Laravel 13, MySQL/phpMyAdmin (`car_dealer`), ≥2 roles (`App\Enums\Role`), session auth (Fortify + `SESSION_DRIVER=database` + table `sessions` + `EnsureRole`), CRUD (planned list from the spec's "Cakupan CRUD"), tema, tampilan & alur bisnis, GitHub (`github.com/Azriel-Fajar/Car-Dealer`), bonus deploy.
2. `Tech Stack & Alasan Pemilihan`: actual versions from `composer.json` and `package.json`.
3. `Setup Lokal`:
   - php.new install, removing `C:\xampp\php` from PATH, and the herd-lite PATH prefix
   - XAMPP MySQL and the `car_dealer` DB, `.env`
   - `php artisan migrate`, `php artisan db:seed`, `composer run dev` on port 8000
   - a demo account table with the three emails and password `password`
4. `Struktur Folder & Arsitektur`: Laravel ↔ Inertia ↔ React, the layout map from `app.tsx`, and the `.surface-public` and `:root` token surfaces.
5. `Autentikasi & Session`:
   - Fortify, the `sessions` table, and session regeneration at login
   - `RoleRedirectResponse`, `role-home`, and the intended-redirect rules
   - inactive-user blocking
   - guest configuration stored in the session `_(Fase 4)_`
6. `Role & Hak Akses`: the spec matrix verbatim, the `role:` middleware usage, `EnsureUserIsActive`, and Policies `_(Fase 2+)_`.
7. `Database`: the `users` columns as implemented, then every table from the spec marked `_(Fase N)_`, with a link to the ERD in FLOWCHART.
8. `Workflow Bisnis & State Machine`: a summary per role, with links to the FLOWCHART diagrams.
9. `Daftar Route`: a table (Method, URL, Name, Middleware, Role) for every route in `php artisan route:list --except-vendor` plus the Fortify auth routes.
10. `Fitur per Halaman & Daftar CRUD`: the Fase 1 pages as built, and the spec's later pages marked planned.
11. `Sistem Animasi`:
    - the `lib/motion` API (the exact function and hook names)
    - how to add an animation (`useGsapContext` and `data-reveal`)
    - the reduced-motion, ≥1024 pin and Inertia cleanup rules
12. `AI Car Advisor` `_(Fase 6)_`: the spec's protection layers summarized as a list.
13. `Keamanan`:
    - Implemented now: the four security headers with their values, Fortify throttling, session regeneration, cookie `httponly`/`same_site` (values read from `config/session.php`), explicit `$fillable` with `role`/`is_active` guarded, the inactive guard, React escaping.
    - Planned: CSP, upload rules, Turnstile, policies. Production: `APP_DEBUG=false` and `SESSION_SECURE_COOKIE=true`.
14. `Testing`: `php artisan test`, `npx vp test run`, `npm run build`, `npm run types:check`, `npm run check`, pint and phpstan, plus what each test file covers. Tests run on SQLite `:memory:` (phpunit.xml).
15. `Deploy` `_(Fase 8)_`: a summary of the spec.
16. `Troubleshooting`:
    - XAMPP PHP 8.2 winning on PATH
    - the ComposerSetup composer crashing on PHP 8.5 (use herd-lite)
    - `intl` missing (why `App\Support\Rupiah` exists)
    - port 8000 vs Apache 80
    - `php artisan storage:link`
    - `php artisan wayfinder:generate --with-form` after route changes
    - MySQL not started

**FLOWCHART.md** has an intro line explaining that the diagrams render on GitHub and are exported to PNG in Fase 7. It contains these 10 `##` sections, each with a ```` ```mermaid ```` block:
1. `Alur Guest → Customer` (`flowchart TD`): browse/filter/compare → configure (stored in the session) → checkout → login/register if needed → back to checkout → contact, payment scheme, delivery → order `pending_payment` → payment instructions → upload proof → `waiting_verification` → approved `booking_paid` or rejected and re-upload → `/account` timeline.
2. `Verifikasi Pembayaran & Proses Order (Sales)`.
3. `Alur Admin` (master data, users & roles, reports, AI advisor controls).
4. `Booking Test Drive`.
5. `State Diagram Status Order` (`stateDiagram-v2`): `pending_payment → waiting_verification → booking_paid → processing → ready → completed`, `waiting_verification → pending_payment` (rejected), and `cancelled` from each non-terminal state with the note "draf, final di Fase 4/5".
6. `State Diagram Status Pembayaran`: `submitted → approved | rejected`.
7. `Sequence Autentikasi & Session` (`sequenceDiagram`): guest → `/checkout` → `auth` middleware stores `url.intended` → `/login` → Fortify (`authenticateUsing`, inactive check) → session regenerated → `RoleRedirectResponse` (customer: intended `/checkout`; staff: `/dashboard`, intended dropped).
8. `Pipeline AI Advisor`: all 14 gates in the spec's order, including 6b `AdvisorInputFilter`, with the rejection branches (429, blocked, flagged/lockout, off_topic).
9. `ERD` (`erDiagram`): every table in the spec's Database section with key columns and FKs. Mark planned tables with a `%%` comment.
10. `Arsitektur` (`flowchart LR`): Browser (React pages/layouts/`lib/motion`) ↔ Inertia ↔ Laravel (routes → middleware `role`/active/security headers → controllers → services/policies) ↔ MariaDB, plus Storage disks (public/private) and OpenAI `_(Fase 6)_`.

Mermaid rules for GitHub:
- Quote any label containing punctuation or spaces with symbols (`A["Upload bukti (jpg/pdf)"]`). Use edge labels like `-->|teks|`.
- Never use `end` as a node id. Use snake_case state names. ER attribute types are single words (`bigint`, `string`, `text`, `json`, `boolean`, `int`, `datetime`, `enum`).
- No HTML and no emoji.

- [ ] **Step 1: Write `docs/DOKUMENTASI.md` per the outline**

- [ ] **Step 2: Write `docs/FLOWCHART.md` per the outline**

- [ ] **Step 3: Validate the Mermaid syntax**

Run: `npx -y @mermaid-js/mermaid-cli@11 -i docs/FLOWCHART.md -o "$TMPDIR/flowchart-check/out.md"`. Use the session scratchpad and keep the renders out of the repo.
Expected: exit 0 and 10 SVGs written.
Fallback if Puppeteer cannot download: a scratch HTML page that loads `mermaid@11` from cdn.jsdelivr.net and runs `mermaid.parse()` on each block, opened in the Browser pane. Every block must parse.

- [ ] **Step 4: Cross-check**

Every route, class, function and config value named in DOKUMENTASI must exist (`grep` each one). `php artisan route:list` must match section 9.
Expected: no mismatches.

- [ ] **Step 5: Run the global verification commands**

Expected: still green, since only docs changed.
