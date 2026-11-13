"""Create review copies from the five supplied questionnaires; never edit originals.

Uses only Python's standard library. Preserves the original DOCX package's
letterhead, footer, media, styles and relationships. No Laravel or database use.
"""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED
import csv
import hashlib
import json
import re
import xml.etree.ElementTree as ET

SOURCE = Path(r'C:\Users\princ\Blackcloudie\IT4B FILES\1ST SEM\ITP 140 - CAPSTONE PROJECT & RESEARCH 2\SPONSORFLOW LATEST DOCS CAPS2\QUESTIONNAIRE AND VALIDATION')
OUT = Path(__file__).resolve().parent
W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'
ET.register_namespace('w', W)
NS = {'w': W}

# No numeric limits are invented: the user will supply adviser-approved values.
TIMING = {
    'T01': ('Page or roster loading', 'Click to fully displayed requested page/roster'),
    'T02': ('Search or filtering', 'Apply query/filter to fully displayed results'),
    'T03': ('Saving a batch or Sponsor decision', 'Submit action to saved-result response'),
    'T04': ('Document upload', 'Confirm selected file to stored-upload response'),
    'T05': ('Opening a confirmation document', 'Open document to readable display'),
    'T06': ('Report summaries and charts', 'Apply filters to completed report display'),
    'T07': ('CSV export', 'Request export to completed CSV download'),
    'T08': ('Database backup', 'Confirm backup to successful completion response'),
    'T09': ('Test database restoration', 'Confirm isolated restoration to completion response'),
}

SECTIONS = {
    'FS': 'FUNCTIONAL SUITABILITY',
    'DS': 'DECISION SUPPORT - HUMAN REVIEW',
    'PE': 'PERFORMANCE EFFICIENCY',
    'UX': 'INTERACTION CAPABILITY (USABILITY)',
    'RE': 'RELIABILITY',
    'SEC': 'SECURITY - ACCESS, CONFIDENTIALITY AND RECORD INTEGRITY',
}

FORMS = {
 'STUDENTS': {
  'title': 'EVALUATION FORM FOR SLE-FHE STUDENTS',
  'FS': [
   'The verification-request form records my request for FASSG review.',
   'The opportunities page identifies sponsorship programs open for application.',
   'The application form accepts the information and documents requested for the selected program.',
   'The dashboard displays my recorded application status when I open or refresh the page.',
   'The registration form accepts the required student-account information.',
   'My Profile provides controls for updating the student-profile information that I am permitted to change.',
   'The notifications area displays messages associated with my application.',
  ],
  'DS': [
   'The displayed program criteria provide a basis for deciding which open sponsorship program to apply for.',
   'The recorded verification and application statuses provide a basis for deciding my next application-related action.',
  ],
  'PE': [
   'The sponsorship opportunities page finishes loading within [T01: ___ seconds] after I open it.',
   'The supporting-document upload finishes within [T04: ___ seconds] after I confirm the test file.',
   'The requested sponsorship search or filter results appear within [T02: ___ seconds] after I apply the query.',
  ],
  'UX': [
   'The student navigation labels identify the pages they open.',
   'The application form labels identify the information required in each field.',
   'The validation message identifies the field or submission that needs attention.',
   'The page controls remain visible and usable on the device used for the evaluation.',
  ],
  'RE': [
   'The result displayed after submission corresponds to the application action I performed.',
   'The uploaded document available after submission matches the test file I selected.',
   'The application I reopen in My Applications contains the information recorded at submission.',
  ],
 },
 'SPONSOR': {
  'title': 'EVALUATION FORM FOR SPONSOR EVALUATORS',
  'FS': [
   'The Sponsor review page displays the Generated Batch submitted by FASSG for the assigned program.',
   'The submitted roster identifies whether each candidate comes from an ordinary application or a Fixed List.',
   'The uploaded confirmation document is associated with the Generated Batch selected during the task.',
   'The approval history provides access to recorded Sponsor decisions and their available confirmation evidence.',
   'The decision controls apply my approval or rejection to the entire displayed submitted batch.',
  ],
  'DS': [
   'The submitted roster provides candidate information for my whole-batch decision.',
   'The displayed candidate verification statuses provide a basis for identifying a batch that needs clarification before I decide.',
   'The recorded decisions and confirmation evidence provide a reference when I review a batch with previous decision history.',
  ],
  'PE': [
   'The submitted batch roster finishes loading within [T01: ___ seconds] after I open it.',
   'The confirmation document becomes readable within [T05: ___ seconds] after I open it.',
   'The recorded batch-decision result appears within [T03: ___ seconds] after I submit my decision.',
   'The confirmation-document upload finishes within [T04: ___ seconds] after I confirm the test file.',
  ],
  'UX': [
   'The approval-queue labels identify the batch records available for review.',
   'The batch status and decision-control labels identify the action available for the displayed batch.',
  ],
  'RE': [
   'The decision shown after submission matches the whole-batch decision I selected.',
   'The roster displayed when I reopen a decided batch matches the roster on which I made that decision.',
  ],
 },
 'FASSG': {
  'title': 'EVALUATION FORM FOR FASSG OFFICE EVALUATORS',
  'FS': [
   'The SLE-FHE verification workspace provides controls for recording a decision on a student verification request.',
   'The program form records the applicable eligibility criteria entered for a sponsorship program.',
   'Selecting a program in the Application Queue displays that program\'s criteria summary.',
   'The Application Queue provides controls for manually selecting ordinary applicants for a Generated Batch.',
   'The Application Queue retains matching applicants in its results when the program has no remaining slots.',
   'The Fixed List workspace provides manual encoding or file import for a Sponsor-provided list.',
   'The Fixed List finalization action becomes available after the required review and endorsement.',
   'Batch preparation supports ordinary applicants, a selected finalized Fixed List roster, or both sources.',
   'Generated Batch review provides controls for recording each member\'s qualification and document verification.',
   'The batch-submission action sends the verified Generated Batch to the assigned Sponsor.',
   'The Sponsor-decision receipt action records my acknowledgement of the decision.',
   'The Accounting-forwarding action requires a separate FASSG action after receipt of the Sponsor decision.',
  ],
  'DS': [
   'The displayed program criteria and recorded qualifications provide a basis for my applicant-eligibility assessment.',
   'Member verification information provides a basis for identifying which candidates need further review before batch submission.',
   'The selected-candidate count and available-slot information provide a basis for deciding whether my proposed roster fits program capacity.',
   'The filtered report summaries provide a basis for identifying sponsorship programs that need follow-up.',
   'The recorded Sponsor decision and handoff status provide a basis for determining whether a batch is ready for Accounting forwarding.',
  ],
  'PE': [
   'The requested queue or batch-filter results appear within [T02: ___ seconds] after I apply the filters.',
   'The draft-batch save result appears within [T03: ___ seconds] after I save the selected roster.',
   'Report summaries and charts finish updating within [T06: ___ seconds] after I apply the filters.',
  ],
  'UX': [
   'The dashboard labels identify the application statistics and progress counts being displayed.',
   'The table headings identify each candidate\'s ID, name, academic details and review status.',
   'The Reset Filters control returns the table to its default filter settings.',
  ],
  'RE': [
   'The saved draft roster contains the candidates selected during the batch-preparation task.',
   'The report figures correspond to the evaluation records and recorded decisions used for the task.',
   'Saving a draft batch leaves the selected Pending applications at Pending status.',
  ],
  'SEC': [
   'In the demonstrated access test, an account without the required role is denied access to the protected student document.',
   'In the demonstrated roster-edit test, an attempted change after batch submission leaves the submitted roster unchanged.',
  ],
 },
 'DICT': {
  'title': 'EVALUATION FORM FOR DICT / SYSTEM ADMINISTRATOR EVALUATORS',
  'FS': [
   'The user-management workspace provides controls for assigning the system\'s predefined roles.',
   'The audit log contains entries for the administrative actions performed during the evaluation.',
   'Maintenance Mode restricts normal access while allowing the designated administrator access.',
  ],
  'DS': [
   'The displayed account roles and statuses provide a basis for deciding which account requires an access change.',
   'The audit-log action, actor and time information provides a basis for identifying an event that requires investigation.',
  ],
  'PE': [
   'The test-database backup completes within [T08: ___ seconds] after I confirm the backup request.',
   'The requested audit-log search or filter results appear within [T02: ___ seconds] after I apply the query.',
   'Restoration of the test backup completes within [T09: ___ seconds] after confirmation in the isolated test environment.',
  ],
  'UX': [
   'The administrative error message identifies the operation that needs attention.',
   'The administrative control labels identify the setting or account property being changed.',
  ],
  'RE': [
   'The recorded result of an administrative task matches the action performed during the evaluation.',
   'The role and status displayed when I reopen an account match the saved values.',
  ],
  'SEC': [
   'In the demonstrated RBAC test, an account outside the permitted role is denied access to the protected student record or document URL.',
   'In the demonstrated integrity test, an unauthorized change request leaves the test record\'s stored values unchanged.',
  ],
 },
 'ACCOUNTING': {
  'title': 'EVALUATION FORM FOR ACCOUNTING OFFICE EVALUATORS',
  'FS': [
   'The beneficiary reference list contains records that meet the documented SLE-FHE, individual approval, Sponsor confirmation and FASSG-forwarding requirements.',
   'The beneficiary detail page displays the information needed for the assigned Accounting reference task.',
   'The Accounting workspace presents beneficiary information for read-only reference.',
   'The print action produces a beneficiary reference list for the selected records.',
   'The export action produces a CSV file containing the selected beneficiary reference records.',
  ],
  'DS': [
   'The displayed Sponsor-confirmation and FASSG-forwarding details provide a basis for checking whether a beneficiary record can be used as an Accounting reference.',
   'The search and filter results provide a basis for selecting the beneficiary records relevant to the assigned reference request.',
   'The beneficiary identifiers and program information provide a basis for comparing exported references with the supplied evaluation records.',
  ],
  'PE': [
   'The requested beneficiary list or dashboard finishes loading within [T01: ___ seconds] after I open it.',
   'The beneficiary CSV download completes within [T07: ___ seconds] after I request the export.',
  ],
  'UX': [
   'The Accounting navigation labels identify the reference pages they open.',
   'The search and filter labels identify the record attributes used to narrow the reference list.',
  ],
  'RE': [
   'The displayed beneficiary count matches the eligible FASSG-forwarded evaluation records.',
   'A beneficiary reference reopened without an intervening authorized update contains the same recorded details.',
   'The exported records correspond to the filters selected during the evaluation.',
  ],
  'SEC': [
   'In the demonstrated access test, a beneficiary record that has not been forwarded by FASSG is unavailable through the Accounting account.',
   'In the demonstrated edit test, an attempted beneficiary-record change through the Accounting account leaves the stored record unchanged.',
  ],
 },
}


def el(tag, attrs=None, text=None):
    node = ET.Element(f'{{{W}}}{tag}', {f'{{{W}}}{k}': str(v) for k, v in (attrs or {}).items()})
    if text is not None:
        node.text = text
    return node


def para(text='', bold=False, center=False, size=21, after=100, keep=False):
    p = el('p')
    pp = el('pPr')
    if keep:
        pp.append(el('keepNext'))
    pp.append(el('spacing', {'before': 0, 'after': after, 'line': 240, 'lineRule': 'auto'}))
    if center:
        pp.append(el('jc', {'val': 'center'}))
    p.append(pp)
    r = el('r')
    rp = el('rPr')
    rp.append(el('rFonts', {'ascii': 'Arial', 'hAnsi': 'Arial', 'cs': 'Arial'}))
    if bold:
        rp.append(el('b'))
    rp.append(el('sz', {'val': size}))
    rp.append(el('szCs', {'val': size}))
    r.append(rp)
    t = el('t', text=text)
    t.set('{http://www.w3.org/XML/1998/namespace}space', 'preserve')
    r.append(t)
    p.append(r)
    return p


def table(rows, widths, header=True):
    tbl = el('tbl')
    pr = el('tblPr')
    pr.append(el('tblW', {'w': sum(widths), 'type': 'dxa'}))
    pr.append(el('jc', {'val': 'center'}))
    borders = el('tblBorders')
    for edge in ['top', 'left', 'bottom', 'right', 'insideH', 'insideV']:
        borders.append(el(edge, {'val': 'single', 'sz': 4, 'color': '707070'}))
    pr.append(borders)
    pr.append(el('tblLayout', {'type': 'fixed'}))
    margins = el('tblCellMar')
    for edge in ['top', 'left', 'bottom', 'right']:
        margins.append(el(edge, {'w': 75 if edge in ['top', 'bottom'] else 90, 'type': 'dxa'}))
    pr.append(margins)
    tbl.append(pr)
    grid = el('tblGrid')
    for width in widths:
        grid.append(el('gridCol', {'w': width}))
    tbl.append(grid)
    for i, cells in enumerate(rows):
        row = el('tr')
        rp = el('trPr')
        rp.append(el('cantSplit'))
        if i == 0 and header:
            rp.append(el('tblHeader'))
        row.append(rp)
        for j, text in enumerate(cells):
            cell = el('tc')
            cp = el('tcPr')
            cp.append(el('tcW', {'w': widths[j], 'type': 'dxa'}))
            if i == 0 and header:
                cp.append(el('shd', {'val': 'clear', 'fill': 'E7EDF3'}))
            cp.append(el('vAlign', {'val': 'center'}))
            cell.append(cp)
            cell.append(para(text, bold=i == 0 and header, center=j > 0, size=20, after=0, keep=i == 0 and header))
            row.append(cell)
        tbl.append(row)
    return tbl


INTRO = (
 'Rate each statement using only the task or demonstration you observed. '
 'Agreement and disagreement are both valid responses. Select one response per item; '
 'use N/A when the feature was not tested or there is insufficient evidence. '
 'Do not use 3 (Neutral) to mean that a feature was not observed.'
)
SCALE = '5 = Strongly Agree; 4 = Agree; 3 = Neutral; 2 = Disagree; 1 = Strongly Disagree; N/A = Not applicable / Not observed.'
PENDING = 'REVIEW DRAFT: Adviser-approved timing limits and test conditions are pending. Complete every timing field before administration.'


def build_document(original, form):
    root = ET.fromstring(original)
    old_body = root.find('w:body', NS)
    sect = old_body.find('w:sectPr', NS)
    width = 10224
    if sect is not None:
        pg = sect.find('w:pgSz', NS)
        margins = sect.find('w:pgMar', NS)
        width = int(pg.get(f'{{{W}}}w')) - int(margins.get(f'{{{W}}}left')) - int(margins.get(f'{{{W}}}right'))
    body = el('body')
    body.append(para(form['title'], bold=True, center=True, size=23, after=120))
    body.append(para(PENDING, bold=True, size=19))
    body.append(para('Name: ____________________________________  Date: __________________'))
    body.append(para('Device / browser: __________________________  Evaluation session: __________'))
    body.append(para(INTRO))
    body.append(para(SCALE, size=20))
    body.append(para('For timed items, the facilitator records elapsed time against the approved limit. Security items are rated only after an authorized demonstration using synthetic test records.', size=20))
    for section, heading in SECTIONS.items():
        if section not in form:
            continue
        body.append(para(heading, bold=True, size=21, after=70, keep=True))
        if section == 'DS':
            body.append(para('Decision support means information used for human judgment, not automatic ranking, selection or approval.', size=19, after=70, keep=True))
        if section == 'SEC':
            body.append(para('These items assess observed controls; they do not certify legal compliance or protection against every attack.', size=19, after=70, keep=True))
        rows = [['Statement', '1', '2', '3', '4', '5', 'N/A']]
        for i, statement in enumerate(form[section], 1):
            rows.append([f'{section}-{i:02d}. {statement}', '', '', '', '', '', ''])
        body.append(table(rows, [width-3480]+[580]*6))
        body.append(para('', after=50, size=4))
    body.append(para('Comments, difficulties encountered, and suggestions:', bold=True, keep=True))
    body.append(para('________________________________________________________________________'))
    body.append(para('________________________________________________________________________'))
    body.append(para('________________________________', center=True, after=0))
    body.append(para('Signature over printed name', center=True, size=20))
    # Keep the original namespace declarations and exact section properties.
    # The original package includes the institution's existing letterhead/footer.
    serial = ET.tostring(body, encoding='utf-8')
    raw_sect = re.search(rb'<w:sectPr\b[\s\S]*?</w:sectPr>', original)
    assert raw_sect is not None
    serial = serial.replace(b'</w:body>', raw_sect.group(0)+b'</w:body>')
    revised, count = re.subn(rb'<w:body\b[^>]*>[\s\S]*?</w:body>', lambda _: serial, original, count=1)
    assert count == 1
    ET.fromstring(revised)
    return revised


def write_guide(item_counts):
    content = '''# SponsorFlow questionnaire revisions - 11 November 2026

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

'''
    content += '| Form | Functional | Decision support | Performance | Usability | Reliability | Security | Total |\n|---|---:|---:|---:|---:|---:|---:|---:|\n'
    for role, counts in item_counts.items():
        content += '| ' + role + ' | ' + ' | '.join(str(counts.get(s, 0)) for s in SECTIONS) + ' | ' + str(sum(counts.values())) + ' |\n'
    (OUT/'Revision_Guide.md').write_text(content, encoding='utf-8')


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    manifest = {'status': 'REVIEW_DRAFT_TIMING_LIMITS_PENDING', 'date': '2026-11-11', 'forms': []}
    counts = {}
    markdown = ['# SponsorFlow - Revised questionnaire content', '', PENDING, '', INTRO, '', SCALE, '']
    for role, form in FORMS.items():
        source = SOURCE/f'SURVEY_QUESTIONNAIRE FOR {role}.docx'
        before = hashlib.sha256(source.read_bytes()).hexdigest()
        dest = OUT/f'SURVEY_QUESTIONNAIRE_FOR_{role}_REVISED_REVIEW.docx'
        with ZipFile(source) as zin, ZipFile(dest, 'w', compression=ZIP_DEFLATED) as zout:
            for info in zin.infolist():
                data = zin.read(info.filename)
                if info.filename == 'word/document.xml':
                    data = build_document(data, form)
                zout.writestr(info, data)
        after = hashlib.sha256(source.read_bytes()).hexdigest()
        assert before == after, f'Original changed: {source}'
        counts[role] = {section: len(form[section]) for section in SECTIONS if section in form}
        manifest['forms'].append({'role': role, 'source': str(source), 'source_sha256_before': before, 'source_sha256_after': after, 'output': dest.name, 'output_sha256': hashlib.sha256(dest.read_bytes()).hexdigest(), 'counts': counts[role]})
        markdown += [f'## {form["title"]}', '']
        for section, heading in SECTIONS.items():
            if section in form:
                markdown += [f'### {heading}', '']
                markdown += [f'- **{section}-{i:02d}** {statement}' for i, statement in enumerate(form[section], 1)]
                markdown.append('')
    (OUT/'Questionnaire_Content.md').write_text('\n'.join(markdown), encoding='utf-8')
    (OUT/'Preservation_Manifest.json').write_text(json.dumps(manifest, indent=2), encoding='utf-8')
    with (OUT/'Timing_Targets.csv').open('w', newline='', encoding='utf-8-sig') as f:
        writer = csv.writer(f)
        writer.writerow(['Timing ID', 'Operation', 'Approved limit (seconds)', 'Measurement boundary', 'Approved workload and file/database size', 'Environment and network', 'Approval authority/reference', 'Status'])
        for tid, (operation, boundary) in TIMING.items():
            writer.writerow([tid, operation, '', boundary, '', '', '', 'PENDING_USER_SUPPLIED_ADVISER_LIMIT'])
    with (OUT/'Timing_Observation_Template.csv').open('w', newline='', encoding='utf-8-sig') as f:
        writer = csv.writer(f)
        writer.writerow(['Session', 'Role', 'Item ID', 'Timing ID', 'Approved limit seconds', 'Fixture/workload', 'Device/browser/network', 'Attempt', 'Elapsed seconds', 'Completed/error', 'Within limit', 'Notes'])
        for role, form in FORMS.items():
            for i, statement in enumerate(form['PE'], 1):
                tid = re.search(r'\[(T\d+):', statement).group(1)
                writer.writerow(['', role, f'PE-{i:02d}', tid]+['']*8)
    write_guide(counts)
    print(json.dumps({'forms': len(FORMS), 'items': sum(sum(c.values()) for c in counts.values()), 'decision_support_items': sum(c.get('DS', 0) for c in counts.values()), 'security_items': sum(c.get('SEC', 0) for c in counts.values()), 'output_directory': str(OUT)}, indent=2))


if __name__ == '__main__':
    main()
