'use strict';
import React, { forwardRef, useRef } from 'react';
import { sanitizeDomProps } from '../inc/domProps';

const Audio = forwardRef(function Audio({ className = null, ...props }, ref){

	const localRef = useRef(null);
	const audioRef = ref || localRef;

	const tagProps = {
		className: (className ? `audio-container ${className}` : 'audio-container'),
		...sanitizeDomProps(props)
	}

	return(
		<div ref={audioRef} {...tagProps}>
			<div className="inner-audio">
				<div className="audio"></div>
			</div>
		</div>
	);

});

export default Audio;
