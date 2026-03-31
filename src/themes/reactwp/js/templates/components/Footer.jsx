'use strict';
import React, { forwardRef, useRef } from 'react';

const Footer = forwardRef(function Footer({ className = null, ...props }, ref){

	const localRef = useRef(null);
	const footerRef = ref || localRef;

	const tagProps = {
		className: className,
		...props
	}

	return(
		<footer ref={footerRef} {...tagProps}></footer>
	);

});

export default Footer;