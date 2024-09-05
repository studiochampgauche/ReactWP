'use strict';
import React, { useEffect } from 'react';
import Metas from '../components/Metas';

const NotFoundPage = () => {
    
	useEffect(() => {
        
        
        return () => {
            
            
            
        }
		
	});
	
	return(
		<>
			<Metas
				title='Page not found'
			/>
			<section id="not__intro">
                <div className="container">
                    
                </div>
            </section>
            <section style={{ background: '#ff0000', height: '100lvh' }}></section>
            <section style={{ background: '#000000', height: '100lvh' }}></section>
            <section style={{ background: '#ff0000', height: '100lvh' }}></section>
            <section id="not__me" style={{ background: '#000000', height: '100lvh' }}></section>
            <section style={{ background: '#ff0000', height: '100lvh' }}></section>
            <section style={{ background: '#000000', height: '100lvh' }}></section>
		</>
	);
	
}

export default NotFoundPage;