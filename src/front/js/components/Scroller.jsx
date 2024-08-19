import React, { useEffect } from 'react';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { ScrollSmoother } from 'gsap/ScrollSmoother';
import { useGScrollReady } from '../contexts/ready/Scroller';

const Scroller = ({ children }) => {
	
	const { setIsGScrollReady } = useGScrollReady();

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


			setIsGScrollReady(true);
			
			
			return () => {
				
				if(window.gscroll){
					
					window.gscroll.kill();
					window.gscroll = null;

					setIsGScrollReady(false);
					
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