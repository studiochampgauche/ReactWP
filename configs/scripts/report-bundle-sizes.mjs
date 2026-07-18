import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { brotliCompressSync, constants, gzipSync } from 'node:zlib';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const configsRoot = path.resolve(__dirname, '..');
const projectRoot = path.resolve(configsRoot, '..');
const themes = ['reactwp'];
const maxAssetBytes = Number(process.env.RWP_MAX_JS_ASSET_KB || 250) * 1024;
const maxInitialGzipBytes = Number(process.env.RWP_MAX_INITIAL_GZIP_KB || 170) * 1024;
const strictBudget = process.env.RWP_BUNDLE_BUDGET_STRICT === '1';

const formatKb = (bytes) => `${(bytes / 1024).toFixed(1)} KB`;

const compressedSizes = (buffer) => {
  return {
    gzip: gzipSync(buffer, { level: 9 }).length,
    brotli: brotliCompressSync(buffer, {
      params: {
        [constants.BROTLI_PARAM_QUALITY]: 11
      }
    }).length
  };
};

const readJson = async (filename) => {
  return JSON.parse(await fs.readFile(filename, 'utf8'));
};

const listJavaScriptFiles = async (directory) => {
  const entries = await fs.readdir(directory, { withFileTypes: true });
  const files = await Promise.all(entries.map(async (entry) => {
    const entryPath = path.join(directory, entry.name);

    if(entry.isDirectory()){
      return listJavaScriptFiles(entryPath);
    }

    return entry.name.endsWith('.js') ? [entryPath] : [];
  }));

  return files.flat();
};

const reportTheme = async (themeName) => {
  const themeRoot = path.resolve(
    projectRoot,
    'dist',
    'wp-content',
    'themes',
    themeName
  );
  const manifestPath = path.join(themeRoot, 'assets', 'js', 'entrypoints.json');
  const manifest = await readJson(manifestPath);
  const scripts = Array.isArray(manifest.scripts) ? manifest.scripts : [];
  const rows = [];
  const allScriptPaths = await listJavaScriptFiles(path.join(themeRoot, 'assets', 'js'));
  const allRows = [];

  for(const filename of allScriptPaths){
    const buffer = await fs.readFile(filename);

    allRows.push({
      file: path.relative(themeRoot, filename).replace(/\\/g, '/'),
      raw: buffer.length,
      usesDevelopmentJsxRuntime: buffer.includes('jsxDEV')
        || buffer.includes('jsx-dev-runtime')
    });
  }

  for(const relativePath of scripts){
    const filename = path.resolve(themeRoot, relativePath);
    const buffer = await fs.readFile(filename);
    const compressed = compressedSizes(buffer);

    rows.push({
      file: relativePath.replace(/\\/g, '/'),
      raw: buffer.length,
      gzip: compressed.gzip,
      brotli: compressed.brotli
    });
  }

  const totals = rows.reduce((result, row) => {
    result.raw += row.raw;
    result.gzip += row.gzip;
    result.brotli += row.brotli;
    return result;
  }, { raw: 0, gzip: 0, brotli: 0 });
  const warnings = [];
  const errors = [];

  allRows.forEach((row) => {
    if(row.raw > maxAssetBytes){
      warnings.push(
        `${row.file} exceeds the ${formatKb(maxAssetBytes)} per-file budget (${formatKb(row.raw)}).`
      );
    }
  });

  if(totals.gzip > maxInitialGzipBytes){
    warnings.push(
      `Initial JavaScript exceeds the ${formatKb(maxInitialGzipBytes)} gzip budget (${formatKb(totals.gzip)}).`
    );
  }

  if(manifest.mode === 'production'){
    allRows
      .filter((row) => row.usesDevelopmentJsxRuntime)
      .forEach((row) => {
        errors.push(`${row.file} contains the React development JSX runtime.`);
      });
  }

  const lines = [
    '',
    `[ReactWP bundle] ${themeName} (${manifest.mode || 'unknown'})`,
    ...rows.map((row) => {
      return `  ${row.file}: ${formatKb(row.raw)} raw, ${formatKb(row.gzip)} gzip, ${formatKb(row.brotli)} br`;
    }),
    `  Initial total: ${formatKb(totals.raw)} raw, ${formatKb(totals.gzip)} gzip, ${formatKb(totals.brotli)} br`
  ];

  warnings.forEach((warning) => lines.push(`  WARNING: ${warning}`));
  errors.forEach((error) => lines.push(`  ERROR: ${error}`));
  process.stdout.write(`${lines.join('\n')}\n`);

  return {
    errors: errors.length,
    warnings: warnings.length
  };
};

const run = async () => {
  let warningCount = 0;
  let errorCount = 0;

  for(const themeName of themes){
    const result = await reportTheme(themeName);

    warningCount += result.warnings;
    errorCount += result.errors;
  }

  if(errorCount > 0 || (strictBudget && warningCount > 0)){
    process.exitCode = 1;
  }
};

run().catch((error) => {
  process.stderr.write(`[ReactWP bundle] ${error.message}\n`);
  process.exit(1);
});
