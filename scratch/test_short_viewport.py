import urllib.request, json, websockets, asyncio, subprocess, time

async def test():
    CHROME = r'C:\Program Files\Google\Chrome\Application\chrome.exe'
    proc = subprocess.Popen([
        CHROME, '--headless=new', '--disable-gpu', '--no-sandbox',
        '--remote-debugging-port=9279', '--window-size=1366,768', 'about:blank'
    ])
    time.sleep(2)
    resp = urllib.request.urlopen('http://127.0.0.1:9279/json')
    ws_url = json.loads(resp.read())[0]['webSocketDebuggerUrl']
    
    async with websockets.connect(ws_url) as ws:
        msg_id = 0
        async def send(method, params=None):
            nonlocal msg_id
            msg_id += 1
            await ws.send(json.dumps({'id': msg_id, 'method': method, 'params': params or {}}))
            while True:
                r = json.loads(await ws.recv())
                if r.get('id') == msg_id: return r.get('result', {})
                
        await send('Page.enable')
        await send('Page.navigate', {'url': 'http://127.0.0.1:8001/login'})
        await asyncio.sleep(2)
        await send('Runtime.evaluate', {'expression': 'document.getElementById("privacyConsentModal").classList.remove("d-none"); document.getElementById("privacyConsentModal").classList.add("show", "d-block");'})
        await asyncio.sleep(0.5)
        
        res = await send('Runtime.evaluate', {'expression': '''
        (function() {
            const footer = document.querySelector('.privacy-modal-footer');
            const fr = footer ? footer.getBoundingClientRect() : null;
            return {
                viewportHeight: window.innerHeight,
                footerBottom: fr ? fr.bottom : null,
                footerTop: fr ? fr.top : null,
                footerVisibleInViewport: fr ? (fr.bottom <= window.innerHeight) : null
            };
        })()
        ''', 'returnByValue': True})
        print('1366x768 inspection:', res.get('result', {}).get('value'))
    proc.terminate()

asyncio.run(test())
