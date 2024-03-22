# Our WordPress Boilerplate
Build immersive 2D or 3D WordPress websites Awwwards in "no time" with our Boilerplate.

![minimum-php](https://img.shields.io/badge/Minimum%20PHP-8.2-ff0000.svg)
![multisite-ready](https://img.shields.io/badge/Multisite%20Ready-no-fcba03.svg)
![en-ready](https://img.shields.io/badge/English%20Ready-yes-44cc11.svg)
![fr-ready](https://img.shields.io/badge/French%20Ready-yes-44cc11.svg)

## Guide
1. Put the WordPress Production Files on root
2. Install your Node Modules in `src > built`
3. In `src` directory, duplicate `wp-config-sample.php` to `wp-config.php` and setup it
4. If is the first setup for your project, run `gulp prod-watch` or `gulp prod` in `src > built`. If not, continue watching by only use `gulp`.
5. Start working


## Ready Libraries to import
- Pixi
- Three
- Barba
- Granim
- GSAP


# Core Plugin

![required-yes](https://img.shields.io/badge/Required-yes-ff0000.svg)

We have build a plugin that help us to handle repetitive needs in each project. [Check it out here!](https://wpboilerplate.champgauche.studio)


> [!IMPORTANT]  
> You'll need ACF Pro to play with our Core Plugin. [Get it here](https://www.advancedcustomfields.com/pro).

# Admin Languages

***Default:*** French

***Translation:*** en_US, en_CA, en_GB, en_AU, en_NZ, en_ZA

# Demo

- [Plugin Manipulation Examples](/src/themes/the-theme/template/functions.php)

# Quick Docs

## PHP

### Call an ACF Field
Use `scg::field($field_name, $id)` or `StudioChampGauche\Utils\Field::get($field_name, $id)` to call an ACF Field. `$field_name` is required. If you do not enter the `$id` parameter, the function will first look in 'option' and then look at the current page.

> [!TIP]  
> You can use `StudioChampGauche\Utils\Field::replace()` in the `acf/init` hook for replace some parts returned from `::get` or `::field`. (e.g: `StudioChampGauche\Utils\Field::replace(['{MAIN_EMAIL}'], [scg::field('contact_email')])`)