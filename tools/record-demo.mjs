/**
 * Records a screen video of the whole purchase, start to finish, with headless Chrome:
 * scroll the product page, pick a finish, fill the checkout, pay inside the Gate-E iframe
 * with the sandbox card, land on the result page, then open the payment in the list.
 *
 * Needs: Node 22+, Google Chrome, ffmpeg, the app running on :8090.
 *   node tools/record-demo.mjs [output.mp4] [cvv]      (default output: screenshots/demo-halo-checkout.mp4)
 *        (cvv 123 = success, 999 = declined)
 */
import { spawn, spawnSync } from 'node:child_process';
import { mkdir, rm, writeFile } from 'node:fs/promises';
import { setTimeout as sleep } from 'node:timers/promises';
import { join, resolve } from 'node:path';

const APP = process.env.APP_URL ?? 'http://localhost:8090';
const CHROME = process.env.CHROME ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const OUTPUT = resolve(process.argv[2] ?? 'screenshots/demo-halo-checkout.mp4');
const CVV = process.argv[3] ?? '123';
const WIDTH = 1440;
const HEIGHT = 900;
const PORT = 9334;
const WORK = resolve('.recording');

const TEST_CARD = { number: '1111222233334444', expiry: '1230', cvv: CVV };
const CUSTOMER = { name: 'Maryam Al Sayed', email: 'maryam.alsayed@example.com', mobile: '39876543' };

/* A visible cursor, injected into every page of the main frame. */
const CURSOR_SCRIPT = `
  addEventListener('DOMContentLoaded', () => {
    const dot = document.createElement('div');
    dot.id = '__cursor';
    dot.style.cssText = 'position:fixed;left:0;top:0;width:22px;height:22px;margin:-11px 0 0 -11px;border-radius:50%;' +
      'background:rgba(229,64,47,.85);box-shadow:0 0 0 4px rgba(229,64,47,.25);z-index:2147483647;pointer-events:none;' +
      'transition:transform .12s;transform:translate(var(--x,-100px),var(--y,-100px))';
    document.body.appendChild(dot);
    addEventListener('mousemove', (e) => { dot.style.setProperty('--x', e.clientX + 'px'); dot.style.setProperty('--y', e.clientY + 'px'); });
  });
  window.__cursorTo = (x, y) => { const d = document.getElementById('__cursor'); if (d) { d.style.setProperty('--x', x + 'px'); d.style.setProperty('--y', y + 'px'); } };
  window.__cursorPress = () => { const d = document.getElementById('__cursor'); if (d) { d.animate([{ scale: 1 }, { scale: 0.6 }, { scale: 1 }], 220); } };
  window.__scrollTo = (target, ms) => new Promise((done) => {
    if (window.__lenis) { window.__lenis.scrollTo(target, { duration: ms / 1000, force: true, onComplete: () => done() }); return; }
    document.documentElement.style.scrollBehavior = 'auto';
    const from = scrollY, start = performance.now(), ease = (t) => t < .5 ? 4*t*t*t : 1 - Math.pow(-2*t + 2, 3) / 2;
    const step = (now) => { const t = Math.min(1, (now - start) / ms); scrollTo(0, from + (target - from) * ease(t)); t < 1 ? requestAnimationFrame(step) : done(); };
    requestAnimationFrame(step);
  });
`;

const chrome = spawn(CHROME, [
  '--headless=new', `--remote-debugging-port=${PORT}`, '--no-first-run', '--hide-scrollbars',
  `--window-size=${WIDTH},${HEIGHT}`, `--user-data-dir=${join(WORK, 'profile')}`, 'about:blank',
], { stdio: 'ignore' });

const frames = [];
let mouse = { x: WIDTH / 2, y: HEIGHT / 2 };

try {
  await rm(WORK, { recursive: true, force: true });
  await mkdir(join(WORK, 'frames'), { recursive: true });

  const cdp = await connect((await waitForPage()).webSocketDebuggerUrl);
  const gatewayContexts = trackGatewayFrame(cdp);

  await cdp.send('Page.enable');
  await cdp.send('Runtime.enable');
  await cdp.send('Emulation.setDeviceMetricsOverride', { width: WIDTH, height: HEIGHT, deviceScaleFactor: 1, mobile: false });
  await cdp.send('Page.addScriptToEvaluateOnNewDocument', { source: CURSOR_SCRIPT });
  await cdp.send('Target.setAutoAttach', { autoAttach: true, waitForDebuggerOnStart: false, flatten: true });

  cdp.on('Page.screencastFrame', ({ data, metadata, sessionId }) => {
    frames.push({ data, time: metadata.timestamp });
    cdp.send('Page.screencastFrameAck', { sessionId });
  });
  await cdp.send('Page.startScreencast', { format: 'jpeg', quality: 82, maxWidth: WIDTH, maxHeight: HEIGHT });

  /* 1. Product page ------------------------------------------------------ */
  await navigate(cdp, `${APP}/`);
  await sleep(4200);                                     // name rises, halo draws itself
  await scrollToY(cdp, 'window.innerHeight * 1.15', 2600); // into the pinned scene
  await scrollToY(cdp, 'window.innerHeight * 2.3', 3200);  // headphones assemble, noise flattens
  await sleep(800);
  await scrollToElement(cdp, '.finishes', 1800);
  await sleep(700);
  await clickElement(cdp, '.finish-tab[data-finish="sand"]');
  await sleep(1300);
  await clickElement(cdp, '.finish-tab[data-finish="night"]');
  await sleep(1300);
  await clickElement(cdp, '.finish-tab[data-finish="tide"]');
  await sleep(1000);
  await scrollToElement(cdp, '.specs', 1600);
  await hoverElement(cdp, '.specs__group:nth-child(1) li:nth-child(2)');
  await sleep(1100);
  await hoverElement(cdp, '.specs__group:nth-child(3) li:nth-child(1)');
  await sleep(1100);
  await scrollToElement(cdp, '.places', 1600);
  await sleep(1200);

  /* 2. Checkout ---------------------------------------------------------- */
  await scrollToElement(cdp, '#checkout', 1800);
  await sleep(800);
  await clickElement(cdp, '.stepper [data-step="1"]');
  await sleep(700);
  await typeInto(cdp, '#name', CUSTOMER.name);
  await typeInto(cdp, '#email', CUSTOMER.email);
  await typeInto(cdp, '#mobile', CUSTOMER.mobile);
  await sleep(500);
  await clickElement(cdp, '#checkout-form [type="submit"]');
  await waitFor(cdp, `document.querySelector('[data-gateway-frame]')?.src.startsWith('http')`, 20000);
  await sleep(1500);
  await scrollToElement(cdp, '.pay__gateway', 1000, -24);

  /* 3. Pay inside the Gate-E iframe ------------------------------------- */
  const gateway = await waitForGatewayForm(cdp, gatewayContexts);
  await sleep(1000);
  await typeInGateway(cdp, gateway, 'input[placeholder="Card Number"]', TEST_CARD.number);
  await typeInGateway(cdp, gateway, 'input[placeholder="MM/YY"]', TEST_CARD.expiry);
  await typeInGateway(cdp, gateway, 'input[placeholder="CVV"]', TEST_CARD.cvv);
  await sleep(600);
  await clickInGateway(cdp, gateway, 'button, [type="submit"]', 'Pay Now');

  /* 4. Result page (reached by the status poller) ------------------------- */
  await waitFor(cdp, `location.pathname === '/callback.php' && document.readyState === 'complete'`, 40000);
  await sleep(3200);
  await scrollToY(cdp, '520', 1800);
  await sleep(1600);
  await scrollToY(cdp, 'document.body.scrollHeight', 1600);
  await sleep(1600);

  /* 5. Payments list and the details modal -------------------------------- */
  await scrollToY(cdp, '0', 900);
  await clickElement(cdp, 'a[href="/payments.php"].button');
  await waitFor(cdp, `location.pathname === '/payments.php' && document.readyState === 'complete'`, 15000);
  await sleep(2200);
  await clickElement(cdp, '[data-view-payment]');
  await waitFor(cdp, `!!document.querySelector('.compare')`, 20000);
  await sleep(3000);
  await evaluate(cdp, `document.querySelector('.modal-body').scrollTo({ top: 600, behavior: 'smooth' })`);
  await sleep(3000);

  await cdp.send('Page.stopScreencast');
  cdp.close();
} finally {
  chrome.kill();
}

await encode();
await rm(WORK, { recursive: true, force: true });
console.log('saved', OUTPUT);

/* ----------------------------------------------------------------------- */
/* Page actions                                                             */
/* ----------------------------------------------------------------------- */

async function navigate(cdp, url) {
  await cdp.send('Page.navigate', { url });
  await waitFor(cdp, `document.readyState === 'complete'`, 20000);
}

async function scrollToY(cdp, expression, ms) {
  await evaluate(cdp, `window.__scrollTo(${expression}, ${ms})`);
}

async function scrollToElement(cdp, selector, ms, offset = 0) {
  await scrollToY(cdp, `document.querySelector(${JSON.stringify(selector)}).getBoundingClientRect().top + scrollY + ${offset}`, ms);
}

async function centerOf(cdp, selector) {
  return evaluate(cdp, `(() => {
    const r = document.querySelector(${JSON.stringify(selector)}).getBoundingClientRect();
    return { x: r.left + r.width / 2, y: r.top + r.height / 2 };
  })()`);
}

async function moveMouse(cdp, x, y, steps = 14) {
  const from = { ...mouse };
  for (let i = 1; i <= steps; i++) {
    const t = i / steps;
    const px = from.x + (x - from.x) * t;
    const py = from.y + (y - from.y) * t;
    await cdp.send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: px, y: py });
    await evaluate(cdp, `window.__cursorTo(${px}, ${py})`);
    await sleep(16);
  }
  mouse = { x, y };
}

async function clickAt(cdp, x, y) {
  await moveMouse(cdp, x, y);
  await evaluate(cdp, 'window.__cursorPress()');
  await cdp.send('Input.dispatchMouseEvent', { type: 'mousePressed', x, y, button: 'left', clickCount: 1 });
  await cdp.send('Input.dispatchMouseEvent', { type: 'mouseReleased', x, y, button: 'left', clickCount: 1 });
}

async function clickElement(cdp, selector) {
  const { x, y } = await centerOf(cdp, selector);
  await clickAt(cdp, x, y);
}

async function hoverElement(cdp, selector) {
  const { x, y } = await centerOf(cdp, selector);
  await moveMouse(cdp, x, y, 20);
}

async function typeText(cdp, text) {
  for (const ch of text) {
    await cdp.send('Input.insertText', { text: ch });
    await sleep(55 + Math.random() * 45);
  }
}

async function typeInto(cdp, selector, text) {
  await clickElement(cdp, selector);
  await sleep(200);
  await typeText(cdp, text);
  await sleep(250);
}

/*
 * The Gate-E page lives in a cross-origin iframe. Depending on process isolation, Chrome exposes it
 * either as its own CDP session (out-of-process iframe) or as an extra JavaScript context in the
 * page's session. We collect every JavaScript context whose origin is Gate-E, from either source.
 */

function trackGatewayFrame(cdp) {
  const contexts = [];
  cdp.on('Runtime.executionContextCreated', ({ context, sessionId }) => {
    if (String(context.origin).includes('gate-e')) contexts.push({ contextId: context.id, sessionId });
  });
  cdp.on('Target.attachedToTarget', ({ sessionId, targetInfo }) => {
    if (targetInfo.type === 'iframe') cdp.send('Runtime.enable', {}, sessionId).catch(() => {});
    cdp.send('Runtime.runIfWaitingForDebugger', {}, sessionId).catch(() => {});
  });
  return contexts;
}

async function waitForGatewayForm(cdp, contexts) {
  const deadline = Date.now() + 30000;
  while (Date.now() < deadline) {
    for (const context of [...contexts].reverse()) {
      const ready = await evaluate(cdp, `!!document.querySelector('input[placeholder="Card Number"]')`, context).catch(() => false);
      if (ready) return context;
    }
    await sleep(300);
  }
  throw new Error('The Gate-E card form did not appear in the iframe');
}

/** Converts a point inside the iframe to page coordinates, so the click is real and visible. */
async function gatewayPoint(cdp, gateway, selector, text) {
  const inner = await evaluate(cdp, `(() => {
    const all = [...document.querySelectorAll(${JSON.stringify(selector)})];
    const el = ${text ? `all.find((e) => e.textContent.trim().includes(${JSON.stringify(text)}))` : 'all[0]'};
    const r = el.getBoundingClientRect();
    return { x: r.left + r.width / 2, y: r.top + r.height / 2 };
  })()`, gateway);
  const box = await evaluate(cdp, `(() => { const r = document.querySelector('[data-gateway-frame]').getBoundingClientRect(); return { left: r.left, top: r.top }; })()`);
  return { x: box.left + inner.x, y: box.top + inner.y };
}

async function typeInGateway(cdp, gateway, selector, text) {
  const { x, y } = await gatewayPoint(cdp, gateway, selector);
  await clickAt(cdp, x, y);
  await sleep(250);
  await typeText(cdp, text);
  await sleep(300);
}

async function clickInGateway(cdp, gateway, selector, text) {
  const { x, y } = await gatewayPoint(cdp, gateway, selector, text);
  await clickAt(cdp, x, y);
}

/* ----------------------------------------------------------------------- */
/* Video                                                                    */
/* ----------------------------------------------------------------------- */

async function encode() {
  if (frames.length < 2) throw new Error('No frames were captured');
  const list = [];
  for (let i = 0; i < frames.length; i++) {
    const file = join(WORK, 'frames', `${String(i).padStart(5, '0')}.jpg`);
    await writeFile(file, Buffer.from(frames[i].data, 'base64'));
    const next = frames[i + 1]?.time ?? frames[i].time + 1.5;
    list.push(`file '${file}'`, `duration ${Math.max(0.001, next - frames[i].time).toFixed(4)}`);
  }
  list.push(`file '${join(WORK, 'frames', `${String(frames.length - 1).padStart(5, '0')}.jpg`)}'`);
  await writeFile(join(WORK, 'frames.txt'), list.join('\n'));

  const result = spawnSync('ffmpeg', [
    '-y', '-loglevel', 'error', '-f', 'concat', '-safe', '0', '-i', join(WORK, 'frames.txt'),
    '-vf', `fps=30,scale=${WIDTH}:-2:flags=lanczos,format=yuv420p`,
    '-c:v', 'libx264', '-preset', 'slow', '-crf', '23', '-movflags', '+faststart', OUTPUT,
  ], { stdio: 'inherit' });
  if (result.status !== 0) throw new Error('ffmpeg failed');
}

/* ----------------------------------------------------------------------- */
/* DevTools protocol plumbing                                               */
/* ----------------------------------------------------------------------- */

/** @param target undefined for the main page, or { contextId, sessionId } for the Gate-E frame */
async function evaluate(cdp, expression, target) {
  const params = { expression, awaitPromise: true, returnByValue: true, ...(target?.contextId ? { contextId: target.contextId } : {}) };
  const { result, exceptionDetails } = await cdp.send('Runtime.evaluate', params, target?.sessionId);
  if (exceptionDetails) throw new Error(`Page script failed: ${exceptionDetails.exception?.description ?? exceptionDetails.text}`);
  return result.value;
}

async function waitFor(cdp, expression, timeoutMs) {
  const deadline = Date.now() + timeoutMs;
  while (Date.now() < deadline) {
    if (await evaluate(cdp, expression).catch(() => false)) return;
    await sleep(250);
  }
  throw new Error(`Timed out waiting for: ${expression}`);
}

async function waitForPage() {
  for (let attempt = 0; attempt < 50; attempt++) {
    try {
      const page = (await (await fetch(`http://127.0.0.1:${PORT}/json`)).json()).find((t) => t.type === 'page');
      if (page) return page;
    } catch { /* Chrome still starting */ }
    await sleep(200);
  }
  throw new Error('Chrome did not start');
}

function connect(url) {
  return new Promise((resolveConnection, reject) => {
    const socket = new WebSocket(url);
    const pending = new Map();
    const listeners = new Map();
    let nextId = 1;

    socket.onmessage = (event) => {
      const message = JSON.parse(event.data);
      if (message.id && pending.has(message.id)) {
        const { ok, fail } = pending.get(message.id);
        pending.delete(message.id);
        message.error ? fail(new Error(message.error.message)) : ok(message.result);
      } else if (message.method) {
        for (const listener of listeners.get(message.method) ?? []) listener({ ...message.params, sessionId: message.sessionId ?? message.params?.sessionId });
      }
    };
    socket.onerror = reject;
    socket.onopen = () => resolveConnection({
      send(method, params = {}, sessionId) {
        return new Promise((ok, fail) => {
          const id = nextId++;
          pending.set(id, { ok, fail });
          socket.send(JSON.stringify({ id, method, params, ...(sessionId ? { sessionId } : {}) }));
        });
      },
      on(method, listener) {
        listeners.set(method, [...(listeners.get(method) ?? []), listener]);
      },
      close: () => socket.close(),
    });
  });
}
