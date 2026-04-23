import { gsap, ScrollTrigger, prefersReducedMotion } from './motion';

const getViewport = () => document.getElementById('viewport');
const getLoader = () => document.getElementById('loader');

const createContext = (done = () => null) => {
    return {
        gsap,
        ScrollTrigger,
        loader: getLoader(),
        viewport: getViewport(),
        reducedMotion: prefersReducedMotion,
        done
    };
};

const defaultSetup = ({ gsap, loader, viewport }) => {
    if(viewport){
        gsap.set(viewport, {
            autoAlpha: 1,
            pointerEvents: 'initial'
        });
    }

    if(loader){
        gsap.set(loader, {
            autoAlpha: 1
        });
    }
};

const defaultLeaveAnimation = ({ gsap, viewport }) => {
    if(!viewport){
        return null;
    }

    return gsap.to(viewport, {
        autoAlpha: 0,
        pointerEvents: 'none',
        duration: 0.2,
        ease: 'power2.out',
        overwrite: 'auto'
    });
};

const defaultLeaveImmediate = ({ gsap, viewport }) => {
    if(viewport){
        gsap.set(viewport, {
            autoAlpha: 0,
            pointerEvents: 'none'
        });
    }
};

const defaultEnterAnimation = ({ gsap, viewport }) => {
    if(!viewport){
        return null;
    }

    return gsap.to(viewport, {
        autoAlpha: 1,
        pointerEvents: 'initial',
        duration: 0.2,
        ease: 'power2.out',
        overwrite: 'auto'
    });
};

const defaultEnterImmediate = ({ gsap, viewport }) => {
    if(viewport){
        gsap.set(viewport, {
            autoAlpha: 1,
            pointerEvents: 'initial'
        });
    }
};

export const PageTransitionAnimation = {
    setupAnimation: defaultSetup,
    leaveAnimation: defaultLeaveAnimation,
    leaveImmediate: defaultLeaveImmediate,
    enterAnimation: defaultEnterAnimation,
    enterImmediate: defaultEnterImmediate,
    configure(){
        return this.reset();
    },
    setSetup(animationFactory){
        this.setupAnimation = typeof animationFactory === 'function'
            ? animationFactory
            : defaultSetup;

        return this;
    },
    setLeave(animationFactory, immediateFactory = null){
        this.leaveAnimation = typeof animationFactory === 'function'
            ? animationFactory
            : defaultLeaveAnimation;

        this.leaveImmediate = typeof immediateFactory === 'function'
            ? immediateFactory
            : animationFactory
                ? null
                : defaultLeaveImmediate;

        return this;
    },
    setEnter(animationFactory, immediateFactory = null){
        this.enterAnimation = typeof animationFactory === 'function'
            ? animationFactory
            : defaultEnterAnimation;

        this.enterImmediate = typeof immediateFactory === 'function'
            ? immediateFactory
            : animationFactory
                ? null
                : defaultEnterImmediate;

        return this;
    },
    reset(){
        this.setupAnimation = defaultSetup;
        this.leaveAnimation = defaultLeaveAnimation;
        this.leaveImmediate = defaultLeaveImmediate;
        this.enterAnimation = defaultEnterAnimation;
        this.enterImmediate = defaultEnterImmediate;
        return this;
    },
    setup(){
        this.setupAnimation?.(createContext());
        return this;
    },
    leave(context = {}){
        const animationContext = {
            ...createContext(),
            ...context
        };

        if(prefersReducedMotion && typeof this.leaveImmediate === 'function'){
            this.leaveImmediate(animationContext);
            return null;
        }

        return this.leaveAnimation?.(animationContext) || null;
    },
    enter(context = {}){
        const animationContext = {
            ...createContext(),
            ...context
        };

        if(prefersReducedMotion && typeof this.enterImmediate === 'function'){
            this.enterImmediate(animationContext);
            return null;
        }

        return this.enterAnimation?.(animationContext) || null;
    }
};

export const pageTransition = {
    setup(){
        PageTransitionAnimation.setup();
    },
    leave(){
        return PageTransitionAnimation.leave();
    },
    enter(){
        return PageTransitionAnimation.enter();
    }
};
