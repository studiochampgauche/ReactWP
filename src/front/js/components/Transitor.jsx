import React, { useEffect, useState, useRef } from 'react'
import { useNavigate, useLocation } from 'react-router-dom';
import { gsap } from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";

const Transitor = ({ children }) => {
	
	
	const navigateRef = useRef(useNavigate());
	
	const [isLeaving, setIsLeaving] = useState(false);
	const [isEntering, setIsEntering] = useState(false);
	const canTransit = useRef(true);
	const to = useRef(null);
    
    const { pathname } = useLocation();
    
    useEffect(() => {
        
        
        /*
        * Make sure you start on position 0 on transition
        */
        window.gscroll ? window.gscroll.scrollTop(0) : window.scrollTo(0, 0);
        
		
        /*
        * Create click events and call transition
        */
		const elements = document.querySelectorAll('[data-transition=true]');
		if(!elements.length) return;
		
		
		const clickEvents = [];
		
		elements.forEach(item => {
			
			const handleClick = (e) => {
			
				e.preventDefault();

				if(!canTransit.current) return;
				canTransit.current = false;


				to.current = item.getAttribute('href') || item.getAttribute('data-to');

				if(!to.current || to.current === pathname){
                    canTransit.current = true;
                    return;
                }

				setIsLeaving(true);
				
			}
			
			
			item.addEventListener('click', handleClick);
			clickEvents.push({element: item, event: handleClick});
			
		});
		
		
		return () => {
			
			if(!clickEvents.length) return;
			
			clickEvents.forEach(({ element, event }) => {
				
				element.removeEventListener('click', event);
				
			});
			
		}
		
		
	}, [pathname]);
	
	
    /*
    * isLeaving transition
    */
	useEffect(() => {
		
		if(!isLeaving) return;
        
		
		const tl = gsap.timeline({
			onComplete: () => {
				
				setIsLeaving(false);
				setIsEntering(true);
				
				
				navigateRef.current(to.current);
                
                if(!window.gscroll) return;
                
                window.gscroll.paused(true);
                //window.gscroll.scrollTop(0);
                ScrollTrigger.refresh();
                ScrollTrigger.getAll().forEach(t => t.kill());
                
				
			}
		});
		
		
		tl
		.to('main', .2, {
			opacity: 0
		});
		
		
		return () => {
			
			tl.kill();
			
		}
		
		
	}, [isLeaving]);
	
	
    /*
    * isEntering transition
    */
	useEffect(() => {
		
		if(!isEntering) return;
		
		
		const tl = gsap.timeline({
            onStart: () => {
                
                if(!window.gscroll) return;
                
                //window.gscroll.scrollTop(0);
                
            },
			onComplete: () => {
				
				setIsEntering(false);
				canTransit.current = true;
                
                
                
                if(!window.gscroll) return;
                
                window.gscroll.paused(false);
				
			}
		});
		
		tl
		.to('main', .2, {
			opacity: 1
		});
		
		
		return () => {
			
			tl.kill();
			
		}
		
		
	}, [isEntering]);
	
	
	return(<main>{children}</main>)
	
}
export default Transitor;