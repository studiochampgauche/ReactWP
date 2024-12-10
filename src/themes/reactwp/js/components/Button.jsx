'use strict';
import React from 'react';
import { Link } from 'react-router-dom';

const Button = ({ to = null, text, className = null, ...props }) => {

	const Tag = to ? Link : 'button';

	const tagProps = {
		to: (to || undefined),
		className: (className ? `btn ${className}` : 'btn'),
		...props
	}

	return(
		<Tag {...tagProps}>
			<span>{text}</span>
		</Tag>
	);

}

export default Button;