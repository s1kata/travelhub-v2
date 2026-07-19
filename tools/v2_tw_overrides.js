/**
 * Генератор v2-tailwind-overrides.css: перекрашивает использованные в PHP-разметке
 * Tailwind-утилиты sky/indigo/orange в палитру v2 (teal/navy/coral) без правки разметки.
 * Запуск: node tools/v2_tw_overrides.js <projectRoot>
 */
const fs = require('fs');
const path = require('path');

const root = process.argv[2];
if (!root) { console.error('usage: node v2_tw_overrides.js <root>'); process.exit(1); }

// v2-шкалы (замена стандартных sky/indigo/orange)
const scales = {
  sky: { // → teal
    50: '#F0F7F6', 100: '#DCEEEC', 200: '#C2E2DF', 300: '#9CCFCB', 400: '#79BCB7',
    500: '#5DA9A4', 600: '#457F7B', 700: '#366360', 800: '#2A4D4A', 900: '#1F3937',
  },
  indigo: { // → deep navy
    50: '#EEEEF5', 100: '#DCDCEB', 200: '#BBBBD6', 300: '#9595BC', 400: '#62629A',
    500: '#3D3D74', 600: '#1A1A40', 700: '#141433', 800: '#10102E', 900: '#0B0B22',
  },
  orange: { // → coral
    50: '#FFF5F5', 100: '#FFE3E3', 200: '#FFCFCF', 300: '#FFB3B3', 400: '#FF8A80',
    500: '#FF6B6B', 600: '#F65252', 700: '#E03E3E', 800: '#B93232', 900: '#8F2727',
  },
};

function hexToRgb(hex) {
  const h = hex.replace('#', '');
  return [parseInt(h.slice(0, 2), 16), parseInt(h.slice(2, 4), 16), parseInt(h.slice(4, 6), 16)];
}
function colorCss(hex, alphaPct) {
  if (alphaPct == null) return hex;
  const [r, g, b] = hexToRgb(hex);
  return `rgba(${r}, ${g}, ${b}, ${(alphaPct / 100).toFixed(2).replace(/0+$/, '').replace(/\.$/, '')})`;
}
function transparentRgb(hex) {
  const [r, g, b] = hexToRgb(hex);
  return `rgb(${r} ${g} ${b} / 0)`;
}

// Сбор использованных классов
const rx = /(?:(hover|focus|group-hover|focus-within):)?(bg|text|border|from|to|via|ring|placeholder|divide|shadow)-(sky|indigo|orange)-(50|100|200|300|400|500|600|700|800|900)(?:\/(\d{1,3}))?/g;
const used = new Set();
function walk(dir) {
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, e.name);
    if (e.isDirectory()) walk(p);
    else if (/\.php$/.test(e.name)) {
      const text = fs.readFileSync(p, 'utf8');
      let m;
      while ((m = rx.exec(text)) !== null) used.add(m[0]);
    }
  }
}
walk(path.join(root, 'frontend'));
walk(path.join(root, 'backend', 'components'));

// Генерация CSS
function esc(cls) {
  return cls.replace(/[:\/]/g, (c) => '\\' + c);
}
const lines = [
  '/* АВТОГЕНЕРАЦИЯ: tools/v2_tw_overrides.js — не редактировать вручную.',
  '   Перекраска Tailwind-утилит sky→teal, indigo→navy, orange→coral (палитра v2). */',
];

const sorted = Array.from(used).sort();
for (const cls of sorted) {
  rx.lastIndex = 0;
  const m = rx.exec(cls);
  if (!m) continue;
  const [, variant, prop, colorName, shadeStr, alphaStr] = m;
  const shade = Number(shadeStr);
  const alpha = alphaStr ? Number(alphaStr) : null;
  const hex = scales[colorName][shade];
  if (!hex) continue;
  const val = colorCss(hex, alpha);

  let sel = '.' + esc(cls);
  if (variant === 'hover') sel += ':hover';
  else if (variant === 'focus') sel += ':focus';
  else if (variant === 'focus-within') sel += ':focus-within';
  else if (variant === 'group-hover') sel = '.group:hover ' + sel;

  let body = '';
  switch (prop) {
    case 'bg': body = `background-color: ${val} !important;`; break;
    case 'text': body = `color: ${val} !important;`; break;
    case 'border': body = `border-color: ${val} !important;`; break;
    case 'placeholder': sel += '::placeholder'; body = `color: ${val} !important;`; break;
    case 'ring': body = `--tw-ring-color: ${val} !important;`; break;
    case 'divide': sel += ' > :not([hidden]) ~ :not([hidden])'; body = `border-color: ${val} !important;`; break;
    case 'shadow': body = `--tw-shadow-color: ${val} !important; --tw-shadow: var(--tw-shadow-colored) !important;`; break;
    case 'from':
      body = `--tw-gradient-from: ${val} !important; --tw-gradient-to: ${transparentRgb(hex)} !important; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to) !important;`;
      break;
    case 'via':
      body = `--tw-gradient-to: ${transparentRgb(hex)} !important; --tw-gradient-stops: var(--tw-gradient-from), ${val}, var(--tw-gradient-to) !important;`;
      break;
    case 'to':
      body = `--tw-gradient-to: ${val} !important;`;
      break;
    default: continue;
  }
  lines.push(`${sel} { ${body} }`);
}

const out = path.join(root, 'frontend', 'css', 'v2-tailwind-overrides.css');
fs.writeFileSync(out, lines.join('\n') + '\n', 'utf8');
console.log('classes:', sorted.length, '->', out);
