import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawn } from 'node:child_process';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const configsRoot = path.resolve(__dirname, '..');
const projectRoot = path.resolve(configsRoot, '..');
const sassCli = path.resolve(configsRoot, 'node_modules', 'sass', 'sass.js');

const themes = ['reactwp'];
const mode = process.argv[2] || 'build';

const createMappings = () => themes.map((themeName) => {
  const input = path.resolve(projectRoot, 'src', 'themes', themeName, 'scss', 'default.scss');
  const output = path.resolve(projectRoot, 'dist', 'wp-content', 'themes', themeName, 'assets', 'css', `${themeName}.min.css`);
  const legacyOutput = path.resolve(projectRoot, 'dist', 'wp-content', 'themes', themeName, 'assets', 'css', `${themeName}.css`);
  const mediasRoot = path.resolve(projectRoot, 'src', 'themes', themeName, 'medias');
  const assetsRoot = path.resolve(projectRoot, 'dist', 'wp-content', 'themes', themeName, 'assets');

  return { input, output, legacyOutput, mediasRoot, assetsRoot };
});

const copyThemeMediaAssets = async (mappings) => {
  await Promise.all(
    mappings.map(async ({ mediasRoot, assetsRoot }) => {
      try {
        const mediaEntries = await fs.readdir(mediasRoot, { withFileTypes: true });

        await Promise.all(
          mediaEntries
            .filter((entry) => entry.isDirectory())
            .map((entry) => fs.cp(
              path.join(mediasRoot, entry.name),
              path.join(assetsRoot, entry.name),
              {
                recursive: true,
                force: true
              }
            ))
        );
      } catch (error) {
        if (error && error.code === 'ENOENT') {
          return;
        }

        throw error;
      }
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
      if(legacyOutput !== output){
        await fs.rm(legacyOutput, { force: true });
      }
    })
  );

  await copyThemeMediaAssets(mappings);

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
