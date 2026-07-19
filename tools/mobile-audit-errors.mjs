const urls = [
  'https://travel63test.ru/frontend/window/offices/office.php?slug=samara-funsun',
  'https://travel63test.ru/frontend/window/404.php',
  'https://travel63test.ru/frontend/window/500.php',
];

for (const url of urls) {
  const res = await fetch(url, { headers: { 'User-Agent': 'Mobile' } });
  const text = await res.text();
  console.log('\n===', url, 'status', res.status, '===');
  console.log(text.slice(0, 1500));
}
