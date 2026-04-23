import { createBundleConfig, resolveFromConfig } from './webpack.shared.config.js';

const themes = [
	'reactwp'
];

export default createBundleConfig({
	cacheName: 'themes',
	items: themes,
	inputDirectory: '../src/themes',
	outputDirectory: '../dist/wp-content/themes',
	templateDirectory: '../src/themes',
	publicPath: '/wp-content/themes/',
	copyPatterns: [
		{
			from: resolveFromConfig('../src/core'),
			to: resolveFromConfig('../dist'),
			noErrorOnMissing: true
		}
	]
});
