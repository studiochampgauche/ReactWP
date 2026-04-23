'use strict';
import React, { forwardRef, useRef } from 'react';
import Button from './Button';
import Wrapper from './Wrapper';

const Contents = forwardRef(function Contents({ uptitle, title, subtitle, text, buttons, titleTag, className = null, ...props }, ref){

	const localRef = useRef(null);
	const contentsRef = ref || localRef;

	const pass = (uptitle || subtitle || title || text || buttons) ? true : false;

	const TitleTag = titleTag || 'h2';

	const tagProps = {
		className: (className ? `contents ${className}` : 'contents'),
		...props
	}

	return(
		pass && (
			<div ref={contentsRef} {...tagProps}>
				<div className="inner-contents">
					{uptitle && (
						<span className="uptitle">{uptitle}</span>
					)}
					{title && (
						<TitleTag>{title}</TitleTag>
					)}
					{subtitle && (
						<span className="subtitle">{subtitle}</span>
					)}
					{text && (
						<Wrapper value={text} />
					)}
					{buttons && (
						<div className="buttons">
							{buttons.map((button, i) => (
								<Button
									key={i}
									{...button}
									to={button?.url ?? button?.to}
									target={button?.target ?? (button?.new_tab ? '_blank' : undefined)}
								/>
							))}
						</div>
					)}
				</div>
			</div>
		)
	);

});

export default Contents;