# SponsorFlow UX Sitemap ? Landing Page and RBAC revision

The supplied diagram was revised as documentation only. Existing role pages, menu hierarchy, colors, and vertical legend were preserved.

## Changes
- Removed the conceptual SponsorFlow Entry Points root and duplicate Public Home box; Landing Page is now the public root.
- Kept Landing Page links to Student Login and Student Registration, and registration returning to login.
- Staff/Sponsor Login and Administrator Login remain separate designated URL entrances. Hidden layout links do not imply landing-page hyperlinks.
- Added one explicitly marked system-control box for authentication/role checks. It is not a screen or a role-selection menu.
- Student Login accepts student accounts; Staff/Sponsor Login accepts FASSG, Sponsor, and Accounting; Administrator Login accepts administrator accounts.
- RBAC grants only the account's assigned role branch. There is no blanket administrator access to other roles' modules.
- Accounting remains read-only; underlying ownership, eligibility, record status, and handoff guards remain additional requirements.
- Changed the gray legend label from All Users to Entry Pages.
- Restored short explanatory footer notes, including shared Sign Out availability.

## Correction to the earlier student-login note
The normal JavaScript login/consent completion path currently redirects to student.dashboard (PortalLoginController::complete and the student login view). The direct POST login handler routes students to student.verification.show. Therefore this diagram shows the Student navigation group without asserting one universal initial page. No application behavior was changed.

## Source evidence
- resources/views/welcome.blade.php: public student login and registration links; no staff/admin login hyperlinks.
- app/Http/Controllers/Auth/StudentLoginController.php
- app/Http/Controllers/Auth/StaffLoginController.php
- app/Http/Controllers/Auth/AdminLoginController.php
- app/Http/Controllers/Auth/PortalLoginController.php
- app/Http/Middleware/CheckRole.php
- app/Http/Middleware/EnsureUserRole.php
- routes/web.php

## Validation
Static text checks confirmed preserved role-page declarations, removal of the old root, valid connection targets, and five RBAC branches. No PlantUML rendering was executed. No database access, application endpoint execution, or application-source changes were performed.
