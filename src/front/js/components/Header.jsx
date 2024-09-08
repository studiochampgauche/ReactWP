'use strict';
import React from 'react';
import { Link } from 'react-router-dom';
import MainNav from './Nav.jsx';
import MainLogo from './Logo.jsx';

const Header = () => {
	
	return(
		<header>
			<div className="plywood">
				<div className="container">
					<Link className="logo" to="/" data-transition="true">
						<MainLogo alt="Demo" />
					</Link>
					<MainNav />
				</div>
			</div>
		</header>
	);
	
}


export default Header