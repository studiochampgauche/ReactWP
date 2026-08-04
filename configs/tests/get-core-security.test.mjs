import assert from 'node:assert/strict';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

const __filename = fileURLToPath(import.meta.url);
const configsRoot = path.resolve(path.dirname(__filename), '..');
const getCore = path.join(configsRoot, 'scripts', 'get-core.mjs');

const runGetCore = (overrides = {}) => spawnSync(process.execPath, [getCore], {
  cwd: configsRoot,
  encoding: 'utf8',
  env: {
    ...process.env,
    REACTWP_ACF_LICENSE_KEY: '',
    ACF_PRO_LICENSE: '',
    REACTWP_ACF_SITE_URL: '',
    RWP_SITE_URL: '',
    REACTWP_ACF_URL: '',
    REACTWP_ACF_VERSION: '',
    REACTWP_ACF_SHA256: '',
    REACTWP_SKIP_ACF: '',
    REACTWP_ACF_EDITION: 'pro',
    ...overrides
  }
});

test('get:core requires official ACF credentials before downloading', () => {
  const result = runGetCore();

  assert.equal(result.status, 1);
  assert.match(result.stderr, /REACTWP_ACF_LICENSE_KEY is required/);
  assert.doesNotMatch(result.stdout, /Downloading WordPress core/);
});

test('get:core rejects an invalid licensed site URL before downloading', () => {
  const result = runGetCore({
    REACTWP_ACF_LICENSE_KEY: 'test-license-key',
    REACTWP_ACF_SITE_URL: 'example.com'
  });

  assert.equal(result.status, 1);
  assert.match(result.stderr, /including http:\/\/ or https:\/\//);
  assert.doesNotMatch(result.stdout, /Downloading WordPress core/);
});

test('private ACF archive overrides remain pinned and verified', () => {
  const result = runGetCore({
    REACTWP_ACF_URL: 'https://private.example/acf-pro.zip',
    REACTWP_ACF_VERSION: '6.8.6'
  });

  assert.equal(result.status, 1);
  assert.match(result.stderr, /REACTWP_ACF_VERSION and REACTWP_ACF_SHA256 are required/);
  assert.doesNotMatch(result.stdout, /Downloading WordPress core/);
});

test('get:core rejects an unknown ACF edition before downloading', () => {
  const result = runGetCore({
    REACTWP_ACF_EDITION: 'enterprise'
  });

  assert.equal(result.status, 1);
  assert.match(result.stderr, /must be free, pro, or none/);
  assert.doesNotMatch(result.stdout, /Downloading WordPress core/);
});
