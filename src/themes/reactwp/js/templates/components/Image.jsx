'use strict';
import React, { forwardRef, useRef } from 'react';

const Image = forwardRef(function Image({ className = null, ...props }, ref){

	const localRef = useRef(null);
	const imageRef = ref || localRef;

	const tagProps = {
		className: (className ? `img-container ${className}` : 'img-container'),
		...props
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