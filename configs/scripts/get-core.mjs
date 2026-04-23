import fs from 'node:fs/promises';
import { createWriteStream } from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawn } from 'node:child_process';
import https from 'node:https';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const projectRoot = path.resolve(__dirname, '../..');
const distRoot = path.resolve(projectRoot, 'dist');

const WORDPRESS_URL = process.env.REACTWP_WORDPRESS_URL || 'https://wordpress.org/latest.zip';
const ACF_URL = process.env.REACTWP_ACF_URL || 'https://reactwp.com/download/plugins/advanced-custom-fields-pro.zip';
const shouldSkipAcf = process.env.REACTWP_SKIP_ACF === '1';

const run = (command, args) => new Promise((resolve, reject) => {
  const child = spawn(command, args, {
    stdio: 'inherit'
  });

  child.on('error', reject);
  child.on('exit', (code) => {
    if (code && code !== 0) {
      reject(new Error(`${command} exited with code ${code}`));
      return;
    }

    resolve();
  });
});

const download = (url, destination) => new Promise((resolve, reject) => {
  const request = https.get(url, (response) => {
    if (response.statusCode && response.statusCode >= 300 && response.statusCode < 400 && response.headers.location) {
      const redirectUrl = new URL(response.headers.location, url).toString();
      download(redirectUrl, destination).then(resolve).catch(reject);
      return;
    }

    if (response.statusCode !== 200) {
      reject(new Error(`Download failed for ${url}: ${response.statusCode}`));
      return;
    }

    const file = createWriteStream(destination);
    response.pipe(file);

    file.on('finish', () => {
      file.close(resolve);
    });

    file.on('error', reject);
  });

  request.on('error', reject);
});

const extractZip = async (zipFile, targetDirectory) => {
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

const main = async () => {
  const tempRoot = await fs.mkdtemp(path.join(os.tmpdir(), 'reactwp-core-'));
  const wordpressZip = path.join(tempRoot, 'wordpress.zip');
  const acfZip = path.join(tempRoot, 'acf.zip');
  const extractRoot = path.join(tempRoot, 'extract');

  try {
    console.log(`Project root: ${projectRoot}`);
    console.log(`Dist root: ${distRoot}`);

    await fs.mkdir(path.join(distRoot, 'wp-content', 'mu-plugins'), { recursive: true });

    console.log('Downloading WordPress core...');
    await download(WORDPRESS_URL, wordpressZip);

    console.log('Extracting WordPress core...');
    await extractZip(wordpressZip, extractRoot);

    console.log('Copying WordPress core to dist...');
    await fs.cp(path.join(extractRoot, 'wordpress'), distRoot, {
      recursive: true,
      force: true
    });

    console.log('Removing bundled default content...');
    await fs.rm(path.join(distRoot, 'wp-content', 'plugins', 'akismet'), { recursive: true, force: true });
    await fs.rm(path.join(distRoot, 'wp-content', 'plugins', 'hello.php'), { force: true });
    await fs.rm(path.join(distRoot, 'wp-content', 'themes', 'twentytwentyfive'), { recursive: true, force: true });
    await fs.rm(path.join(distRoot, 'wp-content', 'themes', 'twentytwentyfour'), { recursive: true, force: true });
    await fs.rm(path.join(distRoot, 'wp-content', 'themes', 'twentytwentythree'), { recursive: true, force: true });

    if (!shouldSkipAcf) {
      console.log('Refreshing bundled ACF PRO...');
      await fs.rm(path.join(distRoot, 'wp-content', 'mu-plugins', 'advanced-custom-fields-pro'), { recursive: true, force: true });
      await download(ACF_URL, acfZip);
      await extractZip(acfZip, path.join(distRoot, 'wp-content', 'mu-plugins'));
    } else {
      console.log('Skipping ACF PRO download because REACTWP_SKIP_ACF=1.');
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
