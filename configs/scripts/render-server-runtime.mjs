import { timingSafeEqual } from 'node:crypto';
import { createServer } from 'node:http';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

const require = createRequire(import.meta.url);
const bundlePath = process.env.RWP_RENDER_BUNDLE || fileURLToPath(new URL('./server.cjs', import.meta.url));
const renderer = require(bundlePath);
const render = renderer.render || renderer.default;
const host = process.env.RWP_SSR_HOST || '127.0.0.1';
const boundedInteger = (value, fallback, minimum, maximum) => {
    const parsed = Number.parseInt(value, 10);

    return Number.isFinite(parsed)
        ? Math.min(maximum, Math.max(minimum, parsed))
        : fallback;
};
const port = boundedInteger(process.env.RWP_SSR_PORT, 3100, 1, 65535);
const secret = process.env.RWP_SSR_SECRET || '';
const bodyLimit = boundedInteger(process.env.RWP_SSR_BODY_LIMIT, 5 * 1024 * 1024, 1024, 50 * 1024 * 1024);
const responseLimit = boundedInteger(process.env.RWP_SSR_RESPONSE_LIMIT, 5 * 1024 * 1024, 1024, 20 * 1024 * 1024);
const renderTimeout = boundedInteger(process.env.RWP_SSR_TIMEOUT, 8000, 100, 60000);
const concurrency = boundedInteger(process.env.RWP_SSR_CONCURRENCY, 8, 1, 128);
const loopbackHosts = new Set(['127.0.0.1', '::1', 'localhost']);
const allowInsecureLoopback = loopbackHosts.has(host)
    && process.env.RWP_SSR_ALLOW_INSECURE_LOOPBACK === '1';
let activeRenders = 0;

if(typeof render !== 'function'){
    throw new Error(`ReactWP renderer not found in ${bundlePath}.`);
}

if(secret.length < 32 && !allowInsecureLoopback){
    throw new Error('A RWP_SSR_SECRET of at least 32 characters is required.');
}

const json = (response, status, payload) => {
    const body = JSON.stringify(payload);

    response.writeHead(status, {
        'Content-Type': 'application/json; charset=utf-8',
        'Content-Length': Buffer.byteLength(body),
        'Cache-Control': 'no-store'
    });
    response.end(body);
};

const authorized = (request) => {
    if(allowInsecureLoopback && !secret){
        return true;
    }

    const received = String(request.headers['x-reactwp-render-secret'] || '');
    const expectedBuffer = Buffer.from(secret);
    const receivedBuffer = Buffer.from(received);

    return expectedBuffer.length === receivedBuffer.length
        && timingSafeEqual(expectedBuffer, receivedBuffer);
};

const readBody = (request) => {
    return new Promise((resolve, reject) => {
        const chunks = [];
        let size = 0;
        let settled = false;

        const fail = (error) => {
            if(settled){
                return;
            }

            settled = true;
            reject(error);
        };

        request.on('data', (chunk) => {
            if(settled){
                return;
            }

            size += chunk.length;

            if(size > bodyLimit){
                chunks.length = 0;
                fail(Object.assign(new Error('Render payload is too large.'), { status: 413 }));
                request.resume();
                return;
            }

            chunks.push(chunk);
        });
        request.on('end', () => {
            if(settled){
                return;
            }

            settled = true;
            resolve(Buffer.concat(chunks).toString('utf8'));
        });
        request.on('error', fail);
    });
};

const withTimeout = (promise) => {
    let timer = null;

    return Promise.race([
        promise,
        new Promise((_, reject) => {
            timer = setTimeout(() => reject(Object.assign(new Error('Render timed out.'), { status: 504 })), renderTimeout);
        })
    ]).finally(() => clearTimeout(timer));
};

const server = createServer(async (request, response) => {
    const url = new URL(request.url || '/', 'http://localhost');

    if(request.method === 'GET' && url.pathname === '/health' && url.search === ''){
        if(!loopbackHosts.has(host) && !authorized(request)){
            json(response, 401, { ok: false, error: 'Unauthorized.' });
            return;
        }

        json(response, 200, { ok: true });
        return;
    }

    if(request.method !== 'POST' || url.pathname !== '/render' || url.search !== ''){
        json(response, 404, { ok: false, error: 'Not found.' });
        return;
    }

    if(!authorized(request)){
        json(response, 401, { ok: false, error: 'Unauthorized.' });
        return;
    }

    if(!String(request.headers['content-type'] || '').toLowerCase().startsWith('application/json')){
        json(response, 415, { ok: false, error: 'Content-Type must be application/json.' });
        return;
    }

    const declaredLength = Number.parseInt(String(request.headers['content-length'] || '0'), 10);

    if(Number.isFinite(declaredLength) && declaredLength > bodyLimit){
        json(response, 413, { ok: false, error: 'Render payload is too large.' });
        request.resume();
        return;
    }

    if(activeRenders >= concurrency){
        json(response, 503, { ok: false, error: 'Render server is busy.' });
        return;
    }

    activeRenders += 1;

    try{
        const rawBody = await readBody(request);
        let body = null;

        try{
            body = JSON.parse(rawBody || '{}');
        } catch(_error){
            throw Object.assign(new Error('Invalid JSON payload.'), { status: 400, expose: true });
        }

        if(
            !body
            || typeof body !== 'object'
            || Array.isArray(body)
            || !body.payload
            || typeof body.payload !== 'object'
            || Array.isArray(body.payload)
            || (body.options != null && (typeof body.options !== 'object' || Array.isArray(body.options)))
        ){
            throw Object.assign(new Error('Invalid render payload.'), { status: 400, expose: true });
        }

        const result = await withTimeout(render(body.payload || {}, body.options || {}));

        if(
            !result
            || typeof result !== 'object'
            || Array.isArray(result)
            || typeof result.html !== 'string'
            || Buffer.byteLength(result.html) > responseLimit
        ){
            throw new Error('Renderer returned an invalid or oversized result.');
        }

        json(response, 200, { ...result, ok: true });
    } catch(error){
        const status = Number.isInteger(error?.status) ? error.status : 500;

        if(status >= 500){
            process.stderr.write(`[ReactWP SSR] ${error?.stack || error?.message || 'Rendering failed.'}\n`);
        }

        json(response, status, {
            ok: false,
            error: error?.expose || status < 500 ? error.message : 'Rendering failed.'
        });
    } finally {
        activeRenders -= 1;
    }
});

server.requestTimeout = renderTimeout + 5000;
server.headersTimeout = Math.min(server.requestTimeout, 10000);
server.keepAliveTimeout = 5000;
server.maxHeadersCount = 64;

server.listen(port, host, () => {
    process.stdout.write(`ReactWP render server listening on http://${host}:${port}\n`);
});
