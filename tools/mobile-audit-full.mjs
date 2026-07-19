/**
 * Full mobile audit — pages + popups on travel63test.ru
 * Usage: node tools/mobile-audit-full.mjs [baseUrl]
 */
import puppeteer from 'puppeteer';

const BASE = (process.argv[2] || 'https://travel63test.ru').replace(/\/$/, '');
const UA =
  'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36 YaBrowser/24.1.0.0';

const PAGES = [
  '/',
  '/frontend/window/promotions.php',
  '/frontend/window/offices.php',
  '/frontend/window/countries-list.php',
  '/frontend/window/about.php',
  '/frontend/window/services.php',
  '/frontend/window/login.php',
  '/frontend/window/turkey-vip-hotels.php',
  '/frontend/window/profile.php',
  '/frontend/window/countries/turkey.php',
  '/frontend/window/offices/samara.php',
];

function auditDom() {
  const vw = window.innerWidth;
  const issues = [];
  const skipHiddenPanel = (el) =>
    el.closest('.site-header-mobile-panel') &&
    !el.closest('.site-header-mobile-panel.is-open');

  document.querySelectorAll('body *').forEach((el) => {
    const st = getComputedStyle(el);
    if (st.display === 'none' || st.visibility === 'hidden' || st.opacity === '0') return;
    if (skipHiddenPanel(el)) return;
    const r = el.getBoundingClientRect();
    if (r.width < 4 || r.height < 4) return;
    if (r.right > vw + 3) {
      issues.push({
        type: 'overflow-x',
        tag: el.tagName.toLowerCase(),
        id: el.id || '',
        cls: String(el.className || '').slice(0, 45),
        right: Math.round(r.right),
        vw,
      });
    }
  });

  const uniq = {};
  issues.forEach((i) => {
    const k = `${i.type}|${i.tag}|${i.id}|${i.cls}`;
    if (!uniq[k]) uniq[k] = i;
  });

  const cssV = [...document.querySelectorAll('link[href*="mobile-site.css"]')]
    .map((l) => l.href)
    .pop();

  return {
    title: document.title,
    path: location.pathname,
    vw,
    scrollW: document.documentElement.scrollWidth,
    overflow: document.documentElement.scrollWidth > vw + 1,
    mobileSite: cssV || 'MISSING',
    issues: Object.values(uniq).slice(0, 12),
  };
}

function auditPopup(el) {
  if (!el) return null;
  const st = getComputedStyle(el);
  if (st.display === 'none' || el.hidden) return null;
  const r = el.getBoundingClientRect();
  const vw = window.innerWidth;
  const vh = window.innerHeight;
  const problems = [];
  if (r.width > vw + 4) problems.push('too-wide');
  if (r.right > vw + 4) problems.push('overflow-right');
  if (r.bottom > vh + 8) problems.push('below-vp');
  if (r.top < -4) problems.push('above-vp');
  if (r.width < vw * 0.5 && r.height > 100) problems.push('narrow-modal');
  return {
    sel: el.id ? `#${el.id}` : el.className?.split?.(' ')?.[0],
    rect: {
      t: Math.round(r.top),
      b: Math.round(r.bottom),
      w: Math.round(r.width),
      h: Math.round(r.height),
    },
    problems,
  };
}

const browser = await puppeteer.launch({
  headless: true,
  args: ['--no-sandbox', '--disable-setuid-sandbox'],
});
const page = await browser.newPage();
await page.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
await page.setUserAgent(UA);

const report = { pages: [], popups: [], errors: [] };

for (const path of PAGES) {
  const url = BASE + path;
  try {
    await page.goto(url, { waitUntil: 'networkidle2', timeout: 60000 });
    await page.waitForTimeout(2000);
    const data = await page.evaluate(auditDom);
    report.pages.push({ url, ...data });

    // Home wizard popups
    if (path === '/' || path === '/frontend/index.php') {
      for (let step = 0; step < 3; step++) {
        await page.evaluate(() => {
          document.querySelector('.th-wizard__next')?.click();
        });
        await page.waitForTimeout(400);
      }
      const popupTests = [
        ['dates', '#tv-sc-dates-btn', '#tv-sc-date-popup'],
        ['tourists', '#tv-tourists-summary', '#tv-tourists-block'],
        ['nights', '#tv-nights-summary', '#tv-nights-popup'],
        ['filters', '#tv-filters-modal-open', '#tv-filters-modal'],
      ];
      for (const [name, trigger, target] of popupTests) {
        try {
          await page.evaluate((sel) => {
            document.querySelector('#tv-sc-overlay')?.click();
            document.getElementById('tv-filters-modal-close')?.click();
            document.querySelector('#tv-tourists-close-btn')?.click();
            const t = document.querySelector(sel);
            if (t) t.click();
          }, trigger);
          await page.waitForTimeout(600);
          const pop = await page.evaluate(
            (sel) => {
              const el = document.querySelector(sel);
              if (!el) return null;
              const st = getComputedStyle(el);
              if (st.display === 'none' || el.hidden) return { missing: true };
              const r = el.getBoundingClientRect();
              const vw = innerWidth;
              const vh = innerHeight;
              const problems = [];
              if (r.width > vw + 4) problems.push('too-wide');
              if (r.right > vw + 4) problems.push('overflow-right');
              if (r.bottom > vh + 8) problems.push('below-vp');
              return {
                rect: { t: Math.round(r.top), b: Math.round(r.bottom), w: Math.round(r.width), h: Math.round(r.height) },
                problems,
                pageOverflow: document.documentElement.scrollWidth > vw + 1,
              };
            },
            target
          );
          report.popups.push({ page: path, name, trigger, target, ...pop });
        } catch (e) {
          report.popups.push({ page: path, name, error: e.message });
        }
      }
      // burger menu
      await page.evaluate(() => {
        document.getElementById('site-header-burger')?.click();
      });
      await page.waitForTimeout(400);
      const menu = await page.evaluate(auditDom);
      report.popups.push({
        page: path,
        name: 'burger-menu',
        overflow: menu.overflow,
        issues: menu.issues,
      });
    }
  } catch (e) {
    report.errors.push({ url, error: e.message });
  }
}

await browser.close();

const badPages = report.pages.filter((p) => p.overflow || p.issues.length);
const badPopups = report.popups.filter((p) => p.problems?.length || p.overflow || p.issues?.length || p.pageOverflow);

console.log('\n=== MOBILE AUDIT SUMMARY ===');
console.log('Pages checked:', report.pages.length);
console.log('Pages with issues:', badPages.length);
console.log('Popups with issues:', badPopups.length);
console.log('Errors:', report.errors.length);

if (badPages.length) {
  console.log('\n--- PAGE ISSUES ---');
  badPages.forEach((p) => {
    console.log(p.url, 'overflow:', p.overflow, 'issues:', JSON.stringify(p.issues));
  });
}

if (badPopups.length) {
  console.log('\n--- POPUP ISSUES ---');
  badPopups.forEach((p) => console.log(JSON.stringify(p)));
}

if (report.errors.length) {
  console.log('\n--- ERRORS ---');
  report.errors.forEach((e) => console.log(e.url, e.error));
}

// pages missing mobile-site
const noMobile = report.pages.filter((p) => !String(p.mobileSite).includes('mobile-site'));
if (noMobile.length) {
  console.log('\n--- MISSING mobile-site.css ---');
  noMobile.forEach((p) => console.log(p.url, p.mobileSite));
}

console.log('\n--- ALL PAGES ---');
report.pages.forEach((p) =>
  console.log(`${p.overflow ? '❌' : '✅'} ${p.path} scrollW=${p.scrollW} issues=${p.issues.length}`)
);
