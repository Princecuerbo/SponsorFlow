"""Focused Word document revisions; no application or database operations."""
from pathlib import Path
from zipfile import ZipFile
from xml.dom import minidom
from datetime import datetime
from contextlib import ExitStack
import argparse
import hashlib
import json
import os
import re
import tempfile

SOURCE = Path(r"C:\Users\princ\Blackcloudie\IT4B FILES\1ST SEM\ITP 140 - CAPSTONE PROJECT & RESEARCH 2\SPONSORFLOW LATEST DOCS CAPS2\QUESTIONNAIRE AND VALIDATION")
OUTPUT = Path(__file__).resolve().parent
HEADINGS = ["FUNCTIONAL SUITABILITY", "PERFORMANCE EFFICIENCY", "USABILITY", "RELIABILITY"]
TIMING_NOTE = ("The stated time limits are proposed estimates for evaluation, not measured results or adviser-approved thresholds. "
               "Record the test environment, data volume, file size, and elapsed time for each timed task.")
ADDITIONS = {
    "STUDENTS": [
        "I used the displayed program criteria to decide which available sponsorship program to apply for.",
        "I used the verification and application statuses to determine my next action.",
    ],
    "SPONSOR": [
        "I used the displayed candidate-source and verification information when deciding on the submitted batch.",
        "I used the roster's verification statuses to identify matters requiring clarification before making a whole-batch decision.",
        "I referred to recorded decisions and confirmation evidence when reviewing the batch's decision history.",
    ],
    "FASSG": [
        "I compared candidate qualifications with the selected program's requirements when assessing eligibility.",
        "I used the batch-member review results to identify candidates requiring further verification.",
        "I used the selected-candidate count and available slots when deciding the batch roster.",
        "I used the filtered reports to identify programs or applications requiring follow-up.",
        "I used the recorded Sponsor decision and handoff status to determine whether a batch was ready for forwarding to Accounting.",
    ],
    "DICT": [
        "I used the account's role and status when deciding whether its access needed to be changed.",
        "I used the actor, action, and timestamp in the audit log to identify the administrative event requiring investigation.",
    ],
    "ACCOUNTING": [
        "I used the Sponsor-confirmation and FASSG-forwarding details to determine whether a beneficiary record could be used for the assigned reference task.",
        "I used the search criteria and filters to identify beneficiary records relevant to the requested reference.",
        "I used the displayed student and program identifiers to compare the exported list with the selected beneficiary records.",
    ],
}


def sha(data):
    return hashlib.sha256(data).hexdigest()


def text(node):
    return "".join(t.firstChild.data for t in node.getElementsByTagName("w:t") if t.firstChild)


def children(node, tag):
    return [c for c in node.childNodes if c.nodeType == c.ELEMENT_NODE and c.tagName == tag]


def rewrite_paragraph(paragraph, value):
    runs = paragraph.getElementsByTagName("w:r")
    props = runs[0].getElementsByTagName("w:rPr") if runs else []
    style = props[0].cloneNode(True) if props else None
    for child in list(paragraph.childNodes):
        if child.nodeType != child.ELEMENT_NODE or child.tagName != "w:pPr":
            paragraph.removeChild(child)
    doc = paragraph.ownerDocument
    run = doc.createElement("w:r")
    if style is not None:
        run.appendChild(style)
    element = doc.createElement("w:t")
    element.appendChild(doc.createTextNode(value))
    run.appendChild(element)
    paragraph.appendChild(run)


def clear_clone_identifiers(node):
    for el in [node, *node.getElementsByTagName("*")]:
        for name in list(el.attributes.keys()):
            if name in ("w14:paraId", "w14:textId") or name.startswith("w:rsid"):
                el.removeAttribute(name)
    for tag in ("w:bookmarkStart", "w:bookmarkEnd", "w:lastRenderedPageBreak", "w:proofErr"):
        for el in list(node.getElementsByTagName(tag)):
            el.parentNode.removeChild(el)


def sections(doc):
    result = {}
    current = None
    for row in doc.getElementsByTagName("w:tr"):
        cells = children(row, "w:tc")
        if not cells:
            continue
        value = text(cells[0]).strip()
        if value in HEADINGS:
            current = value
            result[current] = []
        elif current and re.match(r"^\d+\.", value):
            result[current].append(value)
    return result


def build():
    package = OUTPUT / ("questionnaires-alan-only-" + datetime.now().strftime("%Y%m%d-%H%M%S"))
    package.mkdir()
    (package / "originals").mkdir()
    (package / "revised").mkdir()
    manifest = []
    for role, additions in ADDITIONS.items():
        name = f"SURVEY_QUESTIONNAIRE FOR {role}.docx"
        original = SOURCE / name
        raw = original.read_bytes()
        backup = package / "originals" / name
        backup.write_bytes(raw)
        with ZipFile(original) as z:
            original_xml = z.read("word/document.xml")
            before_doc = minidom.parseString(original_xml)
            doc = minidom.parseString(original_xml)
            before = sections(before_doc)
            assert list(before) == HEADINGS
            assert "Neither Agree" not in text(doc)
            assert "[TARGET:" not in text(doc)
            assert all(re.search(r"within \d+ seconds", s) for s in before["PERFORMANCE EFFICIENCY"])
            rows = list(doc.getElementsByTagName("w:tr"))
            performance_header = next(r for r in rows if text(r) == "PERFORMANCE EFFICIENCY")
            template = rows[rows.index(performance_header) - 1]
            assert text(children(template, "w:tc")[0]) == before[HEADINGS[0]][-1]
            numbered = []
            for num, sentence in enumerate(additions, len(before[HEADINGS[0]]) + 1):
                assert sentence not in text(doc), "Revision already present; do not duplicate it."
                clone = template.cloneNode(True)
                clear_clone_identifiers(clone)
                cells = children(clone, "w:tc")
                assert len(cells) == 6
                value = f"{num}. {sentence}"
                numbered.append(value)
                paragraphs = children(cells[0], "w:p")
                rewrite_paragraph(paragraphs[0], value)
                for p in paragraphs[1:]:
                    cells[0].removeChild(p)
                assert all(not text(c).strip() for c in cells[1:])
                performance_header.parentNode.insertBefore(clone, performance_header)
            body = doc.getElementsByTagName("w:body")[0]
            instructions = next(p for p in children(body, "w:p") if text(p).startswith("Please rate each statement"))
            note = instructions.cloneNode(True)
            clear_clone_identifiers(note)
            rewrite_paragraph(note, TIMING_NOTE)
            body.insertBefore(note, instructions.nextSibling)
            revision = package / "revised" / name
            with ZipFile(revision, "w") as dest:
                dest.comment = z.comment
                for info in z.infolist():
                    dest.writestr(info, doc.toxml(encoding="UTF-8") if info.filename == "word/document.xml" else z.read(info.filename))
        with ZipFile(original) as old, ZipFile(revision) as new:
            assert new.testzip() is None
            assert old.namelist() == new.namelist()
            unchanged_parts = [n for n in old.namelist() if n != "word/document.xml"]
            assert all(old.read(n) == new.read(n) for n in unchanged_parts)
            after_doc = minidom.parseString(new.read("word/document.xml"))
        after = sections(after_doc)
        assert list(after) == HEADINGS, "No extra questionnaire section is allowed."
        assert after[HEADINGS[0]] == before[HEADINGS[0]] + numbered
        assert all(after[h] == before[h] for h in HEADINGS[1:])
        assert all(a.toxml() == b.toxml() for a, b in zip(before_doc.getElementsByTagName("w:sectPr"), after_doc.getElementsByTagName("w:sectPr")))
        # Remove only the added nodes from an independent copy and require an exact DOM match.
        restored = minidom.parseString(after_doc.toxml())
        for row in list(restored.getElementsByTagName("w:tr")):
            cells = children(row, "w:tc")
            if cells and text(cells[0]) in numbered:
                row.parentNode.removeChild(row)
        for p in list(children(restored.getElementsByTagName("w:body")[0], "w:p")):
            if text(p) == TIMING_NOTE:
                p.parentNode.removeChild(p)
        assert restored.toxml() == before_doc.toxml(), "Unexpected change to existing document content or formatting."
        manifest.append({"role": role, "original": str(original), "backup": str(backup), "revised": str(revision),
                         "original_sha256": sha(raw), "revised_sha256": sha(revision.read_bytes()),
                         "added_items": numbered, "counts_before": {k: len(v) for k, v in before.items()},
                         "counts_after": {k: len(v) for k, v in after.items()}, "package_parts_preserved": len(unchanged_parts),
                         "existing_document_nodes_preserved": True})
    (package / "manifest.json").write_text(json.dumps(manifest, indent=2), encoding="utf-8")
    review = ["# Sir Alan questionnaire revisions", "", "Decision-support items are integrated into FUNCTIONAL SUITABILITY. No additional section is introduced.",
              "Existing neutral wording, all 19 numerical thresholds and 3 = Neutral are preserved. Timing values are explicitly marked as estimates.",
              "Security revisions are outside this focused pass. Headers, footers, signature sections and existing table formatting are preserved.",
              "Word page layout has not been rendered; inserted rows may change pagination.", ""]
    for entry in manifest:
        review.extend(["## " + entry["role"], *entry["added_items"], ""])
    (package / "Changes.md").write_text("\n\n".join(review), encoding="utf-8")
    print(json.dumps({"package": str(package), "files": len(manifest), "added_items": sum(len(x["added_items"]) for x in manifest), "all_checks_passed": True}))


def apply(package):
    package = Path(package).resolve(strict=True)
    entries = json.loads((package / "manifest.json").read_text(encoding="utf-8"))
    assert {e["role"] for e in entries} == set(ADDITIONS) and len(entries) == 5
    # Open every target before writing; stop on an unavailable or stale file.
    with ExitStack() as stack:
        for e in entries:
            target = Path(e["original"])
            assert target.resolve().parent == SOURCE.resolve()
            assert target.name == f'SURVEY_QUESTIONNAIRE FOR {e["role"]}.docx'
            f = stack.enter_context(target.open("rb"))
            assert sha(f.read()) == e["original_sha256"], f"Source changed: {target.name}"
            assert sha(Path(e["backup"]).read_bytes()) == e["original_sha256"]
            assert sha(Path(e["revised"]).read_bytes()) == e["revised_sha256"]
    staged = []
    applied = []
    try:
        for e in entries:
            fd, temp = tempfile.mkstemp(prefix=".sf-questionnaire-", suffix=".docx", dir=SOURCE)
            staged.append(Path(temp))
            with os.fdopen(fd, "wb") as f:
                f.write(Path(e["revised"]).read_bytes())
        assert all(sha(Path(e["original"]).read_bytes()) == e["original_sha256"] for e in entries), "Source changed during staging."
        for e, temp in zip(entries, staged):
            os.replace(temp, e["original"])
            applied.append(e)
        assert all(sha(Path(e["original"]).read_bytes()) == e["revised_sha256"] for e in entries)
    except Exception:
        for e in applied:
            Path(e["original"]).write_bytes(Path(e["backup"]).read_bytes())
        raise
    finally:
        for temp in staged:
            if temp.exists():
                temp.unlink()
    report = {"applied": [e["original"] for e in entries], "verified": True, "backups": str(package / "originals")}
    (package / "applied.json").write_text(json.dumps(report, indent=2), encoding="utf-8")
    print(json.dumps(report))


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--apply", metavar="REVIEW_PACKAGE")
    args = parser.parse_args()
    if args.apply:
        apply(args.apply)
    else:
        build()
