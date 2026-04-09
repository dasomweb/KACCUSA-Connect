#!/usr/bin/env node

/**
 * Validate all design token and config JSON files.
 * Usage: node scripts/validate-json.js
 */

const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const hexPattern = /^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/;

let errors = 0;
let checked = 0;

// JSON files to validate
const jsonFiles = [
  'design-tokens/colors.json',
  'design-tokens/typography.json',
  'design-tokens/spacing.json',
  'design-tokens/borders.json',
  'design-tokens/components.json',
  'design-tokens/index.json',
  'theme-config/settings.json',
  'theme-config/header.json',
  'theme-config/footer.json',
];

// 1. Validate JSON syntax
jsonFiles.forEach((file) => {
  const filePath = path.join(root, file);
  if (!fs.existsSync(filePath)) {
    console.error(`MISSING: ${file}`);
    errors++;
    return;
  }

  try {
    JSON.parse(fs.readFileSync(filePath, 'utf8'));
    console.log(`OK: ${file}`);
    checked++;
  } catch (e) {
    console.error(`INVALID JSON: ${file} - ${e.message}`);
    errors++;
  }
});

// 2. Validate color values in colors.json
const colorsPath = path.join(root, 'design-tokens/colors.json');
if (fs.existsSync(colorsPath)) {
  const colors = JSON.parse(fs.readFileSync(colorsPath, 'utf8'));

  function checkColors(obj, keyPath) {
    for (const [k, v] of Object.entries(obj)) {
      if (typeof v === 'string') {
        if (!hexPattern.test(v)) {
          console.error(`INVALID COLOR at ${keyPath}.${k}: ${v}`);
          errors++;
        }
      } else if (typeof v === 'object' && v !== null) {
        checkColors(v, `${keyPath}.${k}`);
      }
    }
  }

  // Skip $schema key
  const { $schema, ...colorData } = colors;
  checkColors(colorData, 'colors');
}

// 3. Validate block template JSON files
const templateDirs = [
  'block-templates/sections',
  'block-templates/pages',
];

templateDirs.forEach((dir) => {
  const dirPath = path.join(root, dir);
  if (!fs.existsSync(dirPath)) return;

  fs.readdirSync(dirPath)
    .filter((f) => f.endsWith('.json'))
    .forEach((file) => {
      const filePath = path.join(dirPath, file);
      try {
        JSON.parse(fs.readFileSync(filePath, 'utf8'));
        console.log(`OK: ${dir}/${file}`);
        checked++;
      } catch (e) {
        console.error(`INVALID JSON: ${dir}/${file} - ${e.message}`);
        errors++;
      }
    });
});

console.log(`\n--- Validation Complete ---`);
console.log(`Checked: ${checked} files`);
console.log(`Errors: ${errors}`);

if (errors > 0) {
  process.exit(1);
}
