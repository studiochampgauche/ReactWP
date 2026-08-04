import assert from 'node:assert/strict';
import { spawn } from 'node:child_process';
import { once } from 'node:events';
import { createServer } from 'node:http';
import { mkdtemp, readFile, rm } from 'node:fs/promises';
import { createRequire } from 'node:module';
import os from 'node:os';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const configsRoot = path.resolve(__dirname, '..');
const projectRoot = path.resolve(configsRoot, '..');
const renderDirectory = path.join(projectRoot, 'dist', 'wp-content', 'themes', 'reactwp', 'assets', 'render');
const rendererPath = path.join(renderDirectory, 'server.cjs');
const require = createRequire(import.meta.url);

const createRoute = (pathName, id = 1, mode = 'static') => ({
    id,
    type: 'page',
    template: 'Default',
    pageName: pathName === '/' ? 'Home' : 'About',
    path: pathName,
    search: '',
    lang: 'en',
    data: {
        hero_title: pathName === '/' ? 'Static home' : 'Static about'
    },
    render: {
        mode,
        cache: {
            html: mode === 'static',
            payload: true,
            media: true,
            tags: []
        }
    }
});

const bootstrap = (route) => ({
    site: {
        name: 'ReactWP',
        language: 'en'
    },
    theme: {
        name: 'ReactWP',
        slug: 'reactwp',
        version: '3.0.0'
    },
    system: {
        cacheVersion: 'test-generation'
    },
    navigation: {},
    route
});

const freePort = async () => {
    const server = createServer();
    server.listen(0, '127.0.0.1');
    await once(server, 'listening');
    const port = server.address().port;
    await new Promise((resolve) => server.close(resolve));
    return port;
};

test('server bundle renders a registered React template', async () => {
    const renderer = require(rendererPath);
    const result = await renderer.render(bootstrap(createRoute('/', 1)));

    assert.equal(result.template, 'Default');
    assert.match(result.html, /Static home/);
    assert.match(result.html, /app-shell__body/);
    assert.ok(result.tags.includes('post:1'));
});

test('rich text rendering removes executable markup and unsafe URLs', async () => {
    const renderer = require(rendererPath);
    const route = createRoute('/', 1);
    route.data.hero_intro = '<strong>Safe</strong><script>alert(1)</script><a href="javascript:alert(2)" onclick="alert(3)">Link</a>';
    const result = await renderer.render(bootstrap(route));

    assert.match(result.html, /<strong>Safe<\/strong>/);
    assert.doesNotMatch(result.html, /<script/i);
    assert.doesNotMatch(result.html, /javascript:/i);
    assert.doesNotMatch(result.html, /onclick/i);
});

test('static generator writes route fragments and a keyed manifest', async (context) => {
    const outputDirectory = await mkdtemp(path.join(os.tmpdir(), 'reactwp-ssg-'));
    const homeRoute = createRoute('/', 1);
    const aboutRoute = createRoute('/about/', 2);
    const api = createServer((request, response) => {
        const url = new URL(request.url, 'http://127.0.0.1');
        let payload = {};

        if(url.pathname.endsWith('/bootstrap')){
            payload = bootstrap(url.searchParams.get('view') === '/about/' ? aboutRoute : homeRoute);
        } else if(url.pathname.endsWith('/sitemap')){
            payload = { items: [{ path: '/' }, { path: '/about/' }] };
        }

        response.writeHead(200, { 'Content-Type': 'application/json' });
        response.end(JSON.stringify(payload));
    });

    api.listen(0, '127.0.0.1');
    await once(api, 'listening');
    const port = api.address().port;
    context.after(() => new Promise((resolve) => api.close(resolve)));
    context.after(() => rm(outputDirectory, { recursive: true, force: true }));

    const generator = spawn(process.execPath, [
        path.join(configsRoot, 'scripts', 'generate-static.mjs'),
        `--site=http://127.0.0.1:${port}`,
        `--output=${outputDirectory}`,
        `--renderer=${rendererPath}`
    ], {
        cwd: configsRoot,
        env: {
            ...process.env,
            RWP_SSG_ALLOW_EXTERNAL_OUTPUT: '1'
        },
        stdio: ['ignore', 'pipe', 'pipe']
    });
    let stderr = '';
    generator.stderr.on('data', (chunk) => {
        stderr += chunk.toString();
    });
    const [code] = await once(generator, 'exit');

    assert.equal(code, 0, stderr);

    const manifest = JSON.parse(await readFile(path.join(outputDirectory, 'manifest.json'), 'utf8'));
    assert.equal(Object.keys(manifest.entries).length, 2);
    assert.equal(manifest.cacheVersion, 'test-generation');
    assert.ok(manifest.entries['en:/']);
    assert.ok(manifest.entries['en:/about/']);

    const aboutHtml = await readFile(
        path.join(outputDirectory, manifest.entries['en:/about/'].file),
        'utf8'
    );
    assert.match(aboutHtml, /Static about/);
});

test('SSR service enforces its secret and renders authorized payloads', async (context) => {
    const port = await freePort();
    const secret = 'reactwp-test-secret-0123456789abcdef';
    const service = spawn(process.execPath, [path.join(renderDirectory, 'serve.mjs')], {
        cwd: renderDirectory,
        env: {
            ...process.env,
            RWP_SSR_PORT: String(port),
            RWP_SSR_SECRET: secret
        },
        stdio: ['ignore', 'pipe', 'pipe']
    });

    context.after(() => {
        if(!service.killed){
            service.kill();
        }
    });

    await Promise.race([
        once(service.stdout, 'data'),
        new Promise((_, reject) => setTimeout(() => reject(new Error('SSR service did not start.')), 5000))
    ]);

    const unauthorized = await fetch(`http://127.0.0.1:${port}/render`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ payload: bootstrap(createRoute('/', 1, 'server')) })
    });
    assert.equal(unauthorized.status, 401);

    const malformed = await fetch(`http://127.0.0.1:${port}/render`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-ReactWP-Render-Secret': secret
        },
        body: '{'
    });
    assert.equal(malformed.status, 400);

    const authorized = await fetch(`http://127.0.0.1:${port}/render`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-ReactWP-Render-Secret': secret
        },
        body: JSON.stringify({ payload: bootstrap(createRoute('/', 1, 'server')) })
    });
    const result = await authorized.json();

    assert.equal(authorized.status, 200);
    assert.equal(result.ok, true);
    assert.match(result.html, /Static home/);
});
