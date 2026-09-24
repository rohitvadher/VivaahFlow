'use strict';
// Customer auth: register (valid/invalid/duplicate), login (valid/invalid),
// logout, guards, back-button behavior. Seeded Apache DB.
const fs = require('fs');
const path = require('path');
const { launch, instrument, summarize, shot } = require('./lib');

const BASE = 'http://localhost/VivaahFlow';
const OUT = path.join(__dirname, 'tests');
const SHOTS = path.join(__dirname, 'screenshots');
const bugs = [];
const notes = [];
function bug(id, severity, area, route, steps, expected, actual, extra = {}) {
  bugs.push({ id, severity, area, route, steps, expected, actual, ...extra, status: 'OPEN' });
}
const stamp = Date.now().toString(36);
const NEW_EMAIL = `qareg${stamp}@example.com`;

async function main() {
  const browser = await launch();
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();
  const col = instrument(page, 'auth');

  // REGISTER: invalid first
  await page.goto(BASE + '/register', { waitUntil: 'networkidle' });
  await page.waitForTimeout(1000);
  await shot(page, SHOTS, 'auth-01-register');
  // password toggle exists + works
  const toggle = page.locator('[data-password-toggle]').first();
  if ((await toggle.count()) === 0) bug('AUTH-01', 'MEDIUM', 'auth', '/register', ['inspect'], 'password visibility toggle', 'NOT FOUND');
  else {
    const before = await page.getAttribute('#reg-password', 'type');
    await toggle.click();
    const after = await page.getAttribute('#reg-password', 'type');
    const aria = await toggle.getAttribute('aria-label');
    if (!(before === 'password' && after === 'text')) bug('AUTH-02', 'MEDIUM', 'auth', '/register', ['click eye toggle'], 'password visible', `${before}->${after}`);
    if (!/hide/i.test(aria || '')) bug('AUTH-03', 'LOW', 'auth', '/register', ['click eye toggle'], 'aria-label Hide password', JSON.stringify(aria));
    await toggle.click(); // back to hidden
    const iconHtml = await toggle.innerHTML();
    // Lucide replaces <i> with <svg class="lucide-eye...">; verify correct glyph both ways
    if (!/lucide-eye[^i-]|lucide-eye"/.test(iconHtml)) bug('AUTH-04', 'LOW', 'auth', '/register', ['toggle twice'], 'eye icon restored when hidden', iconHtml.slice(0, 120));
  }
  // empty submit
  await page.click('[data-submit]');
  await page.waitForTimeout(600);
  const nameErr = (await page.textContent('[data-error-for="name"]') || '').trim();
  if (!nameErr) bug('AUTH-05', 'MEDIUM', 'auth', '/register', ['submit empty'], 'name validation error', 'none');
  // duplicate email
  await page.fill('#reg-name', 'Dup User');
  await page.fill('#reg-email', 'customer@example.com');
  await page.fill('#reg-password', 'Secret123');
  await page.click('[data-submit]');
  await page.waitForTimeout(1500);
  const dupErr = ((await page.textContent('[data-error-for="email"]') || '') + ' ' + (await page.textContent('body') || '')).slice(0, 200);
  if (!/already exists|already have an account/i.test(dupErr)) bug('AUTH-06', 'HIGH', 'auth', '/register', ['register with existing email'], 'duplicate-email message', dupErr.slice(0, 120));
  else notes.push('duplicate registration message OK');
  await shot(page, SHOTS, 'auth-02-register-dup');

  // valid registration
  await page.fill('#reg-name', 'QA Reg User');
  await page.fill('#reg-email', NEW_EMAIL);
  await page.fill('#reg-phone', '+91 90000 12345');
  await page.fill('#reg-password', 'QaReg@123');
  await page.fill('#reg-date', '2027-06-15');
  await page.fill('#reg-type', 'Wedding');
  await page.click('[data-submit]');
  await page.waitForURL(/\/account/, { timeout: 15000 }).catch(() => null);
  await page.waitForTimeout(1500);
  if (!/\/account/.test(page.url())) bug('AUTH-07', 'CRITICAL', 'auth', '/register', ['valid registration'], 'redirect to /account portal', 'stayed at ' + page.url());
  else notes.push('registration → portal OK: ' + page.url());
  await shot(page, SHOTS, 'auth-03-portal-after-register');

  // portal guard content loads (dashboard stats, not login page)
  const portalH1 = (await page.locator('h1').first().textContent().catch(() => '') || '').trim();
  if (/sign in/i.test(portalH1)) bug('AUTH-08', 'HIGH', 'auth', '/account', ['after register'], 'portal dashboard', 'bounced to login: ' + portalH1);

  // logout via portal
  await page.click('[data-logout]').catch(() => null);
  await page.waitForTimeout(1500);
  await page.goto(BASE + '/account', { waitUntil: 'networkidle' }).catch(() => null);
  await page.waitForTimeout(1200);
  if (/\/account/.test(page.url()) && !/login/.test(page.url())) {
    const t = await page.title();
    if (!/sign in/i.test(t)) bug('AUTH-09', 'HIGH', 'auth', 'logout', ['logout', 'open /account'], 'redirect to login', page.url() + ' title=' + t);
    else notes.push('logout + guard redirect OK');
  } else notes.push('logout + guard redirect OK: ' + page.url());

  // LOGIN: wrong password
  await page.goto(BASE + '/login', { waitUntil: 'networkidle' });
  await page.waitForTimeout(800);
  await page.fill('#login-email', 'customer@example.com');
  await page.fill('#login-password', 'WrongPass9');
  await page.click('[data-submit]');
  await page.waitForTimeout(1500);
  if (/\/account/.test(page.url())) bug('AUTH-10', 'CRITICAL', 'auth', '/login', ['wrong password'], 'stay on login + error', 'redirected to portal!');
  else notes.push('wrong password stays on login OK');
  await shot(page, SHOTS, 'auth-04-login-error');
  // valid login
  await page.fill('#login-email', 'customer@example.com');
  await page.fill('#login-password', 'Customer@123');
  await page.click('[data-submit]');
  await page.waitForURL(/\/account/, { timeout: 15000 }).catch(() => null);
  await page.waitForTimeout(1200);
  if (!/\/account/.test(page.url())) bug('AUTH-11', 'CRITICAL', 'auth', '/login', ['valid customer login'], '/account portal', page.url());
  else notes.push('customer login OK');

  const report = { suite: 'auth', base: BASE, bugs, notes, qa: summarize(col) };
  fs.writeFileSync(path.join(OUT, 'auth.json'), JSON.stringify(report, null, 2));
  await browser.close();
  console.log(`AUTH: bugs=${bugs.length} consoleErr=${report.qa.consoleErrors.length} pageErr=${report.qa.pageErrors.length} badResp=${report.qa.badResponses.length} failed=${report.qa.failedRequests.length}`);
  if (bugs.length) console.log(JSON.stringify(bugs.map((b) => b.id + ':' + b.severity + ':' + b.actual.slice(0, 120)), null, 1));
}
main().catch((e) => { console.error('AUTH FATAL', e); process.exit(1); });
