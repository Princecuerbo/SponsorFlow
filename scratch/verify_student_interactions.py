import asyncio, json, subprocess, time, urllib.request

CHROME = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
BASE = "http://127.0.0.1:8001"
PORT = 9286

import websockets

proc = subprocess.Popen([
    CHROME, "--headless=new", "--disable-gpu", "--no-sandbox",
    "--remote-debugging-port=" + str(PORT), "--window-size=390,844", "about:blank"
], stdout=subprocess.PIPE, stderr=subprocess.PIPE)

msg_id = [0]

async def send(ws, method, params=None):
    msg_id[0] += 1
    call = msg_id[0]
    await ws.send(json.dumps({"id": call, "method": method, "params": params or {}}))
    while True:
        msg = json.loads(await ws.recv())
        if msg.get("id") == call:
            return msg.get("result", {})

async def ev(ws, expr):
    r = await send(ws, "Runtime.evaluate", {"expression": expr, "returnByValue": True, "awaitPromise": True})
    return r.get("result", {}).get("value")

LOGIN = """
(function(){
    var e=document.getElementById('email'); var p=document.getElementById('password');
    e.value='student@sponsorflow.test'; p.value='password123';
    var b=document.getElementById('sign-in-btn')||document.querySelector('button[type=submit]');
    b.click(); return true;
})()
"""

async def main():
    time.sleep(2.5)
    with urllib.request.urlopen("http://127.0.0.1:%d/json" % PORT) as resp:
        targets = json.loads(resp.read())
    async with websockets.connect(targets[0]["webSocketDebuggerUrl"], max_size=50 * 1024 * 1024) as ws:
        await send(ws, "Page.enable")
        await send(ws, "Emulation.setDeviceMetricsOverride", {"width": 390, "height": 844, "deviceScaleFactor": 1, "mobile": True})
        await send(ws, "Page.navigate", {"url": BASE + "/login"})
        await asyncio.sleep(1.5)
        await ev(ws, LOGIN)
        await asyncio.sleep(2.2)
        await ev(ws, "(function(){var c=document.getElementById('privacy-agree-check');if(c){c.checked=true;if(window.toggleContinueBtn)window.toggleContinueBtn();var b=document.getElementById('privacy-continue-btn');if(b)b.click();}})()")
        await asyncio.sleep(1.5)

        results = {}
        for name, url, selector in [
            ("dashboard", "/student/dashboard", ".sf-dash-table"),
            ("applications", "/student/applications", ".sf-apps-table"),
        ]:
            await send(ws, "Page.navigate", {"url": BASE + url})
            await asyncio.sleep(1.5)
            results[name] = await ev(ws, """
            (function(){
                var table=document.querySelector(%s);
                var box=table.closest('.table-responsive');
                var before={scrollLeft:box.scrollLeft, clientWidth:box.clientWidth, scrollWidth:box.scrollWidth, overflowX:getComputedStyle(box).overflowX};
                var btn=table.querySelector('tbody a.btn');
                var r0=btn.getBoundingClientRect();
                box.scrollLeft=box.scrollWidth;
                var r1=btn.getBoundingClientRect();
                var cw=document.documentElement.clientWidth;
                var out={
                  box:before,
                  buttonBefore:{left:Math.round(r0.left),right:Math.round(r0.right),fullyVisible:r0.left>=0&&r0.right<=cw},
                  buttonAfterScroll:{left:Math.round(r1.left),right:Math.round(r1.right),fullyVisible:r1.left>=0&&r1.right<=cw},
                  scrollRevealed:box.scrollLeft>0
                };
                box.scrollLeft=0;
                return out;
            })()
            """ % json.dumps(selector))

        # Keyboard focus check on applications page using a real Tab keypress
        await send(ws, "Page.navigate", {"url": BASE + "/student/applications"})
        await asyncio.sleep(1.5)
        await ev(ws, "document.body.focus()")
        keyboard = None
        for _ in range(45):
            await send(ws, "Input.dispatchKeyEvent", {"type": "keyDown", "key": "Tab", "code": "Tab", "windowsVirtualKeyCode": 9, "nativeVirtualKeyCode": 9})
            await send(ws, "Input.dispatchKeyEvent", {"type": "keyUp", "key": "Tab", "code": "Tab", "windowsVirtualKeyCode": 9, "nativeVirtualKeyCode": 9})
            state = await ev(ws, """
            (function(){
              var a=document.activeElement;
              if(!a||!a.matches('.sf-apps-table tbody a.btn')) return null;
              var cs=getComputedStyle(a);
              return {activeElementIsAction:true, outlineStyle:cs.outlineStyle, outlineWidth:cs.outlineWidth,
                      outlineColor:cs.outlineColor, boxShadow:cs.boxShadow,
                      focusVisible: cs.outlineStyle!=='none' || (cs.boxShadow && cs.boxShadow!=='none')};
            })()
            """)
            if state:
                keyboard = state
                break
        results["keyboard"] = keyboard or {"activeElementIsAction": False}
        print(json.dumps(results, indent=2))

try:
    asyncio.run(main())
finally:
    proc.terminate()
