## Changelog

***2024-10-11***
- ***removed***: hideForSeo Class Name


***2024-10-11***
- ***Fixed***: Page Transition

***2024-10-05***
- ***Added***: Cache Concept
- ***Added***: File `src > front > js > addons > Cache.js`
- ***Added***: Cache on REST API endpoint
- ***Updated***: How we cache medias
- ***Updated***: Preloader Panel Demo
- ***Changed***: Stop removing `<scg-load />` elements (temporary)


***2024-10-04***
- ***Updated***: Display function. Now, you can select specific `scg-load` elements instead of loop all each time you call the function.


***2024-10-03***

- ***Added***: Server-Side Rendering for the `<head>` element
- ***Added***: Favicon management from backend
- ***Added***: Global SEO management from backend
- ***Added***: `/admin/wp-json/scg/v1/settings/` endpoint
- ***Fixed***: Robots crawler
- ***Fixed***: HTTP response code for not founded page
- ***Changed***: How Metas.jsx work (React Helmet)
- ***Changed***: We stop redirect wordpress frontend part


***2024-09-28***

- ***Changed***: Move "what's loaded" from `window.SYSTEM` to `window.loader`


***2024-09-23***

- ***Fixed***: A non-secure website cannot load because the Cache API does not exist.


***2024-09-22***

- ***Added***: Medias caching


***2024-09-21***

- ***Fixed***: When you use `window.loader.downloader.display()` you can now have multiple `<scg-load data-value="" />`
- ***Added***: Fonts background preloading
- ***Added***: Audios background preloading
- ***Changed***: Loader concept


***2024-09-14***

- ***Added***: `Wrapper.jsx` component


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