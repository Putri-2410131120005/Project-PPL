import { spawn } from 'node:child_process';
import { createServer } from 'node:http';
import { mkdir, mkdtemp, readFile, rm } from 'node:fs/promises';
import { extname, resolve } from 'node:path';

const publicFolder = resolve('public');
const manifest = JSON.parse(await readFile(resolve(publicFolder, 'build/manifest.json'), 'utf8'));
const appScript = `/build/${manifest['resources/js/app.js'].file}`;
const chrome = process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const profileRoot = resolve('storage/app/hierarchical-back-cdp-');
const modal = `<div class="modal" id="abkConfirmationModal"><h2 id="abkConfirmationModalTitle"></h2><p id="abkConfirmationModalMessage"></p><div id="abkConfirmationModalSubject"></div><button data-bs-dismiss="modal">Batal</button><button id="abkConfirmationModalConfirm">Ya</button></div>`;
const page = ({ path, parent, root = false, form = false }) => `<!doctype html><html><head><meta charset="utf-8"><script type="module" src="${appScript}"></script></head><body data-navigation-route="test" ${parent ? `data-navigation-parent="${parent}"` : ''} ${root ? 'data-navigation-root="true"' : ''}><main id="page">${path}</main>${path === 'login' ? '<a class="abk-google-login-button" href="/oauth">Masuk dengan Google</a>' : ''}${form ? '<form method="POST" action="/saved"><input name="note" value=""></form>' : ''}${root ? '<form method="POST" action="/logout" data-navigation-logout data-confirm-form data-confirm-title="Keluar?" data-confirm-message="Keluar aplikasi" data-confirm-button="Keluar"><button type="submit">Keluar</button></form>' : ''}${modal}</body></html>`;

const server = createServer(async (request, response) => {
    const url = new URL(request.url, 'http://127.0.0.1');
    if (request.method === 'POST' && url.pathname === '/logout') {
        response.writeHead(303, { Location: '/login' });
        response.end();
        return;
    }
    if (url.pathname.startsWith('/build/')) {
        try {
            const file = await readFile(resolve(publicFolder, `.${url.pathname}`));
            response.writeHead(200, { 'Content-Type': extname(url.pathname) === '.js' ? 'text/javascript' : 'text/css' });
            response.end(file);
        } catch {
            response.writeHead(404).end();
        }
        return;
    }
    const pages = {
        '/dashboard': page({ path: 'dashboard', root: true }),
        '/students': page({ path: 'students', parent: '/dashboard' }),
        '/students/42': page({ path: 'student-detail', parent: '/students' }),
        '/form': page({ path: 'form', parent: '/students/42', form: true }),
        '/login': page({ path: 'login' }),
        '/oauth': '<!doctype html><html><body><main id="page">oauth</main></body></html>',
        '/oauth-step': '<!doctype html><html><body><main id="page">oauth-step</main></body></html>',
    };
    const html = pages[url.pathname];
    response.writeHead(html ? 200 : 404, { 'Content-Type': 'text/html; charset=utf-8' });
    response.end(html || 'not found');
});

await new Promise((ok) => server.listen(0, '127.0.0.1', ok));
const origin = `http://127.0.0.1:${server.address().port}`;
await mkdir(resolve('storage/app'), { recursive: true });
const profile = await mkdtemp(profileRoot);
const child = spawn(chrome, ['--headless', '--disable-gpu', '--remote-debugging-port=0', '--no-first-run', `--user-data-dir=${profile}`], { windowsHide: true });

let socket;
try {
    const endpoint = await new Promise((ok, fail) => {
        const timeout = setTimeout(() => fail(new Error('Chrome startup timed out')), 15000);
        child.on('error', fail);
        child.stderr.on('data', (chunk) => {
            const match = chunk.toString().match(/DevTools listening on (ws:\/\/\S+)/);
            if (match) { clearTimeout(timeout); ok(match[1]); }
        });
    });
    socket = new WebSocket(endpoint);
    await new Promise((ok, fail) => { socket.onopen = ok; socket.onerror = fail; });
    let sequence = 0;
    const pending = new Map();
    socket.onmessage = (event) => {
        const response = JSON.parse(event.data);
        const task = pending.get(response.id);
        if (! task) return;
        pending.delete(response.id);
        response.error ? task.fail(new Error(JSON.stringify(response.error))) : task.ok(response.result);
    };
    const send = (method, params = {}, sessionId) => new Promise((ok, fail) => {
        const id = ++sequence;
        pending.set(id, { ok, fail });
        socket.send(JSON.stringify({ id, method, params, ...(sessionId ? { sessionId } : {}) }));
    });
    const { targetId } = await send('Target.createTarget', { url: 'about:blank' });
    const { sessionId } = await send('Target.attachToTarget', { targetId, flatten: true });
    const command = (method, params) => send(method, params, sessionId);
    await command('Page.enable');
    await command('Runtime.enable');

    const evaluate = async (expression) => (await command('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true })).result.value;
    const waitFor = async (predicate, message) => {
        for (let attempt = 0; attempt < 100; attempt++) {
            if (await predicate()) return;
            await new Promise((ok) => setTimeout(ok, 30));
        }
        throw new Error(message);
    };
    const navigate = async (path) => {
        await command('Page.navigate', { url: `${origin}${path}` });
        try {
            const stateCheck = path === '/dashboard'
                ? `history.state?.smartAbkNavigationRoot === '/dashboard'`
                : '!!history.state?.smartAbkNavigationTrap';
            await waitFor(() => evaluate(`document.readyState === 'complete' && ${stateCheck}`), `Page did not initialise: ${path}`);
        } catch (error) {
            const diagnostics = await evaluate(`JSON.stringify({ready:document.readyState,state:history.state,scripts:[...document.scripts].map(s=>s.src),resources:performance.getEntriesByType('resource').map(r=>r.name)})`);
            throw new Error(`${error.message} ${diagnostics}`);
        }
    };

    // Direct detail access still resolves Back to its structural list parent.
    await navigate('/students/42');
    await evaluate('history.back()');
    await waitFor(() => evaluate(`location.pathname === '/students' && document.querySelector('#page')?.textContent === 'students' && history.state?.smartAbkNavigationTrap === '/students'`), 'Detail did not return to students');

    // Forward after a hierarchical Back must not resurrect the abandoned detail
    // or create a Back/Forward loop.
    await evaluate('history.forward()');
    await new Promise((ok) => setTimeout(ok, 150));
    const forwardPath = await evaluate('location.pathname');
    if (forwardPath !== '/students') {
        throw new Error(`Forward left the parent after hierarchical Back: ${forwardPath}`);
    }
    await evaluate('history.back()');
    await waitFor(() => evaluate(`location.pathname === '/dashboard'`), 'Repeated Back did not continue to the structural root');

    // Dirty forms cancel Back first, then leave only after explicit confirmation.
    await navigate('/form');
    await evaluate(`document.querySelector('input[name="note"]').value = 'changed'; document.querySelector('input[name="note"]').dispatchEvent(new Event('input', {bubbles:true}))`);
    let dialogResolve;
    const dialog = new Promise((ok) => { dialogResolve = ok; });
    socket.addEventListener('message', function listener(event) {
        const message = JSON.parse(event.data);
        if (message.method === 'Page.javascriptDialogOpening' && message.sessionId === sessionId) {
            socket.removeEventListener('message', listener);
            dialogResolve();
        }
    });
    await evaluate('history.back()');
    await dialog;
    await command('Page.handleJavaScriptDialog', { accept: false });
    await waitFor(() => evaluate(`location.pathname === '/form'`), 'Cancelled dirty Back left the form');

    let acceptResolve;
    const acceptDialog = new Promise((ok) => { acceptResolve = ok; });
    socket.addEventListener('message', function listener(event) {
        const message = JSON.parse(event.data);
        if (message.method === 'Page.javascriptDialogOpening' && message.sessionId === sessionId) {
            socket.removeEventListener('message', listener);
            acceptResolve();
        }
    });
    await evaluate('history.back()');
    await acceptDialog;
    await command('Page.handleJavaScriptDialog', { accept: true });
    await waitFor(() => evaluate(`location.pathname === '/students/42'`), 'Accepted dirty Back did not reach detail');

    // Dashboard is a root: Back never submits logout. Leaving the page keeps the
    // authenticated session intact; logout is only an explicit account action.
    await navigate('/dashboard');
    if (await evaluate(`!!history.state?.smartAbkNavigationTrap`)) {
        throw new Error('Browser Dashboard incorrectly installed a logout Back trap');
    }
    await evaluate('history.back()');
    await new Promise((ok) => setTimeout(ok, 200));
    if (await evaluate(`location.pathname === '/login'`)) {
        throw new Error('Dashboard Back incorrectly submitted logout');
    }

    // Installed PWA root deliberately has no history guard: the OS owns Back
    // and the session is not logged out by application JavaScript.
    await command('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
    await command('Page.addScriptToEvaluateOnNewDocument', {
        source: `Object.defineProperty(Navigator.prototype, 'standalone', { configurable: true, get: () => true });`,
    });
    await command('Page.navigate', { url: `${origin}/dashboard` });
    await waitFor(() => evaluate(`document.readyState === 'complete' && document.body?.dataset.navigationRoot === 'true'`), 'Standalone dashboard did not load');
    if (await evaluate(`!!history.state?.smartAbkNavigationTrap`)) {
        throw new Error('Installed PWA Dashboard incorrectly intercepted native Back');
    }

    // OAuth pages introduced after the installed-app login are collapsed back
    // to the original application entry before Dashboard becomes active.
    await command('Page.navigate', { url: `${origin}/login` });
    await waitFor(() => evaluate(`document.readyState === 'complete' && !!document.querySelector('.abk-google-login-button')`), 'Installed login did not load');
    await evaluate(`document.querySelector('.abk-google-login-button').click()`);
    await waitFor(() => evaluate(`location.pathname === '/oauth'`), 'OAuth navigation did not start');
    await evaluate(`location.href = '/oauth-step'`);
    await waitFor(() => evaluate(`location.pathname === '/oauth-step'`), 'OAuth intermediate navigation did not load');
    await evaluate(`location.href = '/dashboard'`);
    await waitFor(() => evaluate(`location.pathname === '/dashboard' && sessionStorage.getItem('smart-abk:oauth-history') === null`), 'OAuth history was not normalized');
    await evaluate('history.back()');
    await new Promise((ok) => setTimeout(ok, 200));
    if (await evaluate(`location.pathname === '/oauth' || location.pathname === '/oauth-step'`)) {
        throw new Error('Installed PWA Back returned to OAuth history');
    }

    process.stdout.write('Hierarchical Back browser scenarios passed.\n');
} finally {
    if (socket?.readyState === WebSocket.OPEN) socket.close();
    const exited = new Promise((ok) => child.once('exit', ok));
    child.kill();
    await exited;
    server.close();
    await rm(profile, { recursive: true, force: true, maxRetries: 5, retryDelay: 100 });
}
