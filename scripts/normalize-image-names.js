const fs = require('fs');
const path = require('path');

function escapeRegExp(string) {
  return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function safeName(name) {
  const ext = path.extname(name);
  const base = name.slice(0, -ext.length);
  // decode percent encodings if present
  let decoded = base;
  try { decoded = decodeURIComponent(base); } catch (e) { /* ignore */ }
  // normalize: replace non-alphanumeric with hyphens, collapse hyphens, trim
  const cleaned = decoded
    .replace(/[^a-zA-Z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .replace(/-+/g, '-')
    .toLowerCase();
  return cleaned + ext.toLowerCase();
}

function walk(dir, collect) {
  const items = fs.readdirSync(dir, { withFileTypes: true });
  for (const it of items) {
    const full = path.join(dir, it.name);
    if (it.isDirectory()) walk(full, collect);
    else collect.push(full);
  }
}

function main() {
  const repoRoot = path.resolve(__dirname, '..');
  const imageDirs = [
    path.join(repoRoot, 'Assets', 'images'),
    path.join(repoRoot, 'Assets', 'images', 'optimized')
  ];

  const mappings = [];

  for (const dir of imageDirs) {
    if (!fs.existsSync(dir)) continue;
    const files = fs.readdirSync(dir, { withFileTypes: true });
    for (const f of files) {
      if (!f.isFile()) continue;
      const orig = f.name;
      const target = safeName(orig);
      if (orig === target) continue;
      const origPath = path.join(dir, orig);
      const targetPath = path.join(dir, target);
      let finalTarget = targetPath;
      // avoid overwrite
      if (fs.existsSync(finalTarget)) {
        // append numeric suffix
        const nameOnly = path.basename(target, path.extname(target));
        const ext = path.extname(target);
        let i = 1;
        while (fs.existsSync(path.join(dir, `${nameOnly}-${i}${ext}`))) i++;
        finalTarget = path.join(dir, `${nameOnly}-${i}${ext}`);
      }
      fs.renameSync(origPath, finalTarget);
      const finalName = path.basename(finalTarget);
      mappings.push({ from: orig, to: finalName, dir });
      console.log(`Renamed: ${path.relative(repoRoot, origPath)} -> ${path.relative(repoRoot, finalTarget)}`);
    }
  }

  if (mappings.length === 0) {
    console.log('No image files needed renaming.');
    return;
  }

  // Update references in HTML, CSS, JS files
  const textFiles = [];
  walk(repoRoot, textFiles);
  const targets = textFiles.filter(p => /\.(html|css|js|json)$/.test(p) && !p.includes('node_modules'));

  for (const file of targets) {
    let content = fs.readFileSync(file, 'utf8');
    let changed = false;
    for (const m of mappings) {
      const escapedOrig = escapeRegExp(m.from);
      const encoded = encodeURIComponent(m.from);
      const encodedPlus = m.from.replace(/ /g, '%20');
      const patterns = [escapedOrig, escapeRegExp(encoded), escapeRegExp(encodedPlus)];
      for (const pat of patterns) {
        const re = new RegExp(pat, 'g');
        if (re.test(content)) {
          content = content.replace(re, m.to);
          changed = true;
        }
      }
    }
    if (changed) {
      fs.writeFileSync(file, content, 'utf8');
      console.log(`Updated references in ${path.relative(repoRoot, file)}`);
    }
  }

  console.log('Normalization complete. Please review changes and commit.');
}

main();
