# Source review and validation

These are documentation artifacts. No Laravel bootstrap, endpoints, database queries, migrations, seeders, or runtime feature tests were executed by the renderer.

The diagram is a use-case view, not an activity diagram. It deliberately does not turn chronological approval handoffs into include relationships.

The Word package is structurally validated; pagination in desktop Microsoft Word has not been visually verified. The diagram must also receive visual review.

## Implementation references

- **UC-01 — Login / Authenticate**: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`; `routes/web.php`
- **UC-02 — Register Student Account**: `app/Http/Controllers/Auth/RegisteredUserController.php`; `resources/views/auth/register.blade.php`
- **UC-03 — View Available Programs**: `app/Http/Controllers/Student/ApplicationController.php`; `resources/views/student/programs/index.blade.php`
- **UC-04 — Submit Sponsorship Application**: `app/Http/Controllers/Student/ApplicationController.php`; `app/Http/Requests/Student/StoreApplicationRequest.php`
- **UC-05 — Apply After Previous Sponsorship Expires**: `app/Http/Controllers/Student/ApplicationController.php`; `app/Models/StudentProfile.php`; `resources/views/student/applications/show.blade.php`
- **UC-06 — Track Application Status**: `app/Http/Controllers/Student/ApplicationController.php`; `app/Enums/ApplicationStatus.php`; `resources/views/student/applications/show.blade.php`
- **UC-07 — Manage Sponsorship Programs**: `app/Http/Controllers/Fassg/ProgramManagementController.php`; `app/Http/Requests/Fassg/StoreSponsorshipProgramRequest.php`; `app/Http/Requests/Fassg/UpdateSponsorshipProgramRequest.php`
- **UC-08 — Verify SLE-FHE Eligibility**: `app/Http/Controllers/Fassg/VerificationController.php`; `app/Http/Controllers/Student/SleVerificationController.php`
- **UC-09 — Encode / Import Sponsor Fixed List**: `app/Http/Controllers/Fassg/FixedListController.php`; `app/Http/Requests/Fassg/StoreFixedListItemRequest.php`; `app/Models/FixedListItem.php`
- **UC-10 — Upload Required Documents**: `app/Http/Requests/Student/StoreApplicationRequest.php`; `app/Http/Controllers/Student/ApplicationController.php`
- **UC-11 — Configure Program Eligibility Criteria**: `app/Http/Requests/Fassg/StoreSponsorshipProgramRequest.php`; `app/Http/Requests/Fassg/UpdateSponsorshipProgramRequest.php`; `app/Models/SponsorshipProgram.php`
- **UC-12 — Finalize Fixed List**: `app/Http/Controllers/Fassg/FixedListController.php`; `app/Models/FixedListItem.php`
- **UC-13 — Manually Prepare Generated Batch**: `app/Http/Controllers/Fassg/ApplicantVerificationController.php`; `app/Http/Controllers/Fassg/GeneratedBatchController.php`; `app/Models/BatchCandidate.php`
- **UC-14 — Submit Batch to Sponsor**: `app/Http/Controllers/Fassg/GeneratedBatchController.php`; `app/Models/GeneratedBatch.php`
- **UC-15 — Review Submitted Generated Batches**: `app/Http/Controllers/Sponsor/ReviewController.php`; `routes/web.php`
- **UC-16 — Approve or Reject Entire Submitted Batch**: `app/Http/Controllers/Sponsor/ApprovalUploadController.php`; `app/Http/Controllers/Sponsor/ReviewController.php`; `app/Models/GeneratedBatch.php`
- **UC-17 — Upload Sponsor Confirmation Evidence**: `app/Http/Controllers/Sponsor/ApprovalUploadController.php`; `app/Http/Requests/Sponsor/StoreApprovalDocumentRequest.php`
- **UC-18 — View Forwarded Approved Beneficiary Records**: `app/Http/Controllers/Accounting/ReferenceController.php`; `app/Models/BatchCandidate.php`; `app/Models/Application.php`
- **UC-19 — Print / Export Beneficiary References**: `app/Http/Controllers/Accounting/ReferenceController.php`; `resources/views/accounting/index.blade.php`
- **UC-20 — Generate Summary Reports**: `app/Http/Controllers/Fassg/ReportsController.php`; `routes/web.php`
- **UC-21 — Manage User Accounts and Roles**: `app/Http/Controllers/Admin/UserController.php`; `routes/web.php`
- **UC-22 — View Audit Logs and Manage Backups**: `app/Http/Controllers/Admin/AuditLogController.php`; `app/Http/Controllers/Admin/BackupController.php`
- **UC-23 — Request SLE-FHE Verification**: `app/Http/Controllers/Student/SleVerificationController.php`; `app/Http/Requests/Student/SleFheRequestSubmissionRequest.php`
- **UC-24 — Verify Batch Member Qualifications and Documents**: `app/Http/Controllers/Fassg/ApplicantVerificationController.php`; `app/Http/Controllers/Fassg/GeneratedBatchController.php`; `app/Http/Requests/Fassg/VerifyApplicationRequest.php`
- **UC-25 — Record Receipt of Sponsor Decision**: `app/Http/Controllers/Fassg/GeneratedBatchController.php`; `app/Models/SponsorApproval.php`
- **UC-26 — Forward Approved Beneficiaries to Accounting**: `app/Http/Controllers/Fassg/GeneratedBatchController.php`; `app/Models/SponsorApproval.php`; `app/Models/BatchCandidate.php`
