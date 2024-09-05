'use strict';
import React, { useEffect } from 'react';

const HomePage = ({ acf }) => {
    
    

	useEffect(() => {

        console.log(acf);

        return () => {
            
            
            
        }
		
	});
	
	return(
		<>
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