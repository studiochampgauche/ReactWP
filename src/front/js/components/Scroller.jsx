import React, { useEffect } from 'react';
//import { gsap } from 'gsap';
//import { ScrollTrigger } from 'gsap/ScrollTrigger';
//import { ScrollSmoother } from 'gsap/ScrollSmoother';

const Scroller = ({ children }) => {
	
    window.gscroll = null;
	
	useEffect(() => {
		
        /*gsap.registerPlugin(ScrollTrigger, ScrollSmoother);
        
        
        window.gscroll = ScrollSmoother.create({
			wrapper: '#pageWrapper',
			content: '#pageContent',
			ignoreMobileResize: true,
			smooth: 1
		});*/
		
		return () => {
			
		}
		
		
	}, []);
	
	
	return(
		 <>
            <div id="pageWrapper">
                <div id="pageContent">
			        {children}
                </div>
            </div>
		</>
	)
	
}
export default Scroller;