import { Loader } from '../Loader';

export const configureLoader = () => {
    // ReactWP uses the built-in loader by default.
    // Uncomment this example if you want a v2-like animation flow:
    // keep animating while critical loading is still running,
    // then exit only when window.loader.isLoaded becomes true.
    /*
    */
    Loader.setAnimation(({ gsap, ScrollTrigger, done }) => {
        let tl = gsap.timeline({
            onComplete: () => {
                tl.kill();
                tl = null;

                done();
            }
        });

        tl
            .to({}, {
                duration: 0.1
            })
            .add(() => {
                if(!window.loader?.isLoaded){
                    tl.restart();
                    return;
                }
            })
            .to('#loader', {
                duration: 0.4,
                opacity: 0,
                pointerEvents: 'none',
                onStart: () => {
                    ScrollTrigger?.refresh();
                }
            });

        return tl;
    });
};
