// Render private synthetic HTML exported by PrincipalModuleTest. Node 22+, Chrome.
// No package installation, application DB access, or browser account/profile reuse.
import { spawn } from 'node:child_process';
import { writeFile, readdir, readFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import { pathToFileURL } from 'node:url';

const folder = resolve('storage/app/a19-review');
const chrome = process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const child = spawn(chrome, ['--headless', '--disable-gpu', '--remote-debugging-port=0', '--no-first-run', `--user-data-dir=${resolve('storage/app/a19-cdp')}`], { windowsHide: true });
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
    const files = (await readdir(folder)).filter(file => file.endsWith('.html'));
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
            if (file === 'recap-preview.html') {
                await command('Runtime.evaluate', {expression: `document.querySelector('iframe').src=${JSON.stringify(pathToFileURL(resolve(folder, 'report.pdf')).href)}`});
            }
            const geometry = await command('Runtime.evaluate', { expression: `JSON.stringify({viewport:innerWidth,scroll:document.documentElement.scrollWidth,assets:[...document.querySelectorAll('nav img')].map(i=>({loaded:i.complete&&i.naturalWidth>0,width:i.getBoundingClientRect().width,height:i.getBoundingClientRect().height})),buttons:[...document.querySelectorAll('.ks-button')].map(b=>({label:b.textContent.trim(),width:b.getBoundingClientRect().width,height:b.getBoundingClientRect().height}))})`, returnByValue: true });
            const value = JSON.parse(geometry.result.value);
            const { data } = await command('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
            await writeFile(resolve(folder, file.replace('.html', `-${width}.png`)), Buffer.from(data, 'base64'));
            results.push({ file, width, ...value });
        }
    }
    // Exercise scoped JS with an actual fixture PDF response, and a failed response.
    await command('Emulation.setDeviceMetricsOverride', {width:1440,height:900,deviceScaleFactor:1,mobile:false});
    await command('Page.navigate', {url:pathToFileURL(resolve(folder,'recap.html')).href});
    await new Promise(ok=>setTimeout(ok,200));
    const script = await readFile('resources/js/principal.js','utf8');
    const pdf = (await readFile(resolve(folder,'report.pdf'))).toString('base64');
    await command('Runtime.evaluate', {expression:`window.ksDownloads=0; HTMLAnchorElement.prototype.click=function(){window.ksDownloads++}; window.fetch=async()=>new Response(Uint8Array.from(atob(${JSON.stringify(pdf)}),c=>c.charCodeAt(0)),{headers:{'Content-Type':'application/pdf','Content-Disposition':'attachment; filename="fixture.pdf"'}}); ${script}`});
    await command('Runtime.evaluate', {expression:`document.querySelector('[data-download]').dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true}))`});
    await new Promise(ok=>setTimeout(ok,150));
    let state = await command('Runtime.evaluate', {expression:`({download:ksDownloads,visible:!document.querySelector('.ks-toast').hidden,title:document.querySelector('[data-download-title]').textContent})`,returnByValue:true});
    if(state.result.value.download!==1 || !state.result.value.visible || state.result.value.title!=='Unduhan siap') throw new Error('Success download state failed');
    await command('Runtime.evaluate', {expression:`window.fetch=async()=>new Response('Unavailable',{status:500});document.querySelector('[data-download]').dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true}))`});
    await new Promise(ok=>setTimeout(ok,150));
    state = await command('Runtime.evaluate', {expression:`({download:ksDownloads,title:document.querySelector('[data-download-title]').textContent})`,returnByValue:true});
    if(state.result.value.download!==1 || state.result.value.title!=='Unduhan gagal') throw new Error('Failure download state failed');
    await command('Runtime.evaluate', {expression:`document.querySelector('[data-toast-close]').click()`});
    state = await command('Runtime.evaluate', {expression:`document.querySelector('.ks-toast').hidden`,returnByValue:true});
    if(!state.result.value) throw new Error('Toast did not close');
    console.log('Browser interactions: actual fixture PDF response, failed response, no false download, toast close passed.');
    if (process.env.A19_LIVE_REVIEW) {
        const base='http://127.0.0.1:8099';
        const until = async (expression) => {
            for(let i=0;i<150;i++) {
                const r=await command('Runtime.evaluate',{expression,returnByValue:true});
                if(r.result.value) return r.result.value;
                await new Promise(ok=>setTimeout(ok,40));
            }
            throw new Error('Live browser state timed out: '+expression);
        };
        await command('Page.navigate',{url:base+'/_dev/login'});
        await until(`!!document.querySelector('select[name="role"]')`);
        await command('Runtime.evaluate',{expression:`document.querySelector('select[name="role"]').value='kepala-sekolah';document.querySelector('select[name="role"]').form.requestSubmit()`});
        await until(`location.pathname==='/_dev/kepala-sekolah/dashboard'&&!!document.querySelector('.ks-welcome')`);
        const live=[];
        for(const page of ['dashboard','siswa','rekap','arsip','konfirmasi-keluar']) {
            await command('Page.navigate',{url:base+'/_dev/kepala-sekolah/'+page});
            await until(`document.readyState==='complete'&&location.pathname.endsWith('/${page}')&&document.body.classList.contains('ks')`);
            const {data}=await command('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});
            await writeFile(resolve(folder,'live-'+page+'.png'),Buffer.from(data,'base64'));
            live.push(page);
        }
        await command('Runtime.evaluate',{expression:`document.querySelector('a.ks-button').click()`});
        await until(`location.pathname.endsWith('/dashboard')&&!!document.querySelector('.ks-welcome')`);
        await command('Page.navigate',{url:base+'/_dev/kepala-sekolah/arsip'});
        await until(`document.readyState==='complete'&&!!document.querySelector('select[name="bulan"]')`);
        await command('Runtime.evaluate',{expression:`const s=document.querySelector('select[name="bulan"]');s.value='1';s.dispatchEvent(new Event('change',{bubbles:true}))`});
        await until(`location.search.includes('bulan=1')&&document.readyState==='complete'`);
        await command('Page.navigate',{url:base+'/_dev/kepala-sekolah/konfirmasi-keluar'});
        await until(`!!document.querySelector('.ks-logout-actions form')`);
        await command('Runtime.evaluate',{expression:`document.querySelector('.ks-logout-actions form').requestSubmit()`});
        await until(`location.pathname==='/_dev/login'&&!!document.querySelector('select[name="role"]')`);
        await writeFile(resolve(folder,'live-browser.json'),JSON.stringify({live,login:true,cancel:true,monthAutoSubmit:true,logout:true},null,2));
        console.log('Live MariaDB browser: dropdown login, five pages, cancel, month auto-submit and logout passed.');
    }
    await writeFile(resolve(folder, 'geometry.json'), JSON.stringify(results, null, 2));
    const failures = results.filter(r => r.scroll > r.viewport || r.assets.some(a => !a.loaded || a.width !== 8 || a.height !== 8));
    console.log(JSON.stringify({ renders: results.length, failures }, null, 2));
    if (failures.length) process.exitCode = 1;
    await send('Browser.close');
} finally {
    socket?.close();
    child.kill();
}

