# Our WordPress Boilerplate

Check [the wiki](https://github.com/studiochampgauche/wordpress-boilerplate/wiki) for requirements, installation guide and more

## What's new

***2024-06-20***
- Command lines has been updated:
```
npm run get:wp
npm run build:dev
npm run build:prod
npm run watch:dev
npm run watch:prod
npm run build:watch:dev
npm run build:watch:prod
```
Check the package.json for more informations.


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
- We stop managing plugin conception for concentrated urself in theming. We'll come back to this later.
- Creation of the Wiki