# Our Headless React-WordPress Boilerplate

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
- Preloader + medias download `src > front > js > addons > Loader.js`, `src > back > theme > functions.php`
- Anchor scrolling by `PageTransition.jsx`
- 404 handling


## Requirements

- NodeJS (tested with v20.15.0)
- ACF Pro License Key
- PHP Version 8.2 or higher
- A premium or commercial subscription to the GSAP Club (ensure to add the .npmrc file in the constructor directory to authenticate your account)


## Apache Configuration

```
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.html [L]
```


## Nginx Configuration

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


## How Frontend and Backend Work Together

- If you're unfamiliar with the headless ecosystem, the principle is to have a frontend that is separate from the backend. The frontend communicates with the backend via fetch requests.

- Each page, post, custom post type, and some users (if you have pages for authors, etc.) that compose your routes need to be associated with a Component. To achieve this, you must have a field for each admin element that requires a display to populate the component name, and you need to map your Component in the ecosystem.

- Pages and posts already have the field to manage the component name. If you want this field for another custom post type, you can go to (yourdomain.com/admin/wp-admin/admin.php?page=site-settings) and select your CPT in the `Modules` tab, under the `Component` section. For additional requirements, such as fetching the field for users, you need to edit where the field can be displayed in the `render.php` file of the Champ Gauche Core Plugin, around line 670.

- To map your component in the ecosystem, simply add it to the `componentMap` object in `App.jsx`.

> [!TIP]
> If you have many posts, etc., and don't want to populate a Component Name for each, you can create a logic in your `App.jsx` or use some ACF hooks to populate the field automatically.




## How Page Transitions Work

- Add the attribute `data-transition="true"` to your link element. Without this attribute, the transition will be direct. (Use the `<Link>` component from `react-router-dom`, not `<a ...>`)

- If you want to use `<a ...>` or another custom HTML element, you need to add the class name `goto` with the attribute `data-href=""` (this attribute should only be added if it's not a `<a ...>` element; for `<a ...>`, use `href`). If your `data-href` (or `href`) attribute is an anchor, you need to include the path of your current page if you want to stay on the same page and scroll to the anchor (e.g., if your page is your home page, you should add `/` with your anchor, like `/#my-id`). You don't need to do this with `<Link>`.

- If your element is an anchor, you can control the scroll behavior by adding `data-behavior="smooth|instant"`. If the GSAP SmoothScroller Plugin is active, the default is `smooth`. Without the plugin, the default is `instant`.

- Check the component `PageTransition.jsx` to edit your transition settings.



## Fetch

To get the Ajax base path and REST API base path, use the global object:
```
window.SYSTEM = {
    baseUrl: 'https://wpp.test/',
    adminUrl: 'https://wpp.test/admin/',
    ajaxPath: '/admin/wp-admin/admin-ajax.php',
    restPath: '/admin/wp-json/'
};
```


## Lifecycle

1. In most cases, the `main.min.css` file is loaded before the HTML body. However, with large files or slow connections, this may not always happen. If you suspect that your CSS file might not load before the body, it is advisable to use inline styles within your `<head>` element for the preloader. You can check if `window.SYSTEM.loaded.css` is `true` before closing your preloader animation.

2. The preloader is the first child element of the body, ensuring it displays without delay. This is why you may need to use inline styles for your preloader to prevent Flash of Unstyled Content (FOUC).

3. The `main.min.js` file is loaded.

4. Loader initialization occurs.

5. The application is mounted.

6. A waiting page is displayed to prevent errors while waiting for routes to load.

7. Once the routes are ready, Scroller Component, PageTransition Component and your current page Component is initialized.


## Preloader Animation + Media Download
If you put a look on your `App.jsx` file, you can see this codes:
```
window.loader = Loader.init();

window.medias = {
    download: Loader.download(), 
};
window.medias.init = window.medias.download.init();
```

- `Loader.init()` initializes the preloader animation.
- `window.medias.download.init()` handles the downloading of images and videos while the preloader is running.
- The preloader and media download process work in tandem. The preloader won’t complete unless `window.medias.download.init()` is set.
- If you don’t need to fetch any media, you still need to include `window.medias.download.init()`, but like this: `window.medias.download.init(false)`.
- Medias are given by REST API, endpoint: `/admin/wp-json/scg/v1/medias`. Put a look on REST Requests in your `functions.php` file
- To link medias to a page, use the following code: `<scg-load data-value="YOUR_MEDIA_GROUP_KEY" />`
- Don’t forget to replace `YOUR_MEDIA_GROUP_KEY` with the media group key you want call.
- To init the display of your medias, use `window.medias.download.display()`. Don't forget to link your medias with: `<scg-load data-value="YOUR_MEDIA_GROUP_KEY" />`.


> [!NOTE]
> - The preloader is implemented using a `Promise`. You can determine when the promise is resolved and call your page animation inside this method `window.medias.init.then()` if your page animation is done before the preloader has finish. Put a look on your file `src > front > js > addons > Loader.js`.
> - The display method is also implemented using a `Promise`. If the media selector (i.e., the element you are trying to target with `.querySelector`) does not exist because the display is not done, call the display method within the `.then()` to ensure that the media is displayed properly after the promise is done.

> ```
> const displayFunc = window.medias.download.display();
> displayFunc.then(() => {});
> ```



## Important Notes

- The WordPress frontend (not the React frontend, but the admin frontend part) redirects to the wp-admin. You can delete the `template_redirect` action hook in `functions.php` if you don't want this behavior.

- When you have a media file that isn't imported by your main JS App files, Webpack doesn't know you are using it and won't compile it. You need to force the import by using the appropriate JS file as needed (e.g., if you are using an audio file, go to `src > front > medias > audios` and import your file from the `audios.js` file).

- Currently, the project is not set up for multisite.

- Using the admin side is not required. If you want to use only the frontend React part, you can stop fetching pages in the `App.jsx` file.

- You need to root the `dist` directory in your virtual host or push files from this directory to your hosting. The `dist` directory is created when you install WordPress with the `get:wp` command and is populated throughout your progress.

- Minification and compression are only done in production.



## Admin Languages

***Default:*** French

***Translation:*** en_US, en_CA, en_GB, en_AU, en_NZ, en_ZA


## Changelog

***2024-09-13***

- ***Added***: REST contents replacement for acf returns when you use `StudioChampGauche\Utils\Field::replace($searh, $replace)`



***2024-09-12***
- ***Deleted:*** Text.jsx Component
- ***Added:*** Medias Download Management
- ***Added:*** REST and Ajax Request starter
- ***Updated:*** Short datas returned by pages for the routes 
- ***Updated:*** Add path data to component composing the routes 
- ***Updated:*** For demo, add props to HomePage Component

***2024-09-08***

- Updated boilerplate from v3 to v4.

> [!WARNING]
> v4 is not a continuation of v3 but a breaking change with a new headless ecosystem. If you need v3, [Download here](https://archives.champgauche.studio/wordpress-boilerplate-v3.zip)