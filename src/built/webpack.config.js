import path from 'path';
import { fileURLToPath } from 'url';
import CopyPlugin from 'copy-webpack-plugin';
import MiniCssExtractPlugin from 'mini-css-extract-plugin';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const main = {
	mode: 'development',
	entry: [
		'../themes/the-theme/js/App.js',
		'../themes/the-theme/scss/App.scss'
	],
	output: {
		filename: 'main.min.js',
		path: path.resolve(__dirname, '../../wp-content/themes/the-theme/assets/js')
	},
	module: {
		rules: [
			{
				test: /\.s[ac]ss$/,
				use: [
					{
						loader: 'file-loader',
						options: {outputPath: '../css/', name: 'main.min.css'}
					},
					'sass-loader'
				],
			},
			{
				test: /\.(png|jpg|gif|svg)$/,
				use: [
					{
						loader: 'file-loader',
						options: {
							name: '[name].[ext]',
							outputPath: '../images/'
						}
					}
				]
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
	resolve: {
		modules: [
			path.resolve(__dirname, 'node_modules')
		]
	}
};


export default main;