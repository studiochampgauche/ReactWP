import React from 'react';
import Logo from '../../medias/images/dev-tag.svg';

const MainLogo = (props) => {
	
	return(
		<>
		<img src={Logo} alt={props.alt || 'alt test'} {...props} />
		</>
	)
	
}
export default MainLogo