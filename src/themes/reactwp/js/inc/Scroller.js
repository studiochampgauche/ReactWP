import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { ScrollSmoother } from 'gsap/ScrollSmoother';

gsap.registerPlugin(ScrollTrigger, ScrollSmoother);

const prefersReducedMotion = () => {
    return typeof window !== 'undefined'
        && window.matchMedia
        ? window.matchMedia('(prefers-reduced-motion: reduce)').matches
        : false;
};

const resolveTarget = (target) => {
    if(target == null){
        return 0;
    }

    if(typeof target === 'string' || typeof target === 'number'){
        return target;
    }

    return 0;
};

export const scroller = {
    media: null,
    setState(active){
        document.documentElement.classList.toggle('has-smooth-scroll', active);
        document.body.classList.toggle('has-smooth-scroll', active);
    },
    setLockState(active){
        document.documentElement.classList.toggle('is-scroll-locked', active);
        document.body.classList.toggle('is-scroll-locked', active);
    },
    init(){
        this.kill();

        if(prefersReducedMotion()){
            return null;
        }

        this.media = gsap.matchMedia();
        this.media.add(
            {
                isPointer: '(pointer: fine)'
            },
            (context) => {
                const { isPointer } = context.conditions;

                window.gscroll = ScrollSmoother.create({
                    wrapper: '#pageWrapper',
                    content: '#pageContent',
                    ignoreMobileResize: true,
                    normalizeScroll: Boolean(isPointer),
                    smooth: isPointer ? 1.35 : 0
                });

                this.setState(true);
                ScrollTrigger.refresh();

                return () => {
                    this.setState(false);
                    window.gscroll?.kill();
                    window.gscroll = null;
                };
            }
        );

        return window.gscroll || null;
    },
    kill(){
        this.unlock();
        this.setState(false);
        window.gscroll?.kill();
        window.gscroll = null;

        this.media?.revert();
        this.media = null;
    },
    refresh(){
        if(window.gscroll){
            window.gscroll.refresh();
            return;
        }

        ScrollTrigger.refresh();
    },
    scrollTo(target, smooth = true){
        const nextTarget = resolveTarget(target);

        if(window.gscroll){
            window.gscroll.scrollTo(nextTarget, smooth);
            return;
        }

        if(typeof nextTarget === 'string'){
            const element = document.querySelector(nextTarget);

            if(element){
                element.scrollIntoView({
                    behavior: smooth ? 'smooth' : 'auto',
                    block: 'start'
                });
            }

            return;
        }

        window.scrollTo({
            top: nextTarget,
            behavior: smooth ? 'smooth' : 'auto'
        });
    },
    jumpToTop(){
        if(window.gscroll){
            window.gscroll.scrollTo(0, false);
            return;
        }

        window.scrollTo({
            top: 0,
            behavior: 'auto'
        });
    },
    lock(){
        this.setLockState(true);
        return this;
    },
    unlock(){
        this.setLockState(false);
        return this;
    }
};
