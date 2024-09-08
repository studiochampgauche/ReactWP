# Our headless React-WordPress Boilerplate

Our boilerplate is designed to help us quickly start a React Single Page App (SPA) frontend with a robust PHP backend powered by WordPress and ACF Pro.

This project follows a philosophy of limiting plugin use to keep the administration clean and consistent for clients, while embracing automation with Webpack. This setup allows us to play with node modules in ESM format, React, SASS/SCSS, codes minification and images compression.


> [!NOTE]
> Although our philosophy is to use as few plugins as possible, you are free to install whatever you like without limitations. However, keep in mind that the project only makes sense if you develop your own code around React, ACF, and certain Node modules, rather than relying on numerous different WordPress plugins.


## Ready

- React
- Webpack
- JavaScript minification with `terser-webpack-plugin`
- SCSS or SASS with `sass` and `sass-loader`
- Image Compression with `image-minimizer-webpack-plugin`, (supports GIF, JPG, PNG, and SVG; WEBP support is not implemented yet)
- App routing with `react-router-dom`
- Helmet for managing document head `src > front > js > components > Metas.jsx`
- Font Awesome with individual imports
- GSAP and SmoothScroller `src > front > js > components > Scroller.jsx`
- Page transitions animated with GSAP `src > front > js > components > PageTransition.jsx`
- Preloader `src > front > js > addons > Loader.js`
- Anchor scrolling by `PageTransition.jsx`
- 404 handling


## Requirements

- NodeJS (tested with v20.15.0)
- ACF Pro License Key
- PHP Version 8.2 or higher
- A premium or commercial subscription to the GSAP Club (ensure to add the .npmrc file in the constructor directory to authenticate your account)


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

1. Clone this repository using Git.
2. Navigate to the `constructor` directory.
3. Install WordPress by running `npm run get:wp`.
4. Authenticate your GSAP account by adding the `.npmrc` file.
5. Install the Node modules with `npm i`.
6. In the `src > back` directory, duplicate `wp-config-sample.php` to `wp-config.php` and set it up.
7. In `src > front > js > App.jsx`, change the URLs within the global variable `window.SYSTEM` to reflect your current URL.
8. In the `src > front > template` directory, add your server configuration file, such as your `.htaccess` for Apache.
9. For the first setup of your project, run `npm run watch:front:back` or `npm run build:front:back` in `constructor` directory. After your first setup, you can continue watching or rebuild only the front or back with `npm run watch:front` or `npm run build:back`. When you are ready for production and want to compress, replace `watch` with `prod` in your command line. Check your `package.json` to see the available command lines.
10. Go to your WordPress admin panel to complete the setup (e.g., yourdomain.com/admin).
11. Activate the Champ Gauche Core Theme, Champ Gauche Core Plugin, and the ACF Plugin.
12. Remove other default plugins, themes, pages, and posts.
13. Create your Home Page and set it as a static page (e.g., yourdomain.com/admin/wp-admin/options-reading.php).
14. Set your permalink structure to something other than Plain (the default option).
15. Start working!


## How frontend and backend work together

- If you don't know what a headless ecosystem is, the principle is to have a front-end that is separate from your back-end. With this in mind, you should understand that the front-end communicates with the back-end via fetch requests.

- Each page, post, custom post type, some users if you have pages for authors, etc. that compose your routes, need to be associate to a Component. For do that, you need to have a field with each admin element that require a display for populate the component name and you need to map your Component in the ecosystem.

- Pages and posts already have the field to manage the component name. If you want this field for another custom post type, you can go to (yourdomain.com/admin/wp-admin/admin.php?page=site-settings) and select your CPT in the `Modules` tab, section `Component`. For other requirements, like get the field for users, you need edit where the field can be displayed in the `render.php` file of the Champ Gauche Core Plugin, around line 670.

- For mapping your component in the ecosystem, just add it in the `componentMap` object from `App.jsx`

> [!TIP]
> Maybe you have too much posts, etc. and you don't want populate a Component Name on each. Just know, you can create a logic in your App.jsx or use some acf hooks for populate automatically the field.




## How transition page work

- Add attribute `data-transition="true"` to your link element. Without this attribute, the change'll be direct. (You need to use `<Link>` component of `react-router-dom`, not `<a ...>`)

- If you want use `<a ...>` or another custom HTML element, you need to add the class name `goto` with the attribute `data-href=""` (you need to add this attribute only if it's not a `<a ...>` element. With this element, you use `href`). Just know, if your `data-href` (or `href`) attribute is an anchor, you need to add the path of your current page if you want stay on the same page and scroll to the anchor. (e.g. if your page is your home page, you are probably on the path `/`... So you need to add `/` with your anchor, like `/#my-id`). You don't need to do that with `<Link>`.

- If your element is an anchor, you can control the scroll behavior by adding `data-behavior="smooth|instant"`. If GSAP SmoothScroller Plugin is active, the default is `smooth`. Without the plugin, default is `instant`.

- Checkout the component `PageTransition.jsx` for edit your transition



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


## To know

- WordPress Front-end (not the React Front-end, but the admin front-end part) redirect to the the wp-admin. You can delete the template_redirect action hook inner the functions.php if you don't want that.

- When you have a media file that isn't import by your main JS App files, webpack doesn't know you use it and he don't compile it. You need to force the import by use the JS file according to your needs. (e.g. if you play with an audio file, you need to go in `src > front > medias > audios` and import your file from the `audios.js` file.)

- For now, the project is not done for multisite

- Using the admin side is not required. If you want use only the Front-end React part, you can stop fetching pages inner the `App.jsx` file.

- You need to root the `dist` directory in your virtual host or you need to push files from this directory on your hosting. (`dist` directory is created when you install WordPress with the command line `get:wp` and is populate trought your progress.)

- Minification and compression are done only in production.

### My page animation is done before the first loader has finished

- The preloader is made in a `Promise`. You can decide when the promise is done and you can call your animation inner this method `window.loader.then()`.


## Changelog

***2024-09-08***

- Update boilerplate v3 to v4.

> [!WARNING]
> v4 isn't a following of the v3, but a break changer with a new headless ecosystem. If you want the v3, [Download here](https://archives.champgauche.studio/wordpress-boilerplate-v3.zip)