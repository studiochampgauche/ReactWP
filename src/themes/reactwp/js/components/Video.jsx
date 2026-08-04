'use strict';
import React, { forwardRef, useRef } from 'react';
import { sanitizeDomProps } from '../inc/domProps';

const Video = forwardRef(function Video({ className = null, ...props }, ref){

	const localRef = useRef(null);
	const videoRef = ref || localRef;

	const tagProps = {
		className: (className ? `video-container ${className}` : 'video-container'),
		...sanitizeDomProps(props)
	}

	return(
		<div ref={videoRef} {...tagProps}>
			<div className="inner-video">
				<div className="video"></div>
			</div>
		</div>
	);

});

export default Video;
