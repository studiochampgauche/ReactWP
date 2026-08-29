import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const configsRoot = path.resolve(__dirname, '..');
const requestedTests = process.argv.slice(2);
const tests = requestedTests.length ? requestedTests : [
  './tests/rest-access.test.php',
  './tests/headless-api-security.test.php',
  './tests/route-visibility.test.php',
  './tests/public-payload.test.php',
  './tests/preview-token.test.php',
  './tests/svg-sanitizer.test.php',
  './tests/render-cache.test.php',
  './tests/server-renderer-security.test.php',
  './tests/static-regenerator.test.php',
  './tests/firstload.test.php',
  './tests/seo-route-language.test.php'
];
const candidates = [process.env.PHP_BINARY, 'php'].filter(Boolean);

if (process.platform === 'win32') {
  const laragonPhpRoot = path.join(process.env.LARAGON_ROOT || 'C:\\laragon', 'bin', 'php');

  if (fs.existsSync(laragonPhpRoot)) {
    const installations = fs.readdirSync(laragonPhpRoot, { withFileTypes: true })
      .filter((entry) => entry.isDirectory())
      .map((entry) => path.join(laragonPhpRoot, entry.name, 'php.exe'))
      .filter((filename) => fs.existsSync(filename))
      .sort()
      .reverse();
    candidates.push(...installations);
  }
}

const php = candidates.find((candidate) => {
  const result = spawnSync(candidate, ['-v'], { stdio: 'ignore' });
  return !result.error && result.status === 0;
});

if (!php) {
  throw new Error('PHP was not found. Set PHP_BINARY to the PHP executable before running the security tests.');
}

for (const test of tests) {
  const result = spawnSync(php, [path.resolve(configsRoot, test)], {
    cwd: configsRoot,
    stdio: 'inherit'
  });

  if (result.error) {
    throw result.error;
  }

  if (result.status !== 0) {
    process.exit(result.status || 1);
  }
}
