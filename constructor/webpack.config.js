import front from './webpack.front.config.js';
import theme from './webpack.theme.config.js';
import plugins from './webpack.plugins.config.js';
import muPlugins from './webpack.mu-plugins.config.js';

export default [
	front,
	theme,
	plugins,
	muPlugins
];