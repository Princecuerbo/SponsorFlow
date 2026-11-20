# Student SLE-FHE Verification Status System Audit

**Scope:** Strict read-only, evidence-based audit of SponsorFlow’s Student SLE-FHE verification status lifecycle.  
**Audit date:** 2026-11-20  
**Workspace:** `C:\xampp\htdocs\SponsorFlow` (git repo; pre-existing uncommitted user work preserved)  
**Permitted artifact created by this audit:** `student_sle_fhe_verification_status_system_audit.md` only.

## 1. Executive Summary

- **Authoritative status model (code):** `StudentProfile::getSleFheStatusAttribute()` derives one of four states from dedicated tables (`sle_fhe_verifications`, `sle_fhe_requests`, `sle_fhe_rejections`) with precedence **Verified > Pending Review > Rejected > Unverified** (`app/Models/StudentProfile.php:121-136`, `app/Enums/SleFheStatus.php:5-10`).
- **Verified definition (code):** A profile is verified only when a documented verification row exists with non-null `verified_by` and `verified_at` (`app/Models/StudentProfile.php:198-201,121-125,225-234`). Actorless/incomplete verification rows are not treated as certified (`tests/Feature/FassgSleFheWorkflowTest.php:481-490`, passed).
- **Pending definition (code):** Pending Review is returned when there is no documented verification and `sleFheRequest()->where('status','pending')->exists()` is true (`app/Models/StudentProfile.php:127-129`). `sleFheRequest()` is `latestOfMany()` (`app/Models/StudentProfile.php:101-104`); Eloquent builds this as a max-id subquery, so the status condition applies to the profile’s highest-id request. Older historical pending rows cannot override a newer disposition.
- **Rejected / resubmission (code/tests):** FASSG “not verified / returned for correction” creates a `sle_fhe_rejections` row and sets the pending request’s status to `rejected`, preserving request evidence; student status becomes Rejected when no documented verification and no pending-latest state applies (`app/Http/Controllers/Fassg/VerificationController.php:412-433`, `app/Models/StudentProfile.php:131-135`). Resubmission supersedes existing pending requests and creates a new pending request, yielding Pending Review (`app/Http/Controllers/Student/SleVerificationController.php:144-161`, `tests/Feature/StudentSleFheVerificationTest.php:54-75`, `tests/Feature/StudentRegistrationTest.php:60-83` executed; the equivalent end-to-end path is also covered by executed `tests/Feature/TenStudentWorkflowRegressionTest.php:770-805`; executed suites passed).
- **Page consistency:** `/student/sle-fhe` maps all four derived states distinctly in UI state (`resources/views/student/sle-verification.blade.php:45-50,223-234,309-314,399-455`). `/student/dashboard` collapses every non-verified derived state into the label **“Pending”** with amber styling in the SLE-FHE metric (`resources/views/dashboard.blade.php:75,95-114`), so **Unverified (no request)** and **Rejected** can be presented as “Pending” on the dashboard while the SLE-FHE page shows “Not Verified” or rejected/resubmittable state. Determination source is the same accessor, but displayed outcome is inconsistent.
- **Read-only DB aggregates (no PII):** 15 student profiles; 9 SLE-FHE requests (1 pending, 1 rejected, 7 verified); 9 verifications (9 complete/documented by actor+timestamp fields, 0 incomplete); 1 rejection (1 profile); 1903 registry rows; 6 profiles with no request; 0 profiles with >1 request; 0 profiles with pending request + any verification; 0 profiles with pending request + rejection; 0 profiles with rejection + verification; 0 verified requests lacking a complete verification. Applying the accessor’s precedence via read-only aggregate queries yields 9 `Verified`, 1 `Pending Review`, 1 `Rejected`, 4 `Unverified`. Current DB shows no multi-request or conflicting-state anomalies.
- **Isolation & tests executed:** `phpunit.xml` enforces isolated testing (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, array cache/session/mail, sync queue; `phpunit.xml:20-35`). `.env.testing` not present. Executed non-destructive tests: `StudentSleFheVerificationTest`, `FassgSleFheWorkflowTest`, `StudentRegistrationTest`, `FassgDashboardCounterRegressionTest`, and `TenStudentWorkflowRegressionTest` → **58 passed, 350 assertions**.
- **Conclusion:** **PARTIAL PASS — Status lifecycle is correct, but presentation inconsistencies exist** (core derived lifecycle and FASSG/student transitions are supported by code/tests; dashboard-vs-SLE-FHE presentation collapses distinct non-verified states).

## 2. Audit Scope and Constraints

**In scope (inspected read-only):**
- Dashboard: `app/Http/Controllers/DashboardController.php`, `resources/views/dashboard.blade.php`, `routes/web.php` (student dashboard route).
- SLE-FHE student flow: `app/Http/Controllers/Student/SleVerificationController.php`, `resources/views/student/sle-verification.blade.php`, `resources/views/student/profile.blade.php`, `app/Http/Requests/Student/SleFheRequestSubmissionRequest.php` (referenced by controller), routes in `routes/web.php`.
- Models/enums: `app/Models/StudentProfile.php`, `app/Models/User.php` (identity context via usage), `app/Models/SleFheRequest.php`, `app/Models/SleFheVerification.php`, `app/Models/SleFheRejection.php`, `app/Models/SleFheRegistry.php`, `app/Enums/SleFheStatus.php`.
- FASSG workflow/controllers/views: `app/Http/Controllers/Fassg/VerificationController.php`, `resources/views/fassg/sle-fhe/index.blade.php`, `resources/views/fassg/sle-fhe/verified.blade.php`, FASSG routes context in `routes/web.php`.
- Schema/migrations: `database/migrations/2026_09_22_000001_add_sle_fhe_status_and_relax_registration_address.php`, `2026_09_22_000002_create_sle_fhe_requests_table.php`, `2026_09_22_000003_create_sle_fhe_verifications_table.php`, `2026_09_22_000004_create_sle_fhe_rejections_table.php`, `2026_09_22_000005_cleanup_student_profiles_registration_columns.php`, `2026_09_28_000001_create_sle_fhe_registries_table.php`.
- Tests/config: `phpunit.xml`, `.env.testing` (absent), `tests/Feature/StudentSleFheVerificationTest.php`, `tests/Feature/FassgSleFheWorkflowTest.php`, `tests/Feature/StudentRegistrationTest.php`, and targeted term/search review across `tests/`.

**Searched terms (actual):** `unverified`, `pending`, `verified`, `rejected`, `not_verified`, `sle_fhe_status`, `verification_status`, `sle_fhe_requests`, `sle_fhe_verifications`, `sle_fhe_rejections` (plus `sle_fhe_registries`/SLE-FHE identifiers as needed). No `not_verified` storage token and no `verification_status` column/token were found in app/model/controller code; UI uses “Not Verified” label text.

**Constraints honored:** No source/config/route/model/controller/middleware/UI/DB changes, no migrations/seeders/resets, no verification-request or FASSG-decision mutations, no commits/pushes. Read-only DB inspection performed via aggregate queries only; no real student personal information included. Tests limited to isolated, non-destructive suites under sqlite `:memory:` config.

**Limitations:** Audit did not modify or visually re-capture screenshots; runtime behavior beyond inspected code, executed tests, and aggregate DB checks is not claimed. No middleware/policy files defining SLE-FHE-specific authorization logic were identified by SLE-FHE term search in `app/Http/Middleware` or `app/Policies` (paths searched; policies directory not present per tool result); authorization observed in controllers/routes where applicable (e.g. FASSG role abort in `verifyStudent`, route middleware groups).

## 3. Methodology

1. **Static/code trace:** Located SLE-FHE models, enum, controllers, views, routes, migrations, and tests; read relevant sections with exact line-number context.
2. **Term-based search:** Grepped requested status/field/table terms across `app`, `resources/views`, `tests`, and migrations to identify determination, display, and transition points.
3. **Schema/relationship tracing:** Derived effective schema behavior from migrations and Eloquent relations/casts/accessors.
4. **Read-only DB inspection:** Executed aggregate-only tinker queries (counts and status distributions; no row-level PII selection) against configured local database.
5. **Test isolation and execution:** Verified `phpunit.xml` isolation and absence of `.env.testing`; ran targeted SLE-FHE/student-registration feature tests only.
6. **Comparison:** Compared `/student/dashboard` and `/student/sle-fhe` presentation against the same derived accessor and evaluated icon/color semantics.

## 4. System Inventory

| Area | File(s) | Role |
|---|---|---|
| Enum | `app/Enums/SleFheStatus.php:5-10` | Canonical status values: `Unverified`, `Pending Review`, `Verified`, `Rejected` |
| Student profile model | `app/Models/StudentProfile.php:101-136,198-201,225-234` | Relations to request/verification/rejections; derived `sle_fhe_status`; documented-verification checks/scopes |
| Request model | `app/Models/SleFheRequest.php:16-50` | Address/request record, `status` string, `submitted_at`, `full_address` accessor |
| Verification model | `app/Models/SleFheVerification.php:16-41` | Verification evidence row (`verified_address`, `verified_by`, `verified_at`) |
| Rejection model | `app/Models/SleFheRejection.php:16-41` | Rejection/fix-request history (`rejection_reason`, `rejected_by`, `rejected_at`) |
| Registry model | `app/Models/SleFheRegistry.php:16-47` | Advisory masterlist lookup (`student_id`, `is_verified`, location/course fields) |
| Student controller | `app/Http/Controllers/Student/SleVerificationController.php:22-161` | Show page, profile save, municipality API, submit verification request |
| FASSG controller | `app/Http/Controllers/Fassg/VerificationController.php:45-47,53,119-122,154-249,286-336,412-444` | Pending counts/queue, SLE-FHE index/verified views, verify/reject student, pending-request query |
| Dashboard controller | `app/Http/Controllers/DashboardController.php:26-91,119-122,146-155` | Student dashboard data + SLE-FHE-related metrics; FASSG dashboard counts |
| Student dashboard view | `resources/views/dashboard.blade.php:55-76,95-114` | Derives display from `studentProfile->sle_fhe_status`; SLE-FHE metric card |
| Student SLE-FHE view | `resources/views/student/sle-verification.blade.php:45-50,61-95,223-234,304-314,397-455` | Four-state UI logic, lock/submit states, status card and messaging |
| Student profile view | `resources/views/student/profile.blade.php:20,42-52` | Displays raw derived `sle_fhe_status` badge |
| FASSG SLE-FHE views | `resources/views/fassg/sle-fhe/index.blade.php`, `resources/views/fassg/sle-fhe/verified.blade.php` | Queue and verified masterlist presentation |
| Routes | `routes/web.php:99,103-110` | Student dashboard/SLE-FHE/verification endpoints |
| Migrations | `database/migrations/2026_09_22_000001..0005*.php`, `2026_09_28_000001_create_sle_fhe_registries_table.php` | SLE-FHE tables, status-column lifecycle, registry schema |
| Tests | `tests/Feature/StudentSleFheVerificationTest.php`, `tests/Feature/FassgSleFheWorkflowTest.php`, `tests/Feature/StudentRegistrationTest.php`, `tests/Feature/FassgDashboardCounterRegressionTest.php`, `tests/Feature/TenStudentWorkflowRegressionTest.php` | Lifecycle, queue, derivation, transitions, counters (subset executed) |

## 5. Schema and Relationship Findings

### 5.1 Tables and relevant columns (from migrations/models)

| Table | Key columns/indexes/constraints observed | Evidence |
|---|---|---|
| `users` | Identity/auth record referenced by `student_profiles.user_id` and verification/rejection actor FKs (`verified_by`, `rejected_by`); identity display may also come from profile fields (see `StudentProfile::getFullNameAttribute()` fallback to `user.name`). | `app/Models/StudentProfile.php:50-67`; `app/Models/SleFheVerification.php:38-41`; `app/Models/SleFheRejection.php:38-41` |
| `student_profiles` | Holds student identity/program/campus fields; `sle_fhe_status` column was added in 2026_09_22_000001 (default `Unverified`, backfilled from legacy flag) and later dropped in 2026_09_22_000005 as redundant because status is derived. No stored authoritative SLE-FHE status column in current intended schema. | `database/migrations/2026_09_22_000001_add_sle_fhe_status_and_relax_registration_address.php:49-63,67-71`; `2026_09_22_000005_cleanup_student_profiles_registration_columns.php:27-50` |
| `sle_fhe_requests` | `student_profile_id` FK (cascade delete), `province/municipality_city/barangay/street_purok` nullable strings, `status` string(30) default `pending`, `submitted_at` nullable timestamp; indexes on `status` and `(student_profile_id,status)`. | `2026_09_22_000002_create_sle_fhe_requests_table.php:11-26`; `app/Models/SleFheRequest.php:16-34` |
| `sle_fhe_verifications` | `student_profile_id` FK (cascade delete), `verified_address` nullable, `verified_by` nullable FK to users (nullOnDelete), `verified_at` nullable; **unique(`student_profile_id`)**. | `2026_09_22_000003_create_sle_fhe_verifications_table.php:11-25`; `app/Models/SleFheVerification.php:16-31` |
| `sle_fhe_rejections` | `student_profile_id` FK (cascade delete), `rejection_reason` nullable text, `rejected_by` nullable FK, `rejected_at` nullable; index on `student_profile_id` (history-capable, many rows possible). | `2026_09_22_000004_create_sle_fhe_rejections_table.php:11-25`; `app/Models/SleFheRejection.php:16-31` |
| `sle_fhe_registries` | Masterlist keyed by unique `student_id`; address/course/campus/district fields; `is_verified` boolean default true; indexes on municipality/campus/district. Advisory lookup source, not student-owned request/verification state. | `2026_09_28_000001_create_sle_fhe_registries_table.php:17-36`; `app/Models/SleFheRegistry.php:16-47` |

### 5.2 Eloquent relationships

| Relationship | Definition | Notes |
|---|---|---|
| `StudentProfile::sleFheRequest()` | `hasOne(SleFheRequest::class)->latestOfMany()` | “Current/latest” request by framework default (id/max) (`app/Models/StudentProfile.php:101-104`) |
| `StudentProfile::sleFheRequests()` | `hasMany(SleFheRequest::class)` | Full request history (`106-109`) |
| `StudentProfile::sleFheVerification()` | `hasOne(SleFheVerification::class)` | At most one row by DB unique constraint (`111-114`, migration 000003:24) |
| `StudentProfile::sleFheRejections()` | `hasMany(SleFheRejection::class)` | Rejection history (`116-119`) |
| `SleFheVerification::verifiedBy()` / `SleFheRejection::rejectedBy()` | `belongsTo(User::class, 'verified_by'/'rejected_by')` | Actor attribution; nullable (`SleFheVerification.php:38-41`, `SleFheRejection.php:38-41`) |

### 5.3 Observed request/verification status values in code and DB

| Domain | Values observed | Evidence |
|---|---|---|
| `SleFheStatus` enum (derived profile state) | `Unverified`, `Pending Review`, `Verified`, `Rejected` | `app/Enums/SleFheStatus.php:7-10` |
| `sle_fhe_requests.status` written by code | `pending`, `superseded`, `verified`, `rejected` | `Student/SleVerificationController.php:153-156`; `Fassg/VerificationController.php:318,426`; migration default `pending` `2026_09_22_000002:20` |
| `sle_fhe_requests.status` in DB (aggregate) | `pending` (1), `rejected` (1), `verified` (7) | Read-only tinker aggregate |
| `sle_fhe_verifications` completeness in DB | 9 rows total, 9 with `verified_by` and `verified_at` non-null, 0 incomplete | Read-only tinker aggregate |

## 6. Status Lifecycle Trace

### 6.1 Student submission and resubmission

- **Submit request (`POST /student/sle-fhe/request`)**: Locks profile row; blocks if **any** `sleFheVerification` row exists (`Student/SleVerificationController.php:147-151`). Supersedes all currently pending requests (`status='pending' -> 'superseded'`) and creates a new request with `status='pending'` and `submitted_at=now()` (`153-157`). Redirects with success status (`160`).
- **Historical preservation:** Prior request rows are retained; prior pending rows are dispositioned as `superseded` rather than deleted (`153`, `tests/Feature/StudentSleFheVerificationTest.php:54-75`).
- **Resubmission after rejection:** Allowed when no verification row exists; creates a fresh pending request and derived status becomes Pending Review (`tests/Feature/StudentSleFheVerificationTest.php:131-143` and `tests/Feature/StudentRegistrationTest.php:60-83` executed; the equivalent end-to-end path is also covered by executed `tests/Feature/TenStudentWorkflowRegressionTest.php:770-805`).

### 6.2 FASSG queue selection and verification

- **Pending SLE-FHE queue source:** `pendingSleFheRequestQuery()` selects `sle_fhe_requests` with `status='pending'` and **no `sle_fhe_verifications` row at all** for the profile (`Fassg/VerificationController.php:435-444`). `sleFheIndex()` lists these requests with profile/user/program context (`154-183`).
- **General FASSG verification page counters/listing:** Includes pending SLE-FHE student profiles as those without `sleFheVerification` and with a pending request (`37-67,119-122,144-145`), and separately handles application verification items constrained to profiles that have `sleFheVerification` (`95,105-113`).
- **Approve/verify:** Requires FASSG actor, selected pending request (`verification_request_id`) with `submitted_at`, complete identity/address evidence, and registry match or explicit documented override; aborts if any verification row exists; creates `sle_fhe_verifications` row (actor, timestamp, verified address), marks request `verified`, records audit (`286-336`). Notification sent as outcome `verified` (`333`, `app/Notifications/SleFheVerificationUpdated.php:31` context observed).
- **Reject / return for correction:** Requires reason; aborts if any verification row exists; requires a pending request (latest by id) to exist; creates `sle_fhe_rejections` record and marks that pending request `rejected`; audits and notifies `fix_required` (`412-433`).

### 6.3 Student-facing display flow

- **Dashboard (`/student/dashboard`)**: Loads `studentProfile` and computes `$isVerified = ($studentProfile?->sle_fhe_status ?? null) === 'Verified'`; metric displays `Verified` or `Pending` only (`app/Http/Controllers/DashboardController.php:27,76-90`; `resources/views/dashboard.blade.php:75,95-114`).
- **SLE-FHE page (`/student/sle-fhe`)**: Loads profile/request/verification context and computes `isVerified`, `isPending` (Pending Review), `isRejected`, `isEditable` from derived status; renders distinct banners, badges, lock states, and actions (`Student/SleVerificationController.php:22-69`; `resources/views/student/sle-verification.blade.php:45-50,70-95,223-234,304-314,399-455`).
- **Profile page:** Displays `SLE-FHE: {{ $studentProfile?->sle_fhe_status ?? 'Unverified' }}` with per-state badge styling (`resources/views/student/profile.blade.php:20,42-52`).

## 7. Authoritative State Determination

| Question | Determination | Evidence |
|---|---|---|
| Is `student_profiles.sle_fhe_status` authoritative? | **No stored authoritative column in current design.** Status is computed by accessor from related tables; migration 000005 removes the earlier stored column. | `app/Models/StudentProfile.php:121-136`; `database/migrations/2026_09_22_000001_add_sle_fhe_status_and_relax_registration_address.php:49-63`; `2026_09_22_000005_cleanup_student_profiles_registration_columns.php:27-50` |
| What makes a profile Verified? | Existence of a **documented** verification row: `sle_fhe_verifications.verified_by` and `verified_at` both non-null. | `app/Models/StudentProfile.php:123-125,198-201`; `tests/Feature/FassgSleFheWorkflowTest.php:481-490` |
| What makes a profile Pending Review? | No documented verification and the profile’s latest request (`sleFheRequest()` via `latestOfMany()`, max id) has `status='pending'`. | `app/Models/StudentProfile.php:101-104,127-129`; Eloquent one-of-many max-id behavior (`vendor/laravel/framework/src/Illuminate/Database/Eloquent/Relations/Concerns/CanBeOneOfMany.php:70-142,152-157`) |
| What makes a profile Rejected? | No documented verification, not pending per above check, and at least one rejection history row exists. | `app/Models/StudentProfile.php:131-133`; `tests/Feature/StudentRegistrationTest.php:85-94`, `tests/Feature/FassgSleFheWorkflowTest.php:152-180` |
| Default/fallback? | `Unverified` when none of the above conditions hold. | `app/Models/StudentProfile.php:135`; `tests/Feature/StudentRegistrationTest.php:19-47,60-65` |
| Registry role? | `sle_fhe_registries` is an advisory FASSG match/override input during verification; it does not itself set derived student status. | `app/Http/Controllers/Fassg/VerificationController.php:307-311,341-350`; `database/migrations/2026_09_28_000001_create_sle_fhe_registries_table.php:10-14` |

**Edge condition (code-observed):** `sleFheVerification()->exists()` (any row) is used to block student submission, block FASSG re-verification, and exclude from pending queue (`Student/SleVerificationController.php:149`, `Fassg/VerificationController.php:298,417,442`), while “certified/Verified” status requires a **documented** verification row. An actorless/incomplete verification row can therefore be preserved yet non-certifying and workflow-blocking; current DB has 0 incomplete verification rows.

## 8. Request Resolution and Precedence Findings

| Mechanism | Behavior | Evidence |
|---|---|---|
| Latest request selection | `StudentProfile::sleFheRequest()` uses `latestOfMany()`, which Eloquent implements as a max-id subquery. The pending status condition applies to that highest-id request, not to arbitrary historical rows. | `app/Models/StudentProfile.php:101-104`; `vendor/laravel/framework/src/Illuminate/Database/Eloquent/Relations/Concerns/CanBeOneOfMany.php:70-142,152-157`; verified generated SQL via read-only tinker |
| New submission | All `pending` requests become `superseded`; a new `pending` request is inserted. | `Student/SleVerificationController.php:153-156` |
| FASSG verification success | Selected pending request becomes `verified`; verification row created. | `Fassg/VerificationController.php:312-318` |
| FASSG rejection/return | Latest pending request (by id) becomes `rejected`; rejection history row created. | `Fassg/VerificationController.php:418-427` |
| Precedence in derived status | Documented verification > latest request (max id) is pending > any rejection exists > unverified. | `app/Models/StudentProfile.php:121-136`; `CanBeOneOfMany.php:70-142,152-157` |
| Historical vs active | Requests are historical records with mutable disposition (`pending/superseded/verified/rejected`); verifications are unique-per-profile evidence rows; rejections accumulate as history. | Migrations 000002/0003/0004; controller transitions above |
| Multiple pending requests | Prevented in normal submit flow by superseding all pending before insert; DB currently shows 0 multi-request profiles and 0 pending+verification/rejection overlaps. | `Student/SleVerificationController.php:153-156`; DB aggregates |

**Historical-precedence note:** Pending Review is based on the profile’s highest-id request, not “any pending request”. This is protective: an older pending row cannot override a newer rejected/verified/superseded disposition. The corresponding caveat is that “latest” follows `id`, not `submitted_at`; if requests were ever imported/backfilled with IDs out of chronological order, the selected request might not be the most recently submitted. No such anomaly is evidenced in current aggregates (0 multi-request profiles), and normal submissions create monotonically newer IDs.

## 9. Dashboard vs. SLE-FHE Page Comparison

| Aspect | `/student/dashboard` | `/student/sle-fhe` | Consistency |
|---|---|---|---|
| Route / controller | `routes/web.php:99` → `DashboardController@index/__invoke` (`app/Http/Controllers/DashboardController.php:17-91`) | `routes/web.php:104` (+ aliases 103,107) → `Student\SleVerificationController@show` (`app/Http/Controllers/Student/SleVerificationController.php:22-69`) | Different endpoints/views |
| View | `resources/views/dashboard.blade.php` | `resources/views/student/sle-verification.blade.php` | Different |
| Source of truth used | `($studentProfile?->sle_fhe_status ?? null) === 'Verified'` (`dashboard.blade.php:75`) | `$profile?->sle_fhe_status` mapped to Verified/Pending Review/Rejected/else editable (`sle-verification.blade.php:46-50`) | Same underlying accessor, different mapping |
| Verified presentation | Label “Verified”, green/emerald styling, subtext “Eligible to apply for open sponsorships” (`dashboard.blade.php:100-110`) | Verified banner/badge/locked form/profile-locked state, emerald `bi-shield-check` (`sle-verification.blade.php:71-84,223-228,399-403,421-426,444-448`) | Consistent intent |
| Pending Review presentation | **Shown as “Pending”** (amber) with subtext “Awaiting FASSG masterlist review” (`dashboard.blade.php:106-109`) | Distinct “Pending Review/Under Review/Awaiting FASSG Review” states, amber/clock, form locked under review (`sle-verification.blade.php:229-234,239-251,313,405-408,427-432,450-454`) | **Partially inconsistent label granularity** (dashboard uses generic “Pending”) |
| Rejected presentation | **Falls into non-verified branch → shown as “Pending”** on metric (`dashboard.blade.php:75,106-109`) | Distinct rejected state: “Previous verification request not verified”, editable/resubmittable, not-verified status card context (`sle-verification.blade.php:49-50,86-95,304-308,433-439`) | **Inconsistent** |
| Unverified/no-request presentation | **Falls into non-verified branch → shown as “Pending”** on metric (`dashboard.blade.php:75,106-109`) | Distinct unverified/editable state with “Not Verified” badge and submit CTA (`sle-verification.blade.php:50,304-308,409-413,434-439`) | **Inconsistent** |
| Icon semantics (metric/status block) | Dashboard metric icon fixed to `bi-shield-check` in both states (`dashboard.blade.php:101`); color changes | SLE-FHE uses `bi-shield-check` (verified), `bi-clock` (pending), `bi-shield` (not verified) in status block (`sle-verification.blade.php:402,407,412,423`) | **Inconsistent icon usage vs requested semantics on dashboard** |

## 10. UI/UX Findings

| ID | Severity | Type | Finding | Evidence |
|---|---|---|---|---|
| UIUX-1 | **High** | UI/UX, business-logic presentation | Dashboard SLE-FHE metric collapses all non-verified derived states (`Unverified`, `Pending Review`, `Rejected`) into label **“Pending”** with amber styling and “Awaiting FASSG masterlist review” subtext. A student with no request or a rejected (returned for correction) state can appear as pending on `/student/dashboard` while `/student/sle-fhe` shows “Not Verified” or rejected/resubmittable. | `resources/views/dashboard.blade.php:75,100,105-109`; contrast `resources/views/student/sle-verification.blade.php:46-50,86-95,304-308,409-413,433-439` |
| UIUX-2 | **Medium** | UI/UX | Dashboard metric icon remains `bi-shield-check` regardless of state, instead of reflecting state semantics (shield/clock/shield-check). | `resources/views/dashboard.blade.php:101` vs `resources/views/student/sle-verification.blade.php:402,407,412` |
| UIUX-3 | **Low** | UI/UX | `student/profile.blade.php` displays raw derived status including `Pending Review` but provides no contextual guidance/action parity with SLE-FHE page; not inconsistent with accessor, but less descriptive than SLE-FHE page. | `resources/views/student/profile.blade.php:20,42-52` |
| UIUX-4 | **Low** | UI/UX (informative) | SLE-FHE page includes explicit banner when a non-certified verification row exists (`!$isVerified && $profile?->sleFheVerification`), which helps explain preserved-evidence edge cases. | `resources/views/student/sle-verification.blade.php:61-68` |
| UIUX-5 | **Low** | UI/UX | On `/student/sle-fhe`, the primary status badge uses “Not Verified” for both Rejected and Unverified; the rejected outcome is distinguished by a separate alert rather than by the badge label. This is not a false Verified/Pending state, but the badge alone does not preserve the “Rejected” wording. | `resources/views/student/sle-verification.blade.php:86-95,433-439` |

## 11. Business Logic Findings

| ID | Severity | Type | Finding | Evidence |
|---|---|---|---|---|
| BL-1 | **Medium** | Business logic / data integrity | “Any verification row exists” gates block student submission, FASSG re-verification, and queue inclusion, while “Verified” status requires a **documented** verification (`verified_by` and `verified_at` non-null). An undocumented/actorless verification row can leave a profile non-verified yet unable to proceed through normal request/verification flows without external correction/review path. | `app/Models/StudentProfile.php:121-125,198-201`; `app/Http/Controllers/Student/SleVerificationController.php:149-151`; `app/Http/Controllers/Fassg/VerificationController.php:298-300,417,442-443`; `tests/Feature/FassgSleFheWorkflowTest.php:481-490`; DB aggregate: 0 incomplete verification rows currently |
| BL-2 | **Low** | Business logic (robustness) | “Latest request” is determined by max `id`, not `submitted_at`. Normal submissions create monotonically newer IDs, and historical pending rows cannot override newer dispositions; however, imported/backfilled data with IDs out of chronological order could select a request that is not the most recently submitted. Current DB shows no multi-request profiles or overlapping pending states. | `app/Models/StudentProfile.php:101-104,127-129`; `vendor/laravel/framework/src/Illuminate/Database/Eloquent/Relations/Concerns/CanBeOneOfMany.php:70-142,152-157`; DB aggregates |
| BL-3 | **Low** | Business logic (separation of concerns) | FASSG dashboard metric `verified_sle_fhe` counts profiles with any `sleFheVerification` row **or** applications in certain statuses, which is broader than “documented SLE-FHE verified student” and differs from SLE-FHE status semantics. This can confuse interpretation but is outside student status lifecycle correctness. | `app/Http/Controllers/DashboardController.php:146-155` |
| BL-4 | **Low (latent)** | Business logic / data integrity | FASSG pending counts, pending lists, and pending-request query use “any pending request exists” (`whereHas`/`where status='pending'`), while student-derived Pending Review uses only the latest request by max id. Under normal flows this is equivalent because submission supersedes all pending rows. If an anomalous multi-request data shape left an older pending row after a newer rejection, FASSG queue/counts could still surface the profile as pending while the student status is Rejected. `FassgDashboardCounterRegressionTest.php:42-61` covers the normal rejected-then-pending resubmission shape (queue includes the profile because the new pending request is latest) but does not cover the inverse pending-then-rejected stale shape. Current DB shows 0 multi-request profiles, so this is not observed. | `app/Http/Controllers/Fassg/VerificationController.php:45-47,60-64,119-122,435-444`; `app/Models/StudentProfile.php:101-104,127-129`; `app/Http/Controllers/Student/SleVerificationController.php:153`; `tests/Feature/FassgDashboardCounterRegressionTest.php:42-61`; DB aggregates |

## 12. Data Integrity Findings

| ID | Severity | Type | Finding | Evidence |
|---|---|---|---|---|
| DI-1 | **Low (current state) / Medium (risk)** | Data integrity | DB enforces one verification row per profile (`unique student_profile_id`) but allows nullable actor/timestamp fields; code distinguishes existence vs documented completeness. Current data contains no incomplete verification rows, no pending+verification or pending+rejection overlaps, no rejection+verification overlaps, and no verified request lacking a complete verification. Derived distribution matches the accessor: 9 `Verified`, 1 `Pending Review`, 1 `Rejected`, 4 `Unverified`. | `2026_09_22_000003_create_sle_fhe_verifications_table.php:16-24`; DB aggregates (verifications 9/9 complete; all listed overlap counts 0) |
| DI-2 | **Low** | Data integrity | Rejections are historical (many rows possible); derived status treats “any rejection exists” as Rejected only when not documented-verified and not pending by accessor logic. This preserves history but means historical rejection remains relevant unless superseded by pending/verified states. | `2026_09_22_000004_create_sle_fhe_rejections_table.php:24`; `app/Models/StudentProfile.php:131-135`; DB aggregates (1 rejection/profile) |
| DI-3 | **Informational** | Data integrity observation | Request dispositions observed in DB (`pending`, `rejected`, `verified`) align with controller transitions; `superseded` exists in code path but not present in current aggregate snapshot. | `Student/SleVerificationController.php:153`; `Fassg/VerificationController.php:318,426`; DB request status aggregate |

## 13. Test Isolation Assessment

| Item | Result | Evidence |
|---|---|---|
| `phpunit.xml` present and inspected | Yes | `phpunit.xml:1-36` |
| Test DB isolation configured | **Yes (isolated by config):** `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `DB_URL` empty, array cache/session/mail, sync queue, bcrypt rounds reduced. | `phpunit.xml:20-35` |
| `.env.testing` present | **No** (`File not found`) | Read attempt result during audit |
| Risk of live DB mutation from configured phpunit tests | **Low / not indicated** by inspected config; sqlite in-memory is used for test runtime. | `phpunit.xml:26-28` |
| Tests executed (non-destructive, isolated) | `tests/Feature/StudentSleFheVerificationTest.php`, `tests/Feature/FassgSleFheWorkflowTest.php`, `tests/Feature/StudentRegistrationTest.php`, `tests/Feature/FassgDashboardCounterRegressionTest.php`, `tests/Feature/TenStudentWorkflowRegressionTest.php` | `php artisan test ...` → **58 passed, 350 assertions** |
| Additional relevant tests statically inspected and then executed | `FassgDashboardCounterRegressionTest.php:20-62` covers queue/counter alignment for pending, rejected-only, previously verified, and rejected-then-pending resubmission shapes; `TenStudentWorkflowRegressionTest.php:523-579` covers submit → `Pending Review`, reject → request `rejected` + profile `Rejected`, queue removal, and resubmission form unlock; `:770-805` covers rejected → resubmit → `Pending Review`. Both files passed in this run. Other identified suites (`FassgVerificationQueueTest`, `StudentDashboardTest`) were not executed to limit scope/time. | `tests/Feature/FassgDashboardCounterRegressionTest.php:20-62`; `tests/Feature/TenStudentWorkflowRegressionTest.php:523-579,770-805`; executed test output |

## 14. Scenario Matrix

| Scenario | Actual Dashboard | Actual Verification Page | Database/Code Basis | Expected | Result |
|---|---|---|---|---|---|
| No verification request, no verification, no rejection (Unverified) | Displays **“Pending”** (amber) / “Awaiting FASSG masterlist review” in SLE-FHE metric for any non-verified derived state | Displays **Not Verified** state, editable form, “Request Verification” available | `StudentProfile::getSleFheStatusAttribute()` returns `Unverified` (`StudentProfile.php:135`); dashboard maps non-verified to “Pending” (`dashboard.blade.php:75,106-109`); SLE-FHE maps else/unverified (`sle-verification.blade.php:50,304-308,409-413,434-439`); DB: 6 profiles with no request exist (aggregate) | Unverified should not appear Pending; pages consistent | **FAIL** |
| Pending request exists, no documented verification (Pending Review) | Displays **“Pending”** (amber) | Displays **Pending Review / Under Review / Awaiting FASSG Review**, form locked | Derived status Pending Review (`StudentProfile.php:127-129`); submit creates pending (`Student/SleVerificationController.php:153-156`); SLE-FHE pending UI (`sle-verification.blade.php:48,229-234,239-251,313,427-432`); tests passed (`StudentSleFheVerificationTest.php:26-52,145-169`) | Pending on both, with request locked under review | **PASS** (label wording differs: “Pending” vs “Pending Review/Awaiting…”) |
| Documented verification exists (Verified) | Displays **“Verified”** (emerald) with eligible subtext | Displays verified/recorded status, profile locked, verified badge (`bi-shield-check`) | Documented verification required (`StudentProfile.php:123-125,198-201`); FASSG creates verification and marks request verified (`Fassg/VerificationController.php:312-318`); dashboard/SLE-FHE verified branches (`dashboard.blade.php:75,105-109`; `sle-verification.blade.php:47,71-84,223-228,421-426`); DB: 9 complete verifications | Verified on both | **PASS** |
| FASSG does not verify / returns request for correction (Rejected outcome) | Displays **“Pending”** (amber) because non-verified branch is used | Displays **Previous verification request not verified**; state editable/resubmittable (Rejected/else-editable path), not locked as pending/verified | `rejectStudent` creates rejection and sets request to rejected (`Fassg/VerificationController.php:418-433`); derived status Rejected when no pending/latest-pending and rejection exists (`StudentProfile.php:131-135`); SLE-FHE rejected banner and editable form (`sle-verification.blade.php:49,86-95,304-308,433-439`); tests passed (`FassgSleFheWorkflowTest.php:152-180`, registration derivation) | Rejected/not-verified outcome visible and distinct from pending | **FAIL** (dashboard shows “Pending”; SLE-FHE page itself presents the rejected/resubmittable state correctly) |
| Resubmission after rejection/unsuccessful request | Displays **“Pending”** (amber) after new pending state (same collapsed label as before) | Returns to pending/under-review locked state after submit | Submit supersedes pending and creates new pending request (`Student/SleVerificationController.php:153-156`); derived status becomes Pending Review when pending exists and no documented verification (`StudentProfile.php:127-129`); executed tests passed (`StudentSleFheVerificationTest.php:54-75`, `StudentRegistrationTest.php:60-83`); the equivalent end-to-end path is also covered by executed `TenStudentWorkflowRegressionTest.php:770-805` | Status becomes Pending Review | **PASS** (dashboard already labels non-verified states as “Pending”, so no distinct label change is expected there) |
| Verification row exists but undocumented (actorless/incomplete) | Would display **“Pending”** (not verified) if encountered | Shows preserved-evidence warning banner when verification row exists but not verified; submit blocked server-side; FASSG queue excludes/verify blocked by row existence | `StudentProfile.php:123-125,198-201` vs existence checks in `Student/SleVerificationController.php:149`, `Fassg/VerificationController.php:298,417,442`; tests passed (`FassgSleFheWorkflowTest.php:481-490`); DB: 0 incomplete rows currently | Should be handled as a review/correction edge case without false “Verified” | **NOT VERIFIED** in current DB (no instances); behavior evidenced by code/tests |
| Historical request + newer disposition (multi-request data shape) | N/A (no observed instance) | N/A | History supported; normal flow supersedes/updates statuses; `sleFheRequest()` is `latestOfMany()` (max-id subquery) and pending derivation checks that highest-id request (`StudentProfile.php:101-104,127-129`; `CanBeOneOfMany.php:70-142,152-157`). FASSG queue/counts instead use “any pending request” (`Fassg/VerificationController.php:45-47,60-64,119-122,435-444`). DB: 0 multi-request profiles | Latest effective state must not be overridden incorrectly by history; queue/counts should agree with derived state | **NOT VERIFIED** for anomalous multi-request data shapes (not observed; normal-flow resubmission covered by executed tests; latent BL-4) |

## 15. Prioritized Corrections

*Priorities listed for remediation planning only; no fixes applied in this audit.*

1. **P1 – High (UI/UX / presentation correctness):** Align `/student/dashboard` SLE-FHE metric with derived states (`Unverified`, `Pending Review`, `Rejected`, `Verified`) instead of collapsing all non-verified states to “Pending”. Ensure no-request and rejected students are not labeled pending. (Evidence: `resources/views/dashboard.blade.php:75,105-109`).
2. **P2 – Medium (UI/UX semantics):** Use state-appropriate iconography on dashboard metric consistent with SLE-FHE semantics (`bi-shield` slate for unverified/rejected-not-verified context as applicable, `bi-clock` amber for pending review, `bi-shield-check` emerald for verified) and harmonize wording/colors. (Evidence: `resources/views/dashboard.blade.php:101`; `resources/views/student/sle-verification.blade.php:402,407,412`).
3. **P3 – Medium (Business logic/data integrity edge case):** Define and implement an explicit handling path for verification rows that exist but are not documented/certified (e.g. FASSG review/correction workflow) so profiles cannot become non-certifiable and non-progressable through normal student/FASSG actions. (Evidence: `StudentProfile.php:121-125,198-201`; `Student/SleVerificationController.php:149`; `Fassg/VerificationController.php:298,417,442`; `tests/Feature/FassgSleFheWorkflowTest.php:481-490`).
4. **P4 – Low (Robustness):** Confirm that “latest request” should remain max-`id` based rather than `submitted_at` based, and align FASSG pending queue/count predicates with the same effective-request rule (or explicitly guarantee no stale pending rows) so anomalous multi-request data cannot create queue/status disagreement. Add/retain regression coverage if imported/backfilled requests could have non-chronological IDs. (Evidence: `StudentProfile.php:101-104,127-129`; `Fassg/VerificationController.php:45-47,60-64,119-122,435-444`; DB shows no current multi-request anomaly).
5. **P5 – Low (Clarity):** Consider clarifying FASSG dashboard `verified_sle_fhe` metric naming/scope to distinguish “SLE-FHE verified” from application-progression-based counts. (Evidence: `app/Http/Controllers/DashboardController.php:146-155`).

## 16. Final Assessment

**Explicit answers**

1. **Does SponsorFlow currently distinguish Unverified, Pending, and Verified correctly?**  
   **Partially.** The derived status accessor and SLE-FHE page distinguish `Unverified`, `Pending Review`, `Verified` (and `Rejected`) correctly per code/tests (`app/Models/StudentProfile.php:121-136`; `resources/views/student/sle-verification.blade.php:45-50,399-439`; executed tests passed). The student dashboard SLE-FHE metric does **not** distinguish Unverified from Pending (it labels all non-verified states as “Pending”) (`resources/views/dashboard.blade.php:75,105-109`).

2. **Can a student with no verification request incorrectly appear Pending?**  
   **Yes, on `/student/dashboard`.** A profile with derived `Unverified` (no request/verification/rejection) is rendered as “Pending” by the dashboard metric. On `/student/sle-fhe`, the same state is shown as not verified/editable. (Evidence as above.)

3. **What happens to the displayed status after FASSG does not verify a request?**  
   FASSG’s reject/return action creates a rejection record and marks the pending request `rejected` (`app/Http/Controllers/Fassg/VerificationController.php:418-427`). The derived profile status becomes **Rejected** when no pending/documented-verified state applies (`app/Models/StudentProfile.php:131-135`, tests). SLE-FHE page displays the returned-for-correction/rejected state and allows resubmission (`resources/views/student/sle-verification.blade.php:86-95,304-308,433-439`); dashboard metric displays **“Pending”** (collapsed non-verified label) (`resources/views/dashboard.blade.php:75,105-109`).

4. **Does resubmitting a previously unsuccessful request correctly produce Pending?**  
   **Yes, by code and executed tests.** Submission supersedes existing pending requests and creates a new pending request; derived status resolves to **Pending Review** absent documented verification (`app/Http/Controllers/Student/SleVerificationController.php:153-156`; `app/Models/StudentProfile.php:127-129`; executed `tests/Feature/StudentSleFheVerificationTest.php:54-75` and `tests/Feature/StudentRegistrationTest.php:60-83`; the equivalent end-to-end path is also covered by executed `tests/Feature/TenStudentWorkflowRegressionTest.php:770-805`).

5. **Do the Student Dashboard and SLE-FHE Verification page use consistent status determination?**  
   **No, not in presented outcome.** Both reference the derived `sle_fhe_status` conceptually, but dashboard maps only Verified vs non-verified to “Verified/Pending”, while SLE-FHE maps four states distinctly. This yields inconsistent user-facing status for Unverified and Rejected cases (`dashboard.blade.php:75,105-109` vs `sle-verification.blade.php:45-50,399-439`).

6. **Can historical requests incorrectly override the current effective verification state?**  
   **Not for the student-facing derived status under normal flows; the design is protective there.** Documented verification has highest precedence, and Pending Review checks only the profile’s highest-id request (max-id subquery), so older historical pending rows cannot override a newer rejected/verified/superseded disposition. Current DB shows no multi-request/overlap anomalies. Caveats: (a) “latest” follows `id`, not `submitted_at`, so imported/backfilled data with out-of-order IDs would require review; (b) FASSG pending queue/count queries use “any pending request” and could surface a stale historical pending row under anomalous data, even though the student-facing status would not be Pending (see BL-4; no current instances). (`app/Models/StudentProfile.php:101-104,121-129`; `app/Http/Controllers/Fassg/VerificationController.php:45-47,60-64,119-122,435-444`; `vendor/laravel/framework/src/Illuminate/Database/Eloquent/Relations/Concerns/CanBeOneOfMany.php:70-142,152-157`; DB aggregates).

7. **Are the problems exclusively UI/UX, or do they require business-logic corrections?**  
   **Primarily UI/UX/presentation inconsistencies (dashboard mapping/icons)**, with **potential business-logic/data-integrity correction** warranted for the undocumented-verification-row edge case (exists-but-not-certified blocking behavior). Core Verified/Pending/Rejected/Unverified derivation and tested transitions are otherwise consistent.

8. **Which corrections should be prioritized before refining the interface?**  
   Prioritize **P1** (correct dashboard state mapping so Unverified/Rejected are not shown as Pending) before cosmetic refinement, then address **P3** (undocumented verification-row handling) to prevent edge-case workflow dead-ends; P2/P4/P5 can follow (see Prioritized Corrections).

## 17. Final Conclusion

**PARTIAL PASS — Status lifecycle is correct, but presentation inconsistencies exist**

*Rationale:* Core SLE-FHE lifecycle derivation and request/verification/rejection transitions are implemented in code and supported by isolated executed tests (58 passed, 350 assertions) with no contradictory anomalies observed in read-only DB aggregates. However, `/student/dashboard` presents all non-verified states as “Pending”, creating a confirmed inconsistency with `/student/sle-fhe` (and allowing no-request/unverified or rejected students to appear pending on the dashboard). This is a presentation inconsistency with user-facing status implications, alongside lower-severity edge-case handling concerns noted above.
