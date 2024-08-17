v4 is new and not completed.

The v4 isn't a following of the v3 but a totally new SPA ecosystem with a React Front-End and a separated WordPress Back-End.


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