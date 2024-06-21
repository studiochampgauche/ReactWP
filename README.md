# Our WordPress Boilerplate

Check [the wiki](https://github.com/studiochampgauche/wordpress-boilerplate/wiki) for requirements, installation guide and more

## Last Changelog

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


[More logs](https://github.com/studiochampgauche/wordpress-boilerplate/wiki/Changelog)