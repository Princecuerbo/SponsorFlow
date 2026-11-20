# SponsorFlow Student Portal — UI/UX Refinement Report

**Date:** 2026-11-19
**Target routes:** `/student/dashboard`, `/student/sle-fhe`, `/student/applications`, `/student/applications/{application}`
**Scope:** UI/UX and front-end presentation only.

---

## 1. Executive summary

The four Student Portal screens were refined without changing any controller, model, migration, route, permission, business rule, form action, form value, file path, preview/download link, progress milestone, or database record.

| Area | Outcome |
|---|---|
| Production files changed | 4 Blade views updated, 1 new scoped Blade component |
| Actual UI issues addressed | 14 (see §3) |
| Responsive checkpoints | 8 required viewports, all 4 pages → **0 px page-level horizontal overflow**, 0 off-canvas elements outside intentional table scrollers |
| Automated tests | `php artisan test --filter=Student` → **167 passed, 1162 assertions, exit 0** |
| Front-end build | `npm run build` → **success**, 57 modules transformed |
| Screenshots | 20 files captured/copied into `output/student-portal-ui-refinement/` (§9) |
| Unresolved data inconsistencies | 4 documented in §10; none fabricated or silently patched |

---

## 2. Scope discipline

**Changed (production):**

- `resources/views/dashboard.blade.php`
- `resources/views/student/sle-verification.blade.php`
- `resources/views/student/applications/index.blade.php`
- `resources/views/student/applications/show.blade.php`
- `resources/views/components/student-application-status-badge.blade.php` (new)

**Explicitly not changed:** controllers, models, enums, migrations, database records, `routes/web.php`, middleware, permissions, eligibility/approval logic, document storage, `components/status-badge.blade.php` (shared component left intact), `components/metric-card.blade.php` (shared component left intact), `layouts/app.blade.php`, and `partials/student/*`.

**Pre-existing uncommitted work preserved:** auth views, `partials/_navbar.blade.php`, `student/applications/create.blade.php`, `student/programs/*`, `welcome.blade.php`, and the original `scratch/` contents were not reverted, overwritten, or included in this refinement. All new CSS is page-scoped (`@push('styles')` with `sf-dash-*`, `sf-sle-*`, `sf-apps-*`, `sf-appdetail-*` prefixes); no global Bootstrap overrides were introduced.

---

## 3. Actual issues found and fixed

| # | Page | Issue observed in the baseline | Fix applied |
|---|---|---|---|
| 1 | Dashboard | Table headers were all-caps, bold, and lacked `scope="col"`; screen readers could not associate header cells. | Sentence-case labels (`Program`, `Submitted`, `Status`, `Action`) with `scope="col"` on all four headers. |
| 2 | Dashboard | Row action said only “View”; icon had no `aria-hidden`. | `bi-eye` (outline) + “View Details”; icon hidden from assistive tech. |
| 3 | Dashboard | Empty state used the generic label “Browse Programs” and a decorative shadow. | “Browse Sponsorship Opportunities” with `bi-search`; shadow removed for a flatter institutional look. |
| 4 | Dashboard / Applications | Shared `x-status-badge` rendered `Ongoing` in indigo, inconsistent with the emerald used for active/approved states elsewhere. | New student-only badge component maps in-flight → amber, approved/ongoing/confirmed → emerald, rejected/expired → red. Shared component untouched. |
| 5 | SLE-FHE | Filled icons (`bi-lock-fill`, `bi-patch-check-fill`, `bi-person-badge`, `bi-card-checklist`) clashed with the outline icon language used in the rest of the portal. | Replaced with `bi-lock`, `bi-shield-check`, `bi-person-vcard`, `bi-info-circle`; all decorative icons `aria-hidden`. |
| 6 | SLE-FHE | No dedicated presentation for `Rejected` verification status, even though the model supports it. | Added a non-blocking “Previous verification request not verified” notice; form stays editable, matching existing workflow. |
| 7 | SLE-FHE | Campus fallback displayed the invented value “Not Assigned”. | Replaced with an em dash (—) plus `aria-label="Campus not recorded"`; added a footnote explaining that a dash means the value is not recorded. |
| 8 | SLE-FHE | Success copy implied that verification equals sponsorship approval. | Reworded to “verification confirms eligibility only; FASSG review and sponsor approval are recorded separately.” |
| 9 | SLE-FHE | Disabled address fields had no recognisable read-only treatment and no association with the lock notice. | Added scoped `.sf-readonly-field` styling and `aria-describedby="sf-sle-lock-notice"` on all four disabled controls. |
| 10 | Applications | Card radius (0.5rem) did not match the 14px student-portal card token. | Scoped `.sf-apps-card` uses 14px radius and `#E1E8EC` border, matching the shared student card treatment. |
| 11 | Applications | Active-sponsorship alert used an award icon and wording that could be read as a new grant. | Changed to “Active sponsorship in effect” with `bi-award`, `role="status"`, and an explanation that new applications are disabled until it expires. |
| 12 | Applications | Uppercase header cells, inconsistent action button, and global badge colours. | Sentence-case `scope="col"` headers, `bi-eye` + “View Details”, student-specific status badge. |
| 13 | Application detail | Uneven document rows and unbounded long filenames (up to ~250 characters) distorted the list. | Equal 92px desktop / 128px mobile row heights, 2-line clamp, `title` + `aria-label` with the full filename, `tabindex="0"` for keyboard reading. |
| 14 | All four pages | Keyboard-focused action links had **no visible focus indicator** (measured: `outline-style: none`, `box-shadow: none`). | Added scoped `:focus-visible` outlines (`3px solid #0F2942`, 2px offset) on action links, empty-state links, and clamped filenames. Re-measured: `outline-style: solid`, 3px, `rgb(15, 41, 66)`. |

---

## 4. Before / after comparison

| Page | Before (baseline screenshots / code) | After (captured state) |
|---|---|---|
| Dashboard | Three metric cards with mixed icon fills; uppercase table headers; terse “View” action; generic empty-state CTA. | Three equal-width cards at 1920–1366 px (512/406/352/328 px wide, 159 px tall) using `bi-file-earmark-text`, `bi-award`, `bi-shield-check`; sentence-case headers; “View Details” with `bi-eye`; explicit empty state. |
| SLE-FHE | Filled lock/check icons; “Not Assigned” campus; no rejected-state message; “eligible” wording repeated; disabled fields visually flat. | Outline icon set; em dash + explanatory note for missing campus; rejected-state notice; eligibility-vs-approval wording; read-only field styling and lock-notice association. All exact test-critical strings preserved (`Verification Status: Verified - Profile Locked`, `Awaiting FASSG Review`, `Profile Locked`, `Request Verification`, under-review copy). |
| Applications | 0.5rem card radius; uppercase headers; global badge colours; “View” action; active-sponsorship alert phrased ambiguously. | 14px radius card (`rgb(225,232,236)` border), 11.52px / 700 / 0.576px-tracked headers, amber emerald red student badge tones, “View Details”, clarified active-sponsorship notice. Empty state verified on a real zero-application account. |
| Application detail | Progress labels and icons inconsistent; rurality/campus labels ambiguous; long filenames expanded rows; Program Summary and Back button loosely aligned. | Four-step progress preserved verbatim (`Application Submitted`, `FASSG Application Review`, `Sponsor Batch Approval`, `Ongoing`) with standardized icons; `Address Classification` label with Rural/Urban or em dash; equal document rows with clamped filenames; aligned summary rows and full-width Back button. Document preview links unchanged (`/documents/31` … `/documents/35` in the captured sample). |

---

## 5. Responsive verification (actually executed)

Captured with headless Chrome via CDP at all eight requested viewports on all four pages. “Overflow” is `document.documentElement.scrollWidth - clientWidth`; “off-canvas” counts visible elements outside the viewport **excluding** descendants of `.table-responsive` (intentional horizontal scrollers).

| Viewport | Dashboard | SLE-FHE | Applications | Application detail | Extra states |
|---|---|---|---|---|---|
| 1920×1080 | 0 px / 0 | 0 px / 0 | 0 px / 0 | 0 px / 0 | empty apps 0/0, verified SLE 0/0 |
| 1600×900 | 0 px / 0 | 0 px / 0 | 0 px / 0 | 0 px / 0 | — |
| 1440×900 | 0 px / 0 | 0 px / 0 | 0 px / 0 | 0 px / 0 | — |
| 1366×768 | 0 px / 0 | 0 px / 0 | 0 px / 0 | 0 px / 0 | — |
| 768×1024 | 0 px / 0 | 0 px / 0 | 0 px / 0 | 0 px / 0 | — |
| 430×932 | 0 px / 0 | 0 px / 0 | 0 px / 0 | 0 px / 0 | — |
| 390×844 | 0 px / 0 | 0 px / 0 | 0 px / 0 | 0 px / 0 | empty apps 0/0, verified SLE 0/0 |
| 360×800 | 0 px / 0 | 0 px / 0 | 0 px / 0 | 0 px / 0 | — |

### Key measured geometry

| Viewport | Dashboard metric cards (W×H) | Dashboard table row / action | Applications card / row | Document rows (5) | Back button |
|---|---|---|---|---|---|
| 1920×1080 | 512×159 ×3 | 62 px / 107×29 px | 14px radius / 59 px | 999×92 px | 512×35 px |
| 1600×900 | 406×159 ×3 | 62 px / 107×29 px | 14px radius / 59 px | 785×92 px | 406×35 px |
| 1440×900 | 352×159 ×3 | 62 px / 107×29 px | 14px radius / 59 px | 679×92 px | 352×35 px |
| 1366×768 | 328×159 ×3 | 62 px / 107×29 px | 14px radius / 59 px | 629×92 px | 328×35 px |
| 768×1024 | 229×174 ×3 | 81 px / 85×48 px | 14px radius / 72 px | 686×92 px | 736×35 px |
| 430×932 | 398×136, 398×136, 398×157 | 81 px / 73×48 px | 14px radius / 93 px | 364×128 px | 398×35 px |
| 390×844 | 358×136, 358×136, 358×157 | 81 px / 73×48 px | 14px radius / 93 px | 324×128 px | 358×35 px |
| 360×800 | 328×136, 328×136, 328×157 | 81 px / 73×48 px | 14px radius / 93 px | 294×128 px | 328×35 px |

Additional observations:

- Metric values render at 28px consistently across all viewports; the verification card is 21px taller on mobile because its explanatory line wraps — cards are stacked there, so this does not misalign adjacent content.
- The four-step progress labels are present at every viewport; no step label is clipped.
- Long document filenames clamp to exactly 2 lines at desktop and mobile narrow widths, with the complete value retained in `title` and `aria-label`. At 1920 px one long sample is genuinely 2 lines and not visually truncated; at ≤1600 px the same value is visually clamped but fully accessible.
- The empty applications state measured 1598×339 px at 1920 px and 356×357 px at 390 px, with heading “No applications yet” and CTA “Browse Sponsorship Opportunities”.

### Table actions at narrow widths

At ≥768 px, “View Details” is fully inside the viewport. Below that, both tables use Bootstrap’s `.table-responsive` with `overflow-x: auto`:

| Page @390 px | Container client / scroll width | Action before scroll | Action after scrolling container |
|---|---|---|---|
| Dashboard | 356 / 479 px | left 398, right 472 (off-screen) | left 275, right 349 — fully visible |
| Applications | 356 / 606 px | left 488, right 599 (off-screen) | left 238, right 349 — fully visible |

The actions are therefore **scroll-reachable, not clipped or hidden**; page-level horizontal overflow remains 0 px. This is a residual UX limitation of preserving the existing table layout at ≤430 px (see §10).

---

## 6. Accessibility and keyboard verification

| Check | Result |
|---|---|
| Table header semantics | All dashboard and applications headers use `scope="col"` |
| Decorative icons | `aria-hidden="true"` applied across refined icons |
| Status meaning | Student badge includes a visually hidden description, e.g. “Pending — Awaiting FASSG review”; `title` mirrors it |
| Locked SLE-FHE fields | 4 disabled controls carry `.sf-readonly-field` and `aria-describedby="sf-sle-lock-notice"`; computed disabled style: `rgb(248,250,252)` background, `rgb(203,213,225)` border, `rgb(51,65,85)` text, `not-allowed` cursor |
| Notices | Success/lock/rejection notices use `role="status"` where appropriate |
| Clamped filenames | `tabindex="0"`, full filename in `title` and `aria-label` |
| Keyboard focus | Real `Tab` keypress reaches the applications “View Details” link; computed focus style after fix: `outline-style: solid`, `outline-width: 3px`, `outline-color: rgb(15,41,66)` |
| Workflow/form integrity | All form actions, methods, field names, file inputs, `required`/`disabled` conditions, and four progress milestones preserved |

---

## 7. Automated verification (actually executed)

### Tests

```powershell
php artisan test --filter=Student
```

Result: **exit 0 — Tests: 167 passed (1162 assertions), Duration 7.14s** (re-run after the focus-style fix).

Suites exercised (all PASS): `Tests\Unit\DatabaseCorrectionTest`, `FixedListEndorsementTest`, `RegistryImportPreflightTest`, `SleFheRegistrySupportTest`, `StudentAcademicProgramValidationTest`, and feature suites `AuthenticationTest`, `FassgBatchDraftWorkflowTest`, `FassgSleFheWorkflowTest`, `FassgVerificationQueueTest`, `FixedListTest`, `FullSystemCampaignWorkflowTest`, `MaintenanceModeTest`, `PortalAuthenticationTest`, `RoleAuthorizationTest`, `SessionTimeoutRedirectTest`, `SleFheRegistryDataPreparationTest`, `StudentDashboardTest`, `StudentFassgModulesTest`, `StudentNotificationsRedesignTest`, `StudentPortalSidebarLayoutTest`, `StudentRegistrationTest`, `StudentSleFheVerificationTest`, `TenStudentWorkflowRegressionTest`.

Notably, `StudentSleFheVerificationTest` confirms every preserved exact string and the editable/pending/verified lock behaviour; `StudentFassgModulesTest` confirms application-detail records and documents are unchanged; `StudentDashboardTest` confirms dashboard cards and timeline regression safety.

### Build

```powershell
npm run build
```

Result: **success** — Vite 7.3.6, 57 modules transformed, `public/build/assets/app-BkIqcSvF.css` (39.74 kB), `public/build/assets/app-C5nR2xjt.js` (50.61 kB), built in 1.16s.

### Browser verification helpers

Verification was performed with the local server at `http://127.0.0.1:8001` and Chrome at `C:\Program Files\Google\Chrome\Application\chrome.exe`, using scripts under `scratch/` (`verify_student_ui.py`, `summarize_student_ui.py`, `verify_student_interactions.py`). Raw measurements: `scratch/student_ui_verification.json`; readable summary: `scratch/student_ui_summary.txt`.

---

## 8. Screenshots

All files below are under `output/student-portal-ui-refinement/` (copied from the capture artifact directory so the report is self-contained). Before captures are 1920×1080 and 390×844; after captures use the same dimensions unless noted.

| Page / state | Before (desktop) | Before (mobile) | After (desktop) | After (mobile) |
|---|---|---|---|---|
| Dashboard | `before-student-dashboard-desktop.png` | `before-student-dashboard-mobile.png` | `after-student-dashboard-1920x1080.png` | `after-student-dashboard-390x844.png` |
| SLE-FHE (verified) | `before-student-sle-fhe-desktop.png` | `before-student-sle-fhe-mobile.png` | `after-student-sle-fhe-1920x1080.png` | `after-student-sle-fhe-390x844.png` |
| My Applications (populated) | `before-student-applications-desktop.png` | `before-student-applications-mobile.png` | `after-student-applications-1920x1080.png` | `after-student-applications-390x844.png` |
| Application detail | `before-student-application-show-desktop.png` | `before-student-application-show-mobile.png` | `after-student-detail-1920x1080.png` | `after-student-detail-390x844.png` |

Additional after-state captures: `after-student-applications-empty-1920x1080.png`, `after-student-applications-empty-390x844.png`, `after-student-sle-fhe-verified-1920x1080.png`, `after-student-sle-fhe-verified-390x844.png`.

**Screenshot limitation:** images were captured successfully but were not visually inspected by this model. All visual-state claims in this report are based on DOM text, computed styles, and geometry measurements, plus the recorded before/after artifacts for human review.

---

## 9. Unresolved data inconsistencies (documented, not altered)

1. **Identity fields are split across two sources.** For `student@sponsorflow.test`, `users.name` is `"Maria Santos"` and `users.first_name` is `NULL`, while `student_profiles.first_name`, `middle_name`, `last_name`, `extension_name`, and `campus` are all `NULL`. The navbar therefore renders “Maria Santos” while profile-oriented cards have no name components to display. No name was invented or copied between sources; this needs a data-source decision outside the agreed scope.
2. **Campus is genuinely absent.** Because `student_profiles.campus` is null, the refined views render an em dash with an accessible “Campus not recorded” label instead of the previous fabricated “Not Assigned”. Fixing the stored value is a data task, not a UI change.
3. **Live data does not exercise all status tones.** All 7 applications in the local database are `Pending`. The emerald `Ongoing`/`Approved`/`Confirmed` and red `Rejected`/`Expired` badge tones were verified only through the passing test suite and component mapping, not through live screenshots.
4. **Rejected / unverified SLE-FHE visual states were not captured.** The rejected account (`princelinebert.cuerbo@dorsu.edu.ph`) and pending account (`rhea@dorsu.edu.ph`) have unknown passwords, and resetting them would modify data. The rejected-state notice exists and is covered by `StudentSleFheVerificationTest`, but only the verified lock state was visually captured.
5. **Stored filenames are long and repetitive.** The sample application contains filenames such as `FORM 9-37-CUERBO ET AL.-FACET-BSIT - Copy…pdf` repeated several times. Presentation is now clamped with full values exposed via `title`/`aria-label`; the stored names and file links were not touched.
6. **Narrow-width tables still require horizontal scrolling.** At ≤430 px the six-column/five-column table content is 479/606 px wide inside a 356 px scroller. Actions are reachable and fully visible after scrolling, but converting tables to stacked cards would be a larger structural change outside this presentation pass.

---

## 10. Reproduction

```powershell
# From C:\xampp\htdocs\SponsorFlow
php artisan test --filter=Student
npm run build
python scratch/verify_student_ui.py
python scratch/summarize_student_ui.py
python scratch/verify_student_interactions.py
```

The scripts require the local Laravel server on port 8001 and the installed Chrome/Python `websockets` package; they are auxiliary verification artifacts under `scratch/` and are not used by the application.
