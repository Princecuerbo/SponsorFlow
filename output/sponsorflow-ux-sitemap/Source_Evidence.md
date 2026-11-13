# UX sitemap evidence and scope

Prepared through source inspection only. No endpoints, database queries, migrations, seeders, or application tests were executed. No application source files were modified.

## Interpretation

- The top-level portal and five role headings are navigation groupings, not invented screens. Staff and administrator login pages are designated entry points; the figure does not claim the public landing page links to those protected entrances.
- Student Registration returns to Student Login. The current StudentLoginController and PortalLoginController open SLE-FHE Verification after authentication; this is not an automatic verification decision. The consent interaction is part of Student Login.
- The active layout includes partials._navbar. The old sidebar/UserRole menu definitions contain legacy links and are not the authoritative current top navigation.
- Sponsor Approvals Queue filters assigned programs and opens submitted Generated Batch details. A standalone Sponsor Programs or individual-approval workflow is not invented from legacy routes.
- Beneficiary details, approved batch references, and protected document viewers are children of Accounting’s Confirmed Beneficiaries area. Print and CSV export are controls, not separate management modules.
- FASSG Create Batch is a modal in the Application Queue. Program and finalized Fixed List shortcuts reach this same interface. A Fixed List does not directly submit to a Sponsor or forward to Accounting.
- New Fixed List, request decisions, and Add/Edit User are dialogs or forms. Review/member detail screens and program create/edit screens retain their own routes.
- The small captions summarize key page actions; they do not guarantee every action is available for every status or attempt to catalog every HTTP endpoint.
- Programs/Create or Edit and List Details/Edit Draft combine closely related screens for a readable high-level map. Their underlying routes remain separate.
- Sign Out is one shared authenticated action, repeated availability represented by a footer.

## Existing wording limitation

The Sponsor dashboard contains a descriptive “Forward to Accounting” step. Actual forwarding remains a separate FASSG action in generated batch details. The sitemap follows the authorized route and action, not that misleading dashboard description. No source correction was made as part of documentation work.

## Validation limits

Route names, source file existence, unique node identifiers, XML syntax, text fit, and image dimensions are checked without bootstrapping Laravel. The PNG is visually inspected. This does not constitute browser or runtime navigation testing. The SVG is editable; the PDF contains the high-resolution raster diagram.

## Source references

- `routes/web.php`
- `app/Enums/UserRole.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/partials/_navbar.blade.php`
- `resources/views/welcome.blade.php`
- `app/Http/Controllers/Auth/StudentLoginController.php`
- `app/Http/Controllers/Auth/StaffLoginController.php`
- `app/Http/Controllers/Auth/AdminLoginController.php`
- `app/Http/Controllers/Auth/PortalLoginController.php`
- `resources/views/student/sle-verification.blade.php`
- `resources/views/student/profile.blade.php`
- `resources/views/fassg/programs/index.blade.php`
- `resources/views/fassg/applications/index.blade.php`
- `resources/views/fassg/fixed_lists/index.blade.php`
- `resources/views/fassg/fixed_lists/show.blade.php`
- `resources/views/fassg/generated_batches/index.blade.php`
- `resources/views/fassg/generated_batches/show.blade.php`
- `resources/views/sponsor/approvals/index.blade.php`
- `resources/views/sponsor/approvals/history.blade.php`
- `resources/views/sponsor/lists/show.blade.php`
- `resources/views/accounting/index.blade.php`
- `resources/views/accounting/show.blade.php`
- `resources/views/accounting/beneficiaries/show.blade.php`
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/settings/index.blade.php`
- `resources/views/admin/backups/index.blade.php`
