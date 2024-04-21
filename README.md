# Our WordPress Boilerplate
Build immersive 2D or 3D WordPress websites Awwwards in "no time" with our Boilerplate.

![minimum-php](https://img.shields.io/badge/Minimum%20PHP-8.2-ff0000.svg)
![multisite-ready](https://img.shields.io/badge/Multisite%20Ready-no-fcba03.svg)
![en-ready](https://img.shields.io/badge/English%20Ready-yes-44cc11.svg)
![fr-ready](https://img.shields.io/badge/French%20Ready-yes-44cc11.svg)

> [!CAUTION]  
> We use Gulp for play with gulp-image and gulp-fontmin and you can't update gulp to the last version because it'll break gulp-fontmin.

## Guide
1. Put the WordPress Production Files on root
2. Install your Node Modules in `src > built`
3. In `src` directory, duplicate `wp-config-sample.php` to `wp-config.php` and setup it
4. If is the first setup for your project, run `npm run prod` or `npm run prod-watch` in `src > built`. If not, continue watching with `npm run watch`.
5. Start working


## Requirements
- NodeJS (tested with v20.12.2)
- ACF Pro 6.2.6 or higher
- A premium or commercial subscription to the GSAP Club. (Only if you conserve the Smooth Scroller of GSAP)

> [!WARNING]  
> If you use the free version of GSAP or if your subscription level is other than "Premium", you need to uninstall the current GSAP module and reinstall GSAP by following the npm process [here](https://gsap.com/docs/v3/Installation/). If you don't change, don't forget to authenticate your GSAP account. You can put a look to the npm installation process for that.


## Ready on start
- Sources preloader
- GSAP Smooth Scroller
- Barba Page Transitions


# Core Plugin

![required-yes](https://img.shields.io/badge/Required-yes-ff0000.svg)

We have build a plugin that help us to handle repetitive needs in each project. [Check it out here!](https://wpboilerplate.champgauche.studio)


# Admin Languages

***Default:*** French

***Translation:*** en_US, en_CA, en_GB, en_AU, en_NZ, en_ZA


# Docs
https://github.com/studiochampgauche/wordpress-boilerplate/blob/master/DOCS.md


# What's new
***2024-04-21***
- v3 branch has take the place of Master branch. Master branch has been removed and the v3 has been renamed to master.
- Webpack integration: we removed some tasks done by Gulp and gave them to Webpack. Now, you can import your node modules instead to be forced to place a JS file and call his path.
- We stop managin plugin conception for concentred urself in theming. We will come back to this later.