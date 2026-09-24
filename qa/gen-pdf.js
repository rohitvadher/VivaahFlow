'use strict';
// Minimal MD→print-HTML + Chromium PDF for the QA report. No extra deps.
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

function esc(s) {
  return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}
function inline(s) {
  let h = esc(s);
  h = h.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
  h = h.replace(/`(.+?)`/g, '<code>$1</code>');
  return h;
}
function mdToHtml(md) {
  const lines = md.split('\n');
  let html = '';
  let i = 0;
  while (i < lines.length) {
    const line = lines[i];
    if (/^#{1,3} /.test(line)) {
      const level = line.match(/^#+/)[0].length;
      html += `<h${level}>${inline(line.replace(/^#+ /, ''))}</h${level}>`;
      i++;
    } else if (/^\|/.test(line) && i + 1 < lines.length && /^\|[\s:|-]+\|/.test(lines[i + 1])) {
      const cells = (l) => l.trim().replace(/^\||\|$/g, '').split('|').map((c) => inline(c.trim()));
      const head = cells(line);
      i += 2;
      html += '<table><thead><tr>' + head.map((c) => `<th>${c}</th>`).join('') + '</tr></thead><tbody>';
      while (i < lines.length && /^\|/.test(lines[i])) {
        html += '<tr>' + cells(lines[i]).map((c) => `<td>${c}</td>`).join('') + '</tr>';
        i++;
      }
      html += '</tbody></table>';
    } else if (/^---+$/.test(line.trim())) {
      html += '<hr>';
      i++;
    } else if (/^(\-|\*) /.test(line)) {
      html += '<ul>';
      while (i < lines.length && /^(\-|\*) /.test(lines[i])) {
        html += `<li>${inline(lines[i].replace(/^(\-|\*) /, ''))}</li>`;
        i++;
      }
      html += '</ul>';
    } else if (/^\d+\. /.test(line)) {
      html += '<ol>';
      while (i < lines.length && /^\d+\. /.test(lines[i])) {
        html += `<li>${inline(lines[i].replace(/^\d+\. /, ''))}</li>`;
        i++;
      }
      html += '</ol>';
    } else if (line.trim() === '') {
      i++;
    } else {
      html += `<p>${inline(line)}</p>`;
      i++;
    }
  }
  return html;
}

(async () => {
  const md = fs.readFileSync(path.join(__dirname, 'reports', 'VivaahFlow-Deep-Browser-QA-Report.md'), 'utf8');
  const body = mdToHtml(md);
  const html = `<!DOCTYPE html><html><head><meta charset="utf-8"><style>
    body{font-family:'Segoe UI',Arial,sans-serif;color:#1a1a1a;margin:48px;font-size:12px;line-height:1.55}
    h1{font-size:24px;border-bottom:3px solid #8b0a72;padding-bottom:8px}
    h2{font-size:17px;color:#8b0a72;margin-top:28px;border-bottom:1px solid #e5c2dd;padding-bottom:4px}
    h3{font-size:13px}
    table{border-collapse:collapse;width:100%;margin:10px 0;font-size:11px}
    th,td{border:1px solid #ccc;padding:5px 8px;text-align:left;vertical-align:top}
    th{background:#fdf2fb}
    code{background:#f4f4f4;padding:1px 5px;border-radius:3px;font-size:11px}
    li{margin-bottom:3px}
  </style></head><body>${body}</body></html>`;
  const file = path.join(__dirname, 'reports', 'report.html');
  fs.writeFileSync(file, html);
  const browser = await chromium.launch({ headless: true });
  const page = await (await browser.newContext()).newPage();
  await page.goto('file:///' + file.replace(/\\/g, '/'), { waitUntil: 'load' });
  await page.pdf({ path: path.join(__dirname, 'reports', 'VivaahFlow-Deep-Browser-QA-Report.pdf'), format: 'A4', printBackground: true, margin: { top: '40px', bottom: '40px', left: '40px', right: '40px' } });
  await browser.close();
  const st = fs.statSync(path.join(__dirname, 'reports', 'VivaahFlow-Deep-Browser-QA-Report.pdf'));
  console.log('PDF OK bytes=' + st.size);
})().catch((e) => { console.error('PDF FAIL', e); process.exit(1); });
