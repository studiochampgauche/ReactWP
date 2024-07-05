# Our WordPress Boilerplate

Our Boilerplate is done for help us to start theming fast around modules of Node and ACF Pro on installation instead of wasting time with basics, code source clean up and more.

The project has a mentality that limits the use of plugins, keeping administration clean and consistent for clients. At the same time, an other mentality for automatisation with Webpack that allow us to play with node modules in ESM, SASS/SCSS, codes minification and images compression.


> [!NOTE]
> Although our mentality is to use as few extensions as possible, you can install whatever you like without limitation. Just keep in mind that the project has only sense if you dev your own codes around ACF and some Node modules instead of use multiple differents WordPress plugins.



## Installation Guide

1. Git clone this repo
2. Go to `src > constructor`
3. Install WordPress `npm run get:wp`
4. Install the Node Modules `npm i`
5. In `src` directory, duplicate `wp-config-sample.php` to `wp-config.php` and setup it
6. If is the first setup for your project, run `npm run build:dev` or `npm run watch:dev` in `src > constructor`. If not, continue watching with `npm run watch:dev`.
7. Start working


## Requirements

- NodeJS (tested with v20.12.2, v20.15.0)
- ACF Pro 6.2.6 or higher
- PHP Version 8.2 or higher
- Our Core plugin named "Champ Gauche Core" (included in the repo)
- A premium or commercial subscription to the GSAP Club. (Only if you conserve the Smooth Scroller of GSAP. If you don't conserve, you need the free version.)

> [!WARNING]  
> If you use the free version of GSAP or if your subscription level is other than "Premium", you need to uninstall the current GSAP module and reinstall GSAP by following the npm process [here](https://gsap.com/docs/v3/Installation/). If you don't change, don't forget to authenticate your GSAP account. You can put a look to the npm installation process for that.

> [!CAUTION]
> If you don't conserve the Smooth Scroller, you need to remove PageScroller() from App.js and you need to remove all window.gscroll reference in PageTransitor.js


## Changelog

[Here](https://github.com/studiochampgauche/wordpress-boilerplate/blob/master/CHANGELOG.md)


## Multisite

- On multisite, The JSON Save of ACF doesn't work.


## Languages

***Default:*** French

***Translation:*** en_US, en_CA, en_GB, en_AU, en_NZ, en_ZA