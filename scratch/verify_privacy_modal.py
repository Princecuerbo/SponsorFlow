import subprocess, os, sys, json, time, base64, asyncio, urllib.request

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
    "--remote-debugging-port=9280",
    "--window-size=1920,1080",
    "about:blank"
], stdout=subprocess.PIPE, stderr=subprocess.PIPE)

time.sleep(2.5)

resp = urllib.request.urlopen("http://127.0.0.1:9280/json")
targets = json.loads(resp.read())
ws_url = targets[0]["webSocketDebuggerUrl"]
print("Connected to Chrome via CDP port 9280")

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

audit_results = {}

measure_js = """
(function() {
    const r = {};
    r.docHorizontalOverflow = document.documentElement.scrollWidth - document.documentElement.clientWidth;
    r.viewportWidth = window.innerWidth;
    r.viewportHeight = window.innerHeight;
    r.bodyScrollLocked = (document.body.style.overflow === 'hidden' && document.body.classList.contains('modal-open'));

    const modal = document.getElementById('privacyConsentModal');
    const dialog = modal ? modal.querySelector('.privacy-modal-dialog') : null;
    const card = modal ? modal.querySelector('.privacy-modal-card') : null;
    const header = modal ? modal.querySelector('.privacy-card-header') : null;
    const body = modal ? modal.querySelector('.privacy-modal-body') : null;
    const footer = modal ? modal.querySelector('.privacy-modal-footer') : null;
    const cancelBtn = document.getElementById('privacy-cancel-btn');
    const continueBtn = document.getElementById('privacy-continue-btn');
    const checkbox = document.getElementById('privacy-agree-check');
    const consentCard = document.getElementById('privacy-consent-card-wrapper');
    const consentTitle = consentCard ? consentCard.querySelector('.privacy-consent-title') : null;
    const cardWarning = modal ? modal.querySelector('.privacy-accent-card.card-warning') : null;

    if (dialog && card) {
        const dr = dialog.getBoundingClientRect();
        const cr = card.getBoundingClientRect();
        r.modal = {
            dialogWidth: Math.round(dr.width),
            cardWidth: Math.round(cr.width),
            cardHeight: Math.round(cr.height),
            cardTop: Math.round(cr.top),
            cardBottom: Math.round(cr.bottom),
            containedInViewport: (cr.bottom <= window.innerHeight && cr.top >= 0)
        };
    }

    if (body) {
        const br = body.getBoundingClientRect();
        const cs = getComputedStyle(body);
        r.body = {
            height: Math.round(br.height),
            scrollHeight: body.scrollHeight,
            clientHeight: body.clientHeight,
            isScrollable: body.scrollHeight > body.clientHeight,
            overflowY: cs.overflowY,
            scrollbarWidth: cs.scrollbarWidth
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

    if (continueBtn) {
        const br = continueBtn.getBoundingClientRect();
        const cs = getComputedStyle(continueBtn);
        r.continueBtn = {
            width: Math.round(br.width),
            height: Math.round(br.height),
            disabled: continueBtn.disabled,
            bg: cs.backgroundColor,
            color: cs.color,
            text: continueBtn.textContent.trim()
        };
    }

    if (checkbox && consentCard && consentTitle) {
        const cr = consentCard.getBoundingClientRect();
        const cbr = checkbox.getBoundingClientRect();
        const tr = consentTitle.getBoundingClientRect();
        const cs = getComputedStyle(consentCard);
        r.consent = {
            display: cs.display,
            alignItems: cs.alignItems,
            gap: cs.gap,
            checkboxTopRelative: Math.round(cbr.top - cr.top),
            titleTopRelative: Math.round(tr.top - cr.top),
            topAlignmentDelta: Math.abs(Math.round(cbr.top - tr.top))
        };
    }

    if (cardWarning) {
        const wr = cardWarning.getBoundingClientRect();
        r.cardWarning = {
            top: Math.round(wr.top),
            left: Math.round(wr.left),
            width: Math.round(wr.width),
            height: Math.round(wr.height),
            right: Math.round(wr.right),
            bottom: Math.round(wr.bottom)
        };
    }

    // Check all icons
    const icons = Array.from(modal.querySelectorAll('i')).map(i => i.className);
    r.icons = icons;

    return r;
})();
"""

async def main():
    async with websockets.connect(ws_url, max_size=50*1024*1024) as ws:
        msg_id = 0
        async def send(method, params=None):
            nonlocal msg_id
            msg_id += 1
            call_id = msg_id
            await ws.send(json.dumps({"id": call_id, "method": method, "params": params or {}}))
            while True:
                resp = json.loads(await ws.recv())
                if resp.get("id") == call_id:
                    return resp.get("result", {})

        await send("Page.enable")
        await send("DOM.enable")
        await send("CSS.enable")

        print("Testing all 8 viewports...")

        for width, height, name in viewports:
            is_mobile = width < 768
            await send("Emulation.setDeviceMetricsOverride", {
                "width": width, "height": height, "deviceScaleFactor": 1, "mobile": is_mobile
            })
            await send("Page.navigate", {"url": f"{BASE}/login"})
            await asyncio.sleep(1.5)

            # Open the modal using openPrivacyModal()
            await send("Runtime.evaluate", {
                "expression": "if (window.openPrivacyModal) { window.openPrivacyModal(); } else { document.getElementById('privacyConsentModal').classList.remove('d-none'); document.getElementById('privacyConsentModal').classList.add('show', 'd-block'); }",
                "returnByValue": True
            })
            await asyncio.sleep(0.5)

            # Measure
            eval_res = await send("Runtime.evaluate", {
                "expression": measure_js,
                "returnByValue": True
            })
            data = eval_res.get("result", {}).get("value", {})
            audit_results[name] = data

            # Capture full page screenshot
            ss = await send("Page.captureScreenshot", {"format": "png"})
            raw = base64.b64decode(ss["data"])
            file_name = f"after-privacy-modal-{name}.png"
            full_path = os.path.join(ARTIFACT_DIR, file_name)
            with open(full_path, "wb") as f:
                f.write(raw)
            print(f"[{name}] saved {file_name} - overflow: {data.get('docHorizontalOverflow')}px, modalWidth: {data.get('modal', {}).get('cardWidth')}px, contained: {data.get('modal', {}).get('containedInViewport')}")

        # Now interactive testing and closeup captures at 1920x1080
        await send("Emulation.setDeviceMetricsOverride", {
            "width": 1920, "height": 1080, "deviceScaleFactor": 1, "mobile": False
        })
        await send("Page.navigate", {"url": f"{BASE}/login"})
        await asyncio.sleep(1.5)
        await send("Runtime.evaluate", {"expression": "window.openPrivacyModal()"})
        await asyncio.sleep(0.5)

        # Capture closeup of modal dialog at 1920x1080 (disabled state)
        img_1920 = Image.open(os.path.join(ARTIFACT_DIR, "after-privacy-modal-1920x1080.png"))
        data_1920 = audit_results["1920x1080"]
        m_info = data_1920.get("modal", {})
        c_left = (1920 - m_info.get("cardWidth", 780)) // 2
        c_top = m_info.get("cardTop", 50)
        c_w = m_info.get("cardWidth", 780)
        c_h = m_info.get("cardHeight", 650)

        # Crop modal
        modal_crop = img_1920.crop((c_left - 15, max(0, c_top - 15), c_left + c_w + 15, min(1080, c_top + c_h + 15)))
        modal_crop.save(os.path.join(ARTIFACT_DIR, "closeup-privacy-modal-desktop.png"))
        print("Saved closeup-privacy-modal-desktop.png")

        # Capture closeup of header & outline icons
        header_crop = img_1920.crop((c_left, c_top, c_left + c_w, c_top + 160))
        header_crop.save(os.path.join(ARTIFACT_DIR, "closeup-privacy-header-outline-icons.png"))
        print("Saved closeup-privacy-header-outline-icons.png")

        # Capture closeup of Data Protection Commitment (verify no dark marks!)
        cw_info = data_1920.get("cardWarning", {})
        if cw_info:
            cw_crop = img_1920.crop((cw_info["left"] - 15, cw_info["top"] - 15, cw_info["right"] + 30, cw_info["bottom"] + 15))
            cw_crop.save(os.path.join(ARTIFACT_DIR, "closeup-privacy-data-protection-no-marks.png"))
            print("Saved closeup-privacy-data-protection-no-marks.png")

        # Capture closeup of Checkbox alignment & gap
        cb_top = c_top + c_h - 170
        cb_crop = img_1920.crop((c_left + 20, cb_top, c_left + c_w - 20, cb_top + 90))
        cb_crop.save(os.path.join(ARTIFACT_DIR, "closeup-privacy-checkbox-alignment.png"))
        print("Saved closeup-privacy-checkbox-alignment.png")

        # Capture closeup of footer buttons (disabled)
        footer_top = c_top + c_h - 65
        footer_crop = img_1920.crop((c_left, footer_top - 10, c_left + c_w, c_top + c_h))
        footer_crop.save(os.path.join(ARTIFACT_DIR, "closeup-privacy-footer-buttons-disabled.png"))
        print("Saved closeup-privacy-footer-buttons-disabled.png")

        # INTERACTION 1: Check the checkbox -> verify button enabled
        await send("Runtime.evaluate", {
            "expression": "document.getElementById('privacy-agree-check').click();"
        })
        await asyncio.sleep(0.5)

        # Measure after check
        after_check = await send("Runtime.evaluate", {
            "expression": """
            (function() {
                const btn = document.getElementById('privacy-continue-btn');
                const card = document.getElementById('privacy-consent-card-wrapper');
                const cs = getComputedStyle(btn);
                return {
                    disabled: btn.disabled,
                    bg: cs.backgroundColor,
                    color: cs.color,
                    cardIsCheckedClass: card.classList.contains('is-checked'),
                    text: btn.textContent.trim()
                };
            })()
            """,
            "returnByValue": True
        })
        audit_results["interactive_check"] = after_check.get("result", {}).get("value", {})
        print("After checkbox click:", audit_results["interactive_check"])

        # Capture screenshot with checkbox checked and button enabled
        ss_checked = await send("Page.captureScreenshot", {"format": "png"})
        raw_checked = base64.b64decode(ss_checked["data"])
        with open(os.path.join(ARTIFACT_DIR, "closeup-privacy-modal-checked.png"), "wb") as f:
            f.write(raw_checked)
        img_checked = Image.open(os.path.join(ARTIFACT_DIR, "closeup-privacy-modal-checked.png"))
        footer_crop_enabled = img_checked.crop((c_left, footer_top - 10, c_left + c_w, c_top + c_h))
        footer_crop_enabled.save(os.path.join(ARTIFACT_DIR, "closeup-privacy-footer-buttons-enabled.png"))
        print("Saved closeup-privacy-footer-buttons-enabled.png")

        # INTERACTION 2: Cancel button hover state
        # Hover Cancel button
        await send("Runtime.evaluate", {
            "expression": """
            (function() {
                const btn = document.getElementById('privacy-cancel-btn');
                btn.classList.add('hover-simulated');
                btn.style.setProperty('background-color', '#dc2626', 'important');
                btn.style.setProperty('border-color', '#dc2626', 'important');
                btn.style.setProperty('color', '#ffffff', 'important');
            })()
            """
        })
        await asyncio.sleep(0.3)
        ss_hover = await send("Page.captureScreenshot", {"format": "png"})
        raw_hover = base64.b64decode(ss_hover["data"])
        with open(os.path.join(ARTIFACT_DIR, "temp_hover.png"), "wb") as f:
            f.write(raw_hover)
        img_hover = Image.open(os.path.join(ARTIFACT_DIR, "temp_hover.png"))
        cancel_crop = img_hover.crop((c_left + c_w - 320, footer_top - 10, c_left + c_w - 180, c_top + c_h))
        cancel_crop.save(os.path.join(ARTIFACT_DIR, "closeup-privacy-cancel-hover.png"))
        print("Saved closeup-privacy-cancel-hover.png")

        # INTERACTION 3: Escape key dismisses modal
        await send("Runtime.evaluate", {
            "expression": """
            document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
            """
        })
        await asyncio.sleep(0.5)
        esc_res = await send("Runtime.evaluate", {
            "expression": """
            (function() {
                const m = document.getElementById('privacyConsentModal');
                return {
                    isHidden: m.classList.contains('d-none'),
                    bodyScrollRestored: (!document.body.classList.contains('modal-open') && document.body.style.overflow === ''),
                    checkboxReset: !document.getElementById('privacy-agree-check').checked
                };
            })()
            """,
            "returnByValue": True
        })
        audit_results["escape_test"] = esc_res.get("result", {}).get("value", {})
        print("Escape key test result:", audit_results["escape_test"])

        # INTERACTION 4: Mobile closeup at 390x844
        await send("Emulation.setDeviceMetricsOverride", {
            "width": 390, "height": 844, "deviceScaleFactor": 1, "mobile": True
        })
        await send("Page.navigate", {"url": f"{BASE}/login"})
        await asyncio.sleep(1.5)
        await send("Runtime.evaluate", {"expression": "window.openPrivacyModal()"})
        await asyncio.sleep(0.5)
        ss_mobile = await send("Page.captureScreenshot", {"format": "png"})
        with open(os.path.join(ARTIFACT_DIR, "closeup-privacy-modal-mobile.png"), "wb") as f:
            f.write(base64.b64decode(ss_mobile["data"]))
        print("Saved closeup-privacy-modal-mobile.png")

        # Save audit_results as json
        with open(os.path.join(ARTIFACT_DIR, "privacy_modal_audit_data.json"), "w") as f:
            json.dump(audit_results, f, indent=2)
        print("Saved privacy_modal_audit_data.json")

asyncio.run(main())
proc.terminate()
