import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { brotliCompressSync, constants, gzipSync } from 'node:zlib';
import CopyPlugin from 'copy-webpack-plugin';
import TerserPlugin from 'terser-webpack-plugin';
import ImageMinimizerPlugin from 'image-minimizer-webpack-plugin';
import MiniCssExtractPlugin from 'mini-css-extract-plugin';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const normalizeCacheName = (value = 'bundle') => {
  return String(value)
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9-]+/g, '-')
    .replace(/^-+|-+$/g, '') || 'bundle';
};

const fileBelongsToItem = (filename = '', item) => {
  return filename.includes(`/${item}/`) || filename.includes(`\\${item}\\`);
};

const resolveChunkItem = (items, chunk) => {
  const runtime = chunk?.runtime;
  const runtimeNames = typeof runtime === 'string'
    ? [runtime]
    : runtime && typeof runtime[Symbol.iterator] === 'function'
      ? [...runtime]
      : [];

  return runtimeNames.find((runtimeName) => items.includes(runtimeName))
    || items.find((itemName) => chunk?.name === itemName)
    || items[0]
    || 'shared';
};

const createChunkFilename = (items, folder, extension, isProduction) => {
  return (pathData) => {
    const itemName = resolveChunkItem(items, pathData.chunk);
    const suffix = isProduction
      ? `.[contenthash].min.${extension}`
      : `.${extension}`;

    return `${itemName}/assets/${folder}/chunks/[name]${suffix}`;
  };
};

const createInitialFilename = (items, isProduction) => {
  return (pathData) => {
    const itemName = resolveChunkItem(items, pathData.chunk);

    if(pathData.chunk?.name === itemName){
      return isProduction
        ? `${itemName}/assets/js/${itemName}.min.js`
        : `${itemName}/assets/js/${itemName}.js`;
    }

    const suffix = isProduction
      ? '.[contenthash].min.js'
      : '.js';

    return `${itemName}/assets/js/chunks/[name]${suffix}`;
  };
};

const createLegacyChunkFilename = (items) => {
  return (pathData) => {
    const itemName = resolveChunkItem(items, pathData.chunk);

    return `${itemName}/assets/js/chunks/[name].[contenthash].js`;
  };
};

class EntrypointManifestPlugin {
  constructor(items){
    this.items = items;
  }

  apply(compiler){
    const pluginName = 'ReactWPEntrypointManifestPlugin';

    compiler.hooks.thisCompilation.tap(pluginName, (compilation) => {
      compilation.hooks.processAssets.tap(
        {
          name: pluginName,
          stage: compiler.webpack.Compilation.PROCESS_ASSETS_STAGE_SUMMARIZE
        },
        () => {
          this.items.forEach((itemName) => {
            const entrypoint = compilation.entrypoints.get(itemName);

            if(!entrypoint){
              return;
            }

            const prefix = `${itemName}/`;
            const files = entrypoint.getFiles().map((filename) => {
              return filename.startsWith(prefix)
                ? filename.slice(prefix.length)
                : filename;
            });
            const manifest = {
              mode: compiler.options.mode,
              scripts: files.filter((filename) => filename.endsWith('.js')),
              styles: files.filter((filename) => filename.endsWith('.css'))
            };

            compilation.emitAsset(
              `${itemName}/assets/js/entrypoints.json`,
              new compiler.webpack.sources.RawSource(`${JSON.stringify(manifest, null, 2)}\n`)
            );
          });
        }
      );
    });
  }
}

class PrecompressedAssetsPlugin {
  apply(compiler){
    const pluginName = 'ReactWPPrecompressedAssetsPlugin';

    compiler.hooks.thisCompilation.tap(pluginName, (compilation) => {
      compilation.hooks.processAssets.tap(
        {
          name: pluginName,
          stage: compiler.webpack.Compilation.PROCESS_ASSETS_STAGE_OPTIMIZE_TRANSFER
        },
        () => {
          compilation.getAssets()
            .filter(({ name }) => /\.(?:css|js)$/.test(name))
            .forEach(({ name, source }) => {
              const buffer = Buffer.from(source.buffer());
              const gzipSource = gzipSync(buffer, { level: 9 });
              const brotliSource = brotliCompressSync(buffer, {
                params: {
                  [constants.BROTLI_PARAM_QUALITY]: 11
                }
              });

              compilation.emitAsset(
                `${name}.gz`,
                new compiler.webpack.sources.RawSource(gzipSource)
              );
              compilation.emitAsset(
                `${name}.br`,
                new compiler.webpack.sources.RawSource(brotliSource)
              );
            });
        }
      );
    });
  }
}

class BuildOutputCleanupPlugin {
  constructor({ items, isProduction, cleanupAlternateEntry = false }){
    this.items = items;
    this.isProduction = isProduction;
    this.cleanupAlternateEntry = cleanupAlternateEntry;
  }

  apply(compiler){
    const pluginName = 'ReactWPBuildOutputCleanupPlugin';

    compiler.hooks.afterEmit.tapPromise(pluginName, async (compilation) => {
      const outputPath = compiler.options.output.path;
      const emittedAssets = new Set(
        [...compilation.getAssets()].map(({ name }) => name.replace(/\\/g, '/'))
      );

      if(this.cleanupAlternateEntry){
        await Promise.all(this.items.flatMap((itemName) => {
          const alternateFilename = this.isProduction
            ? `${itemName}.js`
            : `${itemName}.min.js`;
          const alternatePath = path.join(outputPath, itemName, 'assets', 'js', alternateFilename);
          const cleanupPaths = [
            alternatePath,
            `${alternatePath}.br`,
            `${alternatePath}.gz`,
            `${alternatePath}.map`,
            `${alternatePath}.LICENSE.txt`
          ];

          return cleanupPaths.map((cleanupPath) => fs.promises.rm(cleanupPath, { force: true }));
        }));
      }

      await Promise.all(this.items.flatMap((itemName) => {
        return ['js', 'css'].map(async (folder) => {
          const chunksPath = path.join(outputPath, itemName, 'assets', folder, 'chunks');
          let filenames = [];

          try {
            filenames = await fs.promises.readdir(chunksPath);
          } catch(error){
            if(error?.code === 'ENOENT'){
              return;
            }

            throw error;
          }

          await Promise.all(filenames.map((filename) => {
            const assetName = `${itemName}/assets/${folder}/chunks/${filename}`;

            if(emittedAssets.has(assetName)){
              return Promise.resolve();
            }

            return fs.promises.rm(path.join(chunksPath, filename), { force: true });
          }));
        });
      }));
    });
  }
}

const assetGenerator = (items, folder) => ({
  filename: (pathData) => {
    const file = pathData.filename || '';
    const itemName = items.find((item) => fileBelongsToItem(file, item)) || 'shared';
    const ext = path.extname(file);
    const name = path.basename(file, ext);

    return `${itemName}/assets/${folder}/${name}${ext}`;
  }
});

export const resolveFromConfig = (...segments) => path.resolve(__dirname, ...segments);

const resolveEntryFile = (baseDirectory, itemName) => {
  const jsxEntry = resolveFromConfig(baseDirectory, itemName, 'js/App.jsx');
  const jsEntry = resolveFromConfig(baseDirectory, itemName, 'js/App.js');

  return fs.existsSync(jsxEntry) ? jsxEntry : jsEntry;
};

export const createBundleConfig = ({
  cacheName = 'bundle',
  items,
  inputDirectory,
  outputDirectory,
  templateDirectory,
  publicPath,
  extraEntries = () => [],
  copyPatterns = [],
  optimizeInitialBundle = false
}) => {
  return (_env, argv = {}) => {
    const mode = argv.mode || 'development';
    const isProduction = mode === 'production';
    const filesystemCacheDirectory = resolveFromConfig('node_modules', '.cache', 'webpack');
    const filesystemCacheName = `${normalizeCacheName(cacheName)}-${mode}`;

    return {
      mode,
      cache: isProduction
        ? false
        : {
            type: 'filesystem',
            cacheDirectory: filesystemCacheDirectory,
            name: filesystemCacheName
          },
      devtool: isProduction ? false : 'source-map',
      entry: items.reduce((entries, itemName) => {
        entries[itemName] = [
          resolveEntryFile(inputDirectory, itemName),
          resolveFromConfig(inputDirectory, itemName, 'medias/Medias.js'),
          ...extraEntries(itemName)
        ];

        return entries;
      }, {}),
      output: {
        filename: optimizeInitialBundle
          ? createInitialFilename(items, isProduction)
          : '[name]/assets/js/[name].min.js',
        path: resolveFromConfig(outputDirectory),
        chunkFilename: optimizeInitialBundle
          ? createChunkFilename(items, 'js', 'js', isProduction)
          : createLegacyChunkFilename(items),
        publicPath
      },
      module: {
        rules: [
          {
            test: /\.jsx?$/i,
            exclude: /node_modules/,
            use: {
              loader: 'babel-loader',
              options: {
                envName: isProduction ? 'production' : 'development'
              }
            }
          },
          {
            test: /\.s[ac]ss$/i,
            use: [
              optimizeInitialBundle ? MiniCssExtractPlugin.loader : 'style-loader',
              'css-loader',
              'sass-loader'
            ]
          },
          {
            test: /\.(png|jpe?g|gif|svg|webp)$/i,
            type: 'asset/resource',
            generator: assetGenerator(items, 'images')
          },
          {
            test: /\.(mp4|webm)$/i,
            type: 'asset/resource',
            generator: assetGenerator(items, 'videos')
          },
          {
            test: /\.(mp3|wav|ogg)$/i,
            type: 'asset/resource',
            generator: assetGenerator(items, 'audios')
          },
          {
            test: /\.(woff2?|eot|ttf|otf)$/i,
            type: 'asset/resource',
            generator: assetGenerator(items, 'fonts')
          }
        ]
      },
      plugins: [
        ...(optimizeInitialBundle
          ? [
              new MiniCssExtractPlugin({
                filename: createChunkFilename(items, 'css', 'css', isProduction),
                chunkFilename: createChunkFilename(items, 'css', 'css', isProduction)
              }),
              new EntrypointManifestPlugin(items),
              ...(isProduction ? [new PrecompressedAssetsPlugin()] : [])
            ]
          : []),
        new BuildOutputCleanupPlugin({
          items,
          isProduction,
          cleanupAlternateEntry: optimizeInitialBundle
        }),
        new CopyPlugin({
          patterns: [
            ...copyPatterns,
            ...items.map((itemName) => ({
              from: resolveFromConfig(templateDirectory, itemName, 'template'),
              to: resolveFromConfig(outputDirectory, itemName),
              noErrorOnMissing: true
            }))
          ]
        })
      ],
      optimization: {
        minimize: isProduction,
        splitChunks: optimizeInitialBundle
          ? {
              chunks: 'all',
              cacheGroups: {
                default: false,
                defaultVendors: false,
                framework: {
                  test: /[\\/]node_modules[\\/](?:react|react-dom|scheduler)[\\/]/,
                  name: 'framework',
                  priority: 40,
                  enforce: true
                },
                router: {
                  test: /[\\/]node_modules[\\/](?:react-router|react-router-dom)[\\/]/,
                  name: 'router',
                  priority: 30,
                  enforce: true
                },
                motion: {
                  test: /[\\/]node_modules[\\/]gsap[\\/]/,
                  name: 'motion',
                  priority: 20,
                  enforce: true
                },
                vendors: {
                  test: /[\\/]node_modules[\\/]/,
                  name: 'vendors',
                  priority: 10,
                  reuseExistingChunk: true
                }
              }
            }
          : false,
        minimizer: isProduction
          ? [
              new ImageMinimizerPlugin({
                minimizer: {
                  implementation: ImageMinimizerPlugin.imageminMinify,
                  options: {
                    plugins: [
                      ['gifsicle', { interlaced: true }],
                      ['jpegtran', { progressive: true }],
                      ['mozjpeg', { quality: 78 }],
                      ['optipng', { optimizationLevel: 5 }],
                      ['pngquant', { quality: [0.7, 0.9], speed: 4 }],
                      ['svgo', { plugins: [{ name: 'preset-default', params: { overrides: { removeViewBox: false } } }] }]
                    ]
                  }
                }
              }),
              new TerserPlugin()
            ]
          : []
      },
      performance: optimizeInitialBundle
        ? {
            hints: isProduction ? 'warning' : false,
            maxAssetSize: 250 * 1024,
            maxEntrypointSize: 500 * 1024
          }
        : undefined,
      resolve: {
        modules: [
          resolveFromConfig('node_modules')
        ],
        extensions: ['.js', '.jsx', '.scss', '.css'],
        alias: {
          '@theme': resolveFromConfig('../src/themes/reactwp/js'),
          '@runtime': resolveFromConfig('../src/mu-plugins/plugins/reactwp/template/inc/runtime')
        }
      },
      stats: 'errors-warnings'
    };
  };
};
