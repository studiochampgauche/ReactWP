'use strict';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

const PageTransitionAnimation = {
    leaveFactory: null,
    enterFactory: null,
    setLeave(factory = null){
        this.leaveFactory = factory;
        return this;
    },
    setEnter(factory = null){
        this.enterFactory = factory;
        return this;
    },
    leave(context = {}){
        const factory = this.leaveFactory || this.defaultLeave;
        return factory({
            gsap,
            ScrollTrigger,
            ...context
        });
    },
    enter(context = {}){
        const factory = this.enterFactory || this.defaultEnter;
        return factory({
            gsap,
            ScrollTrigger,
            ...context
        });
    },
    defaultLeave(){
        return gsap.to('#viewport', .2, {
            opacity: 0,
            pointerEvents: 'none'
        });
    },

    defaultEnter(){
        return gsap.to('#viewport', .2, {
            opacity: 1,
            pointerEvents: 'initial'
        });
    }
};

export default PageTransitionAnimation;