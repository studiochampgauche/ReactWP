'use strict';
import React, { forwardRef, useRef } from 'react';

const Audio = forwardRef(function Audio({ className = null, ...props }, ref){

	const localRef = useRef(null);
	const audioRef = ref || localRef;

	const tagProps = {
		className: (className ? `audio-container ${className}` : 'audio-container'),
		...props
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