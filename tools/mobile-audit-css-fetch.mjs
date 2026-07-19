const BASE = 'https://travel63test.ru';
const PAGES = [
  '/',
  '/frontend/window/promotions.php',
  '/frontend/window/offices.php',
  '/frontend/window/countries-list.php',
  '/frontend/window/about.php',
  '/frontend/window/services.php',
  '/frontend/window/login.php',
  '/frontend/window/registration-desktop.php',
  '/frontend/window/banks_rekvesit.php',
  '/frontend/window/terms.php',
  '/frontend/window/privacy.php',
  '/frontend/window/countries/turkey.php',
  '/frontend/window/offices/samara.php',
  '/frontend/window/forgot-password.php',
  '/frontend/window/turkey-vip-hotels.php',
];

function headInlineStyle(html) {
  const m = html.match(/<head[^>]*>([\s\S]*?)<\/head>/i);
  if (!m) return false;
  return /<style[\s>]/i.test(m[1]);
}

async function check(path) {
  const url = BASE + path;
  const res = await fetch(url, {
    redirect: 'follow',
    headers: { 'User-Agent': 'Mozilla/5.0 (Linux; Android 13) Mobile Safari/537.36' },
  });
  const html = await res.text();
  const mobile = html.match(/mobile-site\.css\?v=(\d+)/)?.[0] || 'MISSING';
  const pagesCss = [...html.matchAll(/pages\/([a-z0-9-]+)\.css\?v=\d+/gi)].map((x) => x[1]);
  return {
    path,
    status: res.status,
    final: res.url.replace(BASE, ''),
    mobile,
    pages: [...new Set(pagesCss)],
    inlineHead: headInlineStyle(html),
  };
}

const results = await Promise.all(PAGES.map(check));
console.log('\n=== LIVE CSS CHECK (head only) ===\n');
for (const r of results) {
  const ok = r.mobile.includes('v=10') && r.status === 200;
  console.log(`${ok ? '✅' : '❌'} ${r.path} | ${r.mobile} | pages:[${r.pages}] | inlineHead:${r.inlineHead ? 'YES' : 'no'} | ${r.status}`);
}
