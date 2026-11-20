import subprocess, os, sys, json, time, base64, asyncio, urllib.request, io

ARTIFACT_DIR = r"C:\Users\princ\.gemini\antigravity\brain\19b5d224-9ec9-4a8a-aec3-032c3dbb8a87"
CHROME = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
BASE = "http://127.0.0.1:8001"

try:
    from PIL import Image
except ImportError:
    subprocess.run([sys.executable, "-m", "pip", "install", "Pillow", "--quiet"], check=True)
    from PIL import Image

import websockets

proc = subprocess.Popen([
    CHROME, "--headless=new", "--disable-gpu", "--no-sandbox",
    "--remote-debugging-port=9282",
    "--window-size=1920,1080",
    "about:blank"
], stdout=subprocess.PIPE, stderr=subprocess.PIPE)

time.sleep(2.5)

resp = urllib.request.urlopen("http://127.0.0.1:9282/json")
targets = json.loads(resp.read())
ws_url = targets[0]["webSocketDebuggerUrl"]
print("Connected to Chrome via CDP port 9282")

viewports = [
    (1920, 1080, "1920x1080"),
    (1600, 900, "1600x900"),
    (1440, 900, "1440x900"),
    (1366, 768, "1366x768"),
    (768, 1024, "768x1024"),
    (430, 932, "430x932"),
    (390, 844, "390x844"),
    (360, 800, "360x800"),
]

audit_results = {
    "programs": {},
    "application": {},
    "filename_tests": {}
}

async def send_cmd(ws, msg_id_holder, method, params=None):
    msg_id_holder[0] += 1
    call_id = msg_id_holder[0]
    payload = {"id": call_id, "method": method, "params": params or {}}
    await ws.send(json.dumps(payload))
    while True:
        r = json.loads(await ws.recv())
        if r.get("id") == call_id:
            return r.get("result", {})

measure_programs_js = """
(function() {
    const r = {};
    r.docHorizontalOverflow = document.documentElement.scrollWidth - document.documentElement.clientWidth;
    r.viewportWidth = window.innerWidth;
    r.viewportHeight = window.innerHeight;

    // Toolbar measurement
    const toolbar = document.querySelector('.sf-programs-toolbar');
    const searchInput = document.querySelector('.sf-toolbar-input');
    const searchGroup = document.querySelector('.sf-toolbar-input-group');
    const categorySelect = document.querySelector('.sf-toolbar-select');
    const resetBtn = document.querySelector('.sf-btn-reset-filters');

    if (toolbar) {
        r.toolbar = {
            searchGroupHeight: searchGroup ? Math.round(searchGroup.getBoundingClientRect().height) : null,
            selectHeight: categorySelect ? Math.round(categorySelect.getBoundingClientRect().height) : null,
            resetBtnHeight: resetBtn ? Math.round(resetBtn.getBoundingClientRect().height) : null,
            resetBtnBg: resetBtn ? getComputedStyle(resetBtn).backgroundColor : null,
            resetBtnColor: resetBtn ? getComputedStyle(resetBtn).color : null,
            resetBtnBorderColor: resetBtn ? getComputedStyle(resetBtn).borderColor : null,
            resetBtnBorderRadius: resetBtn ? getComputedStyle(resetBtn).borderRadius : null,
            resetBtnText: resetBtn ? resetBtn.textContent.trim() : null
        };
    }

    // Program cards measurement
    const cards = Array.from(document.querySelectorAll('.card.sf-card.h-100'));
    r.cardsCount = cards.length;
    r.cardHeights = cards.map(c => Math.round(c.getBoundingClientRect().height));
    
    // Check titles and apply buttons
    const applyBtns = Array.from(document.querySelectorAll('.sf-program-card-btn'));
    if (applyBtns.length > 0) {
        const b = applyBtns[0];
        const cs = getComputedStyle(b);
        r.applyBtn = {
            height: Math.round(b.getBoundingClientRect().height),
            bg: cs.backgroundColor,
            color: cs.color,
            borderRadius: cs.borderRadius,
            fontWeight: cs.fontWeight
        };
    }

    return r;
})();
"""

measure_app_js = """
(function() {
    const r = {};
    r.docHorizontalOverflow = document.documentElement.scrollWidth - document.documentElement.clientWidth;
    r.viewportWidth = window.innerWidth;
    r.viewportHeight = window.innerHeight;

    // Step badges
    const badges = Array.from(document.querySelectorAll('.sf-step-badge'));
    r.badgesCount = badges.length;
    if (badges.length > 0) {
        const br = badges[0].getBoundingClientRect();
        const cs = getComputedStyle(badges[0]);
        r.badge = {
            width: Math.round(br.width),
            height: Math.round(br.height),
            bg: cs.backgroundColor,
            color: cs.color
        };
    }

    // Readonly fields
    const readonlyInputs = Array.from(document.querySelectorAll('.sf-readonly-field'));
    r.readonlyInputsCount = readonlyInputs.length;
    if (readonlyInputs.length > 0) {
        const cs = getComputedStyle(readonlyInputs[0]);
        r.readonlyInput = {
            height: Math.round(readonlyInputs[0].getBoundingClientRect().height),
            bg: cs.backgroundColor,
            color: cs.color
        };
    }

    // Current GPA and Current Address
    const gpaInput = document.getElementById('current_gpa');
    const addressInput = readonlyInputs.find(i => i.getAttribute('aria-label') && i.getAttribute('aria-label').includes('Address'));
    if (gpaInput) {
        r.gpaInputHeight = Math.round(gpaInput.getBoundingClientRect().height);
    }
    if (addressInput) {
        r.addressInputHeight = Math.round(addressInput.getBoundingClientRect().height);
    }

    // Document upload cards
    const uploadCards = Array.from(document.querySelectorAll('.sf-upload-card'));
    r.uploadCardsCount = uploadCards.length;
    r.uploadCardHeights = uploadCards.map(c => Math.round(c.getBoundingClientRect().height));

    // Submit and Cancel buttons
    const submitBtn = document.querySelector('.btn-navy-submit');
    const cancelBtn = document.querySelector('.btn-app-cancel');

    if (submitBtn) {
        const br = submitBtn.getBoundingClientRect();
        const cs = getComputedStyle(submitBtn);
        r.submitBtn = {
            width: Math.round(br.width),
            height: Math.round(br.height),
            bg: cs.backgroundColor,
            color: cs.color,
            text: submitBtn.textContent.trim()
        };
    }

    if (cancelBtn) {
        const br = cancelBtn.getBoundingClientRect();
        const cs = getComputedStyle(cancelBtn);
        r.cancelBtn = {
            width: Math.round(br.width),
            height: Math.round(br.height),
            bg: cs.backgroundColor,
            color: cs.color,
            borderColor: cs.borderColor,
            text: cancelBtn.textContent.trim()
        };
    }

    return r;
})();
"""

async def main():
    msg_id = [0]
    async with websockets.connect(ws_url, max_size=50*1024*1024) as ws:
        await send_cmd(ws, msg_id, "Page.enable")
        await send_cmd(ws, msg_id, "DOM.enable")
        await send_cmd(ws, msg_id, "Network.enable")

        # Step 1: Login
        await send_cmd(ws, msg_id, "Page.navigate", {"url": f"{BASE}/login"})
        await asyncio.sleep(2)
        login_script = """
        (async function() {
            document.getElementById('email').value = 'student@sponsorflow.test';
            document.getElementById('password').value = 'password123';
            document.getElementById('sign-in-btn').click();
        })()
        """
        await send_cmd(ws, msg_id, "Runtime.evaluate", {"expression": login_script})
        await asyncio.sleep(2)

        modal_script = """
        (async function() {
            const check = document.getElementById('privacy-agree-check');
            if (check) {
                check.checked = true;
                if (window.toggleContinueBtn) window.toggleContinueBtn();
                document.getElementById('privacy-continue-btn').click();
            }
        })()
        """
        await send_cmd(ws, msg_id, "Runtime.evaluate", {"expression": modal_script})
        await asyncio.sleep(3)

        # Step 2: Audit Sponsorship Opportunities across all 8 viewports
        print("Auditing /student/programs across 8 viewports...")
        for width, height, name in viewports:
            is_mobile = width < 768
            await send_cmd(ws, msg_id, "Emulation.setDeviceMetricsOverride", {
                "width": width, "height": height, "deviceScaleFactor": 1, "mobile": is_mobile
            })
            await send_cmd(ws, msg_id, "Page.navigate", {"url": f"{BASE}/student/programs"})
            await asyncio.sleep(1.5)

            eval_res = await send_cmd(ws, msg_id, "Runtime.evaluate", {
                "expression": measure_programs_js, "returnByValue": True
            })
            data = eval_res.get("result", {}).get("value", {})
            audit_results["programs"][name] = data

            ss = await send_cmd(ws, msg_id, "Page.captureScreenshot", {"format": "png"})
            with open(os.path.join(ARTIFACT_DIR, f"student-programs-{name}.png"), "wb") as f:
                f.write(base64.b64decode(ss["data"]))
            print(f"[Programs {name}] saved - overflow: {data.get('docHorizontalOverflow')}px")

        # Step 3: Audit Application Form across all 8 viewports
        print("Auditing /student/applications/create/6 across 8 viewports...")
        for width, height, name in viewports:
            is_mobile = width < 768
            await send_cmd(ws, msg_id, "Emulation.setDeviceMetricsOverride", {
                "width": width, "height": height, "deviceScaleFactor": 1, "mobile": is_mobile
            })
            await send_cmd(ws, msg_id, "Page.navigate", {"url": f"{BASE}/student/applications/create/6"})
            await asyncio.sleep(1.5)

            eval_res = await send_cmd(ws, msg_id, "Runtime.evaluate", {
                "expression": measure_app_js, "returnByValue": True
            })
            data = eval_res.get("result", {}).get("value", {})
            audit_results["application"][name] = data

            ss = await send_cmd(ws, msg_id, "Page.captureScreenshot", {"format": "png"})
            with open(os.path.join(ARTIFACT_DIR, f"student-application-create-{name}.png"), "wb") as f:
                f.write(base64.b64decode(ss["data"]))
            print(f"[Application {name}] saved - overflow: {data.get('docHorizontalOverflow')}px")

        # Step 4: High resolution close-ups & interactive file upload tests at 1920x1080
        await send_cmd(ws, msg_id, "Emulation.setDeviceMetricsOverride", {
            "width": 1920, "height": 1080, "deviceScaleFactor": 1, "mobile": False
        })
        await send_cmd(ws, msg_id, "Page.navigate", {"url": f"{BASE}/student/programs"})
        await asyncio.sleep(1.5)

        # Programs close-ups
        img_prog = Image.open(os.path.join(ARTIFACT_DIR, "student-programs-1920x1080.png"))
        # Toolbar crop
        tb_eval = await send_cmd(ws, msg_id, "Runtime.evaluate", {
            "expression": "JSON.stringify(document.querySelector('.sf-programs-toolbar').getBoundingClientRect())",
            "returnByValue": True
        })
        tbr = json.loads(tb_eval.get("result", {}).get("value"))
        crop_tb = img_prog.crop((int(tbr["left"]) - 10, int(tbr["top"]) - 15, int(tbr["right"]) + 10, int(tbr["bottom"]) + 15))
        crop_tb.save(os.path.join(ARTIFACT_DIR, "closeup-programs-toolbar.png"))
        print("Saved closeup-programs-toolbar.png")

        # Cards crop (first row of cards)
        cards_eval = await send_cmd(ws, msg_id, "Runtime.evaluate", {
            "expression": "JSON.stringify(document.querySelector('.row.g-4').getBoundingClientRect())",
            "returnByValue": True
        })
        cr = json.loads(cards_eval.get("result", {}).get("value"))
        crop_cards = img_prog.crop((int(cr["left"]) - 10, int(cr["top"]) - 10, int(cr["right"]) + 10, min(1080, int(cr["top"]) + 550)))
        crop_cards.save(os.path.join(ARTIFACT_DIR, "closeup-programs-cards.png"))
        print("Saved closeup-programs-cards.png")

        # Step 5: Application page close-ups & Filename Testing
        await send_cmd(ws, msg_id, "Page.navigate", {"url": f"{BASE}/student/applications/create/6"})
        await asyncio.sleep(1.5)

        img_app = Image.open(os.path.join(ARTIFACT_DIR, "student-application-create-1920x1080.png"))

        # Profile crop
        prof_eval = await send_cmd(ws, msg_id, "Runtime.evaluate", {
            "expression": "JSON.stringify(document.querySelector('.card.sf-card.mb-4').getBoundingClientRect())",
            "returnByValue": True
        })
        pr = json.loads(prof_eval.get("result", {}).get("value"))
        crop_prof = img_app.crop((int(pr["left"]) - 10, int(pr["top"]) - 10, int(pr["right"]) + 10, int(pr["bottom"]) + 10))
        crop_prof.save(os.path.join(ARTIFACT_DIR, "closeup-application-header-profile.png"))
        print("Saved closeup-application-header-profile.png")

        # Supporting docs default crop
        docs_eval = await send_cmd(ws, msg_id, "Runtime.evaluate", {
            "expression": "JSON.stringify(document.querySelectorAll('.card.sf-card')[2].getBoundingClientRect())",
            "returnByValue": True
        })
        dr = json.loads(docs_eval.get("result", {}).get("value"))
        crop_docs = img_app.crop((int(dr["left"]) - 10, int(dr["top"]) - 10, int(dr["right"]) + 10, int(dr["bottom"]) + 10))
        crop_docs.save(os.path.join(ARTIFACT_DIR, "closeup-supporting-docs-default.png"))
        print("Saved closeup-supporting-docs-default.png")

        # Sidebar submit and cancel crop
        sidebar_eval = await send_cmd(ws, msg_id, "Runtime.evaluate", {
            "expression": "JSON.stringify(document.querySelector('.col-lg-4 .card.sf-card').getBoundingClientRect())",
            "returnByValue": True
        })
        sr = json.loads(sidebar_eval.get("result", {}).get("value"))
        crop_sidebar = img_app.crop((int(sr["left"]) - 10, int(sr["top"]) - 10, int(sr["right"]) + 10, int(sr["bottom"]) + 10))
        crop_sidebar.save(os.path.join(ARTIFACT_DIR, "closeup-application-submit-cancel.png"))
        print("Saved closeup-application-submit-cancel.png")

        # Simulate Cancel Hover
        await send_cmd(ws, msg_id, "Runtime.evaluate", {
            "expression": """
            (function() {
                const btn = document.querySelector('.btn-app-cancel');
                btn.style.setProperty('background-color', '#dc2626', 'important');
                btn.style.setProperty('border-color', '#dc2626', 'important');
                btn.style.setProperty('color', '#ffffff', 'important');
            })()
            """
        })
        await asyncio.sleep(0.3)
        ss_chover = await send_cmd(ws, msg_id, "Page.captureScreenshot", {"format": "png"})
        img_chover = Image.open(io.BytesIO(base64.b64decode(ss_chover["data"])))
        crop_chover = img_chover.crop((int(sr["left"]) - 10, int(sr["top"]) + 150, int(sr["right"]) + 10, int(sr["bottom"]) + 10))
        crop_chover.save(os.path.join(ARTIFACT_DIR, "closeup-cancel-hover-red.png"))
        print("Saved closeup-cancel-hover-red.png")

        # Reset Cancel hover
        await send_cmd(ws, msg_id, "Runtime.evaluate", {
            "expression": """
            (function() {
                const btn = document.querySelector('.btn-app-cancel');
                btn.style.removeProperty('background-color');
                btn.style.removeProperty('border-color');
                btn.style.removeProperty('color');
            })()
            """
        })

        # Step 6: Test Short Filename, Long Filename, and Long Unbroken Filename
        test_files_js = """
        (function() {
            const shortName = 'grade_slip_2026.pdf';
            const longName = 'dorsu_official_certified_true_copy_transcript_of_records_first_semester_academic_year_2025_2026_with_seal_and_registrar_signature.pdf';
            const unbrokenName = 'very_long_unbroken_filename_without_any_spaces_for_testing_css_overflow_wrap_and_word_break_behavior_sample_document_2026_final_version.pdf';
            const jpgName = 'certificate_of_indigency_barangay_san_jose_original_stamped_2026.jpg';
            const pngName = 'dorsu_cor_student_registration_proof_2026.png';

            // Function to simulate file input selection via DataTransfer
            function attachFakeFile(inputId, fileName, fileType) {
                const input = document.getElementById(inputId);
                const file = new File(['fake content'], fileName, { type: fileType });
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                window.handleFileSelect(input);
            }

            attachFakeFile('grade_slip', shortName, 'application/pdf');
            attachFakeFile('indigency_doc', longName, 'image/jpeg');
            attachFakeFile('cor_doc', unbrokenName, 'image/png');
            attachFakeFile('proof_of_residence', jpgName, 'image/jpeg');
            attachFakeFile('employee_id_doc', pngName, 'image/png');

            // Measure the cards after selection
            const cards = Array.from(document.querySelectorAll('.sf-upload-card'));
            return {
                cardsCount: cards.length,
                cardHeights: cards.map(c => Math.round(c.getBoundingClientRect().height)),
                allCardsEqualHeight: (new Set(cards.map(c => Math.round(c.getBoundingClientRect().height)))).size <= 1,
                gradeSlipFilename: document.getElementById('upload-filename-grade_slip').textContent,
                gradeSlipTitle: document.getElementById('upload-filename-grade_slip').title,
                indigencyFilename: document.getElementById('upload-filename-indigency_doc').textContent,
                indigencyTitle: document.getElementById('upload-filename-indigency_doc').title,
                corFilename: document.getElementById('upload-filename-cor_doc').textContent,
                corTitle: document.getElementById('upload-filename-cor_doc').title,
                actionTexts: Array.from(document.querySelectorAll('.sf-upload-action')).map(a => a.textContent.trim())
            };
        })()
        """
        file_res = await send_cmd(ws, msg_id, "Runtime.evaluate", {
            "expression": test_files_js, "returnByValue": True
        })
        file_data = file_res.get("result", {}).get("value", {})
        audit_results["filename_tests"] = file_data
        print("Filename test measurements:", json.dumps(file_data, indent=2))

        await asyncio.sleep(0.5)

        # Capture Supporting Docs with Files attached
        ss_attached = await send_cmd(ws, msg_id, "Page.captureScreenshot", {"format": "png"})
        img_attached = Image.open(io.BytesIO(base64.b64decode(ss_attached["data"])))
        crop_docs_attached = img_attached.crop((int(dr["left"]) - 10, int(dr["top"]) - 10, int(dr["right"]) + 10, int(dr["bottom"]) + 150))
        crop_docs_attached.save(os.path.join(ARTIFACT_DIR, "closeup-supporting-docs-with-files.png"))
        print("Saved closeup-supporting-docs-with-files.png")

        # Close-up of short filename card (Grade slip)
        card_gs_eval = await send_cmd(ws, msg_id, "Runtime.evaluate", {
            "expression": "JSON.stringify(document.getElementById('upload-card-grade_slip').getBoundingClientRect())",
            "returnByValue": True
        })
        gs_r = json.loads(card_gs_eval.get("result", {}).get("value"))
        crop_gs = img_attached.crop((int(gs_r["left"]) - 8, int(gs_r["top"]) - 8, int(gs_r["right"]) + 8, int(gs_r["bottom"]) + 8))
        crop_gs.save(os.path.join(ARTIFACT_DIR, "closeup-upload-short-filename.png"))
        print("Saved closeup-upload-short-filename.png")

        # Close-up of extremely long filename card (Indigency)
        card_ind_eval = await send_cmd(ws, msg_id, "Runtime.evaluate", {
            "expression": "JSON.stringify(document.getElementById('upload-card-indigency_doc').getBoundingClientRect())",
            "returnByValue": True
        })
        ind_r = json.loads(card_ind_eval.get("result", {}).get("value"))
        crop_ind = img_attached.crop((int(ind_r["left"]) - 8, int(ind_r["top"]) - 8, int(ind_r["right"]) + 8, int(ind_r["bottom"]) + 8))
        crop_ind.save(os.path.join(ARTIFACT_DIR, "closeup-upload-long-filename.png"))
        print("Saved closeup-upload-long-filename.png")

        # Close-up of unbroken long filename card (COR)
        card_cor_eval = await send_cmd(ws, msg_id, "Runtime.evaluate", {
            "expression": "JSON.stringify(document.getElementById('upload-card-cor_doc').getBoundingClientRect())",
            "returnByValue": True
        })
        cor_r = json.loads(card_cor_eval.get("result", {}).get("value"))
        crop_cor = img_attached.crop((int(cor_r["left"]) - 8, int(cor_r["top"]) - 8, int(cor_r["right"]) + 8, int(cor_r["bottom"]) + 8))
        crop_cor.save(os.path.join(ARTIFACT_DIR, "closeup-upload-unbroken-filename.png"))
        print("Saved closeup-upload-unbroken-filename.png")

        # Step 7: Mobile File Upload Presentation (390x844) with files attached
        await send_cmd(ws, msg_id, "Emulation.setDeviceMetricsOverride", {
            "width": 390, "height": 844, "deviceScaleFactor": 1, "mobile": True
        })
        await asyncio.sleep(0.5)
        # Re-attach files in mobile context
        await send_cmd(ws, msg_id, "Runtime.evaluate", {"expression": test_files_js})
        await asyncio.sleep(0.5)

        # Scroll to upload section
        await send_cmd(ws, msg_id, "Runtime.evaluate", {
            "expression": "document.querySelectorAll('.card.sf-card')[2].scrollIntoView({ block: 'start' });"
        })
        await asyncio.sleep(0.5)

        ss_mob_attached = await send_cmd(ws, msg_id, "Page.captureScreenshot", {"format": "png"})
        img_mob_attached = Image.open(io.BytesIO(base64.b64decode(ss_mob_attached["data"])))
        # Crop upload cards section on mobile
        mob_docs_eval = await send_cmd(ws, msg_id, "Runtime.evaluate", {
            "expression": "JSON.stringify(document.querySelectorAll('.card.sf-card')[2].getBoundingClientRect())",
            "returnByValue": True
        })
        md_r = json.loads(mob_docs_eval.get("result", {}).get("value"))
        top_crop = max(0, int(md_r["top"]))
        bottom_crop = min(img_mob_attached.height, top_crop + 650)
        crop_mob_docs = img_mob_attached.crop((0, top_crop, img_mob_attached.width, bottom_crop))
        crop_mob_docs.save(os.path.join(ARTIFACT_DIR, "closeup-upload-mobile-presentation.png"))
        print("Saved closeup-upload-mobile-presentation.png")

        # Save all audit results to JSON
        with open(os.path.join(ARTIFACT_DIR, "student_programs_and_application_audit_data.json"), "w") as f:
            json.dump(audit_results, f, indent=2)
        print("Saved student_programs_and_application_audit_data.json")

asyncio.run(main())
proc.terminate()
