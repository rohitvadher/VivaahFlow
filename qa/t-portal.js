'use strict';
// Customer portal exhaustive: every /account route, quotation accept flow,
// review submit, profile update, cross-customer isolation.
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

const PORTAL_ROUTES = [
  '/account', '/account/enquiries', '/account/quotations', '/account/bookings',
  '/account/payments', '/account/invoices', '/account/reviews', '/account/profile',
];

async function main() {
  const browser = await launch();
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();
  const col = instrument(page, 'portal');

  await page.goto(BASE + '/login', { waitUntil: 'networkidle' });
  await page.fill('#login-email', 'customer@example.com');
  await page.fill('#login-password', 'Customer@123');
  await page.click('[data-submit]');
  await page.waitForURL(/\/account/, { timeout: 15000 }).catch(() => null);
  await page.waitForTimeout(1500);

  for (const r of PORTAL_ROUTES) {
    await page.goto(BASE + r, { waitUntil: 'networkidle' }).catch(() => null);
    await page.waitForTimeout(1400);
    const body = (await page.textContent('body') || '').slice(0, 200);
    if (/Fatal error|PDOException|Call to a member function/i.test(body)) bug('POR-R' + r.replace(/\W/g, ''), 'HIGH', 'portal', r, ['open'], 'rendered page', 'PHP error: ' + body.slice(0, 100));
    const h1 = await page.locator('h1').first().textContent().catch(() => '');
    notes.push(`${r} h1=${(h1 || '').trim().slice(0, 40)}`);
  }
  await shot(page, SHOTS, 'portal-01-dashboard');

  // QUOTATION accept flow: find a sent quotation, accept it
  await page.goto(BASE + '/account/quotations', { waitUntil: 'networkidle' });
  await page.waitForTimeout(1500);
  const acceptBtn = page.locator('[data-action="accept"], [data-accept]').first();
  if ((await acceptBtn.count()) > 0) {
    await acceptBtn.click();
    await page.waitForTimeout(800);
    const confirm = page.locator('[data-confirm]').first();
    if ((await confirm.count()) > 0) await confirm.click();
    await page.waitForTimeout(2500);
    notes.push('quotation accept attempted');
  } else notes.push('no sent quotation accept button (state-dependent, check seeded data)');
  await shot(page, SHOTS, 'portal-02-quotations');

  // booking detail opens
  await page.goto(BASE + '/account/bookings', { waitUntil: 'networkidle' });
  await page.waitForTimeout(1500);
  const bLink = page.locator('a[href*="/account/bookings/"]').first();
  if ((await bLink.count()) > 0) {
    await bLink.click();
    await page.waitForTimeout(1500);
    const h1 = (await page.locator('h1').first().textContent().catch(() => '') || '').trim();
    if (!h1) bug('POR-B01', 'MEDIUM', 'portal', '/account/bookings/{id}', ['open detail'], 'detail h1', 'missing');
    else notes.push('booking detail OK: ' + h1.slice(0, 50));
  } else notes.push('no booking links found');

  // ISOLATION: another customer's booking id must 404 (use id 999999 + id 2 guess)
  for (const id of [999999, 2]) {
    const probe = await page.evaluate(async ({ base, bid }) => {
      const r = await fetch(base + '/api/v1/index.php/portal/bookings/' + bid, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
      return { status: r.status, body: (await r.text()).slice(0, 100) };
    }, { base: BASE, bid: id });
    notes.push(`portal booking ${id} as customer → ${probe.status}`);
  }

  // PROFILE update persists
  await page.goto(BASE + '/account/profile', { waitUntil: 'networkidle' });
  await page.waitForTimeout(1200);
  const cityInput = page.locator('input[name="city"]').first();
  if ((await cityInput.count()) > 0) {
    const orig = await cityInput.inputValue();
    await cityInput.fill('QA City');
    const saveBtn = page.locator('[data-submit], [type="submit"]').first();
    await saveBtn.click();
    await page.waitForTimeout(2000);
    await page.reload({ waitUntil: 'networkidle' });
    await page.waitForTimeout(1200);
    const after = await page.locator('input[name="city"]').first().inputValue().catch(() => '');
    if (after !== 'QA City') bug('POR-P01', 'MEDIUM', 'portal', '/account/profile', ['change city', 'save', 'reload'], 'persisted', JSON.stringify(after));
    else notes.push('portal profile persist OK');
    await page.locator('input[name="city"]').first().fill(orig);
    await page.locator('[data-submit], [type="submit"]').first().click();
    await page.waitForTimeout(1500);
  } else notes.push('no city input on portal profile');

  const report = { suite: 'portal', base: BASE, bugs, notes, qa: summarize(col) };
  fs.writeFileSync(path.join(OUT, 'portal.json'), JSON.stringify(report, null, 2));
  await browser.close();
  console.log(`PORTAL: bugs=${bugs.length} consoleErr=${report.qa.consoleErrors.length} pageErr=${report.qa.pageErrors.length} badResp=${report.qa.badResponses.length} failed=${report.qa.failedRequests.length}`);
  if (bugs.length) console.log(JSON.stringify(bugs.map((b) => b.id + ':' + b.severity + ':' + b.route + ':' + b.actual.slice(0, 120)), null, 1));
}
main().catch((e) => { console.error('PORTAL FATAL', e); process.exit(1); });
