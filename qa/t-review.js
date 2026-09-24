'use strict';
// Focused: QA customer submits review for completed booking 3,
// admin approves, public page shows it.
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

async function confirmIfAny(page) {
  await page.waitForTimeout(600);
  const c = page.locator('[data-confirm]').first();
  if ((await c.count()) > 0) {
    await c.click();
    await page.waitForTimeout(1800);
    return true;
  }
  return false;
}

async function main() {
  const browser = await launch();
  const custCtx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const admCtx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const cust = await custCtx.newPage();
  const adm = await admCtx.newPage();
  const colC = instrument(cust, 'review-customer');
  const colA = instrument(adm, 'review-admin');

  // admin: remove any leftover QA review first (also exercises delete flow),
  // so booking 3 becomes eligible again
  await adm.goto(BASE + '/manage/login', { waitUntil: 'networkidle' });
  await adm.fill('#email', 'admin@example.com');
  await adm.fill('#password', 'Admin@123');
  await adm.click('#login-form [type="submit"]');
  await adm.waitForURL(/\/manage$/, { timeout: 15000 }).catch(() => null);
  await adm.waitForTimeout(1500);
  await adm.goto(BASE + '/manage/reviews', { waitUntil: 'networkidle' });
  await adm.waitForTimeout(1800);
  const preSearch = adm.locator('[data-table-search]').first();
  if ((await preSearch.count()) > 0) {
    await preSearch.fill('QA Sangeet');
    await adm.waitForTimeout(1500);
  }
  const preDel = adm.locator('[data-action="delete"]').first();
  if ((await preDel.count()) > 0) {
    await preDel.click();
    await confirmIfAny(adm);
    await adm.waitForTimeout(2000);
    notes.push('leftover QA review deleted (delete flow OK)');
  } else notes.push('no leftover QA review to delete');

  await cust.goto(BASE + '/login', { waitUntil: 'networkidle' });
  await cust.fill('#login-email', 'qaflowmuf5tlc9@example.com');
  await cust.fill('#login-password', 'QaFlow@123');
  await cust.click('[data-submit]');
  await cust.waitForURL(/\/account/, { timeout: 15000 }).catch(() => null);
  await cust.waitForTimeout(1200);
  await cust.goto(BASE + '/account/reviews', { waitUntil: 'networkidle' });
  await cust.waitForTimeout(2000);
  const cardVisible = await cust.locator('[data-eligible-card]:not([hidden])').count();
  const writeBtn = cust.locator('[data-write-review]').first();
  if ((await writeBtn.count()) === 0) bug('RV-01', 'HIGH', 'portal', '/account/reviews', ['open as QA customer (completed booking 3)'], 'Write-review button for eligible booking', `eligible card visible=${cardVisible}`);
  else {
    notes.push('eligible review button found');
    await writeBtn.click();
    await cust.waitForTimeout(800);
    const modalOpen = (await cust.locator('#review-modal:not([hidden])').count()) > 0;
    if (!modalOpen) bug('RV-02', 'MEDIUM', 'portal', '/account/reviews', ['click write review'], 'review modal opens', 'no modal');
    else {
      // star rating default 5; set title+comment
      await cust.fill('#review-title', 'QA Sangeet was magical');
      await cust.fill('#review-comment', 'The team handled everything beautifully from mandap to send-off.');
      // empty comment validation first
      await cust.fill('#review-comment', '');
      await cust.locator('[data-review-form] [data-submit]').first().click();
      await cust.waitForTimeout(800);
      const cErr = (await cust.textContent('[data-error-for="comment"]') || '').trim();
      if (!cErr) bug('RV-03', 'LOW', 'portal', '/account/reviews', ['submit empty comment'], 'comment validation error', 'none');
      else notes.push('review empty-comment validation OK');
      await cust.fill('#review-comment', 'The team handled everything beautifully from mandap to send-off.');
      await cust.locator('[data-review-form] [data-submit]').first().click();
      await cust.waitForTimeout(2500);
      const stillOpen = (await cust.locator('#review-modal:not([hidden])').count()) > 0;
      const bodyTxt = (await cust.textContent('[data-table]') || '').replace(/\s+/g, ' ');
      if (stillOpen) bug('RV-04', 'HIGH', 'portal', '/account/reviews', ['submit valid review'], 'modal closes + review listed', 'modal still open');
      else if (!/QA Sangeet was magical|pending/i.test(bodyTxt)) bug('RV-05', 'MEDIUM', 'portal', '/account/reviews', ['submit valid review'], 'review appears in list', bodyTxt.slice(0, 120));
      else notes.push('review submitted + listed OK');
    }
  }
  await shot(cust, SHOTS, 'review-01-submitted');

  // admin approve (already authenticated from the cleanup login above —
  // /manage/login would redirect to the dashboard)
  await adm.goto(BASE + '/manage/reviews', { waitUntil: 'networkidle' });
  await adm.waitForTimeout(1800);
  // find QA review row action
  const searchR = adm.locator('[data-table-search]').first();
  if ((await searchR.count()) > 0) {
    await searchR.fill('QA Sangeet');
    await adm.waitForTimeout(1500);
  }
  // admin approve via row quick-action (moderate opens a form modal instead)
  await adm.keyboard.press('Escape').catch(() => null);
  await adm.waitForTimeout(500);
  const apprBtn = adm.locator('[data-action="approve"]').first();
  if ((await apprBtn.count()) === 0) bug('RV-06', 'MEDIUM', 'admin', '/manage/reviews', ['search QA review'], 'approve action', 'NOT FOUND');
  else {
    await apprBtn.click();
    await adm.waitForTimeout(2000);
    notes.push('review approved via quick action');
  }
  // moderate modal: add business reply
  await adm.locator('[data-action="moderate"]').first().click();
  await adm.waitForTimeout(900);
  const replyF = adm.locator('.modal:not([hidden]) textarea[name="reply"], .modal:not([hidden]) [name="reply"]').first();
  if ((await replyF.count()) > 0) {
    await replyF.fill('Thank you for celebrating with us!');
    await adm.locator('.modal:not([hidden]) [data-submit]').first().click();
    await adm.waitForTimeout(2000);
    notes.push('business reply saved via moderate modal');
  } else notes.push('no reply field in moderate modal');
  await adm.keyboard.press('Escape').catch(() => null);

  await shot(adm, SHOTS, 'review-02-moderated');

  // public visibility
  await cust.goto(BASE + '/reviews', { waitUntil: 'networkidle' });
  await cust.waitForTimeout(1500);
  const pubTxt = (await cust.textContent('body') || '').replace(/\s+/g, ' ');
  if (/QA Sangeet was magical/.test(pubTxt)) notes.push('QA review publicly visible OK');
  else bug('RV-07', 'HIGH', 'public', '/reviews', ['after approve'], 'QA review visible', pubTxt.slice(0, 120));

  const report = { suite: 'review', base: BASE, bugs, notes, qa: { customer: summarize(colC), admin: summarize(colA) } };
  fs.writeFileSync(path.join(OUT, 'review.json'), JSON.stringify(report, null, 2));
  await browser.close();
  console.log(`REVIEW: bugs=${bugs.length}`);
  if (bugs.length) console.log(JSON.stringify(bugs.map((b) => b.id + ':' + b.severity + ':' + b.route + ':' + b.actual.slice(0, 130)), null, 1));
}
main().catch((e) => { console.error('REVIEW FATAL', e); process.exit(1); });
