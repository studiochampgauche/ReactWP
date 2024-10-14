'use strict';
import React, { useEffect, useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { useLocation, useSearchParams } from 'react-router-dom';


const Metas = ({ extraDatas, seo }) => {

	const { pathname } = useLocation();
	const [ searchParams ] = useSearchParams();
	const [isOGURL, setOGURL] = useState(null);

	const title = seo?.title || seo.pageTitle + ' - ' + defaultSEO.blogName;
	const og_title = seo.og_title || title;
	const description = seo.description || defaultSEO.description;
	const og_description = seo.og_description || defaultSEO.og_description || description;
	const og_image = seo.og_image || defaultSEO.og_image;

	useEffect(() => {

		const head = document.querySelector('head');
		const metas = head.querySelectorAll('meta[name="robots"], meta[name="description"], meta[property^="og:"]:not([property="og:site_name"]), meta[property^="article:"], meta[property^="profile:"]'); //, meta[property^="og:"]:not([property="og:site_name"]) head.querySelectorAll('meta[name="robots"], meta[name="description"], meta[property^="og:"]');
		metas.forEach(meta => head.removeChild(meta));

	}, []);


	useEffect(() => {

		const paramsString = [...searchParams.entries()]
        .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(value)}`)
        .join('&');


        let newUrl = (SYSTEM.baseUrl + pathname.replace('/', '') + (paramsString && '?' + paramsString)).replace('/?', '?');

        if(newUrl.endsWith('/'))
        	newUrl = newUrl.slice(0, -1);

		setOGURL(newUrl);



	}, [pathname]);


	return (
		<Helmet>

			<title>{title}</title>

			<meta name="robots" content={seo.stop_indexing || !SYSTEM.public ? 'max-image-preview:large, noindex, nofollow' : 'max-image-preview:large, index, follow'} />

			<meta property="og:type" content={seo.og_type || 'website'} />

			<meta property="og:url" content={isOGURL} />

			{description && <meta name="description" content={description} />}

			<meta property="og:title" content={og_title} />

			{og_description && <meta property="og:description" content={og_description} />}

			{og_image && <meta property="og:image" content={og_image} />}

			{seo.og_type === 'article' && [
				(extraDatas.date && <meta key="published_time" property="article:published_time" content={extraDatas.date} />),
				(extraDatas.modified && <meta key="modified_time" property="article:modified_time" content={extraDatas.modified} />),
				(extraDatas.author && <meta key="author" property="article:author" content={extraDatas.author} />)
			]}

			{seo.og_type === 'profile' && [
				(extraDatas?.name?.firstname && <meta key="firts_name" property="profile:first_name" content={extraDatas.name.firstname} />),
				(extraDatas?.name?.lastname && <meta key="last_name" property="profile:last_name" content={extraDatas.name.lastname} />),
				(extraDatas?.username && <meta key="username" property="profile:username" content={extraDatas.username} />)
			]}

		</Helmet>
	)
};

export default Metas;