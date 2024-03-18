'use strict';
import gsap from './gsap/index.js';
import gsapCore from './gsap/gsap-core.js';
import ScrollTrigger from './gsap/ScrollTrigger.js';
import * as Barba from './barba/Barba.js';


export default class PageTransitor{
    
    constructor(){
        
        this.init();
        
    }
    
    onStart(data){
        
        console.log('barba starting');
        
    }
    
    onLeave(data)
            
        const tl = gsap.timeline();

        tl.to(data.container, .4, {
            opacity: 0
        });
        
        return tl;
        
    }
    
    onAfterLeave(){
        
        window.gscroll.paused(true);
        window.gscroll.scrollTop(0);
        ScrollTrigger.refresh();
        ScrollTrigger.getAll().forEach(t => t.kill());
        
    }
    
    onEnter (data){
        
        const tl = gsap.timeline();

        tl.fromTo(data.container, {
            opacity: 0
        }, {
            opacity: 1,
            duration: .4
        });

        return tl;
        
    }
    
    onAfterEnter(){
        
        window.gscroll.paused(false);
        
    }
    
    
    init(){
        barba.init({
            sync: false,
            debug: false,
            cacheIgnore: true,
            cacheFirstPage: false,
            prefetchIgnore: true,
            preventRunning: true,
            transitions: [
                {
                    once: ({next}) => this.onStart(next),
                    leave: async ({current}) => await this.onLeave(current),
                    afterLeave: () => this.onAfterLeave(),
                    enter: ({next}) => this.onEnter(next),
                    afterEnter: () => this.onAfterEnter()
                }
            ]
        });
    }

}