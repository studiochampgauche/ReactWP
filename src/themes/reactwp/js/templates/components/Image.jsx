'use strict';
import React, { useRef } from 'react';
import { Link } from 'react-router-dom';

const Image = ({ className = null, ...props }) => {

	const ref = useRef(null);

	const tagProps = {
		className: (className ? `img-container ${className}` : 'img-container'),
		...props
	}

	return(
		<div {...tagProps}>
			<div ref={ref} className="inner-img">
				<div className="img"></div>
			</div>
		</div>
	);

}

export default Image;