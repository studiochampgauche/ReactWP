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

const SCROLL_KEYS = new Set([
    ' ',
    'ArrowDown',
    'ArrowUp',
    'End',
    'Home',
    'PageDown',
    'PageUp'
]);

export const scroller = {
    media: null,
    lockDepth: 0,
    lockedScrollTop: 0,
    preventScrollEvent(event){
        event.preventDefault();
    },
    preventScrollKey(event){
        if(!SCROLL_KEYS.has(event.key)){
            return;
        }

        event.preventDefault();
    },
    attachLockListeners(){
        window.addEventListener('wheel', this.preventScrollEvent, { passive: false });
        window.addEventListener('touchmove', this.preventScrollEvent, { passive: false });
        window.addEventListener('keydown', this.preventScrollKey, { passive: false });
    },
    detachLockListeners(){
        window.removeEventListener('wheel', this.preventScrollEvent);
        window.removeEventListener('touchmove', this.preventScrollEvent);
        window.removeEventListener('keydown', this.preventScrollKey);
    },
    getScrollTop(){
        if(window.gscroll && typeof window.gscroll.scrollTop === 'function'){
            return window.gscroll.scrollTop();
        }

        return window.scrollY || window.pageYOffset || 0;
    },
    restoreScrollTop(){
        if(window.gscroll && typeof window.gscroll.scrollTop === 'function'){
            window.gscroll.scrollTop(this.lockedScrollTop);
            return;
        }

        window.scrollTo({
            top: this.lockedScrollTop,
            behavior: 'auto'
        });
    },
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
        this.unlock(true);
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
        if(this.lockDepth === 0){
            this.lockedScrollTop = this.getScrollTop();
            window.gscroll?.paused?.(true);
            this.attachLockListeners();
            this.setLockState(true);
            this.restoreScrollTop();
        }

        this.lockDepth += 1;

        return this;
    },
    unlock(force = false){
        if(force){
            this.lockDepth = 0;
        } else if(this.lockDepth > 0){
            this.lockDepth -= 1;
        }

        if(this.lockDepth > 0){
            return this;
        }

        this.lockDepth = 0;
        this.detachLockListeners();
        this.setLockState(false);
        this.restoreScrollTop();
        window.gscroll?.paused?.(false);

        return this;
    }
};
