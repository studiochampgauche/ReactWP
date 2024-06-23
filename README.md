# Our WordPress Boilerplate

Check [the wiki](https://github.com/studiochampgauche/wordpress-boilerplate/wiki) for requirements, installation guide and more.

## What's for?
Play fast around WordPress, ACF Pro and modules of Node.js by stopping waste time with the basics.

- Import your Node modules in JavaScript module syntax (ESM)
- Barba Ready
- GSAP Ready
- GSAP SmoothScroller/ScrollTrigger ready and work's good with Barba
- SCSS/SASS minification to CSS
- JS minification
- Image compression
- SVG Acceptance
- Source code cleanup
- Stop getting multiple image files/dimensions when you upload an image
- Maintenance mode
- and more..

## Last Changelog

***2024-06-22***
- ***Fixed:*** Dependabot alerts

- ***Removed:*** Gulp has been deleted. Gulp was responsible for compressing images and fonts. Now, webpack will compress images (jpg, png, svg and gif... webp optimization will coming soon), but we've stopped compressing fonts.

- ***Removed:*** package-lock.json is no more shared.

- ***Changed:*** Command lines. Now use:
```
npm run get:wp
npm run build:dev
npm run build:prod
npm run watch:dev
npm run watch:prod
```

- ***Updated:*** Barba module updated to 2.10.0.

- ***Updated:*** SASS module updated to 1.77.6.

- ***Updated:*** Webpack module updated to 5.92.1.

- ***Modules added:*** image-minimizer-webpack-plugin, imagemin, imagemin-gifsicle, imagemin-jpegtran, imagemin-mozjpeg, imagemin-optipng, imagemin-pngquant, imagemin-svgo

> [!NOTE]
> Optimization work only with the production mode. (Production mode need to be used only when you are ready to push online because this mode take more time to proceed.)


[More logs](https://github.com/studiochampgauche/wordpress-boilerplate/wiki/Changelog)