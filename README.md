![ReactWP](https://reactwp.com/github-image/banner-black.jpg)

## Requirements
- ACF Pro License
- PHP 8.2+
- GSAP Club Membership
- Node.js
	- For local only
	- minimum tested: v20.15.0
	- maximum tested: v22.12.0

## Installation Guide
1. Go to `constructor`
2. Authenticate your GSAP Account by adding your `.npmrc`
3. From `src`, duplicate the `wp-config-sample.php` to `wp-config.php` and setup it.
4. Install cores, node modules and build the app `npm run get:core && npm i && npm run watch`
5. Setup your backend by adding `/wp-admin` to your url.
6. Start working!

> [!WARNING]
> Since React 19, you need to add with `npm i` command, the parameter `--legacy-peer-deps` until react-helmet-async get an update.

> [!IMPORTANT]
> - If your current GSAP subscription level is not `Premium`, you need to uninstall the current GSAP module for install the module according to your level.