'use strict';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { ScrollSmoother } from 'gsap/ScrollSmoother';


/*
* Register ScrollTrigger, ScrollSmoother
*/
gsap.registerPlugin(ScrollTrigger, ScrollSmoother);

const Scroller = {
	bodyEl: document.body,
	mm: null,
	init(){

		/*
		* Kill
		*/
		this.kill();
		
		/*
		* Return a Promise
		*/
		return new Promise(done => {

			this.mm = gsap.matchMedia();

			this.mm.add({
				all: true,
				isPointer: '(pointer: fine)'
			}, (context) => {
				
				const { isPointer } = context.conditions;

				/*
				* Init smooth scroll
				*/
				window.gscroll = ScrollSmoother.create({
					wrapper: '#pageWrapper',
					content: '#pageContent',
					ignoreMobileResize: true,
					normalizeScroll: isPointer,
					smooth: 2.25
				});

				/*
				* Pause the scroll via GSAP
				*/
				window.gscroll.paused(true);

				/*
				* Stop pausing the scroll with the body
				*/
				this.bodyEl.style.maxHeight = 'initial';
				this.bodyEl.style.overflow = 'initial';

				/*
				* Refresh Scroller according to new body height
				*/
				ScrollTrigger.refresh();

				/*
				* Resolve
				*/
				done();

				return () => {

					window.gscroll?.kill();
					window.gscroll = null;

				};
				
			});

		});

	},
	kill(){
		
		/*
		* Kill Scroller
		*/
		window.gscroll?.kill();
		window.gscroll = null;

		/*
		* Kill matchMedia
		*/
		this.mm?.revert();
		this.mm = null;

	}
}

Scroller.init();