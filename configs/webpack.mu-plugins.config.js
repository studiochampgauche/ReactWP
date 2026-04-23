import { createBundleConfig, resolveFromConfig } from './webpack.shared.config.js';

const plugins = [
	'reactwp'
];

export default createBundleConfig({
	cacheName: 'mu-plugins',
	items: plugins,
	inputDirectory: '../src/mu-plugins/plugins',
	outputDirectory: '../dist/wp-content/mu-plugins',
	templateDirectory: '../src/mu-plugins/plugins',
	publicPath: '/wp-content/mu-plugins/',
	copyPatterns: [
		{
			from: resolveFromConfig('../src/mu-plugins/configs'),
			to: resolveFromConfig('../dist/wp-content/mu-plugins'),
			noErrorOnMissing: true
		}
	]
});
