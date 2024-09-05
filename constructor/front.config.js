import path from 'path';
import { fileURLToPath } from 'url';
import CopyPlugin from 'copy-webpack-plugin';
import TerserPlugin from 'terser-webpack-plugin';
import ImageMinimizerPlugin from 'image-minimizer-webpack-plugin';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const main = {
	entry: {
		app: [
			'../src/front/js/App.jsx',
			'../src/front/scss/App.scss',
			'../src/front/medias/audios/audios.js',
			'../src/front/medias/fonts/fonts.js',
			'../src/front/medias/images/images.js',
			'../src/front/medias/videos/videos.js',
		]
	},
	output: {
		filename: 'main.min.js',
		path: path.resolve(__dirname, '../dist/assets/js')
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
					from: '../src/front/template',
					to: path.resolve(__dirname, '../dist'),
					noErrorOnMissing: true
				},
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
			path.resolve(__dirname, 'node_modules')
		],
		extensions: ['.js', '.jsx']
	}
};


export default main;