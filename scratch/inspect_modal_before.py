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
    "--remote-debugging-port=9278",
    "--window-size=1920,1080",
    "about:blank"
], stdout=subprocess.PIPE, stderr=subprocess.PIPE)

time.sleep(2)

resp = urllib.request.urlopen("http://127.0.0.1:9278/json")
targets = json.loads(resp.read())
ws_url = targets[0]["webSocketDebuggerUrl"]
print("Connected to Chrome via CDP port 9278")

async def send_cmd(ws, method, params=None):
    global msg_id
    msg_id += 1
    call_id = msg_id
    payload = {"id": call_id, "method": method, "params": params or {}}
    await ws.send(json.dumps(payload))
    while True:
        resp = json.loads(await ws.recv())
        if resp.get("id") == call_id:
            return resp.get("result", {})

msg_id = 0

async def main():
    async with websockets.connect(ws_url, max_size=50*1024*1024) as ws:
        await send_cmd(ws, "Page.enable")
        await send_cmd(ws, "DOM.enable")
        await send_cmd(ws, "CSS.enable")
        
        # 1. Navigate to /login
        await send_cmd(ws, "Page.navigate", {"url": f"{BASE}/login"})
        await asyncio.sleep(2)
        
        # 2. Open privacy consent modal
        open_modal_js = """
        (function() {
            const m = document.getElementById('privacyConsentModal');
            if (m) {
                m.classList.remove('d-none');
                m.classList.add('show', 'd-block');
                m.setAttribute('aria-hidden', 'false');
                m.setAttribute('aria-modal', 'true');
                return true;
            }
            return false;
        })()
        """
        res = await send_cmd(ws, "Runtime.evaluate", {"expression": open_modal_js, "returnByValue": True})
        print("Modal opened:", res.get("result", {}).get("value"))
        await asyncio.sleep(1)
        
        # 3. Inspect elements, dimensions, bounding rects, and potential artifacts
        inspect_js = """
        (function() {
            const m = document.getElementById('privacyConsentModal');
            const card = m ? m.querySelector('.privacy-modal-card') : null;
            const body = m ? m.querySelector('.privacy-modal-body') : null;
            const cardWarning = m ? m.querySelector('.card-warning') : null;
            const footer = m ? m.querySelector('.privacy-modal-footer') : null;
            const cancelBtn = footer ? footer.querySelector('button:first-child') : null;
            const continueBtn = document.getElementById('privacy-continue-btn');
            const consentCard = document.getElementById('privacy-consent-card-wrapper');
            const checkbox = document.getElementById('privacy-agree-check');
            
            const info = {};
            if (card) {
                const r = card.getBoundingClientRect();
                info.card = { width: r.width, height: r.height, top: r.top, left: r.left };
            }
            if (body) {
                const r = body.getBoundingClientRect();
                info.body = {
                    scrollHeight: body.scrollHeight,
                    clientHeight: body.clientHeight,
                    scrollWidth: body.scrollWidth,
                    clientWidth: body.clientWidth,
                    overflowY: getComputedStyle(body).overflowY
                };
            }
            if (cardWarning) {
                const r = cardWarning.getBoundingClientRect();
                const cs = getComputedStyle(cardWarning);
                info.cardWarning = {
                    width: r.width,
                    height: r.height,
                    top: r.top,
                    right: r.right,
                    left: r.left,
                    bottom: r.bottom,
                    borderLeft: cs.borderLeft,
                    borderRight: cs.borderRight,
                    boxShadow: cs.boxShadow,
                    position: cs.position
                };
            }
            if (cancelBtn) {
                const r = cancelBtn.getBoundingClientRect();
                const cs = getComputedStyle(cancelBtn);
                info.cancelBtn = {
                    width: r.width,
                    height: r.height,
                    backgroundColor: cs.backgroundColor,
                    color: cs.color,
                    borderColor: cs.borderColor,
                    text: cancelBtn.textContent.trim()
                };
            }
            if (continueBtn) {
                const r = continueBtn.getBoundingClientRect();
                const cs = getComputedStyle(continueBtn);
                info.continueBtn = {
                    width: r.width,
                    height: r.height,
                    disabled: continueBtn.disabled,
                    backgroundColor: cs.backgroundColor,
                    opacity: cs.opacity,
                    text: continueBtn.textContent.trim()
                };
            }
            if (consentCard && checkbox) {
                const cr = consentCard.getBoundingClientRect();
                const cbr = checkbox.getBoundingClientRect();
                const cs = getComputedStyle(consentCard);
                info.consent = {
                    display: cs.display,
                    alignItems: cs.alignItems,
                    gap: cs.gap,
                    checkboxTopOffset: cbr.top - cr.top
                };
            }
            return info;
        })()
        """
        eval_res = await send_cmd(ws, "Runtime.evaluate", {"expression": inspect_js, "returnByValue": True})
        print("DOM Inspection:", json.dumps(eval_res.get("result", {}).get("value"), indent=2))
        
        # 4. Take full screenshot at 1920x1080
        await send_cmd(ws, "Emulation.setDeviceMetricsOverride", {
            "width": 1920, "height": 1080, "deviceScaleFactor": 1, "mobile": False
        })
        await asyncio.sleep(0.5)
        ss = await send_cmd(ws, "Page.captureScreenshot", {"format": "png"})
        raw = base64.b64decode(ss["data"])
        out_path = os.path.join(ARTIFACT_DIR, "before-modal-1920x1080.png")
        with open(out_path, "wb") as f:
            f.write(raw)
        print("Saved:", out_path)
        
        # Also capture closeup of cardWarning
        img = Image.open(out_path)
        # get bounding box of card
        val = eval_res.get("result", {}).get("value", {})
        if "card" in val:
            c = val["card"]
            modal_crop = img.crop((int(c["left"]), int(c["top"]), int(c["left"] + c["width"]), int(c["top"] + c["height"])))
            modal_crop.save(os.path.join(ARTIFACT_DIR, "before-modal-dialog-closeup.png"))
            print("Saved modal dialog closeup")
            
        if "cardWarning" in val:
            cw = val["cardWarning"]
            # add 20px padding around cardWarning
            pad = 20
            cw_crop = img.crop((max(0, int(cw["left"] - pad)), max(0, int(cw["top"] - pad)), min(1920, int(cw["right"] + 40)), min(1080, int(cw["bottom"] + pad))))
            cw_crop.save(os.path.join(ARTIFACT_DIR, "before-modal-card-warning-closeup.png"))
            print("Saved card warning closeup")

asyncio.run(main())
proc.terminate()
