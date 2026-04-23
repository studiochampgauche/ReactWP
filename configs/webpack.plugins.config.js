import { createBundleConfig } from './webpack.shared.config.js';

const plugins = [
	'reactwp-frontend',
	'reactwp-backend',
	'reactwp-images',
	'reactwp-accept-svg',
	'reactwp-acf-local-json',
	'reactwp-seo'
];

export default createBundleConfig({
	cacheName: 'plugins',
	items: plugins,
	inputDirectory: '../src/plugins',
	outputDirectory: '../dist/wp-content/plugins',
	templateDirectory: '../src/plugins',
	publicPath: '/wp-content/plugins/'
});
