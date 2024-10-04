'use strict';
import React, { useEffect, useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { useLocation, useSearchParams } from 'react-router-dom';


const Metas = ({ datas, settings }) => {


	const { pathname } = useLocation();
	const [ searchParams ] = useSearchParams();
	const [isOGURL, setOGURL] = useState(null);

	const description = datas?.seo?.description || settings?.seo?.description;
	const og_description = datas?.seo?.og_description || datas?.seo?.description || settings?.seo?.og_description || settings?.seo?.description;
	const og_image = datas?.seo?.og_image || settings?.seo?.og_image;


	useEffect(() => {

		const head = document.querySelector('head');
		const metas = head.querySelectorAll('meta[name="robots"], meta[name="description"], meta[property^="og:"]:not([property="og:site_name"]), meta[property^="article:"], meta[property^="profile:"]'); //, meta[property^="og:"]:not([property="og:site_name"]) head.querySelectorAll('meta[name="robots"], meta[name="description"], meta[property^="og:"]');
		metas.forEach(meta => head.removeChild(meta));

	}, []);


	useEffect(() => {

		const paramsString = [...searchParams.entries()]
        .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(value)}`)
        .join('&');


        let newUrl = (window.SYSTEM.baseUrl + pathname.replace('/', '') + (paramsString && '?' + paramsString)).replace('/?', '?');

        if(newUrl.endsWith('/'))
        	newUrl = newUrl.slice(0, -1);

		setOGURL(newUrl);



	}, [pathname]);


	return (
		<Helmet>

			<title>{datas?.seo?.title || datas.page_name + ' - ' + (settings?.seo?.site_name || settings.blog_name)}</title>

			<meta name="robots" content={datas?.seo?.stop_indexing || !settings.blog_public ? 'max-image-preview:large, noindex, nofollow' : 'max-image-preview:large, index, follow'} />

			{description && <meta name="description" content={description} />}

			<meta property="og:type" content={datas?.seo?.og_type || 'website'} />

			{datas?.seo?.og_type === 'article' && [
				(datas?.date && <meta key="published_time" property="article:published_time" content={datas.date} />),
				(datas?.modified && <meta key="modified_time" property="article:modified_time" content={datas.modified} />),
				(datas?.author && <meta key="author" property="article:author" content={datas.author} />)
			]}

			{datas?.seo?.og_type === 'profile' && [
				(datas?.name?.firstname && <meta key="firts_name" property="profile:first_name" content={datas.name.firstname} />),
				(datas?.name?.lastname && <meta key="last_name" property="profile:last_name" content={datas.name.lastname} />),
				(datas?.name?.username && <meta key="username" property="profile:username" content={datas.name.username} />)
			]}
			
			<meta property="og:url" content={isOGURL} />

			<meta property="og:title" content={datas?.seo?.og_title || datas?.seo?.title || datas.page_name + ' - ' + (settings?.seo?.site_name || settings.blog_name)} />

			{og_description && <meta property="og:description" content={og_description} />}

			{og_image && <meta property="og:image" content={og_image} />}

		</Helmet>
	)
};

export default Metas;