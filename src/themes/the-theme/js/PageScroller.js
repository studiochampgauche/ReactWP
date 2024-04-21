'use strict';
import { gsap } from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";
import { ScrollSmoother } from "gsap/ScrollSmoother";


class PageScroller{
	
    constructor(){
        
        gsap.registerPlugin(ScrollTrigger, ScrollSmoother);
		
		
		const mm = gsap.matchMedia();
		
		mm.add({
			isPointer: '(pointer: fine)',
			isNotPointer: '(pointer: coarse), (pointer: none)'
		}, (context) => {
			
			let { isPointer, isNotPointer } = context.conditions;
			
			window.gscroll = ScrollSmoother.create({
				wrapper: '#pageWrapper',
				content: '#pageContent',
				ignoreMobileResize: true,
				normalizeScroll: (isPointer ? true : false),
				smooth: 1
			});
			
			
			return () => {
				
				if(window.gscroll){
					
					window.gscroll.kill();
					window.gscroll = null;
					
				}
				
			}
			
		});
        
    }

}

export default PageScroller;