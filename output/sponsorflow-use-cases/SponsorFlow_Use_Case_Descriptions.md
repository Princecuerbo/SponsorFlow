# SponsorFlow — Corrected Use Case Descriptions

The Use Case Diagram presents the interactions of five actors with SponsorFlow: Student Applicant, FASSG Personnel, Sponsor, Accounting Office Personnel, and System Administrator. Students register, request SLE-FHE verification, browse open programs, submit applications and documents, and monitor their status. FASSG manages programs and Fixed Lists, manually prepares Generated Batches, reviews member qualifications, submits the exact roster, records Sponsor decisions, and explicitly forwards eligible approved beneficiaries. Sponsors review submitted batches and approve or reject each entire batch. Accounting views and exports eligible forwarded beneficiary references without editing beneficiary records. The System Administrator manages accounts, audit-log access, and backup operations. Associations identify actor participation; include and extend relationships represent reused or conditional behavior rather than chronological handoffs.

## Common interpretation

- UC-01 to UC-22 retain the original numbering with corrected names and behavior. UC-23 to UC-26 add the missing request, member-review, receipt, and forwarding interactions.
- An Active account is distinct from documented SLE-FHE verification. A Verified application is distinct from Sponsor approval.
- Application statuses: Pending, Verified, Approved, Rejected, Ongoing, Expired, and Resubmission Requested. Batch membership is not a separate application status.
- Generated Batch statuses: Saved, Submitted, Approved, and Rejected. FASSG receipt and Accounting forwarding are separate recorded actions, not additional batch-status labels.
- A Saved batch may contain ordinary Pending/Verified applicants, the complete explicitly selected finalized Fixed List roster, or both. Draft creation and source-list finalization do not consume slots.
- Fixed List members need no separate online application. Internal processing records preserve source provenance and do not manufacture grades, documents, or verification.
- Authentication is a precondition for protected functions. Includes/extends do not represent the chronological Sponsor-to-FASSG-to-Accounting handoff.
- For Accounting, read-only means beneficiary and approval records cannot be edited; an authorized export may still create an audit entry.

## Use case index

| ID | Use case | Primary actor |
|---|---|---|
| UC-01 | Login / Authenticate | All five user roles |
| UC-02 | Register Student Account | Student Applicant |
| UC-03 | View Available Programs | Student Applicant |
| UC-04 | Submit Sponsorship Application | Student Applicant |
| UC-05 | Apply After Previous Sponsorship Expires | Student Applicant |
| UC-06 | Track Application Status | Student Applicant |
| UC-07 | Manage Sponsorship Programs | FASSG Personnel |
| UC-08 | Verify SLE-FHE Eligibility | FASSG Personnel |
| UC-09 | Encode / Import Sponsor Fixed List | FASSG Personnel |
| UC-10 | Upload Required Documents | Student Applicant |
| UC-11 | Configure Program Eligibility Criteria | FASSG Personnel |
| UC-12 | Finalize Fixed List | FASSG Personnel |
| UC-13 | Manually Prepare Generated Batch | FASSG Personnel |
| UC-14 | Submit Batch to Sponsor | FASSG Personnel |
| UC-15 | Review Submitted Generated Batches | Sponsor |
| UC-16 | Approve or Reject Entire Submitted Batch | Sponsor |
| UC-17 | Upload Sponsor Confirmation Evidence | Sponsor |
| UC-18 | View Forwarded Approved Beneficiary Records | Accounting Office Personnel |
| UC-19 | Print / Export Beneficiary References | Accounting Office Personnel |
| UC-20 | Generate Summary Reports | FASSG Personnel |
| UC-21 | Manage User Accounts and Roles | System Administrator |
| UC-22 | View Audit Logs and Manage Backups | System Administrator |
| UC-23 | Request SLE-FHE Verification | Student Applicant |
| UC-24 | Verify Batch Member Qualifications and Documents | FASSG Personnel |
| UC-25 | Record Receipt of Sponsor Decision | FASSG Personnel |
| UC-26 | Forward Approved Beneficiaries to Accounting | FASSG Personnel |

## Table 3.24: Login / Authenticate

| Field | Content |
|---|---|
| Use case ID | UC-01 |
| Use case name | Login / Authenticate |
| Primary actor | All five user roles |
| Secondary actor | None |
| Description | Authenticates an active user and opens the dashboard assigned to the user's role. |
| Preconditions | The user has an existing active account and is not currently authenticated. |
| Trigger | The user opens the login page and submits credentials. |
| Main flow | 1. The system displays the email and password fields.<br>2. The user enters their email and password and submits the form.<br>3. The system validates the credentials and account status.<br>4. The system regenerates the session and determines the user's role.<br>5. The system redirects the user to the Student, FASSG, Sponsor, Accounting, or Administrator dashboard. |
| Alternative flows | 1. The user selects Remember me to request persistent authentication. |
| Exception flows | 1. Invalid credentials or an inactive account prevent authentication and produce a validation message.<br>2. A credential-verification failure returns an error without granting access. |
| Postconditions | An authenticated session exists with role-restricted access. No SLE-FHE verification or sponsorship decision is created. |
| Includes / extends | None. Authentication is a precondition for protected use cases, not a login interaction repeated within each use case. |

## Table 3.25: Register Student Account

| Field | Content |
|---|---|
| Use case ID | UC-02 |
| Use case name | Register Student Account |
| Primary actor | Student Applicant |
| Secondary actor | None |
| Description | Creates a student account and profile using validated identity information and the official academic-program catalog. |
| Preconditions | The student does not already have an account using the submitted institutional email or Student ID. |
| Trigger | The student opens the registration form. |
| Main flow | 1. The system displays the registration form and active undergraduate academic programs.<br>2. The student supplies identity and contact details, institutional email, Student ID, campus, year level, academic program, birthdate, and password confirmation.<br>3. The student accepts the required accuracy and privacy consent and submits the form.<br>4. The system validates required values, Student ID format, institutional email, password, duplicates, and the academic-program selection.<br>5. The system creates an Active student account and linked profile, storing the selected catalog program and canonical course name.<br>6. The system redirects to Login with a registration-success message. |
| Alternative flows | 1. The student supplies optional middle-name or name-extension information. |
| Exception flows | 1. Duplicate identifiers, invalid fields, missing consent, or an inactive, postgraduate, or nonexistent program prevent registration. |
| Postconditions | The account and profile exist. The student remains unverified for SLE-FHE until FASSG separately records legitimate verification. |
| Includes / extends | None. UC-01 is a subsequent action, not an included automatic login. |

## Table 3.26: View Available Programs

| Field | Content |
|---|---|
| Use case ID | UC-03 |
| Use case name | View Available Programs |
| Primary actor | Student Applicant |
| Secondary actor | None |
| Description | Displays open sponsorship opportunities and their requirements to a verified student. |
| Preconditions | The student is authenticated and has documented SLE-FHE verification for access to the student program catalog. |
| Trigger | The student opens Sponsorship Opportunities. |
| Main flow | 1. The system checks the student's SLE-FHE verification.<br>2. The system retrieves open sponsorship programs and related Sponsor and academic-program information.<br>3. The system displays program availability, criteria, and requirements.<br>4. The student reviews an opportunity and may proceed to its application form, where eligibility is checked. |
| Alternative flows | 1. The student searches by program or Sponsor name or filters by program category. |
| Exception flows | 1. An unverified student sees a verification-required state.<br>2. An empty result displays a no-programs-found message. |
| Postconditions | The student has viewed available opportunities. Displaying a program does not certify eligibility or reserve a slot. |
| Includes / extends | None. Application submission is handled by UC-04. |

## Table 3.27: Submit Sponsorship Application

| Field | Content |
|---|---|
| Use case ID | UC-04 |
| Use case name | Submit Sponsorship Application |
| Primary actor | Student Applicant |
| Secondary actor | None; FASSG reviews the stored application in a later interaction. |
| Description | Submits an ordinary student application and applicable supporting evidence for an open program. |
| Preconditions | The student is authenticated, has documented SLE-FHE verification, satisfies applicable program checks, has no conflicting active sponsorship, and is not barred by an existing application or prior rejection for the same program. The application period is open. |
| Trigger | The student selects Apply for an open program. |
| Main flow | 1. The system checks application access and displays the form.<br>2. The student reviews profile details and enters the current GPA/GWA, address, rurality declaration, and any required employee-relative information.<br>3. At the document attachment step, UC-10 supplies attachments when required or provided.<br>4. The student submits the form.<br>5. The system validates fields, program eligibility, and applicable required files.<br>6. The system saves the application and documents and returns a submission confirmation. |
| Alternative flows | 1. Employee-based program requirements request the relevant relationship and evidence.<br>2. UC-05 reuses this submission flow after the student's previous sponsorship has expired. |
| Exception flows | 1. An active sponsorship, duplicate active/pending application, prior rejection for the same program, closed period, failed criterion, or missing/invalid required evidence blocks submission. |
| Postconditions | A Pending application exists for later FASSG selection and review. Submission does not verify or approve the applicant or reserve a slot. |
| Includes / extends | Included by UC-05. Extended by UC-10 at Attach supporting documents when documents are required or supplied. No student application Save as draft feature is represented. |

## Table 3.28: Apply After Previous Sponsorship Expires

| Field | Content |
|---|---|
| Use case ID | UC-05 |
| Use case name | Apply After Previous Sponsorship Expires |
| Primary actor | Student Applicant |
| Secondary actor | None |
| Description | Reuses ordinary application submission when a previous sponsorship has ended and a new eligible opportunity is open. |
| Preconditions | The previous sponsorship has expired, no conflicting active sponsorship remains, and all UC-04 preconditions for the chosen open program are satisfied. |
| Trigger | The student selects Apply on an eligible open program after the prior sponsorship ends. |
| Main flow | 1. The system checks current sponsorship and application restrictions for the selected program.<br>2. The student reviews current profile information and supplies fresh qualifications and applicable evidence through UC-04.<br>3. UC-04 validates and saves a new Pending application.<br>4. The previous sponsorship and its historical evidence remain intact. |
| Alternative flows | 1. The student chooses a different open program that satisfies the applicable restrictions. |
| Exception flows | 1. The previous award remains active, the selected program is closed, or another UC-04 restriction prevents a new application. |
| Postconditions | A new Pending application exists while prior sponsorship history is preserved. Previous attachments are not automatically carried over. |
| Includes / extends | Includes UC-04. This documents a specific application scenario, not a separate Re-apply endpoint or an application to an expired program. |

## Table 3.29: Track Application Status

| Field | Content |
|---|---|
| Use case ID | UC-06 |
| Use case name | Track Application Status |
| Primary actor | Student Applicant |
| Secondary actor | None |
| Description | Shows the student's current saved application information, reviewer notes, and relevant status history. |
| Preconditions | The student is authenticated and may access only their own application records. |
| Trigger | The student opens My Applications or an application detail page. |
| Main flow | 1. The system retrieves applications linked to the student's profile.<br>2. The system displays the applicable status: Pending, Verified, Approved, Rejected, Ongoing, Expired, or Resubmission Requested.<br>3. The student opens a record to inspect its details and available review notes.<br>4. The system displays current stored information and relevant notification content. |
| Alternative flows | 1. For Resubmission Requested, the student supplies corrected evidence through the resubmission form using UC-10; a successful resubmission returns the application to Pending. |
| Exception flows | 1. No applications produces an empty state.<br>2. An attempt to access another student's record is denied. |
| Postconditions | The student has viewed current information. Viewing alone does not change approval or verification. Batch membership is separate from application status. |
| Includes / extends | None. Reapplication is a separate scenario, not an automatic extension of viewing status. |

## Table 3.30: Manage Sponsorship Programs

| Field | Content |
|---|---|
| Use case ID | UC-07 |
| Use case name | Manage Sponsorship Programs |
| Primary actor | FASSG Personnel |
| Secondary actor | None |
| Description | Creates and maintains sponsorship programs, Sponsor assignment, capacity, application dates, and applicable requirements. |
| Preconditions | The user is authenticated as FASSG. A valid Sponsor is available for program assignment. |
| Trigger | FASSG opens program creation, editing, or status controls. |
| Main flow | 1. The system displays the relevant program form.<br>2. FASSG enters or updates the program name, Sponsor, category, capacity, dates, and required documents.<br>3. When configuring or changing eligibility, FASSG performs UC-11.<br>4. FASSG submits the configuration.<br>5. The system validates supported fields and preserves capacity consistency with existing beneficiaries.<br>6. The system saves the program; newly created programs are opened by the implemented creation action. |
| Alternative flows | 1. FASSG uses the available close, open/reopen, or expire actions, subject to their checks.<br>2. An edit preserves existing criteria where no change is requested. |
| Exception flows | 1. Invalid Sponsor or configuration values, invalid dates, or reducing capacity below existing beneficiaries prevents the relevant operation. |
| Postconditions | The program configuration or status is saved. Opening or editing a program does not select or approve candidates. |
| Includes / extends | Extended by UC-11 at Configure program eligibility when criteria are configured or changed. |

## Table 3.31: Verify SLE-FHE Eligibility

| Field | Content |
|---|---|
| Use case ID | UC-08 |
| Use case name | Verify SLE-FHE Eligibility |
| Primary actor | FASSG Personnel |
| Secondary actor | None; the student's request is submitted separately through UC-23. |
| Description | Records a genuine FASSG decision on a pending SLE-FHE verification request using identity, registry, and office evidence. |
| Preconditions | FASSG is authenticated. A matching submitted pending request and complete student identity/address information exist. Existing verification evidence must not be overwritten. |
| Trigger | FASSG selects a pending SLE-FHE request for review. |
| Main flow | 1. The system displays the request, student identity, address information, and institutional registry comparison.<br>2. FASSG examines the evidence and explicitly confirms the student's identity.<br>3. FASSG submits the verification decision for the selected request.<br>4. The system validates the request and supporting match or authorized evidence-based override.<br>5. The system records the verifying officer, timestamp, and verified address; marks the request verified; and records the audit evidence.<br>6. The system makes the verification result available to the student. |
| Alternative flows | 1. FASSG returns a pending request for correction with a reason, preserving its evidence.<br>2. A registry mismatch requires an explicit FASSG override, a reason, and a traceable office-evidence reference; matching alone is not an automatic verification decision. |
| Exception flows | 1. No matching pending request, incomplete identity/address, missing override evidence, or an existing verification record prevents an unsupported overwrite or verification. |
| Postconditions | A documented verification decision exists. No application is automatically approved, and no batch is automatically submitted. |
| Includes / extends | None. UC-23 precedes this interaction. Program-specific member verification is UC-24. |

## Table 3.32: Encode / Import Sponsor Fixed List

| Field | Content |
|---|---|
| Use case ID | UC-09 |
| Use case name | Encode / Import Sponsor Fixed List |
| Primary actor | FASSG Personnel |
| Secondary actor | None; the Sponsor is the originator of the list supplied to FASSG. |
| Description | Records a Sponsor-provided candidate list as an editable Fixed List for a specific program. |
| Preconditions | FASSG is authenticated, the program exists, and the source list is available. Editing requires a Draft list. |
| Trigger | FASSG creates a Fixed List or opens its manual encoding/import controls. |
| Main flow | 1. FASSG identifies the program and source list details.<br>2. FASSG manually encodes candidates or uploads the supported CSV/Excel source.<br>3. The system validates the submitted/imported candidate information and source association.<br>4. The system saves candidate entries and displays their current review states.<br>5. FASSG reviews the stored entries and completes the permitted verification and endorsement actions before later finalization. |
| Alternative flows | 1. FASSG removes an editable draft entry only when the existing historical-reference protections permit it.<br>2. Manual academic-program and year-level selections are checked against their supported values and matching institutional/profile information. |
| Exception flows | 1. Invalid data, source discrepancies, a protected historical reference, or a finalized list blocks the affected operation.<br>2. A candidate without documented SLE-FHE verification cannot be treated as verified through list encoding. |
| Postconditions | The Draft Fixed List contains candidate-source records. No sponsorship slots are reserved or consumed, and no separate online application is demanded from a Fixed List candidate. |
| Includes / extends | None. UC-12 is a later explicit action, not a mandatory part of every encoding/import operation. |

## Table 3.33: Upload Required Documents

| Field | Content |
|---|---|
| Use case ID | UC-10 |
| Use case name | Upload Required Documents |
| Primary actor | Student Applicant |
| Secondary actor | None |
| Description | Supplies applicable supporting files within application submission or an authorized document-resubmission interaction. |
| Preconditions | The student is authenticated and is completing an allowed application or resubmission form. |
| Trigger | The student selects supporting files in the relevant form. |
| Main flow | 1. The form displays document fields for the applicable requirements.<br>2. The student selects PDF, JPG/JPEG, or PNG files.<br>3. The student submits the application or resubmission form.<br>4. The system checks required document types, supported file formats, and the 5120 KB per-file limit.<br>5. On successful form processing, the system stores the files and links document records to the application. |
| Alternative flows | 1. A requested resubmission supplies corrected documents through the existing resubmission form.<br>2. Optional supported attachments may be supplied when the form permits them. |
| Exception flows | 1. A missing required document, unsupported format, excessive size, or unauthorized resubmission is rejected. |
| Postconditions | Successfully submitted files are available for authorized review. Selecting a local file alone does not persist evidence or verify eligibility. |
| Includes / extends | Extends UC-04 at Attach supporting documents when documents are required or supplied. The resubmission context is described in UC-06. |

## Table 3.34: Configure Program Eligibility Criteria

| Field | Content |
|---|---|
| Use case ID | UC-11 |
| Use case name | Configure Program Eligibility Criteria |
| Primary actor | FASSG Personnel |
| Secondary actor | None |
| Description | Defines the qualifications applicable to a sponsorship program without ranking or selecting candidates. |
| Preconditions | FASSG is authenticated and is creating or editing a program. |
| Trigger | FASSG configures or changes eligibility fields in the program form. |
| Main flow | 1. The system displays supported academic-program, year-level, campus, grade, location, document, and applicable employee-relative criteria.<br>2. FASSG selects the criteria required by the program and leaves optional restrictions unset when appropriate.<br>3. FASSG submits the program form.<br>4. The system validates the configured values using the current request rules.<br>5. The system saves the criteria and relevant academic-program associations. |
| Alternative flows | 1. No program-specific grade or location restriction is set where the program does not require it. |
| Exception flows | 1. Unsupported or invalid academic-program, year-level, numerical, or other validated values prevent saving. |
| Postconditions | The saved criteria are available for application and beneficiary checks. They do not establish ranking, automatic selection, or approval. |
| Includes / extends | Extends UC-07 at Configure program eligibility when criteria are configured or changed. This description does not claim server-side province/municipality membership validation absent from the current request rules. |

## Table 3.35: Finalize Fixed List

| Field | Content |
|---|---|
| Use case ID | UC-12 |
| Use case name | Finalize Fixed List |
| Primary actor | FASSG Personnel |
| Secondary actor | None |
| Description | Locks a reviewed Sponsor-provided source list so FASSG can deliberately use it in Generated Batch preparation. |
| Preconditions | FASSG is authenticated. A nonempty editable Draft Fixed List exists for the program, with documented verification and recorded endorsements for its members. |
| Trigger | FASSG selects Finalize List. |
| Main flow | 1. The system checks the list's editable state and retrieves its stored members.<br>2. The system checks each member's recorded endorsement, documented SLE-FHE verification, and list-preparation requirements.<br>3. If every member passes, the system sets the list to Finalized.<br>4. The system records the responsible FASSG officer, timestamp, and audit event.<br>5. The interface presents the finalized list as protected and available for batch preparation. |
| Alternative flows | 1. FASSG postpones finalization and completes permitted draft corrections or reviews. |
| Exception flows | 1. An empty list, missing evidence or endorsement, or a failed preparation check blocks finalization. |
| Postconditions | The source list is Finalized. No program slots are reserved or consumed; no candidate receives priority, Sponsor approval, or Accounting access. |
| Includes / extends | None. A finalized list may later be explicitly selected in UC-13. |

## Table 3.36: Manually Prepare Generated Batch

| Field | Content |
|---|---|
| Use case ID | UC-13 |
| Use case name | Manually Prepare Generated Batch |
| Primary actor | FASSG Personnel |
| Secondary actor | None |
| Description | Creates or safely edits an unsubmitted program batch using explicitly selected ordinary applicants, a finalized Fixed List roster, or both. |
| Preconditions | FASSG is authenticated. The target program is open and unexpired. Candidates have documented SLE-FHE verification and no conflicting active sponsorship. Ordinary applications are Pending or Verified; a selected source list is Finalized and belongs to the same program. |
| Trigger | FASSG opens Create Batch or a permitted draft-batch preparation action. |
| Main flow | 1. FASSG selects the program and batch details.<br>2. FASSG manually checks ordinary applicants and/or deliberately selects a finalized Fixed List.<br>3. For a selected Fixed List, the server retrieves its complete stored roster; it does not trust client-supplied member names.<br>4. The system checks source membership, candidate identity, verification/endorsement prerequisites, existing batch assignments, duplicate membership, and capacity.<br>5. The system saves the exact selected members with source references in a Saved Generated Batch.<br>6. FASSG opens that draft for the separate member review and submission actions. |
| Alternative flows | 1. The batch contains only ordinary applicants, only Fixed List candidates, or a combination.<br>2. The same student selected through both sources is represented once with the retained Fixed List source link; duplicate source identities or conflicting records require review.<br>3. FASSG may remove an individual member from an eligible unsubmitted draft without deleting the original source-list entry. |
| Exception flows | 1. Zero selection, invalid source/program association, missing prerequisite evidence, conflicting membership, or excessive capacity blocks preparation without silently dropping members. |
| Postconditions | A Saved batch exists with unique membership and source provenance. Rank positions remain unset. Draft creation neither marks Pending members Verified nor grants approval or consumes slots. |
| Includes / extends | None. UC-24 and UC-14 are separate subsequent actions; they are not executed automatically during draft creation. |

## Table 3.37: Submit Batch to Sponsor

| Field | Content |
|---|---|
| Use case ID | UC-14 |
| Use case name | Submit Batch to Sponsor |
| Primary actor | FASSG Personnel |
| Secondary actor | None; the assigned Sponsor reviews the result later through UC-15. |
| Description | Submits the complete verified program roster to the assigned Sponsor and freezes its membership for review. |
| Preconditions | FASSG is authenticated. A permitted batch exists, each member has completed required verification, and the roster satisfies eligibility, evidence, identity, and capacity rules. |
| Trigger | FASSG selects Submit to Sponsor on the Generated Batch. |
| Main flow | 1. FASSG reviews the complete roster and submits it.<br>2. The system locks the program, batch, and relevant records for consistency checks.<br>3. The system validates every member, applicable documents and qualifications, conflicts, and current capacity.<br>4. The system records or verifies the exact roster snapshot and its program/Sponsor association.<br>5. The system sets Submitted, advances the submission version, and records the submission audit event.<br>6. The submitted batch becomes available to the assigned Sponsor. |
| Alternative flows | 1. A rejected batch may be resubmitted only after FASSG records receipt of the rejection, the original roster remains intact, and current checks pass. |
| Exception flows | 1. An unverified/ineligible member, missing evidence, over-capacity selection, stale state, or changed frozen roster blocks the entire submission. |
| Postconditions | The exact roster is Submitted and protected against unauthorized membership changes. No Sponsor approval or slot consumption occurs at submission. |
| Includes / extends | None. Completed verification is a precondition; submission does not automatically perform FASSG's human review. No automatic Sponsor email is claimed. |

## Table 3.38: Review Submitted Generated Batches

| Field | Content |
|---|---|
| Use case ID | UC-15 |
| Use case name | Review Submitted Generated Batches |
| Primary actor | Sponsor |
| Secondary actor | None |
| Description | Allows an assigned Sponsor to inspect a FASSG-submitted batch and its candidate evidence before a whole-batch decision. |
| Preconditions | The Sponsor is authenticated, owns the assigned program context, and the batch has been submitted by FASSG. |
| Trigger | The Sponsor opens a submitted batch in the Sponsor portal. |
| Main flow | 1. The system retrieves batches belonging to the Sponsor's programs.<br>2. The Sponsor selects a submitted batch.<br>3. The system displays its exact roster, program details, candidate information, and available supporting evidence.<br>4. The Sponsor reviews the roster and its current decision/evidence state. |
| Alternative flows | 1. The Sponsor uses available applicant search/filter views restricted to the Sponsor's submitted batches.<br>2. The Sponsor views previous decisions in the permitted history view. |
| Exception flows | 1. An unassigned program, Saved draft, or unauthorized record/document request is denied. |
| Postconditions | The Sponsor has reviewed the submitted roster. Viewing does not change membership or approve beneficiaries. |
| Includes / extends | None. Evidence upload and the explicit whole-batch decision are UC-17 and UC-16. |

## Table 3.39: Approve or Reject Entire Submitted Batch

| Field | Content |
|---|---|
| Use case ID | UC-16 |
| Use case name | Approve or Reject Entire Submitted Batch |
| Primary actor | Sponsor |
| Secondary actor | None; FASSG receives and records the decision through UC-25. |
| Description | Records an explicit Sponsor decision on the entire unchanged submitted roster. |
| Preconditions | The Sponsor is authenticated and assigned to the program. The current submission is actionable and its roster is intact. Approval additionally requires current uploaded confirmation evidence and all member/capacity checks to pass. |
| Trigger | The Sponsor explicitly selects Approve Entire Batch or Reject Entire Batch. |
| Main flow | 1. The Sponsor reviews the current whole batch and chooses approval.<br>2. The system validates authorization, submission version, exact membership, and the current uploaded confirmation evidence.<br>3. Within the controlled transaction, the system checks all members' qualifications, documents, verification, active-sponsorship conflicts, and available capacity.<br>4. The system approves the complete batch, records the Sponsor and decision time, and updates the applicable individual beneficiary and active-sponsorship records.<br>5. The system records the audit evidence and sends the implemented application-approval notifications. |
| Alternative flows | 1. For whole-batch rejection, the system records a separate rejection decision and marks the batch Rejected. Existing uploaded evidence and application qualification history are preserved. No individual replacement, re-ranking, or mass application rejection occurs. |
| Exception flows | 1. Stale submission/evidence, unauthorized access, an invalid member, conflicting active sponsorship, or insufficient capacity prevents approval without partial beneficiary updates.<br>2. Standalone individual Sponsor approval/rejection operations are blocked. |
| Postconditions | The batch is Approved or Rejected with a recorded Sponsor decision. Approved batches await FASSG receipt and forwarding; Accounting access is not automatically granted. |
| Includes / extends | None. UC-17 supplies evidence before approval. UC-25 and UC-26 are separately initiated FASSG actions, not included Sponsor actions. |

## Table 3.40: Upload Sponsor Confirmation Evidence

| Field | Content |
|---|---|
| Use case ID | UC-17 |
| Use case name | Upload Sponsor Confirmation Evidence |
| Primary actor | Sponsor |
| Secondary actor | None |
| Description | Stores a Sponsor's approval/confirmation document against the current submitted batch version without deciding the batch. |
| Preconditions | The Sponsor is authenticated and authorized to act on the current submitted batch. |
| Trigger | The Sponsor selects a confirmation file and submits the upload form. |
| Main flow | 1. The Sponsor selects a PDF, JPG/JPEG, or PNG confirmation file.<br>2. The system validates the format and 5120 KB per-file limit.<br>3. The system verifies the assigned program, submission version, and intact roster.<br>4. The system stores the file and creates a Pending Sponsor approval/evidence record linked to the batch, program, version, and uploader.<br>5. The system records the upload and prompts the Sponsor to make an explicit whole-batch decision. |
| Alternative flows | 1. A later permitted upload creates another evidence record while preserving earlier uploaded records; approval must reference current evidence. |
| Exception flows | 1. Invalid file, unauthorized batch, changed submission, or failed persistence blocks the upload action. |
| Postconditions | Confirmation evidence is stored with a Pending decision state. Uploading alone does not approve candidates, impersonate FASSG, or forward to Accounting. |
| Includes / extends | None. Valid stored evidence is a precondition of the approval branch of UC-16, not of its rejection branch. |

## Table 3.41: View Forwarded Approved Beneficiary Records

| Field | Content |
|---|---|
| Use case ID | UC-18 |
| Use case name | View Forwarded Approved Beneficiary Records |
| Primary actor | Accounting Office Personnel |
| Secondary actor | None |
| Description | Provides read-only access to beneficiaries that satisfy the complete verification, Sponsor decision, and FASSG forwarding requirements. |
| Preconditions | Accounting is authenticated. Visible records require documented SLE-FHE verification, individual Approved/Ongoing status with required evidence, an intact approved batch, valid Sponsor confirmation, recorded FASSG receipt, and explicit FASSG forwarding. |
| Trigger | Accounting opens the beneficiary reference list or an authorized beneficiary detail/document. |
| Main flow | 1. The system applies the Accounting visibility and eligibility checks.<br>2. The system displays only eligible forwarded beneficiary records.<br>3. Accounting searches the list or filters by sponsorship program or academic program.<br>4. Accounting opens eligible details and available confirmation/reference documents.<br>5. At the reference-output extension point, Accounting may request UC-19. |
| Alternative flows | 1. No eligible records or no matching filters produces an empty result. |
| Exception flows | 1. Unauthorized or ineligible direct record/document requests are denied.<br>2. Beneficiary editing or approval attempts are unavailable and prohibited. |
| Postconditions | Accounting has viewed eligible beneficiary information without modifying beneficiary, verification, or sponsorship decisions. This use case does not perform payout processing or financial calculations. |
| Includes / extends | Extended by UC-19 at Produce a reference copy when the user requests printing or export. |

## Table 3.42: Print / Export Beneficiary References

| Field | Content |
|---|---|
| Use case ID | UC-19 |
| Use case name | Print / Export Beneficiary References |
| Primary actor | Accounting Office Personnel |
| Secondary actor | None |
| Description | Produces a CSV or browser-printed reference from eligible forwarded beneficiary information. |
| Preconditions | Accounting is authenticated and the relevant beneficiary records satisfy UC-18's eligibility restrictions. |
| Trigger | Accounting selects Export CSV / Excel or Print Master List in the reference interface. |
| Main flow | 1. The system obtains eligible beneficiary results using the current supported filters.<br>2. For export, the system formats the reference columns, sanitizes potentially unsafe CSV values, and streams a CSV download.<br>3. For printing, the browser uses the existing print layout for the displayed reference list.<br>4. The user saves the CSV or completes the browser print action. |
| Alternative flows | 1. The user requests either printing or CSV export; CSV can be opened in spreadsheet software.<br>2. The browser may offer Save as PDF as a printing option; this is not an application-generated Accounting PDF. |
| Exception flows | 1. Ineligible records remain excluded. A failed request or download must not be reported as a successful export. |
| Postconditions | A reference copy is produced without changing beneficiary records. The export action records its audit event. No native Excel workbook, billing subtotal, or payout is generated. |
| Includes / extends | Extends UC-18 when the user requests print or export. |

## Table 3.43: Generate Summary Reports

| Field | Content |
|---|---|
| Use case ID | UC-20 |
| Use case name | Generate Summary Reports |
| Primary actor | FASSG Personnel |
| Secondary actor | None |
| Description | Presents sponsorship statistics and permits the implemented PDF and CSV report exports. |
| Preconditions | FASSG is authenticated and authorized to access the reporting module. |
| Trigger | FASSG opens Reports and selects report filters or an export action. |
| Main flow | 1. The system displays the available academic-year, semester, campus, and sponsorship-program filters.<br>2. FASSG selects the desired supported filters.<br>3. The system retrieves and aggregates the corresponding application, beneficiary, and program information.<br>4. The system displays implemented charts and tables, including slot utilization and demographic distributions.<br>5. If requested, the system generates the implemented PDF report or CSV summary. |
| Alternative flows | 1. FASSG reviews municipality and other demographic breakdowns available in the report.<br>2. FASSG views results without downloading a file. |
| Exception flows | 1. No matching records produces empty or zero-valued results. A failed export must not be presented as completed. |
| Postconditions | FASSG has viewed or exported descriptive reports. Reports do not rank, select, verify, or approve candidates. |
| Includes / extends | None. Accounting's restricted reference export is UC-19, not participation in FASSG report generation. |

## Table 3.44: Manage User Accounts and Roles

| Field | Content |
|---|---|
| Use case ID | UC-21 |
| Use case name | Manage User Accounts and Roles |
| Primary actor | System Administrator |
| Secondary actor | None |
| Description | Creates and edits user accounts and assigns the system's supported roles and account statuses. |
| Preconditions | The Administrator is authenticated and authorized for user management. |
| Trigger | The Administrator opens Manage Users and selects an account operation. |
| Main flow | 1. The system displays searchable user records and role/status filters.<br>2. The Administrator creates an account or opens an existing account for editing.<br>3. The Administrator supplies the supported name, email, role, status, and applicable password information.<br>4. The system validates submitted values and uniqueness constraints.<br>5. The system saves the changes, maintains the related Sponsor profile when applicable, and records the administrative audit event. |
| Alternative flows | 1. The Administrator deactivates or restores an account through the dedicated status actions.<br>2. An optional new password is entered and confirmed during account editing; a blank password field preserves the existing password. |
| Exception flows | 1. Invalid or duplicate values prevent the relevant save.<br>2. Dedicated status actions reject changing the current Administrator's own account status. |
| Postconditions | Account information, role, or status is updated with an audit event. This description does not claim a root-administrator self-demotion guard or an emailed administrator password-reset link. |
| Includes / extends | None. |

## Table 3.45: View Audit Logs and Manage Backups

| Field | Content |
|---|---|
| Use case ID | UC-22 |
| Use case name | View Audit Logs and Manage Backups |
| Primary actor | System Administrator |
| Secondary actor | None |
| Description | Provides recorded audit-log access and the existing authorized SQL backup, download, and restore operations. |
| Preconditions | The Administrator is authenticated and authorized. Backup operations require usable storage and a supported database driver; restore requires an existing accessible completed backup. |
| Trigger | The Administrator opens Audit Logs or Backups and selects an operation. |
| Main flow | 1. The system displays stored audit entries with supported search, role, and date filters, or displays recorded backups.<br>2. The Administrator reviews the requested records.<br>3. If backup creation is explicitly selected, the system builds a SQL database dump and writes the backup file.<br>4. The system records completed backup metadata and the administrative audit event and returns the operation result. |
| Alternative flows | 1. Viewing logs alone creates no backup.<br>2. The Administrator downloads an available completed backup.<br>3. The Administrator explicitly requests restore; the existing action checks backup availability and recognized SQL content, executes restoration, and reports its result. Restoration changes database state and is not part of ordinary log viewing. |
| Exception flows | 1. Unavailable backup files, unrecognized backup content, unsupported database drivers, or storage/database failures prevent completion and are reported through the existing error handling. |
| Postconditions | The selected log or backup operation has completed or reported failure. Generated backups are SQL files; encryption, live intrusion monitoring, and guaranteed successful recovery are not claimed. |
| Includes / extends | None. Log viewing, backup creation, and restore are alternative authorized operations, not a mandatory sequence. |

## Table 3.46: Request SLE-FHE Verification

| Field | Content |
|---|---|
| Use case ID | UC-23 |
| Use case name | Request SLE-FHE Verification |
| Primary actor | Student Applicant |
| Secondary actor | None; FASSG processes the stored request through UC-08. |
| Description | Submits the student's verification request and address information for a separate FASSG evidence review. |
| Preconditions | The student is authenticated, has a complete usable profile, and has no existing verification record that this request would overwrite. |
| Trigger | The student submits the SLE-FHE verification-request form. |
| Main flow | 1. The system displays the student's verification interface.<br>2. The student supplies and reviews the required request and address information.<br>3. The student submits the verification request.<br>4. The system validates the request and checks existing verification evidence.<br>5. The system preserves prior requests, supersedes prior pending requests when applicable, and creates the new pending request with its submission time.<br>6. The system confirms that the request awaits FASSG evidence review. |
| Alternative flows | 1. The student submits corrected information after an earlier request was returned for correction. |
| Exception flows | 1. Invalid request information or an existing verification record blocks an unsupported submission or overwrite. |
| Postconditions | A pending request is available to FASSG. Saving a profile or submitting this request does not automatically verify the student. |
| Includes / extends | None. UC-08 is a later FASSG action, not automatically included in submission. |

## Table 3.47: Verify Batch Member Qualifications and Documents

| Field | Content |
|---|---|
| Use case ID | UC-24 |
| Use case name | Verify Batch Member Qualifications and Documents |
| Primary actor | FASSG Personnel |
| Secondary actor | None |
| Description | Records explicit program-specific qualification verification for an individual member of an unsubmitted Generated Batch. |
| Preconditions | FASSG is authenticated. The batch is Saved and never submitted, the member belongs to its exact program roster, and the processing record is Pending. Genuine SLE-FHE verification is already required. |
| Trigger | FASSG opens a batch member's review form and explicitly submits verification. |
| Main flow | 1. The system displays the member's identity, source, qualifications, and evidence within the batch review.<br>2. FASSG reviews actual grades, documents, and other applicable program requirements.<br>3. For a Fixed List processing record, FASSG supplies genuine missing qualification values and files through the supported review fields, without inventing evidence.<br>4. The system validates membership, all mandatory program conditions, documented verification, and active-sponsorship conflicts.<br>5. The system changes the processing/application record to Verified, records the verification time and audit evidence, and performs the implemented status notification. |
| Alternative flows | 1. FASSG leaves the record Pending while evidence remains incomplete.<br>2. Where the existing review controls permit it, FASSG explicitly requests resubmission or rejects an ordinary application with the required reason; these are human decisions. |
| Exception flows | 1. Missing mandatory evidence, ineligible qualifications, a conflicting sponsorship, wrong membership, a non-Pending record, or a submitted roster blocks verification. |
| Postconditions | The member has recorded eligibility verification only. The batch remains unsubmitted until UC-14, and Sponsor approval remains separate. |
| Includes / extends | None. UC-08 records institutional SLE-FHE verification; UC-24 records program-specific member verification. |

## Table 3.48: Record Receipt of Sponsor Decision

| Field | Content |
|---|---|
| Use case ID | UC-25 |
| Use case name | Record Receipt of Sponsor Decision |
| Primary actor | FASSG Personnel |
| Secondary actor | None; the Sponsor's prior decision is the input record. |
| Description | Records FASSG's explicit acknowledgement of the current Sponsor approval or rejection. |
| Preconditions | FASSG is authenticated. An intact submitted roster has a matching current Sponsor decision, actor, timestamp, and any evidence required for that decision. |
| Trigger | FASSG selects Record Receipt of Sponsor Decision on the batch. |
| Main flow | 1. FASSG reviews the Sponsor decision and available confirmation evidence.<br>2. FASSG submits acknowledgement for the current decision and submission version.<br>3. The system checks the roster, batch status, matching decision, and required evidence.<br>4. The system records the FASSG officer and receipt time and writes the audit event.<br>5. The system confirms that receipt has been recorded. |
| Alternative flows | 1. Both valid approval and rejection decisions may be acknowledged.<br>2. Repeating an already completed acknowledgement preserves its original officer and timestamp. |
| Exception flows | 1. A stale version, missing/mismatched decision, invalid evidence, or unauthorized actor prevents acknowledgement. |
| Postconditions | FASSG receipt is recorded. No beneficiaries are forwarded by this action. Rejected batches remain rejected unless separately resubmitted under the allowed rules. |
| Includes / extends | None. UC-16 precedes receipt; UC-26 is a separate action for approved eligible batches. |

## Table 3.49: Forward Approved Beneficiaries to Accounting

| Field | Content |
|---|---|
| Use case ID | UC-26 |
| Use case name | Forward Approved Beneficiaries to Accounting |
| Primary actor | FASSG Personnel |
| Secondary actor | None; Accounting subsequently accesses eligible results through UC-18. |
| Description | Records FASSG's explicit forwarding of the approved, verified batch for Accounting's read-only reference. |
| Preconditions | FASSG is authenticated. The batch is Approved, the submitted roster is intact, current Sponsor confirmation is valid, FASSG has recorded receipt, members meet all current eligibility/evidence requirements, and program capacity is consistent. |
| Trigger | FASSG selects Forward to Accounting on the approved batch. |
| Main flow | 1. The system checks the current batch, decision, confirmation evidence, submission version, and recorded receipt.<br>2. The system locks the relevant records and validates every member's approval, verification, documents, active-sponsorship conflicts, and capacity consistency.<br>3. The system records the forwarding FASSG officer and timestamp and preserves established historical assignment information.<br>4. The system records the forwarding audit event and returns confirmation.<br>5. Eligible forwarded beneficiary records become available through Accounting's restricted reference views. |
| Alternative flows | 1. Repeating a completed forwarding action preserves its original forwarding identity and time. |
| Exception flows | 1. Missing receipt/evidence, a rejected or unapproved batch, changed roster, invalid member, conflicting sponsorship, or capacity inconsistency blocks forwarding. |
| Postconditions | Explicit FASSG forwarding is recorded. The Sponsor's identity is never substituted for the forwarding officer. Accounting may access only records that continue to satisfy the eligibility checks. |
| Includes / extends | None. Sponsor approval and FASSG receipt are preconditions, not actions automatically executed by forwarding. |
