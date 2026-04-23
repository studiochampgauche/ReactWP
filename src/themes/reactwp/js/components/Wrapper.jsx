'use strict';
import React from 'react';

const Wrapper = ({ value, ...props }) => {

	return(
		<rwp-wrap {...props}>
			{value}
		</rwp-wrap>
	);
	
}


export default Wrapper;