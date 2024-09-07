# Our React WordPress Boilerplate

Our boilerplate is done for help us to start a frontend React SPA with a robust PHP backend based on WordPress + ACF Pro in "no time".

The project has a mentality that limits the use of plugins, keeping administration clean and consistent for clients. At the same time, an other mentality for automatisation with Webpack that allow us to play with node modules in ESM, React, SASS/SCSS, codes minification and images compression.


> [!NOTE]
> Although our mentality is to use as few plugins as possible, you can install whatever you like without limitation. Just keep in mind that the project has only sense if you dev your own codes around React, ACF and some Node modules instead of use multiple differents WordPress plugins.



## Requirements

- NodeJS (tested with v20.15.0)
- ACF Pro License Key
- PHP Version 8.2 or higher
- A premium or commercial subscription to the GSAP Club. (You need to add the .npmrc file in `constructor` directory for authenticate your account.)


## Apache configuration

```
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.html [L]
```


## Nginx configuration

(not tested)
```
location / {
    try_files $uri /index.html;
}
```


## Installation Guide

1. Git clone this repo
2. Go to `constructor`
3. Install WordPress `npm run get:wp`
4. Authenticate your GSAP Account by adding the `.npmrc` file
5. Install the Node Modules `npm i`
6. In `src > back` directory, duplicate `wp-config-sample.php` to `wp-config.php` and setup it
7. From your `App.jsx` file, change URLs inner the global variable `window.SYSTEM` for your current URL
8. In `src > front > template` directory, add your server configuration file like your .htaccess for Apache
9. If is the first setup for your project, run `npm run watch:front:back` or `npm run build:front:back` in `constructor`. After your first setup, you can continue watching or rebuild only the front or the back like this `npm run watch:front` or `npm run build:back`. When you are ready for production and you want compress, relace `watch` by `prod` in your command line. Put a look on your `package.json` for view command lines.
10. Go to your WordPress admin and setup it (yourdomain.com/admin)
11. Active the Champ Gauche Core Theme, Champ Gauche Core Plugin and the ACF Plugin
12. Clear other default plugins, themes, pages and posts
13. Create your Home Page and setup it like a static page (here: yourdomain.com/admin/wp-admin/options-reading.php)
14. Setup your permalink structure to other than `Plain` (the default option)
15. Start Working!


## How frontend and backend work together
...


## How transition page work

- Add attribute `data-transition="true"` to your link element. Without this attribute, the change'll be direct. (You need to use `<Link>` component of `react-router-dom`, not `<a ...>`)

- If you want use `<a ...>` or another custom HTML element, you need to add the class name `goto` with the attribute `data-href=""` (you need to add this attribute only if it's not a `<a ...>` element. With this element, you use `href`). Just know, if your `data-href` (or `href`) attribute is an anchor, you need to add the path of your current page if you want stay on the same page and scroll to the anchor. (e.g. if your page is your home page, you are probably on the path `/`... So you need to add `/` with your anchor, like `/#my-id`). You don't need to do that with `<Link>`.

- If your element is an anchor, you can control the scroll behavior by adding `data-behavior="smooth|instant"`. If GSAP SmoothScroller Plugin is active, the default is `smooth`. Without the plugin, default is `instant`.

- Checkout the component `Transitor.jsx` for edit your transition



## Fetch

Get Ajax basepath and Rest API basepath from the global object:
```
window.SYSTEM = {
    baseUrl: 'https://wpp.test/',
    adminUrl: 'https://wpp.test/admin/',
    ajaxPath: '/admin/wp-admin/admin-ajax.php',
    restPath: '/admin/wp-json/'
};
```


## Lifecycle
...


## To know

- WordPress Front-end (not the React Front-end, but the admin front-end part) redirect to the the wp-admin. You can delete the template_redirect action hook inner the functions.php if you don't want that.

- When you have a media file that isn't import by your main JS App files, webpack doesn't know you use it and he don't compile it. You need to force the import by use the JS file according to your needs. (e.g. if you play with an audio file, you need to go in `src > front > medias > audios` and import your file from the `audios.js` file.)

- For now, the project is not done for multisite

- Using the admin side is not required. If you want use only the Front-end React part, you can stop fetching pages inner the `App.jsx` file.

- You need to root the `dist` directory in your virtual host or you need to push files from this directory on your hosting. (`dist` directory is created when you install WordPress with the command line `get:wp` and is populate when trought your progress.)