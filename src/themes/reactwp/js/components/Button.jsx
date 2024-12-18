'use strict';
import React from 'react';
import { Link } from 'react-router-dom';

const Button = ({ to = null, text, className = null, before, after, ...props }) => {

	const Tag = to ? Link : 'button';

	const tagProps = {
		to: (to || undefined),
		className: (className ? `btn ${className}` : 'btn'),
		...props
	}

	return(
		<Tag {...tagProps}>
			{before && (<div className="btn-before" dangerouslySetInnerHTML={{ __html: before }} />)}
			{text && (<span>{text}</span>)}
			{after && (<div className="btn-after" dangerouslySetInnerHTML={{ __html: after }} />)}
		</Tag>
	);

}

export default Button;