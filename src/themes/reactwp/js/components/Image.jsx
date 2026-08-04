'use strict';
import React, { forwardRef, useRef } from 'react';
import { sanitizeDomProps } from '../inc/domProps';

const Image = forwardRef(function Image({ className = null, ...props }, ref){

	const localRef = useRef(null);
	const imageRef = ref || localRef;

	const tagProps = {
		className: (className ? `img-container ${className}` : 'img-container'),
		...sanitizeDomProps(props)
	}

	return(
		<div ref={imageRef} {...tagProps}>
			<div className="inner-img">
				<div className="img"></div>
			</div>
		</div>
	);

});

export default Image;
