#!/usr/bin/env node
/**
 * Extract inline <style> blocks to frontend/css/pages/*.css
 * and load them BEFORE design_system_head.php so mobile-site.css stays last.
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.join(__dirname, '..');
const frontend = path.join(root, 'frontend');
const pagesCssDir = path.join(frontend, 'css', 'pages');

const FILES = [
  'window/countries-list.php',
  'window/countries/country.php',
  'window/about.php',
  'window/hotels/hotel-detail.php',
  'window/offices/samara.php',
  'window/offices/moscow.php',
  'window/banks_rekvesit.php',
  'window/terms.php',
  'window/privacy.php',
  'window/profile.php',
  'window/dashboard.php',
  'window/personal-info.php',
  'window/passport-data.php',
  'window/edit-user-data.php',
  'window/forgot-password.php',
  'window/reset-password.php',
  'window/login.php',
  'window/login-desktop.php',
  'window/registration-desktop.php',
  'window/for-operators.php',
  'window/turkey-vip-hotels.php',
  'window/video-tutorials.php',
  'guest-template.php',
];

const DS_INCLUDE = /<\?php\s+include\s+__DIR__\s*\/\s*['"](?:\.\.\/){2,3}backend\/components\/design_system_head\.php['"];\s*\?>/;

function cssBasename(relPath) {
  const base = path.basename(relPath, '.php');
  if (relPath.includes('countries/country.php')) return 'country';
  if (relPath.includes('hotels/hotel-detail.php')) return 'hotel-detail';
  if (relPath.includes('offices/samara.php')) return 'offices-samara';
  if (relPath.includes('offices/moscow.php')) return 'offices-moscow';
  if (relPath.includes('banks_rekvesit.php')) return 'banks-rekvesit';
  if (relPath.includes('guest-template.php')) return 'guest-template';
  return base.replace(/_/g, '-');
}

function extractStyles(content) {
  const re = /<style[^>]*>([\s\S]*?)<\/style>/gi;
  const blocks = [];
  let m;
  while ((m = re.exec(content)) !== null) {
    blocks.push({ full: m[0], css: m[1].trim() });
  }
  return blocks;
}

function removeStyles(content) {
  return content.replace(/<style[^>]*>[\s\S]*?<\/style>\s*/gi, '');
}

function moveExistingPageCssBeforeDs(content, cssHref) {
  const linkRe = new RegExp(
    `\\s*<link rel="stylesheet" href="${cssHref.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}"[^>]*>\\s*`,
    'g'
  );
  if (!linkRe.test(content)) return content;
  content = content.replace(linkRe, '');
  return insertLinkBeforeDs(content, cssHref);
}

function insertLinkBeforeDs(content, cssHref) {
  const link = `    <link rel="stylesheet" href="${cssHref}">\n`;
  if (content.includes(cssHref)) {
    // already inserted before DS
    return content;
  }
  if (!DS_INCLUDE.test(content)) {
    console.warn('  WARN: no design_system_head in', cssHref);
    return content + link;
  }
  return content.replace(DS_INCLUDE, (m) => link + '    ' + m);
}

if (!fs.existsSync(pagesCssDir)) fs.mkdirSync(pagesCssDir, { recursive: true });

let ok = 0;
for (const rel of FILES) {
  const filePath = path.join(frontend, rel);
  if (!fs.existsSync(filePath)) {
    console.log('SKIP missing:', rel);
    continue;
  }
  let content = fs.readFileSync(filePath, 'utf8');
  const blocks = extractStyles(content);
  if (blocks.length === 0) {
    console.log('SKIP no style:', rel);
    continue;
  }
  const cssName = cssBasename(rel);
  const cssHref = `/frontend/css/pages/${cssName}.css?v=1`;
  const cssPath = path.join(pagesCssDir, `${cssName}.css`);
  const mergedCss = blocks.map((b) => b.css).join('\n\n');
  fs.writeFileSync(cssPath, mergedCss + '\n', 'utf8');
  content = removeStyles(content);
  content = insertLinkBeforeDs(content, cssHref);
  fs.writeFileSync(filePath, content, 'utf8');
  console.log('OK', rel, '->', cssName + '.css', `(${blocks.length} block(s), ${mergedCss.split('\n').length} lines)`);
  ok++;
}

// Fix promotions + tour-detail: move page CSS before design_system_head
for (const rel of ['window/promotions.php', 'window/tour-detail.php']) {
  const filePath = path.join(frontend, rel);
  if (!fs.existsSync(filePath)) continue;
  let content = fs.readFileSync(filePath, 'utf8');
  const cssFile = rel.includes('promotions') ? 'promotions.css?v=1' : 'tour-detail.css?v=1';
  const href = `/frontend/css/pages/${cssFile.replace('.css?v=1', '.css')}?v=1`;
  content = moveExistingPageCssBeforeDs(content, href);
  fs.writeFileSync(filePath, content, 'utf8');
  console.log('ORDER', rel);
}

console.log(`\nDone: ${ok} files extracted.`);
