'use strict';
import React, { Fragment } from 'react';

const Text = ({ value, html = false }) => {
	
	return(
		<div className="text">
			{value.split('\r\n\r\n').map((paragraph, i) => (

				<p key={i}>
					
					{paragraph.split('\r\n').map((line, j) => (

						<Fragment key={j}>
							{line}
							{j < paragraph.split('\r\n').length - 1 && <br />}
						</Fragment>

					))}

				</p>

			))}
		</div>
	);
	
}


export default Text;