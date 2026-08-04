'use strict';
import React from 'react';
import { sanitizeDomProps } from '../inc/domProps';

const Wrapper = ({ value, ...props }) => {

	return(
		<rwp-wrap {...sanitizeDomProps(props)}>
			{value}
		</rwp-wrap>
	);
	
}


export default Wrapper;
