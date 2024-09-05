'use strict';
import React from 'react';
import { Helmet } from 'react-helmet-async';

const Metas = ({ title, ogTitle, description, ogDescription }) => (
	<Helmet>
		<title>{title || window.defaultMetas.siteName}</title>
		<meta name="description" content={description || window.defaultMetas.description} />
		<meta name="og:title" content={ogTitle || title || window.defaultMetas.siteName} />
		<meta name="og:description" content={ogDescription || description || window.defaultMetas.description} />
	</Helmet>
);

export default Metas;