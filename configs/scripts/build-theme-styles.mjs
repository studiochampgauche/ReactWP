import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawn } from 'node:child_process';
import imagemin from 'imagemin';
import imageminGifsicle from 'imagemin-gifsicle';
import imageminMozjpeg from 'imagemin-mozjpeg';
import imageminPngquant from 'imagemin-pngquant';
import imageminSvgo from 'imagemin-svgo';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const configsRoot = path.resolve(__dirname, '..');
const projectRoot = path.resolve(configsRoot, '..');
const sassCli = path.resolve(configsRoot, 'node_modules', 'sass', 'sass.js');

const themes = ['reactwp'];
const mode = process.argv[2] || 'build';
const imageExtensions = new Set(['.gif', '.jpg', '.jpeg', '.png', '.svg']);

const createMappings = () => themes.map((themeName) => {
  const input = path.resolve(projectRoot, 'src', 'themes', themeName, 'scss', 'default.scss');
  const output = path.resolve(projectRoot, 'dist', 'wp-content', 'themes', themeName, 'assets', 'css', `${themeName}.min.css`);
  const legacyOutput = path.resolve(projectRoot, 'dist', 'wp-content', 'themes', themeName, 'assets', 'css', `${themeName}.css`);
  const mediasRoot = path.resolve(projectRoot, 'src', 'themes', themeName, 'medias');
  const assetsRoot = path.resolve(projectRoot, 'dist', 'wp-content', 'themes', themeName, 'assets');

  return { input, output, legacyOutput, mediasRoot, assetsRoot };
});

const getImagePlugins = (extension) => {
  switch (extension) {
    case '.jpg':
    case '.jpeg':
      return [imageminMozjpeg({ quality: 82, progressive: true })];
    case '.png':
      return [imageminPngquant({ quality: [0.72, 0.88], speed: 1 })];
    case '.gif':
      return [imageminGifsicle({ optimizationLevel: 3 })];
    case '.svg':
      return [imageminSvgo({
        plugins: [{
          name: 'preset-default',
          params: {
            overrides: {
              removeViewBox: false
            }
          }
        }]
      })];
    default:
      return [];
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
        const plugins = getImagePlugins(extension);

        if (!plugins.length) {
          return;
        }

        const input = await fs.readFile(assetPath);
        const output = await imagemin.buffer(input, { plugins });

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

  child.on('exit', (code) => {
    process.exit(code ?? 0);
  });
};

run().catch((error) => {
  console.error(error);
  process.exit(1);
});