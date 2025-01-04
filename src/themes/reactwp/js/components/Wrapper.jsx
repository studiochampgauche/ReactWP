'use strict';
import React from 'react';

const Wrapper = ({ value }) => {

	return(
		<rwp-wrap dangerouslySetInnerHTML={{ __html: value }} />
	);
	
}


export default Wrapper;