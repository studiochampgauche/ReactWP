import path from 'path';
import { fileURLToPath } from 'url';
import CopyPlugin from 'copy-webpack-plugin';
import TerserPlugin from 'terser-webpack-plugin';
import ImageMinimizerPlugin from 'image-minimizer-webpack-plugin';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const themes = [
	'reactwp'
];

const main = {
	cache: true,
	entry: themes.reduce((entries, themeName) => {

		entries[themeName] = [
			`../src/themes/${themeName}/js/App.jsx`,
			`../src/themes/${themeName}/medias/Medias.js`
		];

		return entries;

	}, {}),
	output: {
		filename: '[name]/assets/js/[name].min.js',
		path: path.resolve(__dirname, '../dist/wp-content/themes/')
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
				]
			},
			{
				test: /\.(png|jpg|jpeg|gif|svg|webp)$/i,
				type: 'asset/resource',
				generator: {
					filename: (pathData) => {

						const file = pathData.filename;
						const themeName = themes.find(theme => file.includes(theme));

						const ext = path.extname(file);
						const name = path.basename(file, ext);

						return `${themeName}/assets/images/${name}${ext}`;
					}
				}
			},
			{
				test: /\.(mp4)$/i,
				type: 'asset/resource',
				generator: {
					filename: (pathData) => {

						const file = pathData.filename;
						const themeName = themes.find(theme => file.includes(theme));

						const ext = path.extname(file);
						const name = path.basename(file, ext);

						return `${themeName}/assets/videos/${name}${ext}`;
					}
				}
			},
			{
				test: /\.(mp3)$/i,
				type: 'asset/resource',
				generator: {
					filename: (pathData) => {

						const file = pathData.filename;
						const themeName = themes.find(theme => file.includes(theme));

						const ext = path.extname(file);
						const name = path.basename(file, ext);

						return `${themeName}/assets/audios/${name}${ext}`;
					}
				}
			},
			{
				test: /\.(woff|woff2|eot|ttf|otf)$/i,
				type: 'asset/resource',
				generator: {
					filename: (pathData) => {

						const file = pathData.filename;
						const themeName = themes.find(theme => file.includes(theme));

						const ext = path.extname(file);
						const name = path.basename(file, ext);

						return `${themeName}/assets/fonts/${name}${ext}`;
					}
				}
			},
		],
	},
	plugins: [
		new CopyPlugin({
			patterns: [
				{
					from: '../src/core',
					to: path.resolve(__dirname, '../dist'),
					noErrorOnMissing: true
				},
				...themes.map(themeName => ({
			        from: path.resolve(__dirname, `../src/themes/${themeName}/template`),
			        to: path.resolve(__dirname, `../dist/wp-content/themes/${themeName}`),
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