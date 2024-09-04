'use strict';
import React, { useEffect } from 'react';
import Metas from '../components/Metas';

const HomePage = () => {
    
	useEffect(() => {
		
		
	})
	
	return(
		<>
			<Metas
				title='My title'
				description='My description'
			/>
			<section id="h__intro">
				<div className="container">
					
				</div>
			</section>
			<section style={{ background: '#00ff00', height: '100lvh' }}></section>
			<section style={{ background: '#0000ff', height: '100lvh' }}></section>
		</>
	);
	
}

export default HomePage;