'use strict';
import React from 'react';

const Wrapper = ({ value }) => {

	return(
		<scg-wrap dangerouslySetInnerHTML={{ __html: value }} />
	);
	
}


export default Wrapper;