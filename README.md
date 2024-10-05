# Headless React-WordPress Boilerplate

This boilerplate help us to quickly set up a React Single Page Application (SPA) with a WordPress and ACF Pro backend.


## Features

- Webpack setup
- SCSS/SASS support
- JavaScript & CSS minification
- Image Compression
- ESM format
- App routing
- Document head management
- Smooth Page Transition
- Smooth Scrolling
- Loader Concept
- Cache Concept


## Ready

- React Router
- React Helmet
- GSAP Premium
- Free Font Awesome



## Requirements

- NodeJS (minimum tested: v20.15.0)
- ACF Pro License
- PHP 8.2+
- GSAP Club Membership


## Installation Guide

1. Clone the repository.
2. Navigate to `constructor`.
3. Run `npm run get:wp` to install WordPress.
4. Add `.npmrc` for GSAP autentication.
5. Run `npm i` to install Node modules.
6. Duplicate `wp-config-sample.php` in `src > back` to `wp-config.php` and configure it.
7. Update URLs in `src > front > js > App.jsx` within `window.SYSTEM`.
8. Add your server configuration file in `src > front > template` directory. More explanations [here](https://github.com/studiochampgauche/headless-react-wordpress-boilerplate?tab=readme-ov-file#web-server-configuration).
9. Run `npm run build:front:back` or `npm run watch:front:back` for initialize setup.
10. Configure your WordPress backend `(yourdomain.com/admin)`.
11. Activate the Champ Gauche Core Theme, Champ Gauche Core Plugin, and the ACF Plugin.
12. Remove other default plugins, themes, pages, and posts.
13. Create and set your Home Page as static.
14. Update the permalink structure to anything other than the default Plain option.
15. Start working!



## Web Server Configuration

- Place your production files from `dist` directory on your web server, or set this directory as the root.
- Configure your web server to redirect all URL requests to the `index.php` file, unless the requested file or directory physically exists on the server.

For example, on Apache, we add this in the `.htaccess`:
```
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
```

> [!NOTE]  
> The `dist` directory is created when you install WordPress with the `npm run get:wp` command and is populated throughout your progress.



## Frontend-Backend Interaction

- In a headless setup, the frontend is separate from the backend, communicating via fetch requests.
- Every page, post, custom post type, and some users (like authors) that make up your routes must be linked to a Component. This requires a field for each admin element to assign a component name, which you then map in the ecosystem.
- To map components, add them to the `componentMap` object in `src > front > js > App.jsx`.

> [!NOTE]  
> - Pages and posts already have the required field. For custom post types, go to (yourdomain.com/admin/wp-admin/admin.php?page=site-settings), select your CPT in the `Modules` tab under `Component`. For add the field like for the users, edit the `render.php` file of the Champ Gauche Core Plugin around line 655.
> - If you have many posts and don't want to manually assign a Component Name for each, you can either create logic in App.jsx or use ACF hooks to auto-populate the field.
>
> ### Ajax and REST API
> - Retrieve the Ajax and REST API base paths with `window.SYSTEM.ajaxPath` and `window.SYSTEM.restPath`.



## Page Transitions

- Add `data-transition="true"` to `<Link>` or `<a ...>` elements for transition, otherwise, they will be direct.
- For custom HTML elements, use class `goto` with `data-href=""`.
- If `data-href` or `href` is an anchor, include the current page path if you want stay on the current page. You don't need to do this for `to` attribute.
- When you stay on the same page, control anchor scroll behavior with `data-behavior="smooth|instant"`. Defaults: "smooth" with GSAP SmoothScroller, "instant" without.
- When you change page, you can't control the scroll behavior. The scroll behavior'll be "instant".
- Edit transition settings in `src > front > js > components > PageTransition.jsx`.



## Loader Concept

The loader concept initiates a preloading animation and the downloading of fonts, images, videos and audios. It make sure you have your medias, styles and the GSAP SmoothScroller ready before allowing visitors to navigate.

You'll find the preloader HTML and its inline styles in `src > front > template > index.php`.

You'll find the preloader JavaScript in `src > front > js > addons > Loader.js`:

- `Loader.init()` initiates the preloader animation.
- `Loader.downloader().init()` initiates the downloading of fonts, images, videos and audios. At the same time, it check if we have the styles.
- `Loader.downloader().display()` replaces each target by its media.


We initiates preloading animation and downloads in `src > front > js > App.jsx`:
```
window.loader = {
    anim: Loader.init(),
    downloader: Loader.downloader(),
    isLoaded: {
        css: false,
        fonts: false,
        images: false,
        videos: false,
        audios: false
    }
};
window.loader.medias = window.loader.downloader.init();
```

All functions implemented with the Loader are using a `Promise`:

- `window.loader.anim.then(() => {})` allow you to know when your preloader animation is resolved
- `window.loader.medias.then(({ mediaGroups, fonts }) => {})` allow you to play with images, videos, audios and fonts when they are ready
- `window.loader.downloader.display()` allow you to display images, videos and audios when they are ready. Place your display function in a variable named `displayFunc` and use `displayFunc.then(() => {})` for call something when all medias are displayed.

> [!NOTE]
>- `Loader.init()` can complete only if `Loader.downloader().init()` is launched too. If no media needs to be fetched, still call `Loader.downloader().init(false)`.
>- Medias are fetched via the REST API at `/admin/wp-json/scg/v1/medias`. Refer to the REST requests in your `src > back > theme > functions.php`. `target` parameter is not required.
>- When you use the display function with your component, use `<scg-load data-value="YOUR_MEDIA_GROUP_KEY" />` to link the good group of medias associated.
>- Using the display function will loop all `<scg-load />` element that can be found. Use first parameter for select a specific `data-value`. Default is `scg-load`.
>- When you select a specific data-value, you can delete your element after using it by adding the second parameter "remove" to `true`. Default is `false`.


## Cache Concept

The Cache concept uses the Cache Service Worker API. It lets you add your endpoints (or others) to the cache, and automatically caches media you upload using the Loader concept.

For activate the cache uncomment the cache initializer in `src > front > js > App.jsx`:
```
//Cache.init('scg-cache').then(() => {

    window.loader = {
        anim: Loader.init(),
        downloader: Loader.downloader(),
        isLoaded: {
            css: false,
            fonts: false,
            images: false,
            videos: false,
            audios: false
        }
    };
    window.loader.medias = window.loader.downloader.init();

//});
```

***How it's work***

For exemple, put a look on what we have do for get pages that composing your routes in `src > front > js > App.jsx`:
```
try{

    const pagesPromise = fetch(await Cache.get(window.SYSTEM.restPath + 'wp/v2/pages?_fields=id,title,link,acf'));


    const [callPages] = await Promise.all([pagesPromise]);

    if(!callPages.ok) throw new Error('Pages can\'t be loaded');


    Cache.put(callPages.url, callPages.clone());


    const pages = await callPages.json();

}
```


> [!NOTE]
>- Cache Concept works only on secure URLs.
>- For now, the cache don't update when you change a media with the same url. It's coming.
>- For now, the no more used medias are not deleted from the cache. It's coming.
>- While waiting for the coming elements, you need to use the Cache API by your own.
>- It's ok to have `Cache.put()` even if you don't need to do it, because the function'll work only if the url is not on protocol `blob:`.


## To Know

- Multisite isn't ready.
- Minification and compression works only in production mode.
- Image compression supports GIF, JPG, PNG, and SVG; WEBP support is not implemented yet.
- Image compression works only for the frontend part. If you manage your medias via backend, you need to manage it at your hands.
- Use `<Wrapper value={my_value} />` for insert HTML returns when you can't use `dangerouslySetInnerHTML`.
- If you prefer using `Sass` instead of `Scss`, use `.sass` extension instead of `.scss` and remove all brackets.
- Using `mailto:`, `tel:`, or other schemas/protocols that are not `http` or `https` without `target` attribute will create a bug on click. e.g. use `target="_self"` for mailto or others that are not `http` or `https`.
- On the frontend, if a media file isn’t directly imported in your main JS app files, Webpack won’t recognize or compile it. To ensure Webpack processes the file, you need to manually import it into the relevant JS file. For example, if you're using an audio file, navigate to `src > front > medias > audios` and import the file within `audios.js`.



## Admin Languages

***Default:*** French

***Translation:*** en_US, en_CA, en_GB, en_AU, en_NZ, en_ZA


## Changelog
[Here](https://github.com/studiochampgauche/headless-react-wordpress-boilerplate/blob/master/CHANGELOG.md)


## What's Next
- Update cache when you do a change up for a same url
- Automatically remove no more used url from the cache
- Cache API for fonts
- Maintenance mode