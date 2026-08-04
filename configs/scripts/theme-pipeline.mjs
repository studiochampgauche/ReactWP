import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawn } from 'node:child_process';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const configsRoot = path.resolve(__dirname, '..');
const webpackCli = path.resolve(configsRoot, 'node_modules', 'webpack-cli', 'bin', 'cli.js');
const stylesScript = path.resolve(__dirname, 'build-theme-styles.mjs');
const bundleReportScript = path.resolve(__dirname, 'report-bundle-sizes.mjs');
const staticGeneratorScript = path.resolve(__dirname, 'generate-static.mjs');

const mode = process.argv[2] || 'build';
const webpackMode = mode === 'prod' ? 'production' : 'development';
const isWatch = mode === 'watch';

const children = [];

const spawnChild = (label, command, args) => {
  const child = spawn(command, args, {
    cwd: configsRoot,
    stdio: 'inherit'
  });

  child.on('error', (error) => {
    console.error(`[${label}]`, error);
    process.exit(1);
  });

  children.push(child);
  return child;
};

const shutdown = () => {
  children.forEach((child) => child.kill('SIGINT'));
};

process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);

const jsProcess = spawnChild('themes:js', process.execPath, [
  webpackCli,
  '--mode',
  webpackMode,
  '--config',
  'webpack.themes.config.js',
  ...(isWatch ? ['--watch'] : [])
]);

const cssProcess = spawnChild('themes:css', process.execPath, [
  stylesScript,
  mode
]);

const renderProcess = spawnChild('themes:render', process.execPath, [
  webpackCli,
  '--mode',
  webpackMode,
  '--config',
  'webpack.render.config.js',
  ...(isWatch ? ['--watch'] : [])
]);

if (isWatch) {
  jsProcess.on('exit', (code) => {
    if (code && code !== 0) {
      process.exit(code);
    }
  });

  cssProcess.on('exit', (code) => {
    if (code && code !== 0) {
      process.exit(code);
    }
  });

  renderProcess.on('exit', (code) => {
    if (code && code !== 0) {
      process.exit(code);
    }
  });
} else {
  let completed = 0;

  const handleExit = (code) => {
    if (code && code !== 0) {
      process.exit(code);
    }

    completed += 1;

    if (completed === 3) {
      if (mode !== 'prod') {
        process.exit(0);
        return;
      }

      const reportProcess = spawnChild('themes:report', process.execPath, [bundleReportScript]);

      reportProcess.on('exit', (reportCode) => {
        if(reportCode && reportCode !== 0){
          process.exit(reportCode);
          return;
        }

        if(!process.env.RWP_SITE_URL){
          process.stdout.write('[themes:ssg] Skipped. Set RWP_SITE_URL to generate static routes during production builds.\n');
          process.exit(0);
          return;
        }

        const staticProcess = spawnChild('themes:ssg', process.execPath, [staticGeneratorScript]);
        staticProcess.on('exit', (staticCode) => process.exit(staticCode ?? 0));
      });
    }
  };

  jsProcess.on('exit', handleExit);
  cssProcess.on('exit', handleExit);
  renderProcess.on('exit', handleExit);
}
