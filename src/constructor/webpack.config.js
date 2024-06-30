import path from 'path';
import { fileURLToPath } from 'url';
import CopyPlugin from 'copy-webpack-plugin';
import ImageMinimizerPlugin from 'image-minimizer-webpack-plugin';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const main = {
	entry: {
		app: [
			'../themes/the-theme/js/App.js',
			'../themes/the-theme/scss/App.scss'
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
				use: ['sass-loader'],
				type: 'asset/resource',
				generator: {
					filename: '../css/main.min.css'
				}
			},
			{
				test: /\.(png|jpg|jpeg|gif|svg)$/i,
				use: [],
				type: 'asset/resource',
				generator: {
					filename: '../images/[name].[ext]'
				}
			},
			{
				test: /\.(woff|woff2|eot|ttf|otf)$/i,
				use: [],
				type: 'asset/resource',
				generator: {
					filename: '../fonts/[name].[ext]'
				}
			}
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
					from: '../themes/the-theme/images',
					to: path.resolve(__dirname, '../../wp-content/themes/the-theme/assets/images'),
					noErrorOnMissing: true
				},
				{
					from: '../themes/the-theme/fonts',
					to: path.resolve(__dirname, '../../wp-content/themes/the-theme/assets/fonts'),
					noErrorOnMissing: true
				},
				{
					from: '../extensions/cg-core/template',
					to: path.resolve(__dirname, '../../wp-content/plugins/cg-core'),
					noErrorOnMissing: true
				},
				{
					from: '../themes/the-theme/scss/inc/fa/webfonts',
					to: path.resolve(__dirname, '../../wp-content/themes/the-theme/assets/css/inc/fa/webfonts'),
					noErrorOnMissing: true
				}
			]
		})
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