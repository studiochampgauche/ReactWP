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

***2024-06-30***

- ***Removed:*** mini-css-extract-plugin
- ***Added:*** Font import from JS files
- ***Added:*** Area for audio files
- ***Added:*** Area for video files
- ***Added:*** Import your audio files from your JS files
- ***Added:*** Import your video files from your JS files
- ***Added:*** `src > theme > the-theme > medias` directory
- ***Added:*** `src > theme > the-theme > es` directory
- ***Moved:*** "images/fonts/audios/videos" directories inner "medias" directory
- ***Moved:*** "js/scss" directories inner "es" directory


[More logs](https://github.com/studiochampgauche/wordpress-boilerplate/wiki/Changelog)