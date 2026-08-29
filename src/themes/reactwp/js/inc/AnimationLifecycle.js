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

const trackDoneAccess = (context, onAccess) => {
    if(!context || typeof context !== 'object'){
        return context;
    }

    return new Proxy(context, {
        get(target, property, receiver){
            if(property === 'done'){
                onAccess();
            }

            return Reflect.get(target, property, receiver);
        }
    });
};

export const runAnimationLifecycle = ({
    animationFactory,
    immediateFactory = null,
    createContext,
    reducedMotion = false
}) => {
    return new Promise((resolve) => {
        const done = once(resolve);
        let usesExplicitDone = false;
        const context = trackDoneAccess(createContext(done), () => {
            usesExplicitDone = true;
        });
        const factory = reducedMotion && typeof immediateFactory === 'function'
            ? immediateFactory
            : animationFactory;
        const animation = factory?.(context);

        if(!usesExplicitDone){
            wireCompletion(animation, done);
        }
    });
};
