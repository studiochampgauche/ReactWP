import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawn } from 'node:child_process';
import { brotliCompressSync, constants, gzipSync } from 'node:zlib';
import sharp from 'sharp';
import { optimize as optimizeSvg } from 'svgo';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const configsRoot = path.resolve(__dirname, '..');
const projectRoot = path.resolve(configsRoot, '..');
const sassCli = path.resolve(configsRoot, 'node_modules', 'sass', 'sass.js');

const themes = ['reactwp'];
const mode = process.argv[2] || 'build';
const imageExtensions = new Set(['.gif', '.jpg', '.jpeg', '.png', '.svg', '.webp']);

const createMappings = () => themes.map((themeName) => {
  const input = path.resolve(projectRoot, 'src', 'themes', themeName, 'scss', 'default.scss');
  const output = path.resolve(projectRoot, 'dist', 'wp-content', 'themes', themeName, 'assets', 'css', `${themeName}.min.css`);
  const legacyOutput = path.resolve(projectRoot, 'dist', 'wp-content', 'themes', themeName, 'assets', 'css', `${themeName}.css`);
  const mediasRoot = path.resolve(projectRoot, 'src', 'themes', themeName, 'medias');
  const assetsRoot = path.resolve(projectRoot, 'dist', 'wp-content', 'themes', themeName, 'assets');

  return { input, output, legacyOutput, mediasRoot, assetsRoot };
});

const optimizeImage = async (input, extension, assetPath) => {
  switch (extension) {
    case '.jpg':
    case '.jpeg':
      return sharp(input).autoOrient().jpeg({ quality: 82, progressive: true }).toBuffer();
    case '.png':
      return sharp(input).autoOrient().png({ quality: 88, effort: 10 }).toBuffer();
    case '.gif':
      return sharp(input, { animated: true }).autoOrient().gif({ effort: 10 }).toBuffer();
    case '.webp':
      return sharp(input, { animated: true }).autoOrient().webp({ quality: 82, effort: 6 }).toBuffer();
    case '.svg':
      return Buffer.from(optimizeSvg(input.toString('utf8'), {
        path: assetPath,
        multipass: true,
        plugins: [{
          name: 'preset-default',
          params: {
            overrides: {
              removeViewBox: false
            }
          }
        }]
      }).data);
    default:
      return input;
  }
};

const listFilesRecursive = async (directory) => {
  try {
    const entries = await fs.readdir(directory, { withFileTypes: true });
    const children = await Promise.all(
      entries.map(async (entry) => {
        const entryPath = path.join(directory, entry.name);

        if (entry.isDirectory()) {
          return listFilesRecursive(entryPath);
        }

        return [entryPath];
      })
    );

    return children.flat();
  } catch (error) {
    if (error && error.code === 'ENOENT') {
      return [];
    }

    throw error;
  }
};

const copyThemeMediaAssets = async (mappings) => {
  const copiedRoots = await Promise.all(
    mappings.map(async ({ mediasRoot, assetsRoot }) => {
      try {
        const mediaEntries = await fs.readdir(mediasRoot, { withFileTypes: true });
        const directories = mediaEntries
          .filter((entry) => entry.isDirectory())
          .map((entry) => ({
            from: path.join(mediasRoot, entry.name),
            to: path.join(assetsRoot, entry.name)
          }));

        await Promise.all(
          directories.map(({ from, to }) => fs.cp(from, to, {
            recursive: true,
            force: true
          }))
        );

        return directories.map(({ to }) => to);
      } catch (error) {
        if (error && error.code === 'ENOENT') {
          return [];
        }

        throw error;
      }
    })
  );

  return copiedRoots.flat();
};

const optimizeRasterImages = async (assetPaths) => {
  await Promise.all(
    assetPaths
      .filter((assetPath) => imageExtensions.has(path.extname(assetPath).toLowerCase()))
      .map(async (assetPath) => {
        const extension = path.extname(assetPath).toLowerCase();
        const input = await fs.readFile(assetPath);
        const output = await optimizeImage(input, extension, assetPath);

        if (output.length <= input.length) {
          await fs.writeFile(assetPath, output);
        }
      })
  );
};

const optimizeThemeMediaAssets = async (copiedRoots) => {
  if (!copiedRoots.length) {
    return;
  }

  const assetPaths = (await Promise.all(copiedRoots.map((copiedRoot) => listFilesRecursive(copiedRoot)))).flat();

  if (!assetPaths.length) {
    return;
  }

  await optimizeRasterImages(assetPaths);
};

const removePrecompressedStyles = async (mappings) => {
  await Promise.all(
    mappings.flatMap(({ output }) => [
      fs.rm(`${output}.br`, { force: true }),
      fs.rm(`${output}.gz`, { force: true })
    ])
  );
};

const precompressStyles = async (mappings) => {
  await Promise.all(
    mappings.map(async ({ output }) => {
      const buffer = await fs.readFile(output);
      const gzipSource = gzipSync(buffer, { level: 9 });
      const brotliSource = brotliCompressSync(buffer, {
        params: {
          [constants.BROTLI_PARAM_QUALITY]: 11
        }
      });

      await Promise.all([
        fs.writeFile(`${output}.br`, brotliSource),
        fs.writeFile(`${output}.gz`, gzipSource)
      ]);
    })
  );
};

const run = async () => {
  const mappings = createMappings();

  await Promise.all(
    mappings.map(({ output }) => fs.mkdir(path.dirname(output), { recursive: true }))
  );

  await Promise.all(
    mappings.map(async ({ legacyOutput, output }) => {
      if (legacyOutput !== output) {
        await fs.rm(legacyOutput, { force: true });
      }
    })
  );

  if (mode !== 'prod') {
    await removePrecompressedStyles(mappings);
  }

  const copiedRoots = await copyThemeMediaAssets(mappings);

  if (mode === 'prod') {
    await optimizeThemeMediaAssets(copiedRoots);
  }

  const args = [
    sassCli
  ];

  if (mode === 'watch') {
    args.push('--watch', '--style=expanded');
  } else if (mode === 'prod') {
    args.push('--style=compressed', '--no-source-map');
  } else {
    args.push('--style=expanded');
  }

  mappings.forEach(({ input, output }) => {
    args.push(`${input}:${output}`);
  });

  const child = spawn(process.execPath, args, {
    cwd: configsRoot,
    stdio: 'inherit'
  });

  child.on('exit', async (code) => {
    if (code && code !== 0) {
      process.exit(code);
      return;
    }

    if (mode === 'prod') {
      await precompressStyles(mappings);
    }

    process.exit(0);
  });
};

run().catch((error) => {
  console.error(error);
  process.exit(1);
});
