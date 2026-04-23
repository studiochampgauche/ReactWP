import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import CopyPlugin from 'copy-webpack-plugin';
import TerserPlugin from 'terser-webpack-plugin';
import ImageMinimizerPlugin from 'image-minimizer-webpack-plugin';

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
  copyPatterns = []
}) => {
  return (_env, argv = {}) => {
    const mode = argv.mode || 'development';
    const isProduction = mode === 'production';
    const filesystemCacheDirectory = resolveFromConfig('node_modules', '.cache', 'webpack');
    const filesystemCacheName = `${normalizeCacheName(cacheName)}-${mode}`;

    return {
      mode,
      cache: {
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
        filename: '[name]/assets/js/[name].min.js',
        path: resolveFromConfig(outputDirectory),
        chunkFilename: (pathData) => {
          const runtime = pathData.chunk?.runtime;
          const itemName = Array.isArray(runtime) ? runtime[0] : runtime;

          return `${itemName || 'shared'}/assets/js/chunks/[name].[contenthash].js`;
        },
        publicPath
      },
      module: {
        rules: [
          {
            test: /\.jsx?$/i,
            exclude: /node_modules/,
            use: {
              loader: 'babel-loader'
            }
          },
          {
            test: /\.s[ac]ss$/i,
            use: [
              'style-loader',
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
