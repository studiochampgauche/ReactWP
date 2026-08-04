import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';
import CopyPlugin from 'copy-webpack-plugin';
import TerserPlugin from 'terser-webpack-plugin';
import { themes } from './webpack.themes.config.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const require = createRequire(import.meta.url);

const resolveConfig = (...segments) => path.resolve(__dirname, ...segments);

class TemplateRenderManifestPlugin {
    constructor(renderDirectory = 'assets/render'){
        this.renderDirectory = renderDirectory;
    }

    apply(compiler){
        compiler.hooks.afterEmit.tapPromise('ReactWPTemplateRenderManifestPlugin', async () => {
            const outputPath = compiler.options.output.path;
            const renderPath = path.join(outputPath, this.renderDirectory);
            const bundlePath = path.join(renderPath, 'server.cjs');

            Object.keys(require.cache).forEach((modulePath) => {
                if(modulePath.startsWith(outputPath)){
                    delete require.cache[modulePath];
                }
            });

            const renderer = require(bundlePath);
            const templates = typeof renderer.getTemplateManifest === 'function'
                ? renderer.getTemplateManifest()
                : {};
            const manifest = {
                version: 1,
                generatedAt: new Date().toISOString(),
                templates
            };

            await fs.promises.writeFile(
                path.join(renderPath, 'templates.json'),
                `${JSON.stringify(manifest, null, 2)}\n`,
                'utf8'
            );

            await Promise.all([
                fs.promises.writeFile(
                    path.join(renderPath, '.htaccess'),
                    'Require all denied\n',
                    'utf8'
                ),
                fs.promises.writeFile(
                    path.join(renderPath, 'web.config'),
                    '<?xml version="1.0" encoding="UTF-8"?>\n<configuration><system.webServer><security><authorization><remove users="*" roles="" verbs="" /><add accessType="Deny" users="*" /></authorization></security></system.webServer></configuration>\n',
                    'utf8'
                ),
                fs.promises.writeFile(
                    path.join(renderPath, 'index.php'),
                    '<?php\nhttp_response_code(404);\nexit;\n',
                    'utf8'
                )
            ]);
        });
    }
}

class RenderChunkCleanupPlugin {
    constructor(renderDirectory = 'assets/render'){
        this.renderDirectory = renderDirectory;
    }

    apply(compiler){
        compiler.hooks.afterEmit.tapPromise('ReactWPRenderChunkCleanupPlugin', async (compilation) => {
            const chunksPrefix = `${this.renderDirectory.replace(/\\/g, '/')}/chunks/`;
            const chunksDirectory = path.join(compiler.options.output.path, this.renderDirectory, 'chunks');
            const emitted = new Set(
                compilation.getAssets()
                    .map(({ name }) => name.replace(/\\/g, '/'))
                    .filter((name) => name.startsWith(chunksPrefix))
                    .map((name) => path.basename(name))
            );
            let files = [];

            try{
                files = await fs.promises.readdir(chunksDirectory);
            } catch(error){
                if(error?.code === 'ENOENT'){
                    return;
                }

                throw error;
            }

            await Promise.all(files.map((file) => {
                return emitted.has(file)
                    ? Promise.resolve()
                    : fs.promises.rm(path.join(chunksDirectory, file), { force: true });
            }));

            await Promise.all(['server.cjs.map', 'server.cjs.LICENSE.txt'].map((filename) => {
                const assetName = `${this.renderDirectory.replace(/\\/g, '/')}/${filename}`;

                return compilation.getAsset(assetName)
                    ? Promise.resolve()
                    : fs.promises.rm(
                        path.join(compiler.options.output.path, this.renderDirectory, filename),
                        { force: true }
                    );
            }));
        });
    }
}

export default (_env, argv = {}) => {
    const mode = argv.mode || 'development';
    const isProduction = mode === 'production';

    return themes.map((theme) => ({
        name: `${theme}-render`,
        mode,
        target: 'node',
        devtool: isProduction ? false : 'source-map',
        cache: isProduction
            ? false
            : {
                type: 'filesystem',
                cacheDirectory: resolveConfig('node_modules', '.cache', 'webpack'),
                name: `render-${theme}-${mode}`
            },
        entry: resolveConfig('../src/themes', theme, 'js/render/server.jsx'),
        output: {
            path: resolveConfig('../dist/wp-content/themes', theme),
            filename: 'assets/render/server.cjs',
            chunkFilename: isProduction
                ? 'assets/render/chunks/[name].[contenthash].cjs'
                : 'assets/render/chunks/[name].cjs',
            library: {
                type: 'commonjs2'
            },
            publicPath: `/wp-content/themes/${theme}/`
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
                    test: /\.s?[ac]ss$/i,
                    use: resolveConfig('loaders/ignore-styles-loader.cjs')
                },
                {
                    test: /\.(png|jpe?g|gif|svg|webp)$/i,
                    type: 'asset/resource',
                    generator: {
                        filename: 'assets/images/[name][ext]'
                    }
                },
                {
                    test: /\.(mp4|webm)$/i,
                    type: 'asset/resource',
                    generator: {
                        filename: 'assets/videos/[name][ext]'
                    }
                },
                {
                    test: /\.(mp3|wav|ogg)$/i,
                    type: 'asset/resource',
                    generator: {
                        filename: 'assets/audios/[name][ext]'
                    }
                },
                {
                    test: /\.(woff2?|eot|ttf|otf)$/i,
                    type: 'asset/resource',
                    generator: {
                        filename: 'assets/fonts/[name][ext]'
                    }
                }
            ]
        },
        plugins: [
            new TemplateRenderManifestPlugin('assets/render'),
            new RenderChunkCleanupPlugin('assets/render'),
            new CopyPlugin({
                patterns: [
                    {
                        from: resolveConfig('scripts/render-server-runtime.mjs'),
                        to: 'assets/render/serve.mjs'
                    }
                ]
            })
        ],
        optimization: {
            minimize: isProduction,
            splitChunks: false,
            runtimeChunk: false,
            minimizer: isProduction ? [new TerserPlugin()] : []
        },
        resolve: {
            modules: [resolveConfig('node_modules')],
            extensions: ['.js', '.jsx', '.scss', '.css']
        },
        stats: 'errors-warnings'
    }));
};
