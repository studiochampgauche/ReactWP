'use strict';
import React, { useEffect } from 'react';
import Metas from '../components/Metas';

const HomePage = () => {
    
	useEffect(() => {
        
        
        return () => {
            
            
            
        }
		
	});
	
	return(
		<>
			<Metas
				title='My title'
				description='My description'
			/>
			<section>
				<div className="container">
					
				</div>
			</section>
			<section style={{ background: '#00ff00', height: '100lvh' }}></section>
			<section id="h__intro" style={{ background: '#0000ff', height: '100lvh' }}></section>
		</>
	);
	
}

export default HomePage;