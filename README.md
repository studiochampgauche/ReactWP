# Our WordPress Boilerplate

## Installation Guide

1. Git clone this repo
2. Go to `src > constructor`
3. Install WordPress `npm run get:wp`
4. Install the Node Modules `npm i`
5. In `src` directory, duplicate `wp-config-sample.php` to `wp-config.php` and setup it
6. If is the first setup for your project, run `npm run build:dev` or `npm run watch:dev` in `src > constructor`. If not, continue watching with `npm run watch:dev`.
7. Start working

## Last Changelog

***2024-07-03***

- ***Fixed:*** Recursive replacement turn numeric value to string. Fixed.