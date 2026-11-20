import asyncio, base64, json, os, subprocess, time, urllib.request

CHROME = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
BASE = "http://127.0.0.1:8001"
ARTIFACT_DIR = r"C:\Users\princ\.gemini\antigravity\brain\19b5d224-9ec9-4a8a-aec3-032c3dbb8a87"
PORT = 9285

import websockets

VIEWPORTS = [
    (1920, 1080), (1600, 900), (1440, 900), (1366, 768),
    (768, 1024), (430, 932), (390, 844), (360, 800),
]

MEASURE_JS = r"""
(function () {
    const cw = document.documentElement.clientWidth;
    const overflow = document.documentElement.scrollWidth - cw;
    const bad = [];
    document.querySelectorAll('body *').forEach(function (el) {
        const cs = getComputedStyle(el);
        if (cs.display === 'none' || cs.visibility === 'hidden' || cs.position === 'fixed') return;
        const r = el.getBoundingClientRect();
        if (r.width === 0 || r.height === 0) return;
        if (el.closest('.table-responsive')) return; // intentional horizontal scroll container
        if (r.right > cw + 1 || r.left < -1) {
            bad.push({
                tag: el.tagName.toLowerCase(),
                cls: (el.className && el.className.toString().slice(0, 100)) || '',
                left: Math.round(r.left), right: Math.round(r.right), width: Math.round(r.width)
            });
        }
    });

    const rect = function (s) {
        const el = document.querySelector(s);
        if (!el) return null;
        const r = el.getBoundingClientRect();
        return { w: Math.round(r.width), h: Math.round(r.height), top: Math.round(r.top), right: Math.round(r.right) };
    };
    const style = function (s, props) {
        const el = document.querySelector(s);
        if (!el) return null;
        const cs = getComputedStyle(el);
        const out = {};
        props.forEach(function (p) { out[p] = cs[p]; });
        return out;
    };
    const texts = function (s) {
        return Array.from(document.querySelectorAll(s)).map(function (e) { return e.textContent.trim().replace(/\s+/g, ' '); });
    };

    const r = {
        viewport: window.innerWidth + 'x' + window.innerHeight,
        clientWidth: cw,
        scrollWidth: document.documentElement.scrollWidth,
        horizontalOverflow: overflow,
        overflowingElements: bad.slice(0, 8),
        h1: rect('h1'),
        h1Style: style('h1', ['fontSize', 'lineHeight']),
        pageSubtitle: texts('.sf-page-subtitle, [class*=subtitle]').slice(0, 2)
    };

    // Dashboard
    const metricCards = Array.from(document.querySelectorAll('.sf-dash-metric'));
    if (metricCards.length) {
        r.dashboard = {
            metricCount: metricCards.length,
            cardRects: metricCards.map(function (c) { const b = c.getBoundingClientRect(); return { w: Math.round(b.width), h: Math.round(b.height), top: Math.round(b.top) }; }),
            values: metricCards.map(function (c) { const v = c.querySelector('.sf-heading'); return v ? v.textContent.trim() : null; }),
            valueFontSizes: metricCards.map(function (c) { const v = c.querySelector('.sf-heading'); return v ? getComputedStyle(v).fontSize : null; }),
            icons: metricCards.map(function (c) { const i = c.querySelector('.sf-stat-icon i'); return i ? i.className : null; }),
            headerFont: style('.sf-dash-table thead th', ['fontSize', 'fontWeight', 'letterSpacing']),
            rowHeights: Array.from(document.querySelectorAll('.sf-dash-table tbody tr')).map(function (t) { return Math.round(t.getBoundingClientRect().height); }),
            actionButtons: Array.from(document.querySelectorAll('.sf-dash-table tbody a.btn')).map(function (a) { const b = a.getBoundingClientRect(); return { text: a.textContent.trim(), w: Math.round(b.width), h: Math.round(b.height), right: Math.round(b.right) }; })
        };
    }

    // Applications list
    const appTable = document.querySelector('.sf-apps-table');
    if (appTable) {
        const card = document.querySelector('.sf-apps-card');
        r.applications = {
            cardStyle: style('.sf-apps-card', ['borderRadius', 'borderColor', 'boxShadow']),
            headerFont: style('.sf-apps-table thead th', ['fontSize', 'fontWeight', 'letterSpacing', 'textTransform']),
            headerHeight: rect('.sf-apps-table thead tr'),
            rowHeights: Array.from(document.querySelectorAll('.sf-apps-table tbody tr')).map(function (t) { return Math.round(t.getBoundingClientRect().height); }),
            badges: Array.from(document.querySelectorAll('.sf-apps-table tbody .badge')).map(function (b) { return { text: b.textContent.trim(), bg: getComputedStyle(b).backgroundColor, color: getComputedStyle(b).color }; }),
            actions: Array.from(document.querySelectorAll('.sf-apps-table tbody a.btn')).map(function (a) { const b = a.getBoundingClientRect(); return { text: a.textContent.trim(), w: Math.round(b.width), h: Math.round(b.height), right: Math.round(b.right) }; })
        };
    }
    if (document.querySelector('.sf-apps-empty')) {
        r.applicationsEmpty = {
            rect: rect('.sf-apps-empty'),
            heading: document.querySelector('.sf-apps-empty .fw-semibold') ? document.querySelector('.sf-apps-empty .fw-semibold').textContent.trim() : null,
            link: document.querySelector('.sf-apps-empty a') ? document.querySelector('.sf-apps-empty a').textContent.trim() : null
        };
    }

    // SLE-FHE
    if (document.querySelector('.sf-readonly-field')) {
        const disabled = Array.from(document.querySelectorAll('.sf-readonly-field:disabled'));
        r.sle = {
            headerIcons: Array.from(document.querySelectorAll('.card-header .rounded-3 i')).map(function (i) { return i.className; }),
            disabledCount: disabled.length,
            disabledStyle: disabled.length ? (function () { const cs = getComputedStyle(disabled[0]); return { bg: cs.backgroundColor, border: cs.borderColor, color: cs.color, cursor: cs.cursor }; })() : null,
            lockNotice: document.querySelector('#sf-sle-lock-notice') ? document.querySelector('#sf-sle-lock-notice').textContent.trim().replace(/\s+/g, ' ') : null,
            statusBadges: texts('.card-body .badge').slice(0, 6),
            campus: (function () {
                const labels = Array.from(document.querySelectorAll('.sf-eyebrow, .text-uppercase'));
                const c = labels.find(function (e) { return e.textContent.trim() === 'Campus'; });
                return c && c.parentElement ? c.parentElement.textContent.trim().replace(/\s+/g, ' ') : null;
            })()
        };
    }

    // Application detail
    if (document.querySelector('.sf-appdetail-steps')) {
        r.detail = {
            steps: texts('.sf-appdetail-step'),
            explainer: document.querySelector('.sf-appdetail-steps + p') ? document.querySelector('.sf-appdetail-steps + p').textContent.trim().replace(/\s+/g, ' ') : null,
            docRows: Array.from(document.querySelectorAll('.sf-appdetail-doc')).map(function (d) { const b = d.getBoundingClientRect(); return { w: Math.round(b.width), h: Math.round(b.height) }; }),
            filenames: Array.from(document.querySelectorAll('.sf-appdetail-filename')).map(function (f) {
                const b = f.getBoundingClientRect();
                return { text: f.textContent.trim(), lines: Math.round(b.height / parseFloat(getComputedStyle(f).lineHeight)), title: f.getAttribute('title'), ariaLabel: f.getAttribute('aria-label'), truncatedVisually: f.scrollHeight > f.clientHeight + 1 };
            }),
            viewLinks: Array.from(document.querySelectorAll('.sf-appdetail-doc a.btn')).map(function (a) { const b = a.getBoundingClientRect(); return { text: a.textContent.trim(), href: a.getAttribute('href'), w: Math.round(b.width), h: Math.round(b.height), right: Math.round(b.right) }; }),
            summaryHeading: document.querySelector('.sf-appdetail-card h6') ? document.querySelector('.sf-appdetail-card h6').textContent.trim() : null,
            backButton: (function () { const a = Array.from(document.querySelectorAll('a.btn')).find(function (x) { return x.textContent.includes('Back to My Applications'); }); if (!a) return null; const b = a.getBoundingClientRect(); return { w: Math.round(b.width), h: Math.round(b.height), right: Math.round(b.right) }; })(),
            rurality: (function () {
                const dt = Array.from(document.querySelectorAll('dt')).find(function (e) { return e.textContent.includes('Classification') || e.textContent.includes('Rural'); });
                return dt && dt.nextElementSibling ? dt.textContent.trim() + ' = ' + dt.nextElementSibling.textContent.trim() : null;
            })()
        };
    }
    return r;
})();
"""

proc = subprocess.Popen([
    CHROME, "--headless=new", "--disable-gpu", "--no-sandbox",
    "--remote-debugging-port=" + str(PORT), "--window-size=1920,1080", "about:blank"
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

async def evaluate(ws, expression):
    result = await send(ws, "Runtime.evaluate", {"expression": expression, "returnByValue": True, "awaitPromise": True})
    return result.get("result", {}).get("value")

async def shot(ws, name, width, height, mobile=False):
    await send(ws, "Emulation.setDeviceMetricsOverride", {"width": width, "height": height, "deviceScaleFactor": 1, "mobile": mobile})
    await asyncio.sleep(0.4)
    data = await send(ws, "Page.captureScreenshot", {"format": "png", "captureBeyondViewport": False})
    path = os.path.join(ARTIFACT_DIR, name)
    with open(path, "wb") as f:
        f.write(base64.b64decode(data["data"]))
    print("CAPTURED", name)

async def go(ws, url, width, height, mobile=False):
    await send(ws, "Emulation.setDeviceMetricsOverride", {"width": width, "height": height, "deviceScaleFactor": 1, "mobile": mobile})
    await send(ws, "Page.navigate", {"url": url})
    await asyncio.sleep(1.6)
    await evaluate(ws, "window.scrollTo(0,0);")

async def login(ws, email, password):
    await send(ws, "Network.clearBrowserCookies", {})
    for attempt in range(2):
        await go(ws, BASE + "/login", 1920, 1080)
        await evaluate(ws, """
        (function(){
            var e=document.getElementById('email'); var p=document.getElementById('password');
            if(!e||!p) return false;
            e.value=%s; p.value=%s;
            var b=document.getElementById('sign-in-btn')||document.querySelector('button[type=submit]');
            if(b){ b.click(); return true; } return false;
        })()
        """ % (json.dumps(email), json.dumps(password if attempt == 0 else "password")))
        await asyncio.sleep(2.0)
        await evaluate(ws, """
        (function(){
            var c=document.getElementById('privacy-agree-check');
            if(c){ c.checked=true; if(window.toggleContinueBtn) window.toggleContinueBtn();
              var b=document.getElementById('privacy-continue-btn'); if(b) b.click(); }
        })()
        """)
        await asyncio.sleep(1.5)
        path = await evaluate(ws, "location.pathname")
        if path != "/login":
            return path
    return path

async def main():
    time.sleep(2.5)
    with urllib.request.urlopen("http://127.0.0.1:%d/json" % PORT) as resp:
        targets = json.loads(resp.read())
    ws_url = targets[0]["webSocketDebuggerUrl"]
    results = {"measurements": {}, "logins": {}}
    async with websockets.connect(ws_url, max_size=50 * 1024 * 1024) as ws:
        await send(ws, "Page.enable")
        await send(ws, "DOM.enable")
        await send(ws, "Network.enable")

        pages = [
            ("dashboard", "/student/dashboard"),
            ("sle-fhe", "/student/sle-fhe"),
            ("applications", "/student/applications"),
            ("detail", "/student/applications/7"),
        ]

        results["logins"]["populated"] = await login(ws, "student@sponsorflow.test", "password123")
        for w, h in VIEWPORTS:
            key = "%dx%d" % (w, h)
            results["measurements"][key] = {}
            for name, path in pages:
                await go(ws, BASE + path, w, h, mobile=(w < 992))
                results["measurements"][key][name] = await evaluate(ws, MEASURE_JS)
                if key == "1920x1080":
                    await shot(ws, "after-student-%s-1920x1080.png" % name, w, h)
                if key == "390x844":
                    await shot(ws, "after-student-%s-390x844.png" % name, w, h, True)

        results["logins"]["empty_verified"] = await login(ws, "juana.hills@example.org", "password")
        for path_key, path in [("applications-empty", "/student/applications"), ("sle-fhe-verified", "/student/sle-fhe")]:
            await go(ws, BASE + path, 1920, 1080)
            results["measurements"]["1920x1080"][path_key] = await evaluate(ws, MEASURE_JS)
            await shot(ws, "after-student-%s-1920x1080.png" % path_key, 1920, 1080)
            await go(ws, BASE + path, 390, 844, True)
            results["measurements"]["390x844"][path_key] = await evaluate(ws, MEASURE_JS)
            await shot(ws, "after-student-%s-390x844.png" % path_key, 390, 844, True)

    out = os.path.join(os.path.dirname(os.path.abspath(__file__)), "student_ui_verification.json")
    with open(out, "w", encoding="utf-8") as f:
        json.dump(results, f, indent=2)
    print("WROTE", out)

try:
    asyncio.run(main())
finally:
    proc.terminate()
