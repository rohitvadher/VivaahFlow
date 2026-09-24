'use strict';
// Public website sweep on Apache (seeded vivaahflow DB).
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
  const col = instrument(page, 'public');

  const routes = ['/', '/services', '/packages', '/gallery', '/offers', '/reviews', '/contact', '/login', '/register'];
  for (const r of routes) {
    await page.goto(BASE + r, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1200);
    const title = await page.title();
    notes.push(`${r} title=${title}`);
    const h1 = await page.locator('h1').first().textContent().catch(() => '');
    if (!h1 || !h1.trim()) bug('PUB-' + r.replace(/\W/g, '') + '-H1', 'LOW', 'public', r, ['open page'], 'visible h1', 'missing/empty h1');
  }
  await shot(page, SHOTS, 'public-01-home');
  await page.goto(BASE + '/', { waitUntil: 'networkidle' });
  await page.waitForTimeout(1000);
  await shot(page, SHOTS, 'public-home');

  // services search + filter
  await page.goto(BASE + '/services', { waitUntil: 'networkidle' });
  await page.waitForTimeout(1500);
  const searchVisible = await page.locator('[data-service-search]').isVisible().catch(() => false);
  if (!searchVisible) bug('PUB-S01', 'HIGH', 'public', '/services', ['open'], 'search input visible', 'NOT visible');
  const iconBox = await page.locator('.search-box .input-icon, .search-box svg').count();
  if (iconBox === 0) bug('PUB-S02', 'MEDIUM', 'public', '/services', ['inspect search box'], 'search icon rendered', 'no icon/svg found');
  const cardsBefore = await page.locator('[data-services] .site-card, [data-services] article').count();
  notes.push('service cards initial=' + cardsBefore);
  await page.fill('[data-service-search]', 'photo');
  await page.waitForTimeout(1200);
  const cardsAfter = await page.locator('[data-services] .site-card, [data-services] article').count();
  notes.push('service cards after "photo"=' + cardsAfter);
  if (cardsAfter < 1) bug('PUB-S03', 'HIGH', 'public', '/services', ['search "photo"'], '>=1 matching card', `${cardsAfter} cards`);
  // search icon must survive re-render
  const iconAfter = await page.locator('.search-box svg, .search-box .input-icon').count();
  if (iconAfter === 0) bug('PUB-S04', 'MEDIUM', 'public', '/services', ['search', 'list re-renders'], 'search icon still visible', 'icon gone after render');
  // no-results state
  await page.fill('[data-service-search]', 'zzzznonexistent');
  await page.waitForTimeout(1200);
  const emptyText = (await page.textContent('[data-services]') || '').replace(/\s+/g, ' ');
  if (!/no services/i.test(emptyText)) bug('PUB-S05', 'MEDIUM', 'public', '/services', ['search gibberish'], 'friendly empty state', JSON.stringify(emptyText.slice(0, 120)));
  await page.fill('[data-service-search]', '');
  await page.waitForTimeout(1200);
  await shot(page, SHOTS, 'public-02-services');

  // service detail via first card link
  const firstHref = await page.locator('[data-services] a[href*="/services/"]').first().getAttribute('href').catch(() => null);
  if (!firstHref) bug('PUB-S06', 'HIGH', 'public', '/services', ['find service link'], 'detail link present', 'none found');
  else {
    await page.goto(firstHref.startsWith('http') ? firstHref : BASE + firstHref, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1200);
    await shot(page, SHOTS, 'public-03-service-detail');
    const detH1 = (await page.locator('h1').first().textContent().catch(() => '') || '').trim();
    if (!detH1) bug('PUB-S07', 'MEDIUM', 'public', firstHref, ['open detail'], 'detail h1', 'missing');
    notes.push('service detail h1=' + detH1.slice(0, 60));
  }

  // invalid slug → graceful 404 handling (no raw exception)
  await page.goto(BASE + '/services/no-such-service-xyz', { waitUntil: 'networkidle' });
  await page.waitForTimeout(1200);
  const bodyText = (await page.textContent('body') || '').slice(0, 300);
  if (/Fatal error|PDOException|Call to a member|Undefined/i.test(bodyText)) bug('PUB-S08', 'HIGH', 'public', '/services/bad-slug', ['open invalid slug'], 'graceful not-found', bodyText.slice(0, 120));

  // contact/enquiry form: invalid then valid
  await page.goto(BASE + '/contact', { waitUntil: 'networkidle' });
  await page.waitForTimeout(1000);
  const form = page.locator('form').first();
  const hasForm = (await form.count()) > 0;
  if (!hasForm) bug('PUB-C01', 'HIGH', 'public', '/contact', ['open'], 'enquiry form present', 'no form found');
  else {
    // submit empty → expect client or server validation, no exception
    await form.evaluate((f) => f.querySelector('[type="submit"], button:not([type])')?.click()).catch(() => null);
    await page.waitForTimeout(1000);
    const afterBody = (await page.textContent('body') || '').slice(0, 200);
    if (/Fatal error|PDOException/i.test(afterBody)) bug('PUB-C02', 'HIGH', 'public', '/contact', ['submit empty'], 'validation (no fatal)', afterBody.slice(0, 100));
    await shot(page, SHOTS, 'public-04-contact');
  }

  // images: collect failed/under-sized imgs on home+services
  for (const r of ['/', '/services', '/gallery']) {
    await page.goto(BASE + r, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1000);
    const bad = await page.evaluate(() => {
      const out = [];
      document.querySelectorAll('img').forEach((img) => {
        if (!img.complete || img.naturalWidth === 0) out.push(img.getAttribute('src') || '(no src)');
        else if (!img.getAttribute('alt')) out.push('NO-ALT: ' + (img.getAttribute('src') || ''));
      });
      return out.slice(0, 20);
    });
    if (bad.length) notes.push(`${r} img issues: ${bad.slice(0, 5).join(' | ')}`);
  }

  const report = { suite: 'public', base: BASE, bugs, notes, qa: summarize(col) };
  fs.writeFileSync(path.join(OUT, 'public.json'), JSON.stringify(report, null, 2));
  await browser.close();
  console.log(`PUBLIC: bugs=${bugs.length} notes=${notes.length} consoleErr=${report.qa.consoleErrors.length} pageErr=${report.qa.pageErrors.length} badResp=${report.qa.badResponses.length} failed=${report.qa.failedRequests.length}`);
  if (bugs.length) console.log(JSON.stringify(bugs.map((b) => b.id + ':' + b.severity + ':' + b.actual.slice(0, 100)), null, 1));
}
main().catch((e) => { console.error('PUBLIC FATAL', e); process.exit(1); });
