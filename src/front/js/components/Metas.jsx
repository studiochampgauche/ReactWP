'use strict';
import React from 'react';
import { Helmet } from 'react-helmet-async';
import { useLocation } from 'react-router-dom'


const Metas = ({ robots, title, ogTitle, description, ogDescription, image, ogSiteName, ogUrl, ogType }) => {


	const { pathname } = useLocation();


	return (
		<Helmet>
			<meta name="robots" content={robots || window.defaultMetas.robots} />
			<title>{title || window.defaultMetas.siteName}</title>
			<meta name="description" content={description || window.defaultMetas.description} />
			<meta property="og:type" content={ogType || 'website'} />
			<meta property="og:url" content={ogUrl || window.SYSTEM.baseUrl.slice(0, -1) + (pathname !== '/' ? pathname : '') + '/'} />
			<meta property="og:site_name" content={ogSiteName || window.defaultMetas.siteName} />
			<meta name="og:title" content={ogTitle || title || window.defaultMetas.siteName} />
			<meta name="og:description" content={ogDescription || description || window.defaultMetas.description} />
			<meta property="og:image" content={image || window.defaultMetas.image} />
		</Helmet>
	)
};

export default Metas;