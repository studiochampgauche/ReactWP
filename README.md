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
	- maximum tested: v20.18.0

## Installation Guide
1. Go to `constructor`
2. Install the WordPress Production Files + ACF Pro Plugin `npm run get:core`
3. Authenticate your GSAP Account by adding your `.npmrc` in `constructor`
4. Install your modules `npm i`
5. From `src > back`, duplicate the `wp-config-sample.php` to `wp-config.php` and setup it.
6. Build your app in development mode with `npm run build` or build and watch with `npm run watch`. You can build for production with `npm run prod`.
7. Setup your backend:
	- Remove default posts and pages.
	- Add your Home Page and setup it as a Static Page.
	- Change your permalink for other than Plain Text.
	- Go to `Site settings` tab from sidebar and click on the update button.
8. Start working!

> [!IMPORTANT]
> - On step #2, `get:core` delete at the same time default plugins and twenty themes.
> - With step #3 or #4, you need to uninstall the current GSAP module if your subscription level is not `Premium` for install the module according to your level.
> - You can access your admin by adding `/admin` to your url.
> - You need to configure your web server to redirect all URL requests to the `index.php` file, unless the requested file or directory physically exists on the server.