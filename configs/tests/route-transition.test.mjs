import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';

// Run the real hook's effects against controlled router, animation and loader
// boundaries. No network timing or animation duration can make these tests pass.
const hookSource = (await readFile(new URL('../../src/themes/reactwp/js/inc/useRouteTransition.js', import.meta.url), 'utf8'))
    .replace(/^import .*;\r?\n/gm, '')
    .replace('export const useRouteTransition', 'const useRouteTransition');
const runtimeSource = (await readFile(new URL('../../src/themes/reactwp/js/inc/Runtime.js', import.meta.url), 'utf8'));
const deferred = () => {
    let resolve;
    let reject;
    const promise = new Promise((yes, no) => { resolve = yes; reject = no; });
    return { promise, resolve, reject };
};
const tick = () => new Promise((resolve) => setImmediate(resolve));

const createHarness = ({ immediate = false, promiseAnimation = false, smooth = false } = {}) => {
    const events = [];
    const requests = [];
    const animations = [];
    const refs = [];
    const states = [];
    const frames = [];
    let effects = [];
    let refIndex = 0;
    let stateIndex = 0;
    let activeBlocker;
    let cleanup;
    let lockDepth = 0;
    let location = { pathname: '/', search: '', hash: '' };
    const critical = deferred();
    const paint = deferred();
    const display = deferred();
    const initial = deferred();
    const context = vm.createContext({
        URL, URLSearchParams, Promise,
        console: { warn: (...args) => events.push(['warning', ...args]) },
        document: { getElementById: () => null, querySelector: () => null },
        requestAnimationFrame: (callback) => frames.push(callback),
        window: {
            location: { origin: 'https://reactwp.test', assign: (url) => events.push(['fallback', url]) },
            loader: { criticalDisplay: display.promise },
            gscroll: smooth ? {
                paused: (value) => events.push(['paused', value]),
                scrollTop: (value) => events.push(['smoothScrollTop', value])
            } : undefined,
            scrollTo: (options) => events.push(['scrollTop', options.top])
        },
        useNavigate: () => () => {},
        useLocation: () => location,
        useBlocker: () => activeBlocker,
        useInternalNavigation: () => {},
        useEffect: (callback) => effects.push(callback),
        useLayoutEffect: () => {},
        useEffectEvent: (callback) => callback,
        useRef: (value) => refs[refIndex++] ||= { current: value },
        useState: (value) => {
            const index = stateIndex++;
            states[index] ??= value;
            return [states[index], (next) => { states[index] = next; events.push(['state', next]); }];
        },
        fetchRoute: (view) => {
            const request = deferred();
            requests.push(request);
            events.push(['fetch', view]);
            return request.promise;
        },
        Loader: {
            setLabel: () => {},
            setRoute: (route) => events.push(['route', route.key]),
            markRouteReady: (key) => events.push(['ready', key]),
            prepareRoute: (route) => { events.push(['critical', route.key]); return critical.promise; },
            waitForPaint: () => { events.push(['paint']); return paint.promise; },
            finishInitialLoad: () => { events.push(['initial']); return initial.promise; },
            preloadDeferred: (route) => events.push(['deferred', route.key])
        },
        PageTransitionAnimation: {
            leave: () => {
                events.push(['leave']);
                const completion = deferred();
                let callback;
                const animation = {
                    eventCallback: (name, next) => next === undefined ? callback : (callback = next),
                    totalDuration: () => 1,
                    kill: () => events.push(['kill'])
                };
                animations.push({ complete: () => { callback?.(); completion.resolve(); }, reject: completion.reject });
                return immediate ? null : promiseAnimation ? completion.promise : animation;
            },
            enter: () => { events.push(['enter']); return null; }
        },
        scroller: {
            lock: () => { lockDepth += 1; events.push(['lock']); },
            unlock: () => { lockDepth -= 1; events.push(['unlock']); },
            refresh: () => {},
            getScrollTop: () => 0,
            setLockScrollTop: (value) => events.push(['lockScrollTop', value]),
            scrollTo: (target) => events.push(['scrollTarget', target])
        }
    });
    // Use ReactWP's actual path/query normalization, including bootstrap defaults.
    vm.runInContext(runtimeSource.replace(/^export /gm, '') + '\n' + hookSource, context);
    const render = () => {
        effects = [];
        refIndex = 0;
        stateIndex = 0;
        vm.runInContext('useRouteTransition()', context);
    };
    return {
        events, requests, animations, critical, paint, display, initial,
        count: (name) => events.filter(([event]) => event === name).length,
        get lockDepth(){ return lockDepth; },
        get pendingRoute(){ return refs[0]?.current; },
        start: (target = '/next/?page=2#section') => {
            cleanup?.();
            const url = new URL(target, 'https://reactwp.test');
            activeBlocker = {
                state: 'blocked',
                location: { pathname: url.pathname, search: url.search, hash: url.hash },
                proceed: () => events.push(['proceed', target])
            };
            render();
            cleanup = effects.at(-1)();
        },
        cancel: () => { cleanup?.(); cleanup = null; },
        enter: ({ firstLoad = false } = {}) => {
            location = { pathname: '/next/', search: '', hash: '' };
            states[0] = { key: '/next/', path: '/next/', search: '' };
            refs[1].current = firstLoad;
            render();
            return effects.at(-2)();
        },
        frame: async () => { frames.splice(0).forEach((callback) => callback()); await tick(); }
    };
};

test('leave starts while payload is pending; slow payload and critical assets gate the swap', async () => {
    const h = createHarness();
    h.start();
    assert.equal(h.count('leave'), 1);
    assert.equal(h.count('fetch'), 1);
    assert.equal(h.count('critical'), 0);
    h.animations[0].complete();
    await tick();
    assert.equal(h.count('proceed'), 0);
    h.requests[0].resolve({ template: 'Default' });
    await tick();
    assert.equal(h.count('critical'), 1);
    assert.equal(h.pendingRoute.key, '/next/?page=2');
    assert.equal(h.count('paint'), 0);
    h.critical.resolve();
    await tick();
    assert.equal(h.count('paint'), 1);
    assert.equal(h.count('proceed'), 0);
    h.paint.resolve();
    await tick();
    assert.equal(h.count('proceed'), 1);
    h.cancel();
    assert.equal(h.lockDepth, 1, 'successful navigation keeps the lock until entry');
});

test('a ready payload and critical assets cannot skip an unfinished leave', async () => {
    const h = createHarness();
    h.start('/next/');
    h.requests[0].resolve({});
    h.critical.resolve();
    h.paint.resolve();
    await tick();
    assert.equal(h.count('proceed'), 0);
    assert.equal(h.count('scrollTop'), 0, 'old page must not scroll before leave finishes');
    h.animations[0].complete();
    h.animations[0].complete();
    await tick();
    assert.equal(h.count('scrollTop'), 1);
    assert.equal(h.count('proceed'), 1);
});

for(const options of [{ immediate: true }, { promiseAnimation: true }, { smooth: true }]){
    test(`navigation preserves animation/scroll variants: ${JSON.stringify(options)}`, async () => {
        const h = createHarness(options);
        h.start('/next/');
        h.requests[0].resolve({});
        h.critical.resolve();
        h.paint.resolve();
        h.animations[0].complete();
        await tick();
        assert.equal(h.count('proceed'), 1);
        assert.equal(h.count(options.smooth ? 'smoothScrollTop' : 'scrollTop'), 1);
    });
}

test('same-route hashes bypass fetching and transitions', async () => {
    const h = createHarness();
    h.start('/#section');
    assert.equal(h.count('proceed'), 1);
    assert.equal(h.count('leave'), 0);
    assert.equal(h.count('fetch'), 0);
    assert.equal(h.lockDepth, 0);
});

test('next-route hashes preserve the outgoing scroll position', async () => {
    const h = createHarness({ smooth: true });
    h.start('/next/#section');
    h.animations[0].complete();
    await tick();
    assert.equal(h.count('scrollTop'), 0);
    assert.equal(h.count('smoothScrollTop'), 0);
});

test('superseded responses cannot replace the latest pending route or add scroll locks', async () => {
    const h = createHarness();
    h.start('/old/');
    h.start('/latest/');
    assert.equal(h.lockDepth, 1);
    assert.equal(h.count('kill'), 1);
    h.requests[1].resolve({});
    await tick();
    h.requests[0].resolve({});
    await tick();
    assert.equal(h.pendingRoute.key, '/latest/');
    assert.equal(h.count('critical'), 1);
    h.critical.resolve();
    h.paint.resolve();
    h.animations[0].complete();
    h.animations[1].complete();
    await tick();
    assert.deepEqual(h.events.filter(([event]) => event === 'proceed'), [['proceed', '/latest/']]);
});

for(const phase of ['fetch', 'critical', 'paint', 'animation']){
    test(`active ${phase} rejection falls back with the requested query and hash`, async () => {
        const h = createHarness({ promiseAnimation: phase === 'animation' });
        h.start();
        if(phase !== 'fetch'){
            h.requests[0].resolve({});
            await tick();
        }
        if(phase === 'paint'){
            h.critical.resolve();
            h.animations[0].complete();
            await tick();
        }
        const pending = { fetch: h.requests[0], critical: h.critical, paint: h.paint, animation: h.animations[0] };
        pending[phase].reject(new Error('controlled failure'));
        await tick();
        assert.deepEqual(h.events.filter(([event]) => event === 'fallback'), [['fallback', '/next/?page=2#section']]);
        assert.equal(h.count('proceed'), 0);
    });
}

test('cancelled fetch failures cannot trigger a stale hard navigation', async () => {
    const h = createHarness();
    h.start();
    h.cancel();
    h.requests[0].reject(new Error('obsolete failure'));
    await tick();
    assert.equal(h.count('fallback'), 0);
    assert.equal(h.count('warning'), 0);
    assert.equal(h.lockDepth, 0);
});

test('returning to the current route after leave restores its visibility without a new fetch', async () => {
    const h = createHarness();
    h.start('/slow/');
    h.animations[0].complete();
    h.start('/#section');
    assert.equal(h.count('enter'), 1, 'the interrupted page must be revealed again');
    assert.equal(h.count('fetch'), 1);
    assert.equal(h.lockDepth, 0);
    assert.deepEqual(h.events.filter(([event]) => event === 'route'), [['route', '/']]);
    assert.deepEqual(h.events.filter(([event]) => event === 'ready'), [['ready', '/']]);
    h.requests[0].resolve({});
    await tick();
    assert.equal(h.count('critical'), 0);
    assert.equal(h.pendingRoute, null);
});

test('a failed leave cannot apply a payload that arrives after fallback starts', async () => {
    const h = createHarness({ promiseAnimation: true });
    h.start();
    h.animations[0].reject(new Error('leave failed'));
    await tick();
    h.requests[0].resolve({});
    await tick();
    assert.equal(h.count('fallback'), 1);
    assert.equal(h.count('critical'), 0);
    assert.equal(h.pendingRoute, null);
});

test('cancellation during the paint gate prevents proceed and clears pending data', async () => {
    const h = createHarness();
    h.start();
    h.requests[0].resolve({});
    h.critical.resolve();
    h.animations[0].complete();
    await tick();
    h.cancel();
    h.paint.resolve();
    await tick();
    assert.equal(h.count('proceed'), 0);
    assert.equal(h.pendingRoute, null);
});

test('enter waits for committed critical display and scroll placement', async () => {
    const h = createHarness();
    h.start();
    const cleanup = h.enter();
    await tick();
    assert.equal(h.count('enter'), 0);
    h.display.resolve();
    await tick();
    await h.frame();
    assert.equal(h.count('enter'), 0);
    await h.frame();
    assert.equal(h.count('enter'), 1);
    assert.ok(h.events.findIndex(([event]) => event === 'scrollTarget') < h.events.findIndex(([event]) => event === 'enter'));
    cleanup();
});

test('first load retains its loader reveal without a page enter animation', async () => {
    const h = createHarness();
    h.start();
    const cleanup = h.enter({ firstLoad: true });
    assert.equal(h.count('initial'), 1);
    h.initial.resolve();
    await tick();
    await h.frame();
    assert.equal(h.count('enter'), 0);
    assert.equal(h.count('unlock'), 1);
    cleanup();
});
