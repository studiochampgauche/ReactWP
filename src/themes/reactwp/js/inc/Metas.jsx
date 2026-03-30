'use strict';
import React, { useEffect, useState, useRef } from 'react';
import { Helmet } from 'react-helmet-async';
import { useLocation, useSearchParams } from 'react-router-dom';

const Metas = ({ seo }) => {
    const { pathname } = useLocation();
    const [searchParams] = useSearchParams();
    const [isClientHeadReady, setClientHeadReady] = useState(false);
    const prevPathnameRef = useRef(pathname);

    const title = seo[`title_${CL.value}`] || `${seo.pageName} - ${RWP_SEO.blogName}`;
    const og_title = seo[`og_title_${CL.value}`] || title;
    const description = seo[`description_${CL.value}`] || RWP_SEO[`description_${CL.value}`];
    const og_description = seo[`og_description_${CL.value}`] || RWP_SEO[`description_${CL.value}`] || description;
    const og_image = seo.og_image || RWP_SEO.og_image;

    const paramsString = [...searchParams.entries()]
        .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(value)}`)
        .join('&');

    let ogUrl = (SYSTEM.baseUrl + pathname + (paramsString ? '?' + paramsString : '')).replace('/?', '?');

    if(ogUrl.endsWith('/')){
        ogUrl = ogUrl.slice(0, -1);
    }

    useEffect(() => {
        if(isClientHeadReady){
            prevPathnameRef.current = pathname;
            return;
        }

        if(prevPathnameRef.current !== pathname){
            document.head.querySelectorAll(
                'title, meta[name="description"], meta[property^="og:"], meta[property^="article:"], meta[property^="profile:"]'
            ).forEach((el) => el.remove());

            setClientHeadReady(true);
        }

        prevPathnameRef.current = pathname;
    }, [pathname, isClientHeadReady]);

    if(!isClientHeadReady){
        return null;
    }

    return (
        <Helmet>
            <title>{title}</title>
            <meta property="og:type" content={seo.og_type || 'website'} />
            <meta property="og:url" content={ogUrl} />
            {description && <meta name="description" content={description} />}
            <meta property="og:title" content={og_title} />
            {og_description && <meta property="og:description" content={og_description} />}
            {og_image && <meta property="og:image" content={og_image} />}
        </Helmet>
    );
};

export default Metas;