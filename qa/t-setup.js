'use strict';
// Fresh-install wizard test against isolated QA server (DB missing at start).
const fs = require('fs');
const path = require('path');
const { launch, instrument, summarize, shot } = require('./lib');

const BASE = 'http://127.0.0.1:8102';
const OUT = path.join(__dirname, 'tests');
const SHOTS = path.join(__dirname, 'screenshots');
const bugs = [];
const notes = [];

function bug(id, severity, area, route, steps, expected, actual, extra = {}) {
  bugs.push({ id, severity, area, route, steps, expected, actual, ...extra, status: 'OPEN' });
}

async function main() {
  fs.mkdirSync(OUT, { recursive: true });
  fs.mkdirSync(SHOTS, { recursive: true });
  const browser = await launch();
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();
  const col = instrument(page, 'setup');

  // 1. Open setup with NO database
  await page.goto(BASE + '/setup', { waitUntil: 'networkidle' });
  await page.waitForTimeout(1500);
  await shot(page, SHOTS, 'setup-01-fresh');
  const statusText = (await page.textContent('[data-setup-status-text]') || '').trim();
  notes.push('initial status: ' + statusText);
  const formVisible = await page.isVisible('[data-setup-form]');
  if (!formVisible) bug('SETUP-01', 'CRITICAL', 'setup', '/setup', ['open /setup with no DB'], 'wizard form visible', 'form hidden. status=' + statusText);
  if (!/fresh|create|missing|ready|incomplete/i.test(statusText)) bug('SETUP-02', 'MEDIUM', 'setup', '/setup', ['read status text'], 'message about fresh install / auto-create', JSON.stringify(statusText));

  // 2. Pane 1 validation: empty company name
  await page.fill('#setup-company', '');
  await page.click('[data-next="2"]');
  await page.waitForTimeout(400);
  const errCompany = (await page.textContent('[data-error-for="company_name"]') || '').trim();
  if (!errCompany) bug('SETUP-03', 'HIGH', 'setup', '/setup', ['clear company name', 'Continue'], 'company_name error shown, stay on pane 1', 'no error; pane=' + (await page.isVisible('[data-pane="2"]') ? '2' : '?'));
  else notes.push('pane1 empty validation OK: ' + errCompany);
  await shot(page, SHOTS, 'setup-02-validation');
  await page.fill('#setup-company', 'VivaahFlow QA');

  // invalid business email
  await page.fill('#setup-email', 'not-an-email');
  await page.click('[data-next="2"]');
  await page.waitForTimeout(400);
  const errBizEmail = (await page.textContent('[data-error-for="company_email"]') || '').trim();
  if (!errBizEmail) bug('SETUP-04', 'MEDIUM', 'setup', '/setup', ['business email=not-an-email', 'Continue'], 'company_email error', 'no error shown');
  await page.fill('#setup-email', 'qa@vivaahflow.test');
  await page.click('[data-next="2"]');
  await page.waitForTimeout(400);
  if (!(await page.isVisible('[data-pane="2"]'))) bug('SETUP-05', 'CRITICAL', 'setup', '/setup', ['valid business info', 'Continue'], 'pane 2 visible', 'pane 2 NOT visible');

  // 3. Pane 2 validation
  await page.fill('#setup-admin-name', 'QA Admin');
  await page.fill('#setup-admin-email', 'bad-email');
  await page.fill('#setup-admin-password', '123');
  await page.fill('#setup-admin-confirm', 'different');
  await page.click('[data-next="3"]');
  await page.waitForTimeout(400);
  const eEmail = (await page.textContent('[data-error-for="admin_email"]') || '').trim();
  const ePass = (await page.textContent('[data-error-for="admin_password"]') || '').trim();
  const eConf = (await page.textContent('[data-error-for="admin_password_confirm"]') || '').trim();
  if (!eEmail) bug('SETUP-06', 'HIGH', 'setup', '/setup', ['admin email=bad-email', 'Review & install'], 'admin_email error', 'none');
  if (!ePass) bug('SETUP-07', 'HIGH', 'setup', '/setup', ['password=123'], 'min-length error', 'none');
  if (!eConf) bug('SETUP-08', 'HIGH', 'setup', '/setup', ['mismatched confirm'], 'mismatch error', 'none');
  await shot(page, SHOTS, 'setup-03-admin-validation');

  await page.fill('#setup-admin-email', 'qaadmin@example.com');
  await page.fill('#setup-admin-password', 'QaAdmin@123');
  await page.fill('#setup-admin-confirm', 'QaAdmin@123');
  // uncheck demo for a CLEAN install first (demo covered separately via API + seeded main DB)
  const demoBox = page.locator('[data-setup-form] input[name="with_demo"]');
  if (await demoBox.isChecked()) await demoBox.uncheck();
  await page.click('[data-next="3"]');
  await page.waitForTimeout(500);
  if (!(await page.isVisible('[data-pane="3"]'))) bug('SETUP-09', 'CRITICAL', 'setup', '/setup', ['valid admin info', 'Review & install'], 'pane 3 visible', 'NOT visible');
  const summary = (await page.textContent('[data-setup-summary]') || '').replace(/\s+/g, ' ').trim();
  notes.push('summary: ' + summary.slice(0, 200));
  if (!/VivaahFlow QA/i.test(summary) || !/qaadmin@example\.com/i.test(summary)) bug('SETUP-10', 'MEDIUM', 'setup', '/setup', ['inspect summary'], 'business + admin email shown', JSON.stringify(summary.slice(0, 160)));
  await shot(page, SHOTS, 'setup-04-review');

  // 4. Submit install (clean, no demo)
  await page.click('[data-submit]');
  await page.waitForSelector('[data-setup-done]:not([hidden])', { timeout: 30000 }).catch(() => null);
  const doneVisible = await page.locator('[data-setup-done]').isVisible().catch(() => false);
  await shot(page, SHOTS, 'setup-05-done');
  if (!doneVisible) {
    const st = (await page.textContent('[data-setup-status-text]') || '').trim();
    bug('SETUP-11', 'CRITICAL', 'setup', '/setup', ['Install VivaahFlow'], 'installation complete panel', 'done panel NOT shown. status=' + st);
  } else notes.push('install complete OK (clean, no demo)');

  // 5. Setup lock: reopen /setup
  await page.goto(BASE + '/setup', { waitUntil: 'networkidle' });
  await page.waitForTimeout(1200);
  const locked = await page.locator('[data-setup-installed]').isVisible().catch(() => false);
  const formHidden = !(await page.locator('[data-setup-form]').isVisible().catch(() => true));
  await shot(page, SHOTS, 'setup-06-locked');
  if (!locked) bug('SETUP-12', 'HIGH', 'setup', '/setup', ['reopen /setup after install'], 'already-installed lock panel', 'lock panel NOT shown');
  if (!formHidden) bug('SETUP-13', 'HIGH', 'setup', '/setup', ['reopen /setup after install'], 'form hidden', 'form still visible');

  // 6. API: POST /setup/install must 409 now
  const csrf = await page.evaluate(async () => {
    const r = await fetch('/api/v1/index.php/auth/csrf', { credentials: 'same-origin' }).then((x) => x.json());
    const m = document.querySelector('meta[name="csrf-token"]');
    return (r && r.data && r.data.token) || (m && m.content) || '';
  });
  const reinstall = await page.evaluate(async (token) => {
    const r = await fetch('/api/v1/index.php/setup/install', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-Token': token }, body: JSON.stringify({ company_name: 'Evil', admin_name: 'Evil', admin_email: 'evil@x.test', admin_password: 'Evil@123', admin_password_confirm: 'Evil@123' }) });
    return { status: r.status, body: await r.text() };
  }, csrf);
  if (reinstall.status !== 409) bug('SETUP-14', 'CRITICAL', 'setup', 'POST /setup/install', ['reinstall after complete'], 'HTTP 409', `HTTP ${reinstall.status}: ${reinstall.body.slice(0, 160)}`);
  else notes.push('reinstall blocked with 409 OK');

  // 7. status API reflects installed
  const st = await page.evaluate(async () => fetch('/api/v1/index.php/setup/status', { credentials: 'same-origin' }).then((x) => x.json()));
  if (!(st && st.data && st.data.installed === true)) bug('SETUP-15', 'HIGH', 'setup', 'GET /setup/status', ['check after install'], 'installed:true', JSON.stringify(st).slice(0, 160));

  // 8. Admin login on QA instance works
  await page.goto(BASE + '/manage/login', { waitUntil: 'networkidle' });
  await page.waitForTimeout(800);
  await page.fill('#email', 'qaadmin@example.com');
  await page.fill('#password', 'QaAdmin@123');
  await page.click('#login-form [type="submit"]');
  await page.waitForURL(/\/manage(\/|$)/, { timeout: 15000 }).catch(() => null);
  await page.waitForTimeout(1500);
  const dashUrl = page.url();
  await shot(page, SHOTS, 'setup-07-qa-dashboard');
  if (!/\/manage/.test(dashUrl) || /login/.test(dashUrl)) bug('SETUP-16', 'CRITICAL', 'setup', '/manage/login (qa)', ['login with fresh admin'], 'redirect to /manage dashboard', 'stayed at ' + dashUrl);
  else notes.push('fresh admin login OK: ' + dashUrl);

  const report = { suite: 'setup-clean-install', base: BASE, bugs, notes, qa: summarize(col) };
  fs.writeFileSync(path.join(OUT, 'setup.json'), JSON.stringify(report, null, 2));
  await browser.close();
  console.log(`SETUP: bugs=${bugs.length} notes=${notes.length} consoleErr=${report.qa.consoleErrors.length} pageErr=${report.qa.pageErrors.length} badResp=${report.qa.badResponses.length} failed=${report.qa.failedRequests.length}`);
  if (bugs.length) console.log(JSON.stringify(bugs.map((b) => b.id + ':' + b.severity + ':' + b.actual.slice(0, 100)), null, 1));
}

main().catch((e) => { console.error('SETUP FATAL', e); process.exit(1); });
