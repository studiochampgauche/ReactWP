import assert from 'node:assert/strict';
import test from 'node:test';
import { runAnimationLifecycle } from '../../src/themes/reactwp/js/inc/AnimationLifecycle.js';

const createAnimation = () => {
    let onComplete = null;

    const animation = {
        eventCallback(name, callback){
            assert.equal(name, 'onComplete');

            if(callback === undefined){
                return onComplete;
            }

            onComplete = callback;
            return animation;
        },
        totalDuration(){
            return 1;
        }
    };

    return {
        animation,
        complete(){
            onComplete?.();
        }
    };
};

const waitForTurn = () => new Promise((resolve) => setImmediate(resolve));

test('returned animations resolve automatically when done is not requested', async () => {
    const controlled = createAnimation();
    let resolved = false;
    const lifecycle = runAnimationLifecycle({
        animationFactory: () => controlled.animation,
        createContext: (done) => ({ done })
    }).then(() => {
        resolved = true;
    });

    await waitForTurn();
    assert.equal(resolved, false);

    controlled.complete();
    await lifecycle;

    assert.equal(resolved, true);
});

test('explicit done controls completion independently from the returned animation', async () => {
    const controlled = createAnimation();
    let finish = null;
    let resolved = false;
    const lifecycle = runAnimationLifecycle({
        animationFactory: (context) => {
            finish = context.done;
            return controlled.animation;
        },
        createContext: (done) => ({ done })
    }).then(() => {
        resolved = true;
    });

    controlled.complete();
    await waitForTurn();

    assert.equal(resolved, false);

    finish();
    await lifecycle;

    assert.equal(resolved, true);
});

test('explicit done remains idempotent', async () => {
    let finish = null;
    let resolutions = 0;
    const lifecycle = runAnimationLifecycle({
        animationFactory: ({ done }) => {
            finish = done;
            return null;
        },
        createContext: (done) => ({ done })
    }).then(() => {
        resolutions += 1;
    });

    finish();
    finish();
    await lifecycle;

    assert.equal(resolutions, 1);
});
