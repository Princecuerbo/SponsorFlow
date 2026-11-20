import subprocess, os, sys, json, time, asyncio, urllib.request, websockets

CHROME = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
BASE = "http://127.0.0.1:8001"

proc = subprocess.Popen([
    CHROME, "--headless=new", "--disable-gpu", "--no-sandbox",
    "--remote-debugging-port=9283",
    "--window-size=768,1024",
    "about:blank"
], stdout=subprocess.PIPE, stderr=subprocess.PIPE)

time.sleep(2.5)

resp = urllib.request.urlopen("http://127.0.0.1:9283/json")
targets = json.loads(resp.read())
ws_url = targets[0]["webSocketDebuggerUrl"]

async def send_cmd(ws, msg_id_holder, method, params=None):
    msg_id_holder[0] += 1
    call_id = msg_id_holder[0]
    payload = {"id": call_id, "method": method, "params": params or {}}
    await ws.send(json.dumps(payload))
    while True:
        r = json.loads(await ws.recv())
        if r.get("id") == call_id:
            return r.get("result", {})

find_overflow_js = """
(function() {
    const docW = document.documentElement.clientWidth;
    const overflowing = [];
    document.querySelectorAll('*').forEach(el => {
        const r = el.getBoundingClientRect();
        if (r.right > docW + 1) {
            overflowing.push({
                tag: el.tagName,
                id: el.id,
                className: el.className,
                right: Math.round(r.right),
                width: Math.round(r.width),
                overflow: Math.round(r.right - docW)
            });
        }
    });
    return {
        docClientW: docW,
        docScrollW: document.documentElement.scrollWidth,
        overflow: document.documentElement.scrollWidth - docW,
        items: overflowing.slice(0, 10)
    };
})();
"""

async def main():
    msg_id = [0]
    async with websockets.connect(ws_url) as ws:
        await send_cmd(ws, msg_id, "Page.enable")
        await send_cmd(ws, msg_id, "DOM.enable")
        await send_cmd(ws, msg_id, "Network.enable")

        # Login
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

        # Set 768x1024
        await send_cmd(ws, msg_id, "Emulation.setDeviceMetricsOverride", {
            "width": 768, "height": 1024, "deviceScaleFactor": 1, "mobile": False
        })

        # Test Programs
        await send_cmd(ws, msg_id, "Page.navigate", {"url": f"{BASE}/student/programs"})
        await asyncio.sleep(1.5)
        res_prog = await send_cmd(ws, msg_id, "Runtime.evaluate", {"expression": find_overflow_js, "returnByValue": True})
        print("Programs 768 overflow:", json.dumps(res_prog.get("result", {}).get("value"), indent=2))

        # Test Application
        await send_cmd(ws, msg_id, "Page.navigate", {"url": f"{BASE}/student/applications/create/6"})
        await asyncio.sleep(1.5)
        res_app = await send_cmd(ws, msg_id, "Runtime.evaluate", {"expression": find_overflow_js, "returnByValue": True})
        print("Application 768 overflow:", json.dumps(res_app.get("result", {}).get("value"), indent=2))

asyncio.run(main())
proc.terminate()
