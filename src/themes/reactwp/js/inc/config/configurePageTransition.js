import { PageTransitionAnimation } from '../PageTransition';

export const configurePageTransition = () => {
    // ReactWP uses a simple fade transition by default.
    // Uncomment this example if you want to override the leave/enter animation.
    /*
    PageTransitionAnimation
        .setLeave(({ gsap }) => {
            return gsap.to('#viewport', {
                duration: 0.2,
                opacity: 0,
                pointerEvents: 'none'
            });
        })
        .setEnter(({ gsap }) => {
            return gsap.to('#viewport', {
                duration: 0.2,
                opacity: 1,
                pointerEvents: 'initial'
            });
        });
    */
};
