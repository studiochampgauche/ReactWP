v4 is new and not completed.

The v4 isn't a following of the v3 but a totally new SPA ecosystem with a React Front-End and a separated WordPress Back-End.


## Requirements

- NodeJS (tested with v20.15.0)
- ACF Pro 6.2.6 or higher
- PHP Version 8.2 or higher
- A premium or commercial subscription to the GSAP Club. (You need to and the .npmrc file in `constructor` directory for authenticate your account.)


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


## How transition page work

- Add attribute `data-transition="true"` to your link element. Without this attribute, the change'll be direct.

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