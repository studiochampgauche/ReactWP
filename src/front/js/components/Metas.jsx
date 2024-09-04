'use strict';
import React from 'react';
import { Helmet } from 'react-helmet-async';

const Metas = ({ title, description }) => (
	<Helmet>
		<title>{title}</title>
		<meta name="description" content={description} />
	</Helmet>
);

export default Metas;