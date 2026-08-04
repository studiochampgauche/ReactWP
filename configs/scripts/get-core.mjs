import fs from 'node:fs/promises';
import { createReadStream, createWriteStream } from 'node:fs';
import { createHash } from 'node:crypto';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawn } from 'node:child_process';
import https from 'node:https';
import { createInterface } from 'node:readline/promises';
import { Transform } from 'node:stream';
import { pipeline } from 'node:stream/promises';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const projectRoot = path.resolve(__dirname, '../..');
const distRoot = path.resolve(projectRoot, 'dist');
const DEFAULT_WORDPRESS_URL = 'https://wordpress.org/latest.zip';
const DEFAULT_ACF_COMPOSER_REPOSITORY = 'https://connect.advancedcustomfields.com';
const ACF_FREE_PLUGIN_SLUG = 'advanced-custom-fields';
const ACF_FREE_INFO_URL = 'https://api.wordpress.org/plugins/info/1.2/';
const MAX_REDIRECTS = 5;
const MAX_ARCHIVE_BYTES = 200 * 1024 * 1024;
const MAX_ARCHIVE_ENTRIES = 100000;
const MAX_EXTRACTED_BYTES = 1024 * 1024 * 1024;
const MAX_ENTRY_BYTES = 256 * 1024 * 1024;
const WORDPRESS_URL = process.env.REACTWP_WORDPRESS_URL || DEFAULT_WORDPRESS_URL;
const WORDPRESS_SHA256 = String(process.env.REACTWP_WORDPRESS_SHA256 || '').toLowerCase();
const ACF_URL = String(process.env.REACTWP_ACF_URL || '').trim();
const configuredAcfVersion = String(process.env.REACTWP_ACF_VERSION || '').trim();
const ACF_VERSION = configuredAcfVersion;
const ACF_SHA256 = String(process.env.REACTWP_ACF_SHA256 || '').toLowerCase();
const ACF_COMPOSER_REPOSITORY = String(
  process.env.REACTWP_ACF_COMPOSER_REPOSITORY || DEFAULT_ACF_COMPOSER_REPOSITORY
).trim();
let ACF_LICENSE_KEY = String(
  process.env.REACTWP_ACF_LICENSE_KEY || process.env.ACF_PRO_LICENSE || ''
).trim();
let ACF_SITE_URL = String(
  process.env.REACTWP_ACF_SITE_URL || process.env.RWP_SITE_URL || ''
).trim();
const configuredAcfEdition = String(process.env.REACTWP_ACF_EDITION || '').trim().toLowerCase();
const WORDPRESS_LOCALE = process.env.REACTWP_WORDPRESS_LOCALE || 'en_US';
const usesPrivateAcfArchive = ACF_URL !== '';
const configuredHosts = String(process.env.REACTWP_DOWNLOAD_HOSTS || '')
  .split(',')
  .map((host) => host.trim().toLowerCase())
  .filter(Boolean);
const allowedDownloadHosts = new Set([
  'wordpress.org',
  'downloads.wordpress.org',
  'api.wordpress.org',
  ...configuredHosts
]);

const publicUrlLabel = (value) => {
  try {
    const url = value instanceof URL ? value : new URL(value);
    return `${url.protocol}//${url.host}${url.pathname}`;
  } catch (_error) {
    return '[invalid URL]';
  }
};

const run = (command, args, options = {}) => new Promise((resolve, reject) => {
  const timeout = options.timeout || 120000;
  const child = spawn(command, args, {
    cwd: options.cwd || projectRoot,
    env: options.env || process.env,
    stdio: 'inherit'
  });
  const timer = setTimeout(() => {
    child.kill();
  }, timeout);

  child.on('error', (error) => {
    clearTimeout(timer);
    reject(error);
  });
  child.on('exit', (code, signal) => {
    clearTimeout(timer);

    if (signal || code !== 0) {
      reject(new Error(`${command} exited with ${signal ? `signal ${signal}` : `code ${code}`}`));
      return;
    }

    resolve();
  });
});

const resolveAcfEdition = async () => {
  if (process.env.REACTWP_SKIP_ACF === '1') {
    return 'none';
  }

  if (configuredAcfEdition) {
    if (!['free', 'pro', 'none'].includes(configuredAcfEdition)) {
      throw new Error('REACTWP_ACF_EDITION must be free, pro, or none.');
    }

    return configuredAcfEdition;
  }

  if (usesPrivateAcfArchive || ACF_LICENSE_KEY || ACF_SITE_URL) {
    return 'pro';
  }

  if (!process.stdin.isTTY || !process.stdout.isTTY) {
    return 'free';
  }

  const readline = createInterface({
    input: process.stdin,
    output: process.stdout
  });

  try {
    console.log('Choose the Advanced Custom Fields edition to install:');
    console.log('  1. Free - standard fields from WordPress.org');
    console.log('  2. PRO  - licensed fields, repeaters, and ReactWP settings pages');
    console.log('  3. None - keep any existing ACF installation unchanged');
    const answer = (await readline.question('Selection [1]: ')).trim().toLowerCase();

    if (answer === '' || answer === '1' || answer === 'free') {
      return 'free';
    }

    if (answer === '2' || answer === 'pro') {
      return 'pro';
    }

    if (answer === '3' || answer === 'none') {
      return 'none';
    }

    throw new Error('Invalid ACF selection. Choose 1, 2, or 3.');
  } finally {
    readline.close();
  }
};

const promptText = async (question) => {
  const readline = createInterface({
    input: process.stdin,
    output: process.stdout
  });

  try {
    return (await readline.question(question)).trim();
  } finally {
    readline.close();
  }
};

const promptSecret = (question) => new Promise((resolve, reject) => {
  if (!process.stdin.isTTY || typeof process.stdin.setRawMode !== 'function') {
    resolve('');
    return;
  }

  const input = process.stdin;
  const output = process.stdout;
  const wasRaw = input.isRaw;
  const wasPaused = input.isPaused();
  let value = '';

  const cleanup = () => {
    input.removeListener('data', onData);
    input.setRawMode(Boolean(wasRaw));

    if (wasPaused) {
      input.pause();
    }
  };
  const onData = (chunk) => {
    for (const character of String(chunk)) {
      if (character === '\u0003') {
        cleanup();
        output.write('\n');
        reject(new Error('ACF credential entry was cancelled.'));
        return;
      }

      if (character === '\r' || character === '\n') {
        cleanup();
        output.write('\n');
        resolve(value.trim());
        return;
      }

      if (character === '\u0008' || character === '\u007f') {
        if (value) {
          value = value.slice(0, -1);
          output.write('\b \b');
        }
        continue;
      }

      if (character >= ' ' && value.length < 512) {
        value += character;
        output.write('*');
      }
    }
  };

  output.write(question);
  input.setEncoding('utf8');
  input.setRawMode(true);
  input.resume();
  input.on('data', onData);
});

const collectAcfLicenseCredentials = async () => {
  if ((!ACF_LICENSE_KEY || !ACF_SITE_URL) && process.stdin.isTTY && process.stdout.isTTY) {
    if (!ACF_LICENSE_KEY) {
      ACF_LICENSE_KEY = await promptSecret('ACF PRO license key: ');
    }

    if (!ACF_SITE_URL) {
      ACF_SITE_URL = await promptText('Licensed site URL (including http:// or https://): ');
    }
  }

  return validateAcfLicenseCredentials();
};

const validateAcfLicenseCredentials = () => {
  if (
    !ACF_LICENSE_KEY
    || ACF_LICENSE_KEY.length > 512
    || /[\u0000-\u001f\u007f]/.test(ACF_LICENSE_KEY)
  ) {
    throw new Error('REACTWP_ACF_LICENSE_KEY is required to install ACF PRO from its official Composer repository.');
  }

  let siteUrl;

  try {
    siteUrl = new URL(ACF_SITE_URL);
  } catch (_error) {
    throw new Error('REACTWP_ACF_SITE_URL must be the complete licensed site URL, including http:// or https://.');
  }

  if (
    !['http:', 'https:'].includes(siteUrl.protocol)
    || !siteUrl.hostname
    || siteUrl.username
    || siteUrl.password
    || siteUrl.search
    || siteUrl.hash
  ) {
    throw new Error('REACTWP_ACF_SITE_URL must be a clean HTTP(S) site URL without credentials, query parameters, or a fragment.');
  }

  return siteUrl.toString().replace(/\/$/, '');
};

const composerEnvironment = async (auth) => {
  const env = {
    ...process.env,
    COMPOSER_AUTH: JSON.stringify(auth),
    COMPOSER_NO_INTERACTION: '1'
  };

  if (process.platform !== 'win32') {
    return env;
  }

  const explicitPhp = String(process.env.REACTWP_PHP_BINARY || process.env.PHP_BINARY || '').trim();
  let phpDirectory = explicitPhp ? path.dirname(explicitPhp) : '';

  if (!phpDirectory) {
    const laragonPhpRoot = path.join(process.env.LARAGON_ROOT || 'C:\\laragon', 'bin', 'php');

    try {
      const installations = (await fs.readdir(laragonPhpRoot, { withFileTypes: true }))
        .filter((entry) => entry.isDirectory())
        .map((entry) => path.join(laragonPhpRoot, entry.name, 'php.exe'))
        .sort()
        .reverse();

      for (const candidate of installations) {
        try {
          await fs.access(candidate);
          phpDirectory = path.dirname(candidate);
          break;
        } catch (_error) {
          // Keep looking for an accessible PHP installation.
        }
      }
    } catch (_error) {
      // Composer will report a clear error if PHP is unavailable.
    }
  }

  if (phpDirectory) {
    const pathKey = Object.keys(env).find((key) => key.toUpperCase() === 'PATH') || 'PATH';
    env[pathKey] = `${phpDirectory}${path.delimiter}${env[pathKey] || ''}`;
  }

  return env;
};

const assertDownloadUrl = (value) => {
  const url = new URL(value);

  if (url.protocol !== 'https:') {
    throw new Error(`Downloads must use HTTPS: ${publicUrlLabel(url)}`);
  }

  if (url.username || url.password || !allowedDownloadHosts.has(url.hostname.toLowerCase())) {
    throw new Error(`Download host is not allowed: ${url.hostname}`);
  }

  return url;
};

const openResponse = (url) => new Promise((resolve, reject) => {
  const request = https.get(url, {
    headers: {
      'User-Agent': 'ReactWP secure project tooling'
    }
  }, resolve);

  request.setTimeout(30000, () => {
    request.destroy(new Error(`Download timed out: ${publicUrlLabel(url)}`));
  });
  request.on('error', reject);
});

const download = async (value, destination, redirectCount = 0) => {
  const url = assertDownloadUrl(value);
  const response = await openResponse(url);

  if (response.statusCode >= 300 && response.statusCode < 400 && response.headers.location) {
    response.resume();

    if (redirectCount >= MAX_REDIRECTS) {
      throw new Error(`Too many redirects while downloading ${publicUrlLabel(url)}`);
    }

    const redirectUrl = new URL(response.headers.location, url).toString();
    return download(redirectUrl, destination, redirectCount + 1);
  }

  if (response.statusCode !== 200) {
    response.resume();
    throw new Error(`Download failed for ${publicUrlLabel(url)}: ${response.statusCode}`);
  }

  const declaredLength = Number.parseInt(response.headers['content-length'] || '0', 10);

  if (declaredLength > MAX_ARCHIVE_BYTES) {
    response.destroy();
    throw new Error(`Download is larger than ${MAX_ARCHIVE_BYTES} bytes: ${publicUrlLabel(url)}`);
  }

  let receivedBytes = 0;
  const limiter = new Transform({
    transform(chunk, _encoding, callback) {
      receivedBytes += chunk.length;

      if (receivedBytes > MAX_ARCHIVE_BYTES) {
        callback(new Error(`Download exceeded ${MAX_ARCHIVE_BYTES} bytes: ${publicUrlLabel(url)}`));
        return;
      }

      callback(null, chunk);
    }
  });

  try {
    await pipeline(response, limiter, createWriteStream(destination, { flags: 'wx' }));
  } catch (error) {
    await fs.rm(destination, { force: true });
    throw error;
  }
};

const requestJson = async (value, redirectCount = 0) => {
  const url = assertDownloadUrl(value);
  const response = await openResponse(url);

  if (response.statusCode >= 300 && response.statusCode < 400 && response.headers.location) {
    response.resume();

    if (redirectCount >= MAX_REDIRECTS) {
      throw new Error(`Too many redirects while requesting ${publicUrlLabel(url)}`);
    }

    return requestJson(new URL(response.headers.location, url).toString(), redirectCount + 1);
  }

  if (response.statusCode !== 200) {
    response.resume();
    throw new Error(`Request failed for ${publicUrlLabel(url)}: ${response.statusCode}`);
  }

  const chunks = [];
  let bytes = 0;

  for await (const chunk of response) {
    bytes += chunk.length;

    if (bytes > 5 * 1024 * 1024) {
      throw new Error(`JSON response is unexpectedly large: ${publicUrlLabel(url)}`);
    }

    chunks.push(chunk);
  }

  return JSON.parse(Buffer.concat(chunks).toString('utf8'));
};

const digestFile = async (filename, algorithm) => {
  const hash = createHash(algorithm);

  for await (const chunk of createReadStream(filename)) {
    hash.update(chunk);
  }

  return hash.digest('hex').toLowerCase();
};

const verifyDigest = async (filename, expected, algorithm) => {
  if (!new RegExp(`^[a-f0-9]{${algorithm === 'sha256' ? 64 : 32}}$`, 'i').test(expected)) {
    throw new Error(`A valid ${algorithm.toUpperCase()} checksum is required for ${filename}.`);
  }

  const actual = await digestFile(filename, algorithm);

  if (actual !== expected.toLowerCase()) {
    throw new Error(`${algorithm.toUpperCase()} mismatch for ${filename}. Expected ${expected}, received ${actual}.`);
  }
};

const inspectZip = async (zipFile) => {
  const handle = await fs.open(zipFile, 'r');

  try {
    const stats = await handle.stat();
    const tailLength = Math.min(stats.size, 65557);
    const tail = Buffer.alloc(tailLength);
    await handle.read(tail, 0, tailLength, stats.size - tailLength);
    let endOffset = -1;

    for (let offset = tail.length - 22; offset >= 0; offset--) {
      if (tail.readUInt32LE(offset) === 0x06054b50) {
        endOffset = offset;
        break;
      }
    }

    if (endOffset < 0) {
      throw new Error(`Invalid ZIP archive: ${zipFile}`);
    }

    const disk = tail.readUInt16LE(endOffset + 4);
    const centralDisk = tail.readUInt16LE(endOffset + 6);
    const diskEntries = tail.readUInt16LE(endOffset + 8);
    const totalEntries = tail.readUInt16LE(endOffset + 10);
    const centralSize = tail.readUInt32LE(endOffset + 12);
    const centralOffset = tail.readUInt32LE(endOffset + 16);

    if (
      disk !== 0
      || centralDisk !== 0
      || diskEntries !== totalEntries
      || totalEntries === 0xffff
      || centralSize === 0xffffffff
      || centralOffset === 0xffffffff
      || totalEntries > MAX_ARCHIVE_ENTRIES
      || centralSize > 64 * 1024 * 1024
      || centralOffset + centralSize > stats.size
    ) {
      throw new Error(`Unsupported or unsafe ZIP structure: ${zipFile}`);
    }

    const central = Buffer.alloc(centralSize);
    await handle.read(central, 0, centralSize, centralOffset);
    const names = new Set();
    let cursor = 0;
    let extractedBytes = 0;

    for (let index = 0; index < totalEntries; index++) {
      if (cursor + 46 > central.length || central.readUInt32LE(cursor) !== 0x02014b50) {
        throw new Error(`Invalid ZIP central directory entry: ${zipFile}`);
      }

      const madeByPlatform = central.readUInt16LE(cursor + 4) >> 8;
      const flags = central.readUInt16LE(cursor + 8);
      const method = central.readUInt16LE(cursor + 10);
      const compressedSize = central.readUInt32LE(cursor + 20);
      const uncompressedSize = central.readUInt32LE(cursor + 24);
      const filenameLength = central.readUInt16LE(cursor + 28);
      const extraLength = central.readUInt16LE(cursor + 30);
      const commentLength = central.readUInt16LE(cursor + 32);
      const externalAttributes = central.readUInt32LE(cursor + 38);
      const localOffset = central.readUInt32LE(cursor + 42);
      const entryLength = 46 + filenameLength + extraLength + commentLength;

      if (cursor + entryLength > central.length || filenameLength === 0) {
        throw new Error(`Truncated ZIP entry: ${zipFile}`);
      }

      if (
        (flags & 0x1) !== 0
        || ![0, 8].includes(method)
        || compressedSize === 0xffffffff
        || uncompressedSize === 0xffffffff
        || uncompressedSize > MAX_ENTRY_BYTES
      ) {
        throw new Error(`Encrypted, unsupported, ZIP64, or oversized ZIP entry rejected: ${zipFile}`);
      }

      const filenameBuffer = central.subarray(cursor + 46, cursor + 46 + filenameLength);
      const filename = filenameBuffer.toString('utf8').replace(/\\/g, '/');
      const segments = filename.split('/').filter(Boolean);
      const normalizedName = segments.join('/');
      const unixMode = madeByPlatform === 3 ? (externalAttributes >>> 16) & 0xffff : 0;

      if (
        filename.includes('\u0000')
        || filename.includes('\ufffd')
        || filename.startsWith('/')
        || /^[a-z]:/i.test(filename)
        || segments.includes('..')
        || normalizedName === ''
        || ((unixMode & 0xf000) === 0xa000)
      ) {
        throw new Error(`Unsafe ZIP entry path or symbolic link rejected: ${filename || zipFile}`);
      }

      const localHeader = Buffer.alloc(30);

      if(localOffset + localHeader.length > stats.size){
        throw new Error(`Invalid ZIP local header offset: ${filename}`);
      }

      await handle.read(localHeader, 0, localHeader.length, localOffset);

      if(localHeader.readUInt32LE(0) !== 0x04034b50){
        throw new Error(`Invalid ZIP local header: ${filename}`);
      }

      const localFilenameLength = localHeader.readUInt16LE(26);
      const localExtraLength = localHeader.readUInt16LE(28);
      const localFlags = localHeader.readUInt16LE(6);
      const localMethod = localHeader.readUInt16LE(8);
      const localCompressedSize = localHeader.readUInt32LE(18);
      const localUncompressedSize = localHeader.readUInt32LE(22);
      const localFilenameBuffer = Buffer.alloc(localFilenameLength);
      await handle.read(localFilenameBuffer, 0, localFilenameLength, localOffset + 30);
      const localFilename = localFilenameBuffer.toString('utf8').replace(/\\/g, '/');

      if(
        localFilename !== filename
        || localFlags !== flags
        || localMethod !== method
        || localCompressedSize === 0xffffffff
        || localUncompressedSize === 0xffffffff
        || ((flags & 0x8) === 0 && (
          localCompressedSize !== compressedSize
          || localUncompressedSize !== uncompressedSize
        ))
      ){
        throw new Error(`ZIP local and central entry metadata do not match: ${filename}`);
      }

      const dataOffset = localOffset + 30 + localFilenameLength + localExtraLength;

      if(dataOffset > stats.size || compressedSize > stats.size - dataOffset){
        throw new Error(`ZIP entry data exceeds the archive boundary: ${filename}`);
      }

      const duplicateKey = normalizedName.toLowerCase();

      if (names.has(duplicateKey)) {
        throw new Error(`Duplicate ZIP entry rejected: ${filename}`);
      }

      names.add(duplicateKey);
      extractedBytes += uncompressedSize;

      if (extractedBytes > MAX_EXTRACTED_BYTES) {
        throw new Error(`ZIP archive expands beyond ${MAX_EXTRACTED_BYTES} bytes: ${zipFile}`);
      }

      cursor += entryLength;
    }

    if (cursor !== central.length) {
      throw new Error(`Unexpected data in ZIP central directory: ${zipFile}`);
    }

    return {
      entries: totalEntries,
      extractedBytes
    };
  } finally {
    await handle.close();
  }
};

const extractZip = async (zipFile, targetDirectory) => {
  await inspectZip(zipFile);
  await fs.mkdir(targetDirectory, { recursive: true });

  if (process.platform === 'win32') {
    await run('powershell.exe', [
      '-NoProfile',
      '-Command',
      `Expand-Archive -LiteralPath '${zipFile.replace(/'/g, "''")}' -DestinationPath '${targetDirectory.replace(/'/g, "''")}' -Force`
    ]);
    return;
  }

  await run('unzip', ['-o', zipFile, '-d', targetDirectory]);
};

const verifyWordPress = async (wordpressRoot) => {
  const versionFile = await fs.readFile(path.join(wordpressRoot, 'wp-includes', 'version.php'), 'utf8');
  const match = versionFile.match(/\$wp_version\s*=\s*['"]([^'"]+)['"]/);

  if (!match) {
    throw new Error('The downloaded WordPress version could not be identified.');
  }

  const version = match[1];
  const checksumUrl = `https://api.wordpress.org/core/checksums/1.0/?version=${encodeURIComponent(version)}&locale=${encodeURIComponent(WORDPRESS_LOCALE)}`;
  const response = await requestJson(checksumUrl);
  const checksums = response?.checksums;

  if (!checksums || typeof checksums !== 'object' || Object.keys(checksums).length < 100) {
    throw new Error(`WordPress did not return a valid checksum set for ${version} (${WORDPRESS_LOCALE}).`);
  }

  const discoveredFiles = [];
  const walk = async (directory) => {
    for (const entry of await fs.readdir(directory, { withFileTypes: true })) {
      const filename = path.join(directory, entry.name);

      if (entry.isSymbolicLink()) {
        throw new Error(`WordPress archives may not contain symbolic links: ${filename}`);
      }

      if (entry.isDirectory()) {
        await walk(filename);
      } else if (entry.isFile()) {
        discoveredFiles.push(path.relative(wordpressRoot, filename).replace(/\\/g, '/'));
      }
    }
  };

  await walk(wordpressRoot);
  const unexpectedFiles = discoveredFiles.filter((filename) => {
    return !Object.prototype.hasOwnProperty.call(checksums, filename);
  });

  if (unexpectedFiles.length) {
    throw new Error(`WordPress archive contains unverified files: ${unexpectedFiles.slice(0, 10).join(', ')}`);
  }

  for (const [relativePath, checksum] of Object.entries(checksums)) {
    const filename = path.resolve(wordpressRoot, relativePath);
    const relative = path.relative(wordpressRoot, filename);

    if (relative.startsWith('..') || path.isAbsolute(relative)) {
      throw new Error(`Unsafe WordPress checksum path: ${relativePath}`);
    }

    await verifyDigest(filename, String(checksum), 'md5');
  }

  console.log(`Verified WordPress ${version} (${WORDPRESS_LOCALE}) against official checksums.`);

  return { version, checksums };
};

const replaceWordPressCore = async (wordpressRoot, verification) => {
  await fs.mkdir(distRoot, { recursive: true });
  await Promise.all([
    fs.rm(path.join(distRoot, 'wp-admin'), { recursive: true, force: true }),
    fs.rm(path.join(distRoot, 'wp-includes'), { recursive: true, force: true })
  ]);

  for(const entry of await fs.readdir(distRoot, { withFileTypes: true })){
    if(
      (entry.isFile() || entry.isSymbolicLink())
      && entry.name.toLowerCase().endsWith('.php')
      && entry.name.toLowerCase() !== 'wp-config.php'
    ){
      await fs.rm(path.join(distRoot, entry.name), { force: true });
    }
  }

  await fs.cp(wordpressRoot, distRoot, {
    recursive: true,
    force: true,
    filter(source){
      const relative = path.relative(wordpressRoot, source);
      const firstSegment = relative.split(path.sep)[0];

      return firstSegment !== 'wp-content';
    }
  });

  for(const [relativePath, checksum] of Object.entries(verification.checksums)){
    if(relativePath.startsWith('wp-content/')){
      continue;
    }

    await verifyDigest(path.join(distRoot, relativePath), String(checksum), 'md5');
  }
};

const verifyAcf = async (acfRoot, expectedVersion = ACF_VERSION) => {
  const pluginFile = path.join(acfRoot, 'acf.php');
  const contents = await fs.readFile(pluginFile, 'utf8');
  const match = contents.match(/^\s*\*?\s*Version:\s*([^\r\n]+)/mi);
  const actualVersion = match ? match[1].trim() : '';

  if (!actualVersion) {
    throw new Error('The installed ACF PRO package does not expose a valid plugin version.');
  }

  if (expectedVersion && actualVersion !== expectedVersion) {
    throw new Error(`Unexpected ACF version. Expected ${expectedVersion}, received ${actualVersion || 'unknown'}.`);
  }

  return actualVersion;
};

const installFreeAcf = async (tempRoot) => {
  const infoUrl = new URL(ACF_FREE_INFO_URL);
  infoUrl.searchParams.set('action', 'plugin_information');
  infoUrl.searchParams.set('request[slug]', ACF_FREE_PLUGIN_SLUG);
  infoUrl.searchParams.set('request[fields][sections]', '0');
  infoUrl.searchParams.set('request[fields][versions]', '1');
  const plugin = await requestJson(infoUrl.toString());

  if (!plugin || typeof plugin !== 'object' || plugin.slug !== ACF_FREE_PLUGIN_SLUG) {
    throw new Error('WordPress.org returned invalid ACF plugin metadata.');
  }

  const expectedVersion = ACF_VERSION || String(plugin.version || '').trim();
  const versions = plugin.versions && typeof plugin.versions === 'object'
    ? plugin.versions
    : {};
  const downloadUrl = ACF_VERSION
    ? versions[ACF_VERSION]
    : plugin.download_link;

  if (
    !/^\d+(?:\.\d+){1,3}(?:[-.][a-z0-9]+)?$/i.test(expectedVersion)
    || typeof downloadUrl !== 'string'
    || !downloadUrl
  ) {
    throw new Error(`ACF Free ${ACF_VERSION || 'latest'} is not available from WordPress.org.`);
  }

  const parsedDownloadUrl = assertDownloadUrl(downloadUrl);

  if (parsedDownloadUrl.hostname.toLowerCase() !== 'downloads.wordpress.org') {
    throw new Error(`Unexpected ACF Free download host: ${parsedDownloadUrl.hostname}`);
  }

  const archive = path.join(tempRoot, 'acf-free.zip');
  const extractRoot = path.join(tempRoot, 'acf-free-extract');
  const acfRoot = path.join(extractRoot, ACF_FREE_PLUGIN_SLUG);

  console.log(`Downloading ACF Free ${expectedVersion} from WordPress.org...`);
  await download(parsedDownloadUrl.toString(), archive);
  await extractZip(archive, extractRoot);
  const actualVersion = await verifyAcf(acfRoot, expectedVersion);
  console.log(`Verified official ACF Free ${actualVersion}.`);

  return acfRoot;
};

const installAcfWithComposer = async (tempRoot) => {
  const siteUrl = validateAcfLicenseCredentials();
  let repositoryUrl;

  try {
    repositoryUrl = new URL(ACF_COMPOSER_REPOSITORY);
  } catch (_error) {
    throw new Error('REACTWP_ACF_COMPOSER_REPOSITORY must be a valid HTTPS URL.');
  }

  if (
    repositoryUrl.protocol !== 'https:'
    || repositoryUrl.username
    || repositoryUrl.password
    || repositoryUrl.search
    || repositoryUrl.hash
  ) {
    throw new Error('REACTWP_ACF_COMPOSER_REPOSITORY must be a clean HTTPS URL.');
  }

  if (
    repositoryUrl.toString().replace(/\/$/, '') !== DEFAULT_ACF_COMPOSER_REPOSITORY
    && !allowedDownloadHosts.has(repositoryUrl.hostname.toLowerCase())
  ) {
    throw new Error(`ACF Composer repository host is not allowed: ${repositoryUrl.hostname}`);
  }

  const composerRoot = path.join(tempRoot, 'acf-composer');
  const acfRoot = path.join(
    composerRoot,
    'wp-content',
    'mu-plugins',
    'advanced-custom-fields-pro'
  );
  const composerJson = {
    name: 'reactwp/acf-installer',
    description: 'Temporary official ACF PRO installer for ReactWP',
    repositories: [{
      type: 'composer',
      url: repositoryUrl.toString().replace(/\/$/, '')
    }],
    require: {
      'composer/installers': '^2.0',
      'wpengine/advanced-custom-fields-pro': ACF_VERSION || '*'
    },
    config: {
      'allow-plugins': {
        'composer/installers': true
      },
      'secure-http': true,
      'sort-packages': true
    },
    extra: {
      'installer-paths': {
        'wp-content/mu-plugins/{$name}/': [
          'wpengine/advanced-custom-fields-pro'
        ]
      }
    }
  };

  await fs.mkdir(composerRoot, { recursive: true });
  await fs.writeFile(
    path.join(composerRoot, 'composer.json'),
    `${JSON.stringify(composerJson, null, 2)}\n`,
    { encoding: 'utf8', mode: 0o600 }
  );

  let existingAuth = {};

  if (process.env.COMPOSER_AUTH) {
    try {
      existingAuth = JSON.parse(process.env.COMPOSER_AUTH);
    } catch (_error) {
      throw new Error('COMPOSER_AUTH contains invalid JSON.');
    }

    if (!existingAuth || typeof existingAuth !== 'object' || Array.isArray(existingAuth)) {
      throw new Error('COMPOSER_AUTH must contain a JSON object.');
    }
  }

  const existingHttpBasic = existingAuth['http-basic']
    && typeof existingAuth['http-basic'] === 'object'
    && !Array.isArray(existingAuth['http-basic'])
    ? existingAuth['http-basic']
    : {};
  const auth = {
    ...existingAuth,
    'http-basic': {
      ...existingHttpBasic,
      [repositoryUrl.hostname]: {
        username: ACF_LICENSE_KEY,
        password: siteUrl
      }
    }
  };
  const composerArgs = [
    'install',
    '--no-dev',
    '--prefer-dist',
    '--no-interaction',
    '--no-progress',
    '--no-scripts',
    '--no-ansi'
  ];
  const env = await composerEnvironment(auth);
  env.COMPOSER_HOME = path.join(tempRoot, 'composer-home');
  env.COMPOSER_CACHE_DIR = path.join(tempRoot, 'composer-cache');
  const composerBinary = String(process.env.REACTWP_COMPOSER_BINARY || 'composer').trim();
  const command = process.platform === 'win32'
    ? process.env.ComSpec || 'cmd.exe'
    : composerBinary;
  const args = process.platform === 'win32'
    ? ['/d', '/c', composerBinary, ...composerArgs]
    : composerArgs;

  try {
    await run(command, args, {
      cwd: composerRoot,
      env,
      timeout: 300000
    });
  } catch (error) {
    throw new Error(
      'Composer could not install ACF PRO. Verify Composer, the license key, the site URL, and the license activation limit.',
      { cause: error }
    );
  }

  const actualVersion = await verifyAcf(acfRoot);
  console.log(`Verified official ACF PRO ${actualVersion}.`);

  return acfRoot;
};

const main = async () => {
  const acfEdition = await resolveAcfEdition();

  if (WORDPRESS_URL !== DEFAULT_WORDPRESS_URL && !WORDPRESS_SHA256) {
    throw new Error('REACTWP_WORDPRESS_SHA256 is required when using a custom WordPress archive URL.');
  }

  if (acfEdition !== 'pro' && usesPrivateAcfArchive) {
    throw new Error('REACTWP_ACF_URL can only be used with REACTWP_ACF_EDITION=pro.');
  }

  if (acfEdition === 'pro' && usesPrivateAcfArchive && (!configuredAcfVersion || !ACF_SHA256)) {
    throw new Error('REACTWP_ACF_VERSION and REACTWP_ACF_SHA256 are required when overriding ACF PRO with a private archive.');
  }

  if (acfEdition === 'pro' && !usesPrivateAcfArchive) {
    await collectAcfLicenseCredentials();
  }

  const tempRoot = await fs.mkdtemp(path.join(os.tmpdir(), 'reactwp-core-'));
  const wordpressZip = path.join(tempRoot, 'wordpress.zip');
  const acfZip = path.join(tempRoot, 'acf.zip');
  const extractRoot = path.join(tempRoot, 'extract');
  const wordpressRoot = path.join(extractRoot, 'wordpress');

  try {
    console.log(`Project root: ${projectRoot}`);
    console.log(`Dist root: ${distRoot}`);

    console.log('Downloading WordPress core over HTTPS...');
    await download(WORDPRESS_URL, wordpressZip);

    if (WORDPRESS_SHA256) {
      await verifyDigest(wordpressZip, WORDPRESS_SHA256, 'sha256');
    }

    console.log('Extracting WordPress core...');
    await extractZip(wordpressZip, extractRoot);
    const wordpressVerification = await verifyWordPress(wordpressRoot);
    let acfRoot = '';

    let acfDirectory = '';

    if (acfEdition === 'pro' && usesPrivateAcfArchive) {
      console.log(`Downloading pinned ACF PRO ${ACF_VERSION} over HTTPS...`);
      await download(ACF_URL, acfZip);
      await verifyDigest(acfZip, ACF_SHA256, 'sha256');

      const acfExtractRoot = path.join(tempRoot, 'acf-extract');
      await extractZip(acfZip, acfExtractRoot);
      acfRoot = path.join(acfExtractRoot, 'advanced-custom-fields-pro');
      await verifyAcf(acfRoot);
      acfDirectory = 'advanced-custom-fields-pro';
    } else if (acfEdition === 'pro') {
      console.log(`Installing ${ACF_VERSION ? `ACF PRO ${ACF_VERSION}` : 'the latest ACF PRO release'} from the official Composer repository...`);
      acfRoot = await installAcfWithComposer(tempRoot);
      acfDirectory = 'advanced-custom-fields-pro';
    } else if (acfEdition === 'free') {
      acfRoot = await installFreeAcf(tempRoot);
      acfDirectory = 'advanced-custom-fields';
    } else {
      console.log('Keeping the existing ACF installation unchanged.');
    }

    console.log('Copying verified WordPress core to dist...');
    await replaceWordPressCore(wordpressRoot, wordpressVerification);

    if (acfRoot) {
      console.log(`Installing verified ${acfDirectory === 'advanced-custom-fields-pro' ? 'ACF PRO' : 'ACF Free'}...`);
      const muPluginsRoot = path.join(distRoot, 'wp-content', 'mu-plugins');
      const destination = path.join(muPluginsRoot, acfDirectory);
      const replacedEdition = path.join(
        muPluginsRoot,
        acfDirectory === 'advanced-custom-fields-pro'
          ? 'advanced-custom-fields'
          : 'advanced-custom-fields-pro'
      );
      await fs.mkdir(path.dirname(destination), { recursive: true });
      await fs.rm(replacedEdition, { recursive: true, force: true });
      await fs.rm(destination, { recursive: true, force: true });
      await fs.cp(acfRoot, destination, { recursive: true, force: true });
    }

    console.log('ReactWP core is ready.');
  } finally {
    await fs.rm(tempRoot, { recursive: true, force: true });
  }
};

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
