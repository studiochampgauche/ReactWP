import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawn } from 'node:child_process';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const configsRoot = path.resolve(__dirname, '..');
const webpackCli = path.resolve(configsRoot, 'node_modules', 'webpack-cli', 'bin', 'cli.js');
const themePipeline = path.resolve(__dirname, 'theme-pipeline.mjs');

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

const tasks = [
  spawnChild('themes', process.execPath, [themePipeline, mode]),
  spawnChild('plugins', process.execPath, [
    webpackCli,
    '--mode',
    webpackMode,
    '--config',
    'webpack.plugins.config.js',
    ...(isWatch ? ['--watch'] : [])
  ]),
  spawnChild('mu-plugins', process.execPath, [
    webpackCli,
    '--mode',
    webpackMode,
    '--config',
    'webpack.mu-plugins.config.js',
    ...(isWatch ? ['--watch'] : [])
  ])
];

if (isWatch) {
  tasks.forEach((task) => {
    task.on('exit', (code) => {
      if (code && code !== 0) {
        process.exit(code);
      }
    });
  });
} else {
  let completed = 0;

  const handleExit = (code) => {
    if (code && code !== 0) {
      process.exit(code);
    }

    completed += 1;

    if (completed === tasks.length) {
      process.exit(0);
    }
  };

  tasks.forEach((task) => {
    task.on('exit', handleExit);
  });
}
