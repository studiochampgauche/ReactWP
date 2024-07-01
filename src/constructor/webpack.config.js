import path from 'path';
import { fileURLToPath } from 'url';
import CopyPlugin from 'copy-webpack-plugin';
import ImageMinimizerPlugin from 'image-minimizer-webpack-plugin';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const main = {
	entry: {
		app: [
			'../themes/the-theme/es/js/App.js',
			'../themes/the-theme/es/scss/App.scss'
		]
	},
	output: {
		filename: 'main.min.js',
		path: path.resolve(__dirname, '../../wp-content/themes/the-theme/assets/js')
	},
	module: {
		rules: [
			{
				test: /\.s[ac]ss$/i,
				use: [
					{
						loader: 'file-loader',
						options: {
							name: 'main.min.css',
							outputPath: '../css/'
						}
					},
					'sass-loader'
				],
			},
			{
				test: /\.(png|jpg|jpeg|gif|svg)$/i,
				use: [
					{
						loader: 'file-loader',
						options: {
							name: '[name].[ext]',
							outputPath: '../images/',
						}
					}
				]
			},
			{
				test: /\.(mp4)$/i,
				use: [
					{
						loader: 'file-loader',
						options: {
							name: '[name].[ext]',
							outputPath: '../videos/',
						}
					}
				]
			},
			{
				test: /\.(mp3)$/i,
				use: [
					{
						loader: 'file-loader',
						options: {
							name: '[name].[ext]',
							outputPath: '../audios/',
						}
					}
				]
			},
			{
				test: /\.(woff|woff2|eot|ttf|otf)$/i,
				use: [
					{
						loader: 'file-loader',
						options: {
							name: '[name].[ext]',
							outputPath: '../fonts/',
						}
					}
				]
			},
		],
	},
	plugins: [
		new CopyPlugin({
			patterns: [
				{
					from: '../wp-config.php',
					to: path.resolve(__dirname, '../../'),
					noErrorOnMissing: true
				},
				{
					from: '../themes/the-theme/template',
					to: path.resolve(__dirname, '../../wp-content/themes/the-theme'),
					noErrorOnMissing: true
				},
				{
					from: '../themes/the-theme/medias/images',
					to: path.resolve(__dirname, '../../wp-content/themes/the-theme/assets/images'),
					noErrorOnMissing: true
				},
				{
					from: '../themes/the-theme/medias/audios',
					to: path.resolve(__dirname, '../../wp-content/themes/the-theme/assets/audios'),
					noErrorOnMissing: true
				},
				{
					from: '../themes/the-theme/medias/videos',
					to: path.resolve(__dirname, '../../wp-content/themes/the-theme/assets/videos'),
					noErrorOnMissing: true
				},
				{
					from: '../themes/the-theme/medias/fonts',
					to: path.resolve(__dirname, '../../wp-content/themes/the-theme/assets/fonts'),
					noErrorOnMissing: true
				},
				{
					from: '../extensions/cg-core/template',
					to: path.resolve(__dirname, '../../wp-content/plugins/cg-core'),
					noErrorOnMissing: true
				},
				{
					from: '../themes/the-theme/es/scss/inc/fa/webfonts',
					to: path.resolve(__dirname, '../../wp-content/themes/the-theme/assets/css/inc/fa/webfonts'),
					noErrorOnMissing: true
				}
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
			})
		],
	},
	resolve: {
		modules: [
			path.resolve(__dirname, 'node_modules')
		]
	}
};


export default main;