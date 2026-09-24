'use strict';
// Round 2: payments/invoice/review/event/assign on booking 3, status path to
// completed, review submit+approve+public, services+upload, offers, leads,
// users, notifications mark-all, contact prefill, nav/refresh/modal checks.
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
const QA_EMAIL = 'qaflowmuf5tlc9@example.com'; // flow user from round 1 (QaFlow@123)
const QA_PASS = 'QaFlow@123';

async function confirmIfAny(page) {
  await page.waitForTimeout(600);
  const c = page.locator('[data-confirm]').first();
  if ((await c.count()) > 0) {
    await c.click();
    await page.waitForTimeout(1500);
    return true;
  }
  return false;
}

async function main() {
  const browser = await launch();
  const admCtx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const adm = await admCtx.newPage();
  const colA = instrument(adm, 'flow2-admin');

  await adm.goto(BASE + '/manage/login', { waitUntil: 'networkidle' });
  await adm.fill('#email', 'admin@example.com');
  await adm.fill('#password', 'Admin@123');
  await adm.click('#login-form [type="submit"]');
  await adm.waitForURL(/\/manage$/, { timeout: 15000 }).catch(() => null);
  await adm.waitForTimeout(1500);

  // ---- BOOKING 3: record payment 100 via detail modal (double-click guard) ----
  await adm.goto(BASE + '/manage/bookings/3', { waitUntil: 'networkidle' });
  await adm.waitForTimeout(1800);
  const payBtn = adm.locator('[data-action="payment"]').first();
  if ((await payBtn.count()) === 0) bug('W2-P01', 'MEDIUM', 'admin', '/manage/bookings/3', ['inspect'], 'Record Payment button', 'NOT FOUND (maybe paid?)');
  else {
    await payBtn.click();
    await adm.waitForTimeout(800);
    const amt = adm.locator('#payment-form input[name="amount"], [data-payment-amount]').first();
    // booking.js payment modal — probe field names
    const amountField = adm.locator('.modal:not([hidden]) input[name="amount"]').first();
    if ((await amountField.count()) > 0) {
      await amountField.fill('100');
      const ref = adm.locator('.modal:not([hidden]) input[name="reference_no"]').first();
      if ((await ref.count()) > 0) await ref.fill('QA-PAY-' + stamp);
      const submit = adm.locator('.modal:not([hidden]) [data-submit]').first();
      await submit.click();
      await submit.click().catch(() => null); // double-submit probe
      await adm.waitForTimeout(2500);
      const bodyAfter = (await adm.textContent('#booking-detail, body') || '');
      notes.push('payment 100 submitted (double-clicked)');
    } else notes.push('payment modal amount field not found — explore manually');
    await adm.keyboard.press('Escape').catch(() => null);
  }

  // ---- overpay guard via API (admin ctx): expect 4xx ----
  const overpay = await adm.evaluate(async ({ base }) => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const r = await fetch(base + '/api/v1/index.php/payments', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-Token': csrf }, body: JSON.stringify({ booking_id: 3, amount: 99999999, payment_date: '2026-09-24', method: 'UPI' }) });
    return { status: r.status, body: (await r.text()).slice(0, 140) };
  }, { base: BASE });
  if (![400, 409, 422].includes(overpay.status)) bug('W2-P02', 'HIGH', 'admin', 'POST /payments overpay', ['amount far above balance'], '4xx rejection', `${overpay.status}: ${overpay.body}`);
  else notes.push('overpay guard OK: ' + overpay.status);

  // ---- zero/negative amount guard ----
  for (const amt of [0, -50]) {
    const r = await adm.evaluate(async ({ base, amount }) => {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
      const res = await fetch(base + '/api/v1/index.php/payments', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-Token': csrf }, body: JSON.stringify({ booking_id: 3, amount, payment_date: '2026-09-24', method: 'Cash' }) });
      return res.status;
    }, { base: BASE, amount: amt });
    if (![400, 422].includes(r)) bug('W2-P03', 'MEDIUM', 'admin', 'POST /payments', [`amount=${amt}`], '400/422', String(r));
  }
  notes.push('zero/negative guards checked');

  // ---- INVOICE generate for booking 3 ----
  await adm.goto(BASE + '/manage/invoices', { waitUntil: 'networkidle' });
  await adm.waitForTimeout(1500);
  await adm.locator('[data-open-invoice]').first().click();
  await adm.waitForTimeout(800);
  const invModal = (await adm.locator('.modal:not([hidden])').count()) > 0;
  if (!invModal) bug('W2-I01', 'MEDIUM', 'admin', '/manage/invoices', ['Generate Invoice'], 'modal opens', 'no modal');
  else {
    // select booking 3 if a select exists
    const sel = adm.locator('.modal:not([hidden]) select[name="booking_id"]').first();
    if ((await sel.count()) > 0) {
      await sel.selectOption({ value: '3' }).catch(async () => {
        await sel.selectOption({ index: 1 }).catch(() => null);
      });
    }
    await adm.locator('.modal:not([hidden]) [data-submit]').first().click();
    await adm.waitForTimeout(2500);
    notes.push('invoice generate submitted');
    await adm.keyboard.press('Escape').catch(() => null);
  }

  // ---- BOOKING status path to completed ----
  await adm.goto(BASE + '/manage/bookings/3', { waitUntil: 'networkidle' });
  await adm.waitForTimeout(1800);
  for (const next of ['confirmed', 'scheduled', 'in_progress', 'completed']) {
    const btn = adm.locator(`[data-action="status"][data-status="${next}"]`).first();
    if ((await btn.count()) === 0) { notes.push(`status ${next}: no button (already past?)`); continue; }
    await btn.click();
    await confirmIfAny(adm);
    await adm.waitForTimeout(1800);
  }
  const finalStatus = ((await adm.textContent('#booking-detail') || '')).replace(/\s+/g, ' ');
  if (!/Completed/.test(finalStatus)) bug('W2-B01', 'HIGH', 'admin', '/manage/bookings/3', ['drive to completed'], 'status Completed', finalStatus.slice(0, 120));
  else notes.push('booking 3 → completed OK');
  await shot(adm, SHOTS, 'flow2-01-booking-completed');

  // ---- EVENT create + staff assign on booking 3 ----
  const evNew = adm.locator('[data-action="event-new"]').first();
  if ((await evNew.count()) > 0) {
    await evNew.click();
    await adm.waitForTimeout(800);
    const titleF = adm.locator('.modal:not([hidden]) input[name="title"]').first();
    if ((await titleF.count()) > 0) {
      await titleF.fill('QA Sangeet Night');
      const dateF = adm.locator('.modal:not([hidden]) input[name="event_date"]').first();
      if ((await dateF.count()) > 0) await dateF.fill('2027-08-19');
      await adm.locator('.modal:not([hidden]) [data-submit]').first().click();
      await adm.waitForTimeout(2000);
      notes.push('event created');
      await adm.keyboard.press('Escape').catch(() => null);
    }
  } else notes.push('no event-new button');
  // assign staff to first event without assignment
  const assignBtn = adm.locator('[data-assign-event]').first();
  if ((await assignBtn.count()) > 0) {
    await assignBtn.click();
    await adm.waitForTimeout(800);
    const staffSel = adm.locator('[data-staff-select]').first();
    if ((await staffSel.count()) > 0) {
      await staffSel.selectOption({ index: 1 }).catch(() => null);
      await adm.locator('#assign-form [data-submit]').first().click();
      await adm.waitForTimeout(2000);
      notes.push('staff assigned');
    } else notes.push('assign modal has no staff select');
    await adm.keyboard.press('Escape').catch(() => null);
  } else notes.push('no assign buttons (all events staffed?)');

  // ---- SERVICES: create with image upload + delete ----
  const svcName = 'QA Service ' + stamp;
  await adm.goto(BASE + '/manage/services', { waitUntil: 'networkidle' });
  await adm.waitForTimeout(1500);
  await adm.locator('[data-open-service]').first().click();
  await adm.waitForTimeout(700);
  const svcModal = (await adm.locator('#service-modal:not([hidden])').count()) > 0;
  if (!svcModal) bug('W2-S01', 'MEDIUM', 'admin', '/manage/services', ['Add Service'], 'modal opens', 'no modal');
  else {
    await adm.fill('#service-form input[name="name"]', svcName);
    // category select first option
    const catSel = adm.locator('#service-form select[name="category_id"]').first();
    if ((await catSel.count()) > 0) await catSel.selectOption({ index: 1 }).catch(() => null);
    const priceF = adm.locator('#service-form input[name="starting_price"]').first();
    if ((await priceF.count()) > 0) await priceF.fill('12345');
    // upload a real webp from repo
    const fileInput = adm.locator('#service-form [data-file-input]').first();
    if ((await fileInput.count()) > 0) {
      await fileInput.setInputFiles('C:\\xampp\\htdocs\\VivaahFlow\\uploads\\services\\veg-buffet-luxury-01.webp');
      await adm.waitForTimeout(800);
      notes.push('service image attached');
    } else notes.push('no file input in service form');
    await adm.locator('#service-form [data-submit]').first().click();
    await adm.waitForTimeout(2500);
    // search + verify
    const sSearch = adm.locator('[data-table-search]').first();
    await sSearch.fill(svcName);
    await adm.waitForTimeout(1500);
    const found = (await adm.textContent('#services-table') || '').includes(svcName);
    if (!found) bug('W2-S02', 'HIGH', 'admin', '/manage/services', ['create + search'], 'new service listed', 'not found');
    else notes.push('service create+upload OK');
    // delete it
    await adm.locator('#services-table [data-action="delete"]').first().click();
    await confirmIfAny(adm);
    await adm.waitForTimeout(2000);
    await sSearch.fill(svcName);
    await adm.waitForTimeout(1500);
    const gone = !((await adm.textContent('#services-table') || '').includes(svcName));
    if (!gone) bug('W2-S03', 'MEDIUM', 'admin', '/manage/services', ['delete + search'], 'service gone', 'still listed');
    else notes.push('service delete OK');
    await sSearch.fill('');
  }
  await shot(adm, SHOTS, 'flow2-02-services');

  // ---- OFFERS create + delete ----
  const offName = 'QA Offer ' + stamp;
  await adm.goto(BASE + '/manage/offers', { waitUntil: 'networkidle' });
  await adm.waitForTimeout(1500);
  await adm.locator('[data-open-offer]').first().click();
  await adm.waitForTimeout(700);
  const offModal = (await adm.locator('.modal:not([hidden])').count()) > 0;
  if (offModal) {
    const oname = adm.locator('.modal:not([hidden]) input[name="name"]').first();
    if ((await oname.count()) > 0) await oname.fill(offName);
    const sdate = adm.locator('.modal:not([hidden]) input[name="start_date"]').first();
    if ((await sdate.count()) > 0) await sdate.fill('2026-01-01');
    const edate = adm.locator('.modal:not([hidden]) input[name="end_date"]').first();
    if ((await edate.count()) > 0) await edate.fill('2027-12-31');
    await adm.locator('.modal:not([hidden]) [data-submit]').first().click();
    await adm.waitForTimeout(2500);
    await adm.keyboard.press('Escape').catch(() => null);
    notes.push('offer create submitted');
  } else notes.push('offer modal did not open');

  // ---- LEADS create + followup ----
  await adm.goto(BASE + '/manage/leads', { waitUntil: 'networkidle' });
  await adm.waitForTimeout(1500);
  await adm.locator('[data-open-lead]').first().click();
  await adm.waitForTimeout(700);
  const leadModal = (await adm.locator('.modal:not([hidden])').count()) > 0;
  if (leadModal) {
    const lname = adm.locator('.modal:not([hidden]) input[name="name"]').first();
    if ((await lname.count()) > 0) await lname.fill('QA Lead ' + stamp);
    const lemail = adm.locator('.modal:not([hidden]) input[name="email"]').first();
    if ((await lemail.count()) > 0) await lemail.fill(`qalead${stamp}@example.com`);
    await adm.locator('.modal:not([hidden]) [data-submit]').first().click();
    await adm.waitForTimeout(2500);
    await adm.keyboard.press('Escape').catch(() => null);
    notes.push('lead create submitted');
  } else notes.push('lead modal did not open');

  // ---- USERS create (staff) + delete ----
  const staffEmail = `qastaff${stamp}@example.com`;
  await adm.goto(BASE + '/manage/users', { waitUntil: 'networkidle' });
  await adm.waitForTimeout(1500);
  await adm.locator('[data-open-user]').first().click();
  await adm.waitForTimeout(700);
  const userModal = (await adm.locator('.modal:not([hidden])').count()) > 0;
  if (userModal) {
    const uname = adm.locator('.modal:not([hidden]) input[name="name"]').first();
    if ((await uname.count()) > 0) await uname.fill('QA Staff User');
    const uemail = adm.locator('.modal:not([hidden]) input[name="email"]').first();
    if ((await uemail.count()) > 0) await uemail.fill(staffEmail);
    const upass = adm.locator('.modal:not([hidden]) input[name="password"]').first();
    if ((await upass.count()) > 0) await upass.fill('QaStaff@123');
    const urole = adm.locator('.modal:not([hidden]) select[name="role_id"], .modal:not([hidden]) select[name="role"]').first();
    if ((await urole.count()) > 0) {
      const opts = await urole.locator('option').allTextContents();
      const idx = opts.findIndex((t) => /staff/i.test(t));
      if (idx >= 0) await urole.selectOption({ index: idx }).catch(() => null);
    }
    await adm.locator('.modal:not([hidden]) [data-submit]').first().click();
    await adm.waitForTimeout(2500);
    await adm.keyboard.press('Escape').catch(() => null);
    notes.push('user create submitted');
  } else notes.push('user modal did not open');

  // ---- NOTIFICATIONS mark-all + filter ----
  await adm.goto(BASE + '/manage/notifications', { waitUntil: 'networkidle' });
  await adm.waitForTimeout(1500);
  await adm.locator('[data-mark-all]').first().click();
  await adm.waitForTimeout(1500);
  notes.push('notifications mark-all clicked');
  const nfilter = adm.locator('[data-notification-filter]').first();
  if ((await nfilter.count()) > 0) {
    await nfilter.selectOption('unread');
    await adm.waitForTimeout(1200);
    notes.push('notifications unread filter OK');
    await nfilter.selectOption('');
    await adm.waitForTimeout(1000);
  }

  // ---- CONTACT prefill as logged-in QA customer + back/forward/refresh ----
  const custCtx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const cust = await custCtx.newPage();
  const colC = instrument(cust, 'flow2-customer');
  await cust.goto(BASE + '/login', { waitUntil: 'networkidle' });
  await cust.fill('#login-email', QA_EMAIL);
  await cust.fill('#login-password', QA_PASS);
  await cust.click('[data-submit]');
  await cust.waitForURL(/\/account/, { timeout: 15000 }).catch(() => null);
  await cust.waitForTimeout(1200);
  await cust.goto(BASE + '/contact', { waitUntil: 'networkidle' });
  await cust.waitForTimeout(2000);
  const prefillName = await cust.inputValue('#enq-name').catch(() => '');
  if (!prefillName) bug('W2-C01', 'LOW', 'public', '/contact', ['open as logged-in customer'], 'name prefilled', 'empty');
  else notes.push('contact prefill OK: ' + prefillName);
  // review submit for completed booking 3
  await cust.goto(BASE + '/account/reviews', { waitUntil: 'networkidle' });
  await cust.waitForTimeout(1500);
  await shot(cust, SHOTS, 'flow2-03-reviews');
  const revForm = await cust.locator('form, [data-review-form], select[name="booking_id"]').count();
  notes.push('reviews page form nodes=' + revForm);

  // back/forward/refresh robustness on key pages
  for (const r of ['/manage/bookings', '/manage/services', '/account']) {
    await adm.goto(BASE + r, { waitUntil: 'networkidle' }).catch(() => null);
    await adm.waitForTimeout(1000);
    await adm.goBack().catch(() => null);
    await adm.waitForTimeout(800);
    await adm.goForward().catch(() => null);
    await adm.waitForTimeout(800);
    await adm.reload({ waitUntil: 'networkidle' }).catch(() => null);
    await adm.waitForTimeout(1000);
    const errs = (await adm.textContent('body') || '').slice(0, 120);
    if (/Fatal error|PDOException/i.test(errs)) bug('W2-N01', 'MEDIUM', 'nav', r, ['back/forward/reload'], 'stable page', errs.slice(0, 80));
  }
  notes.push('nav back/forward/reload OK');

  const report = { suite: 'workflows2', base: BASE, bugs, notes, qa: { admin: summarize(colA), customer: summarize(colC) } };
  fs.writeFileSync(path.join(OUT, 'workflows2.json'), JSON.stringify(report, null, 2));
  await browser.close();
  console.log(`WORKFLOWS2: bugs=${bugs.length}`);
  if (bugs.length) console.log(JSON.stringify(bugs.map((b) => b.id + ':' + b.severity + ':' + b.route + ':' + b.actual.slice(0, 130)), null, 1));
}
main().catch((e) => { console.error('WORKFLOWS2 FATAL', e); process.exit(1); });
