'use strict';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { ScrollSmoother } from 'gsap/ScrollSmoother';


const Scroller = () => {

	window.gscroll = null;

	gsap.registerPlugin(ScrollTrigger, ScrollSmoother);


	const mm = gsap.matchMedia();
	mm.add({
		isPointer: '(pointer: fine)',
		isNotPointer: '(pointer: coarse), (pointer: none)'
	}, async (context) => {
		
		let { isPointer, isNotPointer } = context.conditions;
		
		gsap.set('body', {
			maxHeight: 'initial',
			overflow: 'initial'
		});

		window.gscroll = await ScrollSmoother.create({
			wrapper: '#pageWrapper',
			content: '#pageContent',
			ignoreMobileResize: true,
			normalizeScroll: (isPointer ? true : false),
			smooth: 2.25
		});

		window.gscroll.paused(true);


		window.loader.isLoaded.gscroll = true;
		
		
		return () => {
			
			if(window.gscroll){
				
				window.gscroll.kill();
				window.gscroll = null;
				
			}
			
		}
		
	});

}

Scroller();