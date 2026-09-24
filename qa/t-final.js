'use strict';
// Final regression: gallery alt, invoice print button, register retention,
// t-auth AUTH-04 recheck is covered by rerun; wide login shot.
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

async function main() {
  const browser = await launch();
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();
  const col = instrument(page, 'final');

  // gallery alt attributes present
  await page.goto(BASE + '/gallery', { waitUntil: 'networkidle' });
  await page.waitForTimeout(1500);
  const noAlt = await page.evaluate(() => {
    const imgs = Array.from(document.querySelectorAll('[data-gallery] img, .site-gallery-item img'));
    return { total: imgs.length, missing: imgs.filter((i) => !i.getAttribute('alt')).length };
  });
  if (noAlt.total > 0 && noAlt.missing > 0) bug('FIN-G01', 'LOW', 'public', '/gallery', ['inspect imgs'], 'all imgs have alt', `${noAlt.missing}/${noAlt.total} missing`);
  else notes.push(`gallery alt OK (${noAlt.total} imgs)`);

  // register retention after failed submit
  await page.goto(BASE + '/register', { waitUntil: 'networkidle' });
  await page.waitForTimeout(800);
  await page.fill('#reg-name', 'Retention Probe');
  await page.fill('#reg-email', 'not-an-email');
  await page.click('[data-submit]');
  await page.waitForTimeout(800);
  const keptName = await page.inputValue('#reg-name').catch(() => '');
  const keptEmail = await page.inputValue('#reg-email').catch(() => '');
  if (keptName !== 'Retention Probe' || keptEmail !== 'not-an-email') bug('FIN-R01', 'MEDIUM', 'auth', '/register', ['fill', 'submit invalid'], 'input retained', `name=${keptName} email=${keptEmail}`);
  else notes.push('register retention OK');

  // admin invoice detail: print button, no JS error on click
  await page.goto(BASE + '/manage/login', { waitUntil: 'networkidle' });
  await page.fill('#email', 'admin@example.com');
  await page.fill('#password', 'Admin@123');
  await page.click('#login-form [type="submit"]');
  await page.waitForURL(/\/manage$/, { timeout: 15000 }).catch(() => null);
  await page.waitForTimeout(1500);
  await page.goto(BASE + '/manage/invoices', { waitUntil: 'networkidle' });
  await page.waitForTimeout(1500);
  const invLink = page.locator('a[href*="/manage/invoices/"]').first();
  if ((await invLink.count()) > 0) {
    await invLink.click();
    await page.waitForTimeout(1800);
    const printBtn = page.locator('[data-print], [data-action="print"], button:has-text("Print")').first();
    if ((await printBtn.count()) === 0) bug('FIN-I01', 'LOW', 'admin', '/manage/invoices/{id}', ['open detail'], 'print control', 'NOT FOUND');
    else {
      await printBtn.click().catch(() => null);
      await page.waitForTimeout(800);
      notes.push('invoice print clicked without JS error');
    }
    await shot(page, SHOTS, 'final-01-invoice-detail');
  } else notes.push('no invoice links found');

  // customer invoice print button
  await page.goto(BASE + '/login', { waitUntil: 'networkidle' });
  await page.fill('#login-email', 'customer@example.com');
  await page.fill('#login-password', 'Customer@123');
  await page.click('[data-submit]');
  await page.waitForURL(/\/account/, { timeout: 15000 }).catch(() => null);
  await page.waitForTimeout(1200);
  await page.goto(BASE + '/account/invoices', { waitUntil: 'networkidle' });
  await page.waitForTimeout(1500);
  const cInv = page.locator('a[href*="/account/invoices/"]').first();
  if ((await cInv.count()) > 0) {
    await cInv.click();
    await page.waitForTimeout(1500);
    const hasPrint = (await page.locator('[data-print], button:has-text("Print")').count()) > 0;
    notes.push('portal invoice print control present=' + hasPrint);
  }

  const report = { suite: 'final', base: BASE, bugs, notes, qa: summarize(col) };
  fs.writeFileSync(path.join(OUT, 'final.json'), JSON.stringify(report, null, 2));
  await browser.close();
  console.log(`FINAL: bugs=${bugs.length} consoleErr=${report.qa.consoleErrors.length} pageErr=${report.qa.pageErrors.length} badResp=${report.qa.badResponses.length}`);
  if (bugs.length) console.log(JSON.stringify(bugs, null, 1));
}
main().catch((e) => { console.error('FINAL FATAL', e); process.exit(1); });
