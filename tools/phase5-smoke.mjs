/**
 * Phase 5 smoke — local or remote base URL.
 * Usage: node tools/phase5-smoke.mjs [baseUrl]
 */
const BASE = (process.argv[2] || 'http://127.0.0.1:8765').replace(/\/$/, '');

const checks = [];

function pass(name, detail) {
  checks.push({ ok: true, name, detail });
}
function fail(name, detail) {
  checks.push({ ok: false, name, detail });
}

async function fetchText(path) {
  const res = await fetch(BASE + path, { redirect: 'follow' });
  const text = await res.text();
  return { status: res.status, text, url: res.url };
}

async function main() {
  console.log('Smoke base:', BASE);

  // Static asset presence (filesystem when local server)
  const assetPaths = [
    '/frontend/css/th-unified-ui.css',
    '/frontend/js/th-promo-lead.js',
    '/frontend/css/pages/promotions.css',
  ];
  for (const p of assetPaths) {
    try {
      const r = await fetch(BASE + p);
      if (r.ok) pass('asset ' + p, 'HTTP ' + r.status);
      else fail('asset ' + p, 'HTTP ' + r.status);
    } catch (e) {
      fail('asset ' + p, String(e.message || e));
    }
  }

  try {
    const promo = await fetchText('/frontend/window/promotions.php');
    if (promo.status === 200) pass('promotions.php', 'HTTP 200');
    else fail('promotions.php', 'HTTP ' + promo.status);

    const html = promo.text;
    const mustHave = [
      ['th-promo-lead.js', 'THPromoLead script'],
      ['promo-hero-lead-btn', 'hero micro-CTA'],
      ['promo-results-sticky-lead', 'results sticky lead'],
      ['th-site-feedback-overlay', 'feedback modal'],
      ['promo-sticky-cta-btn', 'promo sticky CTA'],
      ['th-unified-ui.css', 'unified UI CSS'],
      ['mobile-site.css?v=12', 'mobile-site v12'],
    ];
    for (const [needle, label] of mustHave) {
      if (html.includes(needle)) pass('promo HTML: ' + label, needle);
      else fail('promo HTML: ' + label, 'missing ' + needle);
    }
    try {
      const cardJs = await fetchText('/frontend/js/th-tour-card.js');
      if (cardJs.text.includes('data-th-promo-card-lead')) pass('tour-card promo lead btn', 'in th-tour-card.js');
      else fail('tour-card promo lead btn', 'missing in th-tour-card.js');
    } catch (e) {
      fail('tour-card.js fetch', String(e.message || e));
    }
    if (html.includes('promo-bottom-lead')) fail('promo no bottom form', 'found promo-bottom-lead');
    else pass('promo no bottom form', 'removed');
  } catch (e) {
    fail('promotions.php fetch', String(e.message || e));
  }

  try {
    const home = await fetchText('/');
    if (home.status === 200) pass('home', 'HTTP 200');
    else fail('home', 'HTTP ' + home.status);
    if (home.text.includes('th-unified-ui.css')) pass('home unified CSS', 'linked');
    else fail('home unified CSS', 'not linked');
    if (home.text.includes('th-site-lead-bar')) pass('home lead bar', 'present');
    else fail('home lead bar', 'missing');
    if (home.text.includes('open_booking')) fail('home no open_booking', 'found in HTML');
    else pass('home no open_booking', 'clean');
  } catch (e) {
    fail('home fetch', String(e.message || e));
  }

  try {
    const turkey = await fetchText('/frontend/window/countries/turkey.php');
    if (turkey.status === 200) pass('country turkey.php', 'HTTP 200');
    else fail('country turkey.php', 'HTTP ' + turkey.status);
    if (turkey.text.includes('loadCountryTvRegions')) pass('country regions JS', 'loadCountryTvRegions present');
    else fail('country regions JS', 'loadCountryTvRegions missing');
    if (turkey.text.includes('country-tv-departure')) pass('country departure field', 'present');
    else fail('country departure field', 'missing');
  } catch (e) {
    fail('country page fetch', String(e.message || e));
  }

  const ok = checks.filter((c) => c.ok).length;
  const bad = checks.filter((c) => !c.ok);
  console.log('\n--- Results ---');
  checks.forEach((c) => {
    console.log((c.ok ? 'OK  ' : 'FAIL') + ' ' + c.name + (c.detail ? ' — ' + c.detail : ''));
  });
  console.log('\n' + ok + '/' + checks.length + ' passed');
  if (bad.length) process.exit(1);
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
