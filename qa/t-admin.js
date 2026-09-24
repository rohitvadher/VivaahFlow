'use strict';
// Admin exhaustive sweep: login variants, every /manage route, CRUD spot
// checks (category create/delete, settings save), modals, permissions.
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

const ADMIN_ROUTES = [
  '/manage/login', '/manage', '/manage/customers', '/manage/enquiries', '/manage/leads',
  '/manage/categories', '/manage/services', '/manage/packages', '/manage/offers',
  '/manage/quotations', '/manage/bookings', '/manage/events', '/manage/staff',
  '/manage/payments', '/manage/invoices', '/manage/reviews', '/manage/reports',
  '/manage/notifications', '/manage/settings', '/manage/users', '/manage/activity',
  '/manage/help', '/manage/profile',
];

async function loginAs(page, email, pw) {
  await page.goto(BASE + '/manage/login', { waitUntil: 'networkidle' });
  await page.waitForTimeout(600);
  await page.fill('#email', email);
  await page.fill('#password', pw);
  await page.click('#login-form [type="submit"]');
  await page.waitForTimeout(2000);
}

async function main() {
  const browser = await launch();
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();
  const col = instrument(page, 'admin');

  // LOGIN variants
  await page.goto(BASE + '/manage/login', { waitUntil: 'networkidle' });
  await page.waitForTimeout(900);
  await shot(page, SHOTS, 'admin-01-login');
  // toggle check
  const tgl = page.locator('[data-password-toggle]').first();
  if ((await tgl.count()) === 0) bug('ADM-A01', 'MEDIUM', 'admin', '/manage/login', ['inspect'], 'password toggle', 'NOT FOUND');
  else {
    await tgl.click();
    const tp = await page.getAttribute('#password', 'type');
    if (tp !== 'text') bug('ADM-A02', 'MEDIUM', 'admin', '/manage/login', ['click toggle'], 'password visible', 'type=' + tp);
  }
  // empty submit → stays, no redirect
  await page.fill('#email', '');
  await page.fill('#password', '');
  await page.click('#login-form [type="submit"]');
  await page.waitForTimeout(800);
  if (!/login/.test(page.url())) bug('ADM-A03', 'MEDIUM', 'admin', '/manage/login', ['empty submit'], 'stay on login', page.url());
  // wrong password
  await page.fill('#email', 'admin@example.com');
  await page.fill('#password', 'WrongPass9');
  await page.click('#login-form [type="submit"]');
  await page.waitForTimeout(1500);
  if (!/login/.test(page.url())) bug('ADM-A04', 'CRITICAL', 'admin', '/manage/login', ['wrong password'], 'stay on login', 'left login: ' + page.url());
  else notes.push('wrong admin password stays OK');
  // valid login
  await loginAs(page, 'admin@example.com', 'Admin@123');
  if (!/\/manage/.test(page.url()) || /login/.test(page.url())) bug('ADM-A05', 'CRITICAL', 'admin', '/manage/login', ['valid login'], '/manage dashboard', page.url());
  else notes.push('admin login OK: ' + page.url());
  await shot(page, SHOTS, 'admin-02-dashboard');

  // Every route
  for (const r of ADMIN_ROUTES) {
    if (r === '/manage/login') continue;
    await page.goto(BASE + r, { waitUntil: 'networkidle' }).catch(() => null);
    await page.waitForTimeout(1400);
    const body = (await page.textContent('body') || '').slice(0, 200);
    if (/Fatal error|PDOException|Call to a member function|Undefined index|Division by zero/i.test(body)) {
      bug('ADM-R' + r.replace(/\W/g, ''), 'HIGH', 'admin', r, ['open'], 'rendered page', 'PHP error text: ' + body.slice(0, 100));
    }
    const h1 = await page.locator('h1, .page-title').first().textContent().catch(() => '');
    notes.push(`${r} h1=${(h1 || '').trim().slice(0, 40)}`);
  }
  await shot(page, SHOTS, 'admin-03-settings');

  // CATEGORY CRUD: create → search → edit → delete (uses [data-open-category] flow)
  const catName = 'QA Cat ' + Date.now().toString(36);
  const catName2 = catName + ' Edited';
  await page.goto(BASE + '/manage/categories', { waitUntil: 'networkidle' });
  await page.waitForTimeout(1500);
  await page.click('[data-open-category]');
  await page.waitForTimeout(600);
  if (!(await page.locator('#category-modal:not([hidden])').count())) bug('ADM-C01', 'MEDIUM', 'admin', '/manage/categories', ['Add Category'], 'modal opens', 'no visible modal');
  else {
    await page.fill('#category-form input[name="name"]', catName);
    // double-submit guard: click save twice rapidly
    await page.click('#category-form [data-submit]');
    await page.click('#category-form [data-submit]').catch(() => null);
    await page.waitForTimeout(2500);
    const searchInput = page.locator('[data-table-search]').first();
    await searchInput.fill(catName);
    await page.waitForTimeout(1500);
    const bodyText = (await page.textContent('#categories-table') || '');
    if (!bodyText.includes(catName)) bug('ADM-C02', 'HIGH', 'admin', '/manage/categories', ['create + search'], 'new category listed', bodyText.slice(0, 120));
    else notes.push('category create+search OK');
    // edit first matching row
    await page.locator('#categories-table [data-action="edit"]').first().click();
    await page.waitForTimeout(600);
    await page.fill('#category-form input[name="name"]', catName2);
    await page.click('#category-form [data-submit]');
    await page.waitForTimeout(2000);
    await searchInput.fill(catName2);
    await page.waitForTimeout(1500);
    const bodyText2 = (await page.textContent('#categories-table') || '');
    if (!bodyText2.includes(catName2)) bug('ADM-C03', 'MEDIUM', 'admin', '/manage/categories', ['edit + search'], 'edited name listed', bodyText2.slice(0, 120));
    else notes.push('category edit OK');
    // delete it (confirm dialog)
    await page.locator('#categories-table [data-action="delete"]').first().click();
    await page.waitForTimeout(700);
    await page.locator('[data-confirm]').first().click().catch(() => null);
    await page.waitForTimeout(2000);
    await searchInput.fill(catName2);
    await page.waitForTimeout(1500);
    const bodyText3 = (await page.textContent('#categories-table') || '');
    if (bodyText3.includes(catName2)) bug('ADM-C04', 'MEDIUM', 'admin', '/manage/categories', ['delete + search'], 'category gone', 'still listed');
    else notes.push('category delete OK');
    // cancel-path: open delete, cancel, still present check on another row is skipped (destructive caution)
    await searchInput.fill('');
    await page.waitForTimeout(1200);
  }

  // SETTINGS save + persistence
  await page.goto(BASE + '/manage/settings', { waitUntil: 'networkidle' });
  await page.waitForTimeout(1200);
  const footerInput = page.locator('input[name="footer_text"]').first();
  if ((await footerInput.count()) > 0) {
    const orig = await footerInput.inputValue();
    const sentinel = 'QA footer check 12345';
    await footerInput.fill(sentinel);
    await page.locator('#settings-form [type="submit"], [data-submit]').first().click();
    await page.waitForTimeout(2000);
    await page.reload({ waitUntil: 'networkidle' });
    await page.waitForTimeout(1200);
    const after = await page.locator('input[name="footer_text"]').first().inputValue().catch(() => '');
    if (after !== sentinel) bug('ADM-S01', 'HIGH', 'admin', '/manage/settings', ['change footer', 'save', 'reload'], 'persisted value', JSON.stringify(after));
    else notes.push('settings persist OK');
    await footerInput.fill(orig);
    await page.locator('#settings-form [type="submit"], [data-submit]').first().click();
    await page.waitForTimeout(1500);
  } else bug('ADM-S02', 'MEDIUM', 'admin', '/manage/settings', ['inspect'], 'footer_text input', 'NOT FOUND');

  // PERMISSION: customer session must get 401/403 on admin API
  const ctx2 = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const cust = await ctx2.newPage();
  await cust.goto(BASE + '/login', { waitUntil: 'networkidle' });
  await cust.fill('#login-email', 'customer@example.com');
  await cust.fill('#login-password', 'Customer@123');
  await cust.click('[data-submit]');
  await cust.waitForTimeout(2000);
  const probe = await cust.evaluate(async (base) => {
    const r = await fetch(base + '/api/v1/index.php/users', { credentials: 'same-origin', headers: { Accept: 'application/json' } });
    return { status: r.status, body: (await r.text()).slice(0, 120) };
  }, BASE);
  if (![401, 403].includes(probe.status)) bug('ADM-P01', 'CRITICAL', 'security', 'GET /users as customer', ['login as customer', 'fetch /users'], '401/403', `${probe.status}: ${probe.body}`);
  else notes.push('customer→admin API blocked OK: ' + probe.status);
  const anonProbe = await page.evaluate(async (base) => {
    const r = await fetch(base + '/api/v1/index.php/dashboard', { credentials: 'omit', headers: { Accept: 'application/json' } });
    return r.status;
  }, BASE).catch(() => 'eval-fail');
  notes.push('anon /dashboard status=' + anonProbe);

  // logout via confirm dialog, then guard check
  await page.goto(BASE + '/manage', { waitUntil: 'networkidle' }).catch(() => null);
  await page.evaluate(() => document.querySelector('[data-logout]')?.click()).catch(() => null);
  await page.waitForTimeout(800);
  await page.locator('[data-confirm]').first().click().catch(() => null);
  await page.waitForTimeout(2000);
  await page.goto(BASE + '/manage', { waitUntil: 'domcontentloaded' }).catch(() => null);
  await page.waitForTimeout(1200);
  const afterLogout = page.url();
  if (/\/manage$|\/manage\?|\/manage\/dashboard/.test(afterLogout) && !/login/.test(afterLogout)) {
    const t = await page.title().catch(() => '');
    notes.push('post-logout /manage → ' + afterLogout + ' title=' + t);
  } else notes.push('post-logout guard OK: ' + afterLogout);

  const report = { suite: 'admin', base: BASE, bugs, notes, qa: summarize(col) };
  fs.writeFileSync(path.join(OUT, 'admin.json'), JSON.stringify(report, null, 2));
  await browser.close();
  console.log(`ADMIN: bugs=${bugs.length} consoleErr=${report.qa.consoleErrors.length} pageErr=${report.qa.pageErrors.length} badResp=${report.qa.badResponses.length} failed=${report.qa.failedRequests.length}`);
  if (bugs.length) console.log(JSON.stringify(bugs.map((b) => b.id + ':' + b.severity + ':' + b.route + ':' + b.actual.slice(0, 120)), null, 1));
}
main().catch((e) => { console.error('ADMIN FATAL', e); process.exit(1); });
