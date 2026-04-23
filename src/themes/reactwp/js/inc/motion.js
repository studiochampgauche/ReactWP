import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

export { gsap, ScrollTrigger };

export const prefersReducedMotion = typeof window !== 'undefined'
    && window.matchMedia
    ? window.matchMedia('(prefers-reduced-motion: reduce)').matches
    : false;

export const once = (callback) => {
    let called = false;

    return (...args) => {
        if(called){
            return;
        }

        called = true;
        callback(...args);
    };
};

const wireCompletion = (animation, done) => {
    if(!animation){
        done();
        return;
    }

    if(typeof animation.eventCallback === 'function'){
        const previousOnComplete = animation.eventCallback('onComplete');

        animation.eventCallback('onComplete', (...args) => {
            previousOnComplete?.apply(animation, args);
            done();
        });

        const totalDuration = typeof animation.totalDuration === 'function'
            ? animation.totalDuration()
            : null;

        if(totalDuration === 0){
            requestAnimationFrame(done);
        }

        return;
    }

    if(typeof animation.then === 'function'){
        Promise.resolve(animation).finally(done);
        return;
    }

    done();
};

export const runAnimation = ({ animationFactory, immediateFactory = null, createContext }) => {
    return new Promise((resolve) => {
        const done = once(resolve);
        const context = createContext(done);

        if(prefersReducedMotion && typeof immediateFactory === 'function'){
            const animation = immediateFactory(context);
            wireCompletion(animation, done);
            return;
        }

        const animation = animationFactory?.(context);

        wireCompletion(animation, done);
    });
};
