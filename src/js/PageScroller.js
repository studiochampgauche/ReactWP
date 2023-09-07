'use strict';
import gsap from './gsap/index.js';
import gsapCore from './gsap/gsap-core.js';
import ScrollTrigger from './gsap/ScrollTrigger.js';
import ScrollSmoother from './gsap/ScrollSmoother.js';


export default class PageScroller{
    
    constructor(){
        
        gsap.registerPlugin(ScrollTrigger, ScrollSmoother);

		return ScrollSmoother.create({
			wrapper: '#pageWrapper',
			content: '#pageContent',
			ignoreMobileResize: true,
			smooth: 1
		});
        
    }

}