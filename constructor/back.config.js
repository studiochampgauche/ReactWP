import path from 'path';
import { fileURLToPath } from 'url';
import CopyPlugin from 'copy-webpack-plugin';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const mainBack = {
	entry: '../src/back/theme/assets/js/App.js',
	output: {
		filename: 'main.min.js',
		path: path.resolve(__dirname, '../dist/admin/wp-content/themes/the-theme/assets/js'),
	},
	plugins: [
		new CopyPlugin({
			patterns: [
				{
					from: '../src/back/wp-config.php',
					to: path.resolve(__dirname, '../dist/admin'),
					noErrorOnMissing: true
				},
				{
					from: '../src/back/theme',
					to: path.resolve(__dirname, '../dist/admin/wp-content/themes/the-theme'),
					noErrorOnMissing: true,
					globOptions: {
						ignore: [
						'**/App.js'
						]
					}
				}
			]
		}),
	],
	resolve: {
		modules: [
			path.resolve(__dirname, 'node_modules')
		]
	}
};


export default mainBack;