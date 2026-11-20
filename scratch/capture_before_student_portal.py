import subprocess, os, sys, json, time, urllib.request, asyncio, base64

CHROME = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
BASE = "http://127.0.0.1:8001"
ARTIFACT_DIR = r"C:\Users\princ\.gemini\antigravity\brain\19b5d224-9ec9-4a8a-aec3-032c3dbb8a87"

import websockets

chrome_proc = subprocess.Popen([
    CHROME, "--headless=new", "--disable-gpu", "--no-sandbox",
    "--remote-debugging-port=9283",
    "--window-size=1920,1080",
    "about:blank"
], stdout=subprocess.PIPE, stderr=subprocess.PIPE)

time.sleep(2.5)

resp = urllib.request.urlopen("http://127.0.0.1:9283/json")
targets = json.loads(resp.read())
ws_url = targets[0]["webSocketDebuggerUrl"]
print("Connected to Chrome via CDP port 9283")

msg_id = [0]

async def send_cmd(ws, method, params=None):
    msg_id[0] += 1
    call_id = msg_id[0]
    payload = {"id": call_id, "method": method, "params": params or {}}
    await ws.send(json.dumps(payload))
    while True:
        r = json.loads(await ws.recv())
        if r.get("id") == call_id:
            return r.get("result", {})

async def capture_page(ws, url, filename, width=1920, height=1080, mobile=False):
    await send_cmd(ws, "Emulation.setDeviceMetricsOverride", {
        "width": width, "height": height, "deviceScaleFactor": 1, "mobile": mobile
    })
    await send_cmd(ws, "Page.navigate", {"url": url})
    await asyncio.sleep(2)
    # Scroll to top
    await send_cmd(ws, "Runtime.evaluate", {"expression": "window.scrollTo(0, 0);"})
    await asyncio.sleep(0.5)
    
    shot = await send_cmd(ws, "Page.captureScreenshot", {"format": "png", "captureBeyondViewport": False})
    data = base64.b64decode(shot["data"])
    out_path = os.path.join(ARTIFACT_DIR, filename)
    with open(out_path, "wb") as f:
        f.write(data)
    print(f"Captured {filename}")

async def main():
    try:
        async with websockets.connect(ws_url, max_size=50*1024*1024) as ws:
            await send_cmd(ws, "Page.enable")
            await send_cmd(ws, "DOM.enable")
            await send_cmd(ws, "Network.enable")

            # Login
            await send_cmd(ws, "Page.navigate", {"url": f"{BASE}/login"})
            await asyncio.sleep(2)
            login_script = """
            (async function() {
                document.getElementById('email').value = 'student@sponsorflow.test';
                document.getElementById('password').value = 'password123';
                document.getElementById('sign-in-btn').click();
            })()
            """
            await send_cmd(ws, "Runtime.evaluate", {"expression": login_script})
            await asyncio.sleep(2)

            # Accept privacy modal if present
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
            await send_cmd(ws, "Runtime.evaluate", {"expression": modal_script})
            await asyncio.sleep(2)

            pages = [
                (f"{BASE}/student/dashboard", "before-student-dashboard-desktop.png", "before-student-dashboard-mobile.png"),
                (f"{BASE}/student/sle-fhe", "before-student-sle-fhe-desktop.png", "before-student-sle-fhe-mobile.png"),
                (f"{BASE}/student/applications", "before-student-applications-desktop.png", "before-student-applications-mobile.png"),
                (f"{BASE}/student/applications/7", "before-student-application-show-desktop.png", "before-student-application-show-mobile.png"),
            ]

            for url, desk_file, mob_file in pages:
                await capture_page(ws, url, desk_file, 1920, 1080, False)
                await capture_page(ws, url, mob_file, 390, 844, True)

            print("All before screenshots captured successfully.")
    finally:
        chrome_proc.terminate()

asyncio.run(main())
