import subprocess, os, sys, json, time, urllib.request, asyncio

CHROME = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
BASE = "http://127.0.0.1:8001"

import websockets

proc = subprocess.Popen([
    CHROME, "--headless=new", "--disable-gpu", "--no-sandbox",
    "--remote-debugging-port=9281",
    "--window-size=1920,1080",
    "about:blank"
], stdout=subprocess.PIPE, stderr=subprocess.PIPE)

time.sleep(2.5)

resp = urllib.request.urlopen("http://127.0.0.1:9281/json")
targets = json.loads(resp.read())
ws_url = targets[0]["webSocketDebuggerUrl"]
print("Connected to Chrome via CDP port 9281")

async def send_cmd(ws, method, params=None):
    global msg_id
    msg_id += 1
    call_id = msg_id
    payload = {"id": call_id, "method": method, "params": params or {}}
    await ws.send(json.dumps(payload))
    while True:
        r = json.loads(await ws.recv())
        if r.get("id") == call_id:
            return r.get("result", {})

msg_id = 0

async def main():
    async with websockets.connect(ws_url, max_size=50*1024*1024) as ws:
        await send_cmd(ws, "Page.enable")
        await send_cmd(ws, "DOM.enable")
        await send_cmd(ws, "Network.enable")

        # Navigate to /login
        await send_cmd(ws, "Page.navigate", {"url": f"{BASE}/login"})
        await asyncio.sleep(2)

        # Fill credentials and submit
        login_script = """
        (async function() {
            document.getElementById('email').value = 'student@sponsorflow.test';
            document.getElementById('password').value = 'password123';
            document.getElementById('sign-in-btn').click();
        })()
        """
        await send_cmd(ws, "Runtime.evaluate", {"expression": login_script})
        print("Filled credentials and clicked sign in")
        await asyncio.sleep(2)

        # Accept privacy modal
        modal_script = """
        (async function() {
            const check = document.getElementById('privacy-agree-check');
            if (check) {
                check.checked = true;
                if (window.toggleContinueBtn) window.toggleContinueBtn();
                document.getElementById('privacy-continue-btn').click();
                return 'modal submitted';
            }
            return 'modal not found';
        })()
        """
        m_res = await send_cmd(ws, "Runtime.evaluate", {"expression": modal_script, "returnByValue": True})
        print("Modal result:", m_res.get("result", {}).get("value"))
        await asyncio.sleep(3)

        # Check current URL
        url_res = await send_cmd(ws, "Runtime.evaluate", {"expression": "window.location.href", "returnByValue": True})
        current_url = url_res.get("result", {}).get("value")
        print("Current URL after login:", current_url)

        # Navigate to /student/programs
        await send_cmd(ws, "Page.navigate", {"url": f"{BASE}/student/programs"})
        await asyncio.sleep(2)
        prog_url = await send_cmd(ws, "Runtime.evaluate", {"expression": "window.location.href", "returnByValue": True})
        print("Programs URL:", prog_url.get("result", {}).get("value"))

        # Navigate to /student/applications/create/6
        await send_cmd(ws, "Page.navigate", {"url": f"{BASE}/student/applications/create/6"})
        await asyncio.sleep(2)
        app_url = await send_cmd(ws, "Runtime.evaluate", {"expression": "window.location.href", "returnByValue": True})
        print("Application create URL:", app_url.get("result", {}).get("value"))

asyncio.run(main())
proc.terminate()
