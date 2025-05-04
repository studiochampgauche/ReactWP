'use strict';
import React from 'react';

const Wrapper = ({ value, ...props }) => {

	return(
		<rwp-wrap {...props} dangerouslySetInnerHTML={{ __html: value }} />
	);
	
}


export default Wrapper;