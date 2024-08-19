import React, { useEffect } from 'react';
import Metas from '../components/Metas';
import { useGScrollReady } from '../contexts/ready/Scroller';

const HomePage = () => {
	
    const { isGScrollReady } = useGScrollReady();
    
	useEffect(() => {
		
		if(!isGScrollReady) return;

		//window.gscroll.paused(true)

		return () => {
            
            
			
		}
		
	}, [isGScrollReady])
	
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