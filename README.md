# Our WordPress Boilerplate

Check [the wiki](https://github.com/studiochampgauche/wordpress-boilerplate/wiki) for requirements, installation guide and more

## Last Changelog

***2024-06-22***
- Removed: Gulp has been deleted. Gulp was responsible for compressing images and fonts. Now, webpack will compress images (jpg, png, svg and gif... webp optimization will coming soon), but we've stopped compressing fonts.

- Removed: package-lock.json is no more shared.

- Changed: Command lines. Now use:
```
npm run get:wp
npm run build:dev
npm run build:prod
npm run watch:dev
npm run watch:prod
```

- Updated: Barba module updated to 2.10.0.

- Updated: SASS module updated to 1.77.6.

- Updated: Webpack module updated to 5.92.1.

- Modules added: image-minimizer-webpack-plugin, imagemin, imagemin-gifsicle, imagemin-jpegtran, imagemin-mozjpeg, imagemin-optipng, imagemin-pngquant, imagemin-svgo

> [!NOTE]
> Optimization work only with the production mode.


[More logs](https://github.com/studiochampgauche/wordpress-boilerplate/wiki/Changelog)