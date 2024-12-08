![ReactWP](https://reactwp.com/github-image/banner-black.jpg)

> [!NOTE]
> We have remove our docs. It'll be back soon.

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
5. Setup your backend by adding `/admin/wp-admin` to your url.
6. Start working!

> [!WARNING]
> Since React 19, you need to add with `npm i` command, the parameter `--legacy-peer-deps` until react-helmet get an update.

> [!IMPORTANT]
> - On step #4, `get:core` delete at the same time default plugins and twenty themes.
> - If your current GSAP subscription level is not `Premium`, you need to uninstall the current GSAP module for install the module according to your level.
> - You need to configure your web server to redirect all URL requests to the `index.php` file, unless the requested file or directory physically exists on the server.