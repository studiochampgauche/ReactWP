v4 is new and not completed.

The v4 isn't a following of the v3 but a totally new React Front-End SPA ecosystem with a separated WordPress Back-End.


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

... Is coming




## How transition page work

- Add attribute `data-transition="true"` to your link element. Without this attribute, the change'll be direct. (You need to use `<Link>`, not `<a ...>`)

- If your link has anchor, you can constrol the behavior by adding `data-behavior="smooth|instant|auto"`. If GSAP SmoothScroller Plugin is active, the default is `smooth`. Without the plugin, default is `instant`.

- Checkout the component `Transitor.jsx` for edit your transition



## Fetch

We have set the ajaxurl and a basepath for the call to the REST inner a global object:
```
window.SYSTEM = {
    ajaxurl: '/admin/wp-admin/admin-ajax.php',
    restBasePath: '/admin/wp-json/'
};
```


## To know

- WordPress Front-end (not the React Front-end, but the admin front-end part) redirect to the the wp-admin. You can delete the template_redirect action hook inner the functions.php if you don't want that.

- The WordPress admin panel is still on v3. Some elements of the admin are useless.


#### My Files audios/videos/images/fonts/ doesn't transfer from `src` to `dist`

- When you have a media file that isn't import by your main JS App files, webpack doesn't know you use it and he don't compile it. You need to force the import by use the JS file according to your needs. (e.g. if you play with an audio file, you need to go in `src > front > medias > audios` and import your file from the `audios.js` file.) 