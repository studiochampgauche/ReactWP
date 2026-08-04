'use strict';
import React, { forwardRef, useRef } from 'react';
import { sanitizeDomProps } from '../inc/domProps';

const Footer = forwardRef(function Footer({ show = false, className = null, ...props }, ref){

	const localRef = useRef(null);
	const footerRef = ref || localRef;

	if(!show){
		return null;
	}

	const tagProps = {
		className: className,
		...sanitizeDomProps(props)
	}

	return(
		<footer ref={footerRef} {...tagProps}></footer>
	);

});

export default Footer;
