import puppeteer from 'puppeteer';

const BASE = process.argv[2] || 'https://travel63test.ru';
const urls = [
  '/',
  '/frontend/window/promotions.php',
  '/frontend/window/offices.php',
  '/frontend/window/countries-list.php',
  '/frontend/window/about.php',
  '/frontend/window/services.php',
  '/frontend/window/profile.php',
  '/frontend/window/login.php',
  '/frontend/window/turkey-vip-hotels.php',
];

function auditPage() {
  const issues = [];
  const vw = window.innerWidth;
  const vh = window.innerHeight;
  const bad = [];

  document.querySelectorAll('body *').forEach((el) => {
    const st = getComputedStyle(el);
    if (st.display === 'none' || st.visibility === 'hidden' || st.opacity === '0') return;
    const r = el.getBoundingClientRect();
    if (r.width < 4 || r.height < 4) return;
    if (r.right > vw + 3) {
      bad.push({
        type: 'overflow-x',
        tag: el.tagName.toLowerCase(),
        id: el.id || '',
        cls: (el.className && typeof el.className === 'string') ? el.className.split(/\s+/).slice(0, 3).join('.') : '',
        right: Math.round(r.right),
        w: Math.round(r.width),
      });
    }
    if (st.position === 'fixed' && r.bottom > vh + 2 && r.top < vh) {
      bad.push({
        type: 'fixed-offscreen',
        tag: el.tagName.toLowerCase(),
        id: el.id || '',
        cls: (el.className && typeof el.className === 'string') ? el.className.split(/\s+/).slice(0, 2).join('.') : '',
        bottom: Math.round(r.bottom),
        vh,
      });
    }
  });

  const uniq = {};
  bad.forEach((i) => {
    const k = `${i.type}|${i.tag}|${i.id}|${i.cls}`;
    if (!uniq[k]) uniq[k] = i;
  });

  return {
    title: document.title,
    scrollW: document.documentElement.scrollWidth,
    clientW: document.documentElement.clientWidth,
    overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1,
    css: {
      mobileSite: [...document.querySelectorAll('link[href*="mobile-site.css"]')].map((l) => l.href),
      ds: [...document.querySelectorAll('link[href*="design-system.css"]')].map((l) => l.href),
    },
    issues: Object.values(uniq).slice(0, 15),
  };
}

const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
const page = await browser.newPage();
await page.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
await page.setUserAgent(
  'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36 YaBrowser/24.1.0.0'
);

for (const path of urls) {
  const url = BASE.replace(/\/$/, '') + path;
  try {
    await page.goto(url, { waitUntil: 'networkidle2', timeout: 60000 });
    await page.waitForTimeout(2500);
    const report = await page.evaluate(auditPage);
    console.log('\n=== ' + url);
    console.log(JSON.stringify(report, null, 2));
  } catch (e) {
    console.log('\n=== ' + url + ' ERROR: ' + e.message);
  }
}

await browser.close();
