#!/usr/bin/env node
/**
 * Move pages/*.css links to BEFORE design_system_head.php
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const frontend = path.join(__dirname, '..', 'frontend');

const DS_RE =
  /<\?php\s+include\s+__DIR__\s*\.\s*['"]\/(?:\.\.\/)*backend\/components\/design_system_head\.php['"];\s*\?>/;

const PAGE_CSS_RE =
  /\s*<link rel="stylesheet" href="(\/frontend\/css\/pages\/[^"]+\.css\?v=\d+)"[^>]*>\s*/g;

function fixFile(filePath) {
  let content = fs.readFileSync(filePath, 'utf8');
  const hrefs = [];
  let m;
  const re = new RegExp(PAGE_CSS_RE.source, 'g');
  while ((m = re.exec(content)) !== null) {
    hrefs.push(m[1]);
  }
  if (hrefs.length === 0) return false;

  content = content.replace(PAGE_CSS_RE, '');
  // strip orphan links after </html>
  content = content.replace(/<\/html>\s*<link[^>]+>\s*$/i, '</html>\n');

  const unique = [...new Set(hrefs)];
  const links = unique.map((h) => `    <link rel="stylesheet" href="${h}">`).join('\n') + '\n';

  if (!DS_RE.test(content)) {
    console.warn('NO DS:', filePath);
    return false;
  }

  if (content.includes(unique[0]) && content.indexOf(unique[0]) < content.search(DS_RE)) {
    return false; // already fixed
  }

  content = content.replace(DS_RE, (ds) => links + '    ' + ds);
  fs.writeFileSync(filePath, content, 'utf8');
  console.log('FIX', path.relative(frontend, filePath), unique.join(', '));
  return true;
}

const phpFiles = [];
function walk(dir) {
  for (const ent of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, ent.name);
    if (ent.isDirectory()) walk(p);
    else if (ent.name.endsWith('.php')) phpFiles.push(p);
  }
}
walk(frontend);

let n = 0;
for (const f of phpFiles) {
  if (fixFile(f)) n++;
}
console.log(`Fixed ${n} files.`);
