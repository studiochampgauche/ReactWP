import path from 'path';
import { fileURLToPath } from 'url';
import CopyPlugin from 'copy-webpack-plugin';
import TerserPlugin from 'terser-webpack-plugin';
import ImageMinimizerPlugin from 'image-minimizer-webpack-plugin';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const plugins = [
	'reactwp'
];

const main = {
	cache: true,
	entry: plugins.reduce((entries, pluginName) => {

		entries[pluginName] = [
			`../src/mu-plugins/plugins/${pluginName}/js/App.js`,
			`../src/mu-plugins/plugins/${pluginName}/medias/Medias.js`
		];

		return entries;

	}, {}),
	output: {
		filename: '[name]/assets/js/[name].min.js',
		path: path.resolve(__dirname, '../dist/wp-content/mu-plugins/'),
		chunkFilename: (pathData) => {

			const runtime = pathData.chunk?.runtime;

			const plugin = Array.isArray(runtime) ? runtime[0] : runtime;

			return `${plugin}/assets/js/chunks/[name].[contenthash].js`;
		},
		publicPath: '/wp-content/mu-plugins/'
	},
	module: {
		rules: [
			{
				test: /\.jsx?$/,
				exclude: /node_modules/,
				use: {
					loader: 'babel-loader',
				},
			},
			{
				test: /\.s[ac]ss$/i,
				use: [
					'style-loader',
					'css-loader',
					'sass-loader'
				],
			},
			{
				test: /\.(png|jpg|jpeg|gif|svg|webp)$/i,
				type: 'asset/resource',
				generator: {
					filename: (pathData) => {

						const file = pathData.filename;
						const pluginName = plugins.find(plugin => file.includes(plugin));

						const ext = path.extname(file);
						const name = path.basename(file, ext);

						return `${pluginName}/assets/images/${name}${ext}`;
					}
				}
			},
			{
				test: /\.(mp4)$/i,
				type: 'asset/resource',
				generator: {
					filename: (pathData) => {

						const file = pathData.filename;
						const pluginName = plugins.find(plugin => file.includes(plugin));

						const ext = path.extname(file);
						const name = path.basename(file, ext);

						return `${pluginName}/assets/videos/${name}${ext}`;
					}
				}
			},
			{
				test: /\.(mp3)$/i,
				type: 'asset/resource',
				generator: {
					filename: (pathData) => {

						const file = pathData.filename;
						const pluginName = plugins.find(plugin => file.includes(plugin));

						const ext = path.extname(file);
						const name = path.basename(file, ext);

						return `${pluginName}/assets/audios/${name}${ext}`;
					}
				}
			},
			{
				test: /\.(woff|woff2|eot|ttf|otf)$/i,
				type: 'asset/resource',
				generator: {
					filename: (pathData) => {

						const file = pathData.filename;
						const pluginName = plugins.find(plugin => file.includes(plugin));

						const ext = path.extname(file);
						const name = path.basename(file, ext);

						return `${pluginName}/assets/fonts/${name}${ext}`;
					}
				}
			},
		],
	},
	plugins: [
		new CopyPlugin({
			patterns: [
				{
			        from: '../src/mu-plugins/configs',
			        to: path.resolve(__dirname, `../dist/wp-content/mu-plugins/`),
			        noErrorOnMissing: true,
			    },
				...plugins.map(pluginName => ({
			        from: path.resolve(__dirname, `../src/mu-plugins/plugins/${pluginName}/template`),
			        to: path.resolve(__dirname, `../dist/wp-content/mu-plugins/${pluginName}`),
			        noErrorOnMissing: true,
			    }))
			]
		}),
	],
	optimization: {
		minimizer: [
			new ImageMinimizerPlugin({
				minimizer: {
					implementation: ImageMinimizerPlugin.imageminMinify,
					options: {
						plugins: [
							['gifsicle', { interlaced: true }],
							['jpegtran', { progressive: true }],
							['mozjpeg', { quality: 75 }],
							['optipng', { optimizationLevel: 5 }],
							['pngquant', { quality: [0.65, 0.90], speed: 4, }],
							['svgo', { plugins: [{ name: 'preset-default', params: { overrides: { removeViewBox: false }}}]}]
						],
					},
				},
			}),
			new TerserPlugin()
		],
	},
	resolve: {
		modules: [
			path.resolve(__dirname, 'node_modules'),
		],
		extensions: ['.js', '.jsx', '.css', '.scss']
	}
};


export default main;