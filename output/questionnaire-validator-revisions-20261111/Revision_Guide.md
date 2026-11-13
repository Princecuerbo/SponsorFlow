# SponsorFlow questionnaire revisions - 11 November 2026

Status: REVIEW COPIES. Content revisions are prepared; numerical response-time limits are awaiting the user's adviser-approved values. Do not administer these drafts with timing placeholders.

## Validator changes and exact placement

| Validator request | Revision | Placement |
|---|---|---|
| More decision-support coverage | 15 new items on using displayed information for human decisions | New Decision Support section immediately after Functional Suitability in every form |
| Explicit response-time limits | Every performance item references a timing ID and a seconds field | Performance Efficiency; complete from Timing_Targets.csv |
| Neutral wording | Factual, task-specific statements replace praise and vague success guarantees | All rating sections and introductory instructions |
| Rating 3 = Neutral | Neutral replaces both previous neutral-anchor variants | Every form's scale legend |
| Access control, confidentiality, tamper resistance | Two observed-control items per requested role | New Security section after Reliability in FASSG, Accounting and DICT |
| DICT RBAC and privacy | Protected-record/document URL authorization and rejected-write integrity checks | DICT SEC-01 and SEC-02 |

The user explicitly retained 1 Strongly Disagree, 2 Disagree, 3 Neutral, 4 Agree, 5 Strongly Agree. N/A is a separate nonnumeric response, not Neutral or zero. Neutral wording reduces leading language; an agreement scale can still be subject to acquiescence bias. No reverse-scored trick questions were introduced.

The Decision Support section is a domain-specific grouping under functional suitability, not an additional ISO quality characteristic. The interface section is headed Interaction Capability (Usability) to align the terminology with the manuscript's ISO/IEC 25010:2023 reference while retaining the familiar label in parentheses. Confirm the individual item-to-characteristic mapping with the validators. Do not describe these selected sections as full ISO coverage or certification.

## Timing fields: required before distribution

The user chose adviser-approved limits and has not yet provided the numbers. No proposed 3/5/10/60-second limits were inserted or represented as approved. Timing_Targets.csv lists T01-T09, measurement boundaries, blank limit fields, workload fields and approval-reference fields.

Use the same approved limit for the same timing ID. If tasks need different limits, assign separate task IDs rather than silently reusing one number. Record deployment/environment, device, browser, network, dataset size, file format/size, roster size, export row count, database size and concurrent workload as applicable. Files must also satisfy the current application's type/size validation; the inspected Student and Sponsor upload rules allow PDF/JPG/JPEG/PNG up to 5120 KB.

Run a predefined number of attempts per task, record each elapsed time and failures, and use a prespecified summary rule. Distinguish server or network failures from a completed slow task. Do not discard failures or infer measured performance from a Likert mean. A timed item is N/A if not observed or its approved limit is missing; do not administer an incomplete draft as a finalized instrument.

Timing_Observation_Template.csv is an unfilled recording sheet. No test, benchmark, backup or restoration was executed to create these documents.

## Role-specific decision support

| Form | IDs | Decision supported |
|---|---|---|
| Students | DS-01 to DS-02 | Which open program to consider and next application action |
| Sponsor | DS-01 to DS-03 | Whole-batch review, clarification needs, and use of previous decision evidence |
| FASSG | DS-01 to DS-05 | Eligibility review, missing verification, capacity-aware manual roster decisions, program follow-up, Accounting handoff readiness |
| Accounting | DS-01 to DS-03 | Eligibility of reference records, record selection by request, and comparison of exported references |
| DICT | DS-01 to DS-02 | Account-access review and audit-event investigation |

Decision-support tasks use the system's actual information displays. They do not introduce GPA ranking, automated candidate selection, automatic SLE-FHE verification, financial calculations or automatic approval/forwarding.

## Security demonstrations for the evaluation facilitator

Use only authorized test accounts and synthetic fixtures in an independently isolated environment. These are planned checks, not results. Never test restoration, roster editing, rejected writes or cross-role access using real institutional records. Show the permitted baseline and denied case; record the role, object, expected response, actual response and before/after values. Use N/A if no demonstration was observed. A missing UI button alone is insufficient evidence of server-side access control.

| Form / item | Demonstration and evidence |
|---|---|
| FASSG SEC-01 | An authorized FASSG account can open the synthetic document; a different role cannot retrieve that same protected URL. Inspect the response for disclosure, not only its status code. |
| FASSG SEC-02 | Capture the exact submitted roster; attempt a prohibited membership change using the controlled test; verify the request is rejected and membership is unchanged. |
| Accounting SEC-01 | Contrast an eligible explicitly forwarded record with an otherwise comparable unforwarded record. Check list, direct detail and confirmation-document access. No unforwarded student information should be disclosed. |
| Accounting SEC-02 | Attempt a prohibited write through the Accounting account; inspect denial and confirm all targeted beneficiary values remain unchanged. |
| DICT SEC-01 | Use a role matrix for protected pages and direct student/document URLs; test both permitted and nonpermitted accounts. Do not infer confidentiality solely from hidden navigation. |
| DICT SEC-02 | Capture a synthetic record, submit an unauthorized change request, and compare its stored values afterward. This assesses the demonstrated case only, not immunity to all tampering. |

Do not claim encryption, immutable audit storage, penetration-test completion or legal certification from these survey answers. Role-specific security scores complement technical evidence; they do not replace it.

## Evaluation task coverage

- Students: register; request FASSG SLE-FHE review; compare open-program criteria; submit test documents/application; reopen status and notifications; update permitted profile fields.
- FASSG: review synthetic verification evidence; configure applicable program criteria; inspect all matching applicants at zero slots; encode/import and endorse/finalize a Fixed List; prepare ordinary-only, fixed-only and mixed drafts; compare roster count with capacity; review every member; submit; receive a Sponsor decision; separately forward eligible approved records; inspect filtered reports.
- Sponsor: review assigned exact submitted rosters and both sources; upload test confirmation evidence; perform an explicit whole-batch decision; reopen decision history. Use a second batch for rejection rather than changing an already decided batch.
- Accounting: review only eligible forwarded fixtures; compare program/student reference details; search/filter; print; export CSV; compare output to the expected selected set. SponsorFlow does not calculate tuition adjustments or post payments.
- DICT: change a test account's predefined role/status; inspect audit events; demonstrate maintenance mode; perform backup/restoration only in the isolated test environment; observe the two security demonstrations.

## Legal and standards context

RA 10931 concerns access to tertiary education and student financial assistance; it is not itself a software security certification. RA 10173 addresses personal-data protection, including education information and security safeguards. The questionnaire therefore asks about observable role-based access and record integrity, rather than asking respondents to certify compliance with either law.

- National Privacy Commission, RA 10173 (Sections 3 and 20): https://privacy.gov.ph/data-privacy-act-/
- UniFAST, RA 10931 / UAQTEA overview: https://unifast.gov.ph/uaqtea.html
- ISO/IEC 25010:2023 product-quality model: https://www.iso.org/standard/78176.html
- ISO standard preview, terminology changes: https://cdn.standards.iteh.ai/samples/78176/13ff8ea97048443f99318920757df124/ISO-IEC-25010-2023.pdf

Sources were consulted on 11 November 2026. This is an instrument-revision note, not a legal opinion.

## Scoring and review

Exclude N/A from numerical means and report valid-response counts. Keep domain decision-support results visible rather than diluting them in a pooled total. Different roles have different questions and item counts: do not treat the role forms as interchangeable measurements or pool all items without a documented weighting rule. Do not equate a score of 3 with a demonstrated pass. Validation/pilot-testing and final adviser approval remain separate from this content revision.

## Source and file preservation

Each DOCX is a copy of its matching source package with only word/document.xml replaced. Existing letterhead, footer, artwork, relationships and styles are retained. The comment heading is rendered once. Originals are SHA-256 checked before and after generation. Application source, environment configuration and database contents are not changed.

The copies preserve the existing page size and margins. Tables have repeated column headers, fixed rating-column widths and non-splitting rows. Automated package/content checks do not replace opening the files in Word and checking final pagination before printing.

## Item counts

| Form | Functional | Decision support | Performance | Usability | Reliability | Security | Total |
|---|---:|---:|---:|---:|---:|---:|---:|
| STUDENTS | 7 | 2 | 3 | 4 | 3 | 0 | 19 |
| SPONSOR | 5 | 3 | 4 | 2 | 2 | 0 | 16 |
| FASSG | 12 | 5 | 3 | 3 | 3 | 2 | 28 |
| DICT | 3 | 2 | 3 | 2 | 2 | 2 | 14 |
| ACCOUNTING | 5 | 3 | 2 | 2 | 3 | 2 | 17 |
