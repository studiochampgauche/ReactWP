import React, { useEffect } from 'react';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { ScrollSmoother } from 'gsap/ScrollSmoother';
import { useForce } from '../contexts/Force';

const Scroller = ({ children }) => {
	
	const { setIsReady } = useForce();

	useEffect(() => {
		
        gsap.registerPlugin(ScrollTrigger, ScrollSmoother);
		
		
		const mm = gsap.matchMedia();
		
		mm.add({
			isPointer: '(pointer: fine)',
			isNotPointer: '(pointer: coarse), (pointer: none)'
		}, async (context) => {
			
			let { isPointer, isNotPointer } = context.conditions;
			
			window.gscroll = await ScrollSmoother.create({
				wrapper: '#pageWrapper',
				content: '#pageContent',
				ignoreMobileResize: true,
				normalizeScroll: (isPointer ? true : false),
				smooth: 2.25
			});


			setIsReady(true);
			
			
			return () => {
				
				if(window.gscroll){
					
					window.gscroll.kill();
					window.gscroll = null;

					setIsReady(false);
					
				}
				
			}
			
		});

		
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