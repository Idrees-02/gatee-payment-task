/**
 * Captures the submission screenshots with headless Chrome over the DevTools protocol.
 * No dependencies: Node 22+ (built-in WebSocket) and an installed Google Chrome.
 *
 *   node tools/screenshots.mjs            # app on :8090, phpMyAdmin on :8091
 *
 * Output: screenshots/*.png
 */
import { spawn } from 'node:child_process';
import { mkdir, writeFile } from 'node:fs/promises';
import { setTimeout as sleep } from 'node:timers/promises';

const APP = process.env.APP_URL ?? 'http://localhost:8090';
const PMA = process.env.PMA_URL ?? 'http://localhost:8091';
const CHROME = process.env.CHROME ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const PORT = 9333;
const OUT = new URL('../screenshots/', import.meta.url);

const shots = [
  { file: '01-product-hero.png', url: `${APP}/` , wait: 2500 },
  { file: '02-scroll-scene.png', url: `${APP}/`, scrollTo: '.assembly', scrollExtra: 0.9, wait: 2500 },
  { file: '03-finish-picker.png', url: `${APP}/`, scrollTo: '.finishes', click: '.finish-tab[data-finish="sand"]', wait: 2500 },
  { file: '04-checkout-form.png', url: `${APP}/`, scrollTo: '#checkout', wait: 2500 },
  { file: '05-validation-errors.png', url: `${APP}/`, scrollTo: '#checkout', script: submitEmptyForm, wait: 2000 },
  { file: '06-gateway-in-iframe.png', url: `${APP}/`, scrollTo: '#checkout', script: submitValidForm, wait: 14000 },
  { file: '07a-result-paid.png', url: `${APP}/callback.php?payment_id=10117946748990003&payment_code=HALO-261114-F5C68C`, wait: 2000 },
  { file: '07b-result-declined.png', url: `${APP}/callback.php?payment_id=10117946749490002&payment_code=HALO-261114-E7B330`, wait: 2000 },
  { file: '07-payments-list.png', url: `${APP}/payments.php`, wait: 1500 },
  { file: '08-payment-modal.png', url: `${APP}/payments.php`, script: openFirstPaidPayment, wait: 5000 },
  { file: '09-mobile-hero.png', url: `${APP}/`, mobile: true, wait: 2500 },
  { file: '10-mobile-checkout.png', url: `${APP}/`, mobile: true, scrollTo: '#checkout', wait: 2500 },
  { file: 'db-01-customers.png', url: `${PMA}/index.php?route=/sql&db=gatee_shop&table=customers&pos=0`, wait: 2500 },
  { file: 'db-02-payments.png', url: `${PMA}/index.php?route=/sql&db=gatee_shop&table=payments&pos=0`, wait: 2500 },
  { file: 'db-03-transactions.png', url: `${PMA}/index.php?route=/sql&db=gatee_shop&table=transactions&pos=0`, wait: 2500 },
  { file: 'db-04-structure.png', url: `${PMA}/index.php?route=/database/structure&db=gatee_shop`, wait: 2500 },
];

/* Page scripts (run inside the page) ------------------------------------- */

function submitEmptyForm() {
  document.querySelector('#checkout-form [type=submit]').click();
}

function submitValidForm() {
  document.getElementById('name').value = 'Screenshot Demo';
  document.getElementById('email').value = 'demo@example.com';
  document.getElementById('mobile').value = '33000000';
  document.querySelector('#checkout-form [type=submit]').click();
  setTimeout(() => document.querySelector('.pay__gateway').scrollIntoView({ block: 'start', behavior: 'instant' }), 3000);
}

function openFirstPaidPayment() {
  const row = [...document.querySelectorAll('tbody tr')].find((tr) => tr.textContent.includes('Paid'));
  (row ?? document.querySelector('tbody tr')).querySelector('[data-view-payment]').click();
}

/* Chrome DevTools plumbing --------------------------------------------------- */

const chrome = spawn(CHROME, [
  '--headless=new', `--remote-debugging-port=${PORT}`, '--no-first-run', '--hide-scrollbars',
  '--user-data-dir=' + new URL('../.chrome-profile/', import.meta.url).pathname, 'about:blank',
], { stdio: 'ignore' });

try {
  await mkdir(OUT, { recursive: true });
  const target = await waitForTarget();
  const cdp = await connect(target.webSocketDebuggerUrl);
  await cdp.send('Page.enable');
  await cdp.send('Runtime.enable');

  for (const shot of shots) {
    const width = shot.mobile ? 390 : 1440;
    const height = shot.mobile ? 844 : 900;
    await cdp.send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: shot.mobile ? 3 : 2, mobile: !!shot.mobile });
    await cdp.send('Page.navigate', { url: shot.url });
    await sleep(2000);
    if (shot.scrollTo) {
      await evaluate(cdp, `document.documentElement.style.scrollBehavior='auto';
        window.scrollTo(0, document.querySelector(${JSON.stringify(shot.scrollTo)}).getBoundingClientRect().top + scrollY + innerHeight * ${shot.scrollExtra ?? 0});`);
      await sleep(600);
    }
    if (shot.click) await evaluate(cdp, `document.querySelector(${JSON.stringify(shot.click)}).click()`);
    if (shot.script) await evaluate(cdp, `(${shot.script.toString()})()`);
    await sleep(shot.wait);
    const { data } = await cdp.send('Page.captureScreenshot', { format: 'png' });
    await writeFile(new URL(shot.file, OUT), Buffer.from(data, 'base64'));
    console.log('saved', shot.file);
  }
  cdp.close();
} finally {
  chrome.kill();
}

async function waitForTarget() {
  for (let attempt = 0; attempt < 50; attempt++) {
    try {
      const targets = await (await fetch(`http://127.0.0.1:${PORT}/json`)).json();
      const page = targets.find((t) => t.type === 'page');
      if (page) return page;
    } catch { /* Chrome still starting */ }
    await sleep(200);
  }
  throw new Error('Chrome did not start');
}

function connect(url) {
  return new Promise((resolve, reject) => {
    const socket = new WebSocket(url);
    const pending = new Map();
    let nextId = 1;
    socket.onmessage = (event) => {
      const message = JSON.parse(event.data);
      if (message.id && pending.has(message.id)) {
        const { ok, fail } = pending.get(message.id);
        pending.delete(message.id);
        message.error ? fail(new Error(message.error.message)) : ok(message.result);
      }
    };
    socket.onerror = reject;
    socket.onopen = () => resolve({
      send: (method, params = {}) => new Promise((ok, fail) => {
        const id = nextId++;
        pending.set(id, { ok, fail });
        socket.send(JSON.stringify({ id, method, params }));
      }),
      close: () => socket.close(),
    });
  });
}

async function evaluate(cdp, expression) {
  const { exceptionDetails } = await cdp.send('Runtime.evaluate', { expression, awaitPromise: true });
  if (exceptionDetails) console.warn('page script failed:', exceptionDetails.text);
}
