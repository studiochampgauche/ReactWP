![ReactWP](https://reactwp.com/github-image/banner-black.jpg)

# Introduction

![Built](https://img.shields.io/badge/Built-Webpack-blue)
![Multisite Ready](https://img.shields.io/badge/Multisite-Ready-brightgreen)
![Server](https://img.shields.io/badge/Server-PHP-orange)

ReactWP mix Server-Side Data Injection and Server-Side Rendering on first load, followed by client-side rendering.

# Ready
- Page Transition Animation
- GSAP Smooth Scrolling and ScrollTrigger
- SEO Management
- Consent box management
- Cache Service Workers (work only with https protocol)
- A preloader that waits until media files are fully downloaded


# Requirements
- ACF Pro License
- PHP 8.2+
- GSAP Club Membership
- Node.js
	- For local only
	- Tested with: v20.15.0 to v22.12.0

# Installation Guide
1. Navigate to the `constructor` directory
2. Authenticate your GSAP Account by adding your `.npmrc` file.
3. From `src`, duplicate the `wp-config-sample.php` to `wp-config.php` and setup it.
4. Install cores, node modules and build the app `npm run get:core && npm i && npm run watch`
5. Setup your backend by adding `/wp-admin` to your url.
6. Start working!

> [!WARNING]
> Since React 19, you need to add with `npm i` command, the parameter `--legacy-peer-deps` until react-helmet-async get an update.

> [!IMPORTANT]
> If your current GSAP subscription level is not `Premium`, you need to uninstall the current GSAP module for install the module according to your level.


# What's next for ReactWP?
- Right now, you can only download all medias in a one time call instead of being able to download it page per page when the page is request. We'll add this possibility.