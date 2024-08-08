## Changelog

***2024-08-08***

- ***Moved:*** `js` and `scss` directories from `src` has been placed out of `es`.
- ***Removed:*** `es` directory has been deleted


***2024-08-07***

- ***Added:*** Secure audios and videos directories by adding an index.php file.


***2024-07-16***

- ***Fixed:*** JavaScript Minification. We add Terser Webpack Plugin.



***2024-07-03***

- ***Fixed:*** Recursive replacements turn numeric value to string. Fixed. [731dcf7](https://github.com/studiochampgauche/wordpress-boilerplate/commit/731dcf78c28b6f347109433136376ecff5b55e35)


***2024-06-30***

- ***Removed:*** mini-css-extract-plugin
- ***Added:*** Font import from JS files
- ***Added:*** Area for audio files
- ***Added:*** Area for video files
- ***Added:*** Import your audio files from your JS files
- ***Added:*** Import your video files from your JS files
- ***Added:*** `src > theme > the-theme > medias` directory
- ***Added:*** `src > theme > the-theme > es` directory
- ***Moved:*** "images, fonts, audios, videos" directories inner "medias" directory
- ***Moved:*** "js, scss" directories inner "es" directory

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
> Optimization work only with the production mode. (Production mode need to be used only when you are ready to push online because this mode take more time to proceed.

***2024-06-20***
- Fixed: when you set default for scg::source, the index.php and front-page.php files were affected by the change. We have added `'url' => false` to counter this fact.

- Command lines has been updated. You can now use:
```
npm run get:wp
npm run build:dev
npm run build:prod
npm run watch:dev
npm run watch:prod
npm run build:watch:dev
npm run build:watch:prod
```
Put a look on the package.json file inner `src > constructor` for more informations.

- `built` directory has been renamed to `constructor`

***2024-05-18***
- SCSS Mixin "break()" has upgraded. You can now manage your media queries like that:

```
@include break($type, $breaks...)


@include break('screen, print', '(max-width: 1366px)', '(max-width: 1600px) and (orientation: landscape)')
```

***2024-04-27***
- You can now import images. E.g. `import myLogo from '../images/logo.svg';`

***2024-04-25***
- The Webback mode has been change for `development` instead of `production`

***2024-04-22***
- You can now install the latest version of WordPress directly with `npm run get-wordpress`

***2024-04-21***
- v3 branch has take the place of Master branch. Master branch has been removed and the v3 has been renamed to master.
- Webpack integration: we removed some tasks done by Gulp and gave them to Webpack. Now, you can import your node modules instead to be forced to place a JS file and call his path. The performance is increase too with only one file JS loaded instead of each source imported.
- We stop managing plugin conception for concentrated urself in theming. We will come back to this later.
- Creation of the wiki