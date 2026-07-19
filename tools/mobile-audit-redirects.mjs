const urls = [
  'https://travel63test.ru/frontend/window/login.html',
  'https://travel63test.ru/frontend/window/registration.html',
  'https://travel63test.ru/frontend/window/bank_rekvesit.php',
  'https://travel63test.ru/frontend/window/offices/office.php?slug=samara-funsun',
  'https://travel63test.ru/frontend/window/404.php',
];

for (const url of urls) {
  const res = await fetch(url, { redirect: 'manual', headers: { 'User-Agent': 'Mobile' } });
  const loc = res.headers.get('location');
  const text = res.status < 400 ? await res.text() : '';
  const head = text.slice(0, 3000);
  const styleInHead = (head.match(/<style[\s>]/gi) || []).length;
  const mobile = text.match(/mobile-site\.css\?v=(\d+)/)?.[0] || 'MISSING';
  console.log('\n---', url);
  console.log('status:', res.status, loc ? '→ ' + loc : '');
  console.log('mobile:', mobile, '| head<style>:', styleInHead);
  if (text.length < 500) console.log('body:', text.replace(/\s+/g, ' ').slice(0, 200));
}
