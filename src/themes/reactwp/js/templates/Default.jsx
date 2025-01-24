'use strict';
import React from 'react';
import {Link} from 'react-router-dom';

const Default = () => {
	
	return(
		<>
			<section style={{
				background: '#00ff00',
				height: '100lvh'
			}}>
				<span>Hello World!</span>
				<Link to="/ok">ok-</Link>
				<Link to="/accueil/test">test</Link>
			</section>
		</>
	);
	
}

export default Default;