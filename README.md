![ReactWP](https://reactwp.com/github-image/banner-black.jpg)

![Built](https://img.shields.io/badge/Built-Webpack-blue)
![Multisite Ready](https://img.shields.io/badge/Work%20With%20Multisite-Yes-brightgreen)
![Server](https://img.shields.io/badge/Server-PHP-orange)

> [!WARNING]
> Our plugins and themes are designed for English, French, or both languages simultaneously. However, the structure can still be used without them if needed.
>
> ***Why limit the languages?***
>
> ReactWP is designed to remain consistent and as concise as possible. This is why we handle specific languages. For example, this allows us to manage English and French on the same page without having a lot of duplication.
> We may eventually consider support for three languages.

# Requirements
- ACF Pro License
- PHP 8.2+
- GSAP Club Membership
- Node.js
	- For local only
	- Tested with: v20.15.0 to v22.12.0

> [!NOTE]
> If you need a translation plugin, the boilerplate is done for work with [Polylang](https://polylang.pro/).

# Installation Guide
1. Clone the repo
2. Navigate to the `configs` directory;
3. Authenticate your GSAP Account by adding your `.npmrc` file;
4. From `src > core`, duplicate the wp-config-sample.php to wp-config.php and setup it;
5. Install cores, node modules and build the app with `npm run get:core && npm i --legacy-peer-deps && npm run watch`;
6. Setup your backend by adding `/wp-admin` to your url;
7. Start working!

> [!NOTE]
> - If your current GSAP subscription level is not `Premium`, you need to uninstall the current GSAP module for install the module according to your level.
> - With step 6, if you conserve our default theme, you need to active the ReactWP SEO Plugin and save your home page
> - When you add a ReactWP plugin, go save your "Site settings".


# What's next for ReactWP ?
- Caching system: Since 2025-01-22, each media you download need to be dowloaded again when you reload. We'll fix that.
- Docs