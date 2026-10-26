// Render private synthetic HTML exported by GuruMapelModuleTest. Node 22+, Chrome.
// No package installation, application DB access, or browser account/profile reuse.
import { spawn } from 'node:child_process';
import { writeFile, readdir } from 'node:fs/promises';
import { resolve } from 'node:path';
import { pathToFileURL } from 'node:url';

const folder = resolve('storage/app/a16-review');
const chrome = process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const child = spawn(chrome, ['--headless', '--disable-gpu', '--remote-debugging-port=0', '--no-first-run', `--user-data-dir=${resolve('storage/app/a16-cdp')}`], { windowsHide: true });
let socket;
try {
    const endpoint = await new Promise((resolveEndpoint, reject) => {
        const timeout = setTimeout(() => reject(new Error('Chrome startup timed out')), 15000);
        child.on('error', reject);
        child.stderr.on('data', chunk => {
            const match = chunk.toString().match(/DevTools listening on (ws:\/\/\S+)/);
            if (match) { clearTimeout(timeout); resolveEndpoint(match[1]); }
        });
    });
    socket = new WebSocket(endpoint);
    await new Promise((ok, fail) => { socket.onopen = ok; socket.onerror = fail; });
    let sequence = 0;
    const pending = new Map();
    socket.onmessage = event => {
        const response = JSON.parse(event.data);
        const task = pending.get(response.id);
        if (task) {
            pending.delete(response.id);
            clearTimeout(task.timer);
            response.error ? task.reject(new Error(JSON.stringify(response.error))) : task.resolve(response.result);
        }
    };
    const send = (method, params = {}, sessionId) => new Promise((resolveTask, reject) => {
        const id = ++sequence;
        const timer = setTimeout(() => { pending.delete(id); reject(new Error(`CDP timeout: ${method}`)); }, 15000);
        pending.set(id, { resolve: resolveTask, reject, timer });
        socket.send(JSON.stringify({ id, method, params, ...(sessionId ? { sessionId } : {}) }));
    });
    const { targetId } = await send('Target.createTarget', { url: 'about:blank' });
    const { sessionId } = await send('Target.attachToTarget', { targetId, flatten: true });
    const command = (method, params) => send(method, params, sessionId);
    await command('Page.enable');
    const files = (await readdir(folder)).filter(file => file.endsWith('.html') && file !== 'login.html');
    const results = [];
    for (const [width, height] of [[1440, 900], [768, 1024], [390, 844]]) {
        await command('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: false });
        for (const file of files) {
            await command('Page.navigate', { url: pathToFileURL(resolve(folder, file)).href });
            // Poll DOM readiness without relying on arbitrary long waits.
            for (let i = 0; i < 100; i++) {
                const ready = await command('Runtime.evaluate', { expression: 'document.readyState', returnByValue: true });
                if (ready.result.value === 'complete') break;
                await new Promise(ok => setTimeout(ok, 30));
            }
            const geometry = await command('Runtime.evaluate', { expression: `JSON.stringify({viewport:innerWidth,scroll:document.documentElement.scrollWidth,navigation:(()=>{const n=document.querySelector('.gm-breadcrumbs');const h=document.querySelector('.abk-main>.abk-topbar');const c=n?.nextElementSibling;const controls=document.querySelector('.abk-session-controls');const f=document.querySelector('.abk-sidebar-footer');const side=f?.closest('aside');const nr=n?.getBoundingClientRect();const hr=h?.getBoundingClientRect();const cr=c?.getBoundingClientRect();return {count:document.querySelectorAll('.gm-breadcrumbs').length,current:n?.querySelectorAll('[aria-current="page"]').length,order:!!n&&h?.nextElementSibling===n&&nr.top>=hr.bottom&&(!c||cr.top>=nr.bottom),aligned:!!c&&Math.abs(nr.left-cr.left)<1,overflow:!n||n.scrollWidth>n.clientWidth,footerGap:f?Math.round(side.getBoundingClientRect().bottom-f.getBoundingClientRect().bottom):null,footerAfterMenu:!!f&&f.getBoundingClientRect().top>=side.querySelector('nav').getBoundingClientRect().bottom,forms:[...controls.querySelectorAll('form')].map(f=>({method:f.method,action:new URL(f.action).pathname,csrf:!!f.querySelector('input[name="_token"]')}))}})(),assets:[...document.querySelectorAll('nav img')].map(i=>({loaded:i.complete&&i.naturalWidth>0,width:i.getBoundingClientRect().width,height:i.getBoundingClientRect().height}))})`, returnByValue: true });
            const value = JSON.parse(geometry.result.value);
            const { data } = await command('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
            await writeFile(resolve(folder, file.replace('.html', `-${width}.png`)), Buffer.from(data, 'base64'));
            results.push({ file, width, ...value });
        }
    }
    await writeFile(resolve(folder, 'geometry.json'), JSON.stringify(results, null, 2));
    const failures = results.filter(r => r.scroll > r.viewport || r.navigation.count!==1 || r.navigation.current!==1 || !r.navigation.order || !r.navigation.aligned || r.navigation.overflow || r.navigation.footerGap!==24 || !r.navigation.footerAfterMenu || r.navigation.forms.length!==3 || r.navigation.forms.some(f=>f.method!=='post'||!f.csrf) || r.assets.some(a => !a.loaded || a.width !== 8 || a.height !== 8));
    console.log(JSON.stringify({ renders: results.length, failures }, null, 2));
    if (failures.length) process.exitCode = 1;
    await send('Browser.close');
} finally {
    socket?.close();
    child.kill();
}
