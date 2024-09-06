'use strict';
import React, { Fragment } from 'react';

const Text = ({ value }) => {
	
	return(
		<div className="text">
			{value.split('\r\n\r\n').map((paragraph, i) => (

				<p key={i} {...({dangerouslySetInnerHTML: { __html: paragraph.split('\r\n').join('<br />') }})} />

			))}
		</div>
	);
	
}


export default Text;