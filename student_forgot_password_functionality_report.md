# SponsorFlow — Student Forgot Password Functionality Report

**Scope:** Student Portal only (`/login` → **Forgot Password?**). No staff, sponsor, FASSG, accounting, or administrator login/account functionality was changed.

**Environment used for the demonstration:** local XAMPP/Laravel with `STUDENT_PASSWORD_RECOVERY_ENABLED=true`. The seeded demo account `student@sponsorflow.test` (birthdate `2004-06-15`) was used only to reach the authorized reset form; its password was **not** changed during UI verification.

---

## 1. Audited Student authentication files

Read-only inspection was completed before any edit:

- `routes/web.php` — all route groups, guest routes, role-protected student routes.
- `config/app.php`, `config/auth.php`, `phpunit.xml`, `composer.json`, `package.json`.
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `app/Http/Controllers/Auth/PortalLoginController.php`
- `app/Http/Controllers/Auth/StudentLoginController.php`
- `app/Http/Controllers/Auth/RegisteredUserController.php`
- `app/Http/Controllers/Student/ProfileController.php`
- `app/Models/User.php`, `app/Models/StudentProfile.php`, `app/Models/SystemSetting.php`
- `app/Enums/UserRole.php`, `app/Enums/UserStatus.php`
- `database/migrations/0001_01_01_000000_create_users_table.php`
- `database/migrations/2026_08_23_000001_create_student_profiles_table.php`
- `database/seeders/RoleAndUserSeeder.php`
- `resources/views/auth/student-login.blade.php`, `resources/views/auth/register.blade.php`, `resources/views/layouts/app.blade.php`
- `tests/Feature/AuthenticationTest.php`, `tests/Feature/PortalAuthenticationTest.php`

Live MariaDB structure was also inspected through Laravel (read-only): `users` contains 30 accounts (15 student, 7 FASSG, 6 sponsor, 1 accounting, 1 admin) and `student_profiles` contains 15 records.

---

## 2. Actual database relationships

| Item | Verified source |
| --- | --- |
| Student email | `users.email` (unique, 191 chars) |
| Student birthdate | `student_profiles.birthdate` (`date`, nullable) |
| Relationship | `student_profiles.user_id` → `users.id`, unique `user_id`, foreign key with cascade delete; `User::studentProfile()` is `hasOne`, `StudentProfile::user()` is `belongsTo` |
| Role identification | `users.role` enum: `student`, `fassg`, `sponsor`, `accounting`, `admin`; checked with `UserRole::Student` / `User::isStudent()` |
| Status handling | `users.status` enum: `active`, `inactive`, `suspended`; existing login requires `User::isActive()` |
| Password hashing/validation | `users.password` uses the `hashed` cast; registration and profile password updates use `Hash::make()` with `Password::min(8)->mixedCase()->numbers()->symbols()` plus `confirmed` |
| Prior recovery support | No student recovery route/controller existed. Only the default `password_reset_tokens` migration table was present; no email delivery, OTP, or notification flow exists. |
| Existing session mechanism | `PortalLoginController` uses a server-side `pending_user_id` session value for the privacy-consent step. The new recovery flow follows the same convention with a dedicated, short-lived authorization key. |

No schema changes, migrations, or updates to existing database records were made.

---

## 3. Implemented Student-only routes

All routes are inside the existing `guest` middleware group in `routes/web.php`:

| Method | Path | Route name | Purpose |
| --- | --- | --- | --- |
| GET | `/student/forgot-password` | `student.password.request` | Verification form, or assistance page when disabled |
| POST | `/student/forgot-password` | `student.password.verify` | Validates email + birthdate and issues authorization |
| GET | `/student/reset-password` | `student.password.reset` | Create-new-password form (authorized only) |
| POST | `/student/reset-password` | `student.password.reset.store` | Consumes authorization and updates the student password |

Nonstudent accounts are rejected in both `verify()` and `reset()` by re-checking `role === student`, `status === active`, and the presence of a profile with a birthdate. Soft-deleted users are excluded by Laravel's `SoftDeletes` scope.

---

## 4. New Blade pages

- `resources/views/auth/student-forgot-password.blade.php` — **Forgot Password?** with the required subtitle, email, date of birth, Continue, and Back to Student Sign In.
- `resources/views/auth/student-reset-password.blade.php` — **Create New Password** with the required subtitle, New Password and Confirm New Password fields, `bi-eye` / `bi-eye-slash` toggles, the existing registration password-policy helper text, Save New Password, and Back to Student Sign In.
- `resources/views/auth/student-recovery-assistance.blade.php` — **Account Recovery Assistance** with the required administrator-verification message and Back to Student Sign In.
- `resources/views/auth/partials/student-auth-styles.blade.php` — shared scoped styles copied from the Student Sign In design (42px inputs, 44px primary button, 8px radius, navy/neutral buttons, responsive rules).
- `resources/views/auth/partials/student-auth-hero.blade.php` — shared split-screen navy branding panel.

The existing Student Sign In page was left visually unchanged; only the previously inert `Forgot Password?` link now points to `route('student.password.request')`.

---

## 5. Password validation and security controls

- **Generic failures:** incorrect email, incorrect birthdate, missing account, inactive account, nonstudent role, and missing profile all return the same message: “We couldn't verify the information provided. Please check your details or contact the authorized university office.”
- **Rate limiting:** `Illuminate\Support\Facades\RateLimiter` with the existing `max_login_attempts` setting (fallback 5), keyed by IP and by email-hash + IP for verification, and by IP for reset submissions, with a 5-minute decay.
- **Authorization payload:** session key `student_password_recovery` stores only `user_id`, a 64-character random-token hash, and an expiry timestamp. No password, birthdate, or email is stored in the authorization.
- **Short-lived:** default TTL is 10 minutes, configurable via `STUDENT_PASSWORD_RECOVERY_TTL`.
- **Single-use:** the authorization is consumed (session key forgotten) before the password write; duplicate submissions are rejected. Session IDs are regenerated on verification and on reset.
- **Revalidation:** the target account is rechecked as an active student with a profile before both showing the reset form and saving the password.
- **Password policy:** identical to Student Registration and profile password change — `required`, `confirmed`, `Password::min(8)->mixedCase()->numbers()->symbols()`; hashed with `Hash::make()`.
- **No auto-login:** success redirects to `/login` with the required success status; `assertGuest()` is covered by tests.
- **Server-side production gate:** every controller action checks `config('app.student_password_recovery_enabled')`. When false, GET pages render the assistance page and POST requests are redirected without processing. This is not a UI-only or JavaScript condition.
- **Cache control:** recovery pages send `no-store`, `no-cache`, `must-revalidate`, and `Pragma: no-cache` headers.

---

## 6. Changed files

Tracked modifications:

- `.env.example`
- `config/app.php`
- `resources/views/auth/student-login.blade.php` (link only)
- `routes/web.php`

New files:

- `app/Http/Controllers/Auth/StudentPasswordRecoveryController.php`
- `resources/views/auth/student-forgot-password.blade.php`
- `resources/views/auth/student-reset-password.blade.php`
- `resources/views/auth/student-recovery-assistance.blade.php`
- `resources/views/auth/partials/student-auth-styles.blade.php`
- `resources/views/auth/partials/student-auth-hero.blade.php`
- `tests/Feature/StudentPasswordRecoveryTest.php`
- `output/student-forgot-password/*.png` and `ui-metrics.json`

Local-only environment (ignored by Git):

- `.env` — `STUDENT_PASSWORD_RECOVERY_ENABLED=true` for the isolated demonstration. It must remain unset/false on Render.

`npm run build` regenerated `public/build/` assets (gitignored).

---

## 7. Desktop and mobile screenshots

All screenshots were captured from a real headless Chrome session against the local Laravel server.

**Forgot Password**

- [1920×1080](output/student-forgot-password/forgot-password-1920x1080.png)
- [1600×900](output/student-forgot-password/forgot-password-1600x900.png)
- [1440×900](output/student-forgot-password/forgot-password-1440x900.png)
- [1366×768](output/student-forgot-password/forgot-password-1366x768.png)
- [768×1024](output/student-forgot-password/forgot-password-768x1024.png)
- [430×932](output/student-forgot-password/forgot-password-430x932.png)
- [390×844](output/student-forgot-password/forgot-password-390x844.png)
- [360×800](output/student-forgot-password/forgot-password-360x800.png)

**Create New Password (authorized session)**

- [1920×1080](output/student-forgot-password/reset-password-1920x1080.png)
- [1600×900](output/student-forgot-password/reset-password-1600x900.png)
- [1440×900](output/student-forgot-password/reset-password-1440x900.png)
- [1366×768](output/student-forgot-password/reset-password-1366x768.png)
- [768×1024](output/student-forgot-password/reset-password-768x1024.png)
- [430×932](output/student-forgot-password/reset-password-430x932.png)
- [390×844](output/student-forgot-password/reset-password-390x844.png)
- [360×800](output/student-forgot-password/reset-password-360x800.png)

**Account Recovery Assistance (configuration disabled)**

- [1920×1080](output/student-forgot-password/assistance-1920x1080.png)
- [390×844](output/student-forgot-password/assistance-390x844.png)

Layout measurements for all 8 viewports are recorded in [ui-metrics.json](output/student-forgot-password/ui-metrics.json). Every viewport passed: horizontal overflow `0`, input group height `44px` (42px control plus 1px borders), primary button `44px`, secondary button `42px`, border radius `8px`, visible password icons, and a working `bi-eye` → `bi-eye-slash` toggle.

---

## 8. Test results

Commands actually executed:

| Command | Result |
| --- | --- |
| `php artisan test tests/Feature/StudentPasswordRecoveryTest.php` | **16 passed**, 101 assertions |
| `php artisan test --filter='AuthenticationTest\|PortalAuthenticationTest\|StudentPasswordRecoveryTest'` | **28 passed**, 149 assertions |
| `php artisan test` | **543 passed**, 4,293 assertions |
| `npm run build` | Vite build succeeded (57 modules, CSS + JS emitted) |

Covered scenarios: matching student email/birthdate, incorrect email, incorrect birthdate, nonexistent account, nonstudent roles, inactive student, missing profile, confirmation mismatch, weak password, valid hashed-password update, blocked direct navigation, expired authorization, reused authorization, rate limiting, production-disabled configuration, and normal student sign-in with the new password after reset. No actual student password was changed during testing.

---

## 9. Production restrictions

- New configuration keys in `config/app.php`: `app.student_password_recovery_enabled` (**default `false`**) and `app.student_password_recovery_ttl` (default `10` minutes).
- `.env.example` documents `STUDENT_PASSWORD_RECOVERY_ENABLED=false` with an explicit warning never to enable it on a public deployment.
- The local `.env` is the only place where the flag is set to `true`. Render must leave the variable unset or set it to `false`.
- With the flag false, both recovery GET routes render **Account Recovery Assistance**, and both POST routes are redirected before any validation or database write.
- No email links, OTPs, or notifications were introduced, so the public deployment cannot be taken over by knowledge of an email address and birthdate alone.

---

## 10. Remaining limitations

- Email address + birthdate is weak proof of identity. This direct reset is intentionally limited to isolated local/testing environments with dedicated demonstration accounts; production requires stronger identity verification or institutional assistance.
- The authorization is bound to the browser session that completed verification and expires after 10 minutes; it cannot be resumed on another device.
- Rate limiting is IP- and email-keyed; a distributed attacker can still spread attempts across addresses, though per-IP throttling and generic errors limit practical guessing.
- No CAPTCHA, email/OTP delivery, reset audit log, or administrator-initiated reset flow was added, consistent with the stated scope and the absence of an email delivery system.
- The assistance page contains the specified administrator-verification message but no contact link, because no support channel/address was defined in the requirements.
