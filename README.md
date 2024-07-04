# Our WordPress Boilerplate

## Installation Guide

1. Git clone this repo
2. Go to `src > constructor`
3. Install WordPress `npm run get:wp`
4. Install the Node Modules `npm i`
5. In `src` directory, duplicate `wp-config-sample.php` to `wp-config.php` and setup it
6. If is the first setup for your project, run `npm run build:dev` or `npm run watch:dev` in `src > constructor`. If not, continue watching with `npm run watch:dev`.
7. Start working

## Requirements

- NodeJS (tested with v20.12.2)
- ACF Pro 6.2.6 or higher
- PHP Version 8.2 or higher
- Our Core plugin named "Champ Gauche Core" (included in the repo)
- A premium or commercial subscription to the GSAP Club. (Only if you conserve the Smooth Scroller of GSAP)

> [!WARNING]  
> If you use the free version of GSAP or if your subscription level is other than "Premium", you need to uninstall the current GSAP module and reinstall GSAP by following the npm process [here](https://gsap.com/docs/v3/Installation/). If you don't change, don't forget to authenticate your GSAP account. You can put a look to the npm installation process for that.

> [!CAUTION]
> If you don't conserve the Smooth Scroller, you need to remove PageScroller() from App.js and you need to remove all window.gscroll reference in PageTransitor.js