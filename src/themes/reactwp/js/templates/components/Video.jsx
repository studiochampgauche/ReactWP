'use strict';
import React, { useRef } from 'react';
import { Link } from 'react-router-dom';

const Video = ({ className = null, ...props }) => {

	const ref = useRef(null);

	const tagProps = {
		className: (className ? `video-container ${className}` : 'video-container'),
		...props
	}

	return(
		<div {...tagProps}>
			<div ref={ref} className="inner-video">
				<div className="video"></div>
			</div>
		</div>
	);

}

export default Video;