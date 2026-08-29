import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { once, runAnimationLifecycle } from './AnimationLifecycle';

export { gsap, ScrollTrigger };
export { once };

export const prefersReducedMotion = typeof window !== 'undefined'
    && window.matchMedia
    ? window.matchMedia('(prefers-reduced-motion: reduce)').matches
    : false;

export const runAnimation = ({ animationFactory, immediateFactory = null, createContext }) => {
    return runAnimationLifecycle({
        animationFactory,
        immediateFactory,
        createContext,
        reducedMotion: prefersReducedMotion
    });
};
