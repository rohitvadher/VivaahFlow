'use strict';
const { chromium } = require('playwright');

const VIEWPORTS = {
  desktop: { width: 1440, height: 900 },
  wide: { width: 1920, height: 1080 },
};

async function launch(opts = {}) {
  const browser = await chromium.launch({ headless: true, ...opts });
  return browser;
}

// Attach console + network collectors to a page. Returns collector object.
function instrument(page, label) {
  const col = {
    label,
    consoleErrors: [],
    pageErrors: [],
    failedRequests: [],
    badResponses: [],
  };
  page.on('console', (msg) => {
    const type = msg.type();
    if (type === 'error') {
      col.consoleErrors.push(msg.text().slice(0, 500));
    }
  });
  page.on('pageerror', (err) => {
    col.pageErrors.push(String(err && err.message ? err.message : err).slice(0, 500));
  });
  page.on('requestfailed', (req) => {
    const url = req.url();
    if (url.includes('fonts.g') || url.includes('cdn.tailwindcss') || url.includes('cdnjs') || url.includes('unpkg') || url.includes('jsdelivr')) {
      col.failedRequests.push('CDN-FAIL: ' + url.slice(0, 160));
    } else {
      col.failedRequests.push(req.failure()?.errorText + ' :: ' + url.slice(0, 160));
    }
  });
  page.on('response', (res) => {
    const s = res.status();
    const url = res.url();
    if (url.includes('fonts.g') || url.includes('cdnjs') || url.includes('unpkg') || url.includes('jsdelivr') || url.includes('cdn.tailwindcss')) return;
    if (s >= 400) {
      col.badResponses.push(s + ' :: ' + url.slice(0, 160));
    }
  });
  return col;
}

function summarize(col) {
  // Filter known-harmless third-party noise
  const noise = [/favicon/i, /net::ERR_INTERNET_DISCONNECTED/];
  const isNoise = (s) => noise.some((re) => re.test(s));
  return {
    label: col.label,
    consoleErrors: col.consoleErrors.filter((s) => !isNoise(s)),
    pageErrors: col.pageErrors.filter((s) => !isNoise(s)),
    failedRequests: col.failedRequests.filter((s) => !isNoise(s)),
    badResponses: col.badResponses.filter((s) => !isNoise(s)),
  };
}

async function shot(page, dir, name) {
  try {
    await page.screenshot({ path: `${dir}/${name}.png` });
    return `${name}.png`;
  } catch (e) {
    return null;
  }
}

module.exports = { launch, instrument, summarize, shot, VIEWPORTS };
