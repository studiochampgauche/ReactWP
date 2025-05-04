'use strict';
import React from 'react';
import { Link } from 'react-router-dom';
import Button from './Button';
import Wrapper from './Wrapper';

const Contents = ({ uptitle, title, subtitle, text, buttons, titleTag, className = null, ...props }) => {

	const pass = (uptitle || subtitle || title || text || buttons) ? true : false;

	const TitleTag = titleTag || 'h2';

	const tagProps = {
		className: (className ? `contents ${className}` : 'contents'),
		...props
	}

	return(
		pass && (
			<div {...tagProps}>
				<div className="inner-contents">
					{uptitle && (
						<span className="uptitle" dangerouslySetInnerHTML={{ __html: uptitle }} />
					)}
					{title && (
						<TitleTag dangerouslySetInnerHTML={{ __html: title }} />
					)}
					{subtitle && (
						<span className="subtitle" dangerouslySetInnerHTML={{ __html: subtitle }} />
					)}
					{text && (
						<Wrapper value={text} />
					)}
					{buttons && (
						<div className="buttons">
							{buttons.map((button, i) => (
								<Button
									key={i}
									to={button?.url}
									text={button?.text}
									target={button?.new_tab ? '_blank' : undefined}
									className={button.className}
								/>
							))}
						</div>
					)}
				</div>
			</div>
		)
	);

}

export default Contents;