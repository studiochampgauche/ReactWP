import { createHash } from 'node:crypto';
import { lstat, mkdir, realpath, rename, rm, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import tls from 'node:tls';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const configsRoot = path.resolve(__dirname, '..');
const projectRoot = path.resolve(configsRoot, '..');
const require = createRequire(import.meta.url);

const useSystemCertificates = () => {
    if(
        typeof tls.getCACertificates !== 'function'
        || typeof tls.setDefaultCACertificates !== 'function'
    ){
        return;
    }

    try{
        tls.setDefaultCACertificates([
            ...tls.getCACertificates('default'),
            ...tls.getCACertificates('system')
        ]);
    } catch(error){
        process.stderr.write(`[ReactWP SSG] Unable to load system CA certificates: ${error.message}\n`);
    }
};

useSystemCertificates();

const readArgument = (name, fallback = '') => {
    const inline = process.argv.find((argument) => argument.startsWith(`--${name}=`));

    if(inline){
        return inline.slice(name.length + 3);
    }

    const index = process.argv.indexOf(`--${name}`);
    return index >= 0 ? process.argv[index + 1] || fallback : fallback;
};

const loopbackHosts = new Set(['127.0.0.1', '::1', 'localhost']);
const validateSiteUrl = (value) => {
    const url = new URL(String(value || ''));

    if(
        url.username
        || url.password
        || url.search
        || url.hash
        || (url.protocol !== 'https:' && !(url.protocol === 'http:' && loopbackHosts.has(url.hostname)))
    ){
        throw new Error('RWP_SITE_URL must be an HTTPS WordPress URL without credentials, query parameters, or fragments. HTTP is allowed only for loopback development.');
    }

    return url.toString().replace(/\/+$/, '');
};
const rawSiteUrl = readArgument('site', process.env.RWP_SITE_URL || '');
const siteUrl = rawSiteUrl ? validateSiteUrl(rawSiteUrl) : '';
const theme = readArgument('theme', process.env.RWP_THEME || 'reactwp');
const maxRoutes = Math.min(50000, Math.max(1, Number.parseInt(process.env.RWP_SSG_MAX_ROUTES || '5000', 10) || 5000));
const maxJsonBytes = Math.min(50 * 1024 * 1024, Math.max(1024, Number.parseInt(process.env.RWP_SSG_MAX_JSON_BYTES || String(10 * 1024 * 1024), 10) || 10 * 1024 * 1024));
const maxHtmlBytes = Math.min(20 * 1024 * 1024, Math.max(1024, Number.parseInt(process.env.RWP_SSG_MAX_HTML_BYTES || String(5 * 1024 * 1024), 10) || 5 * 1024 * 1024));
const requestTimeout = Math.min(120000, Math.max(1000, Number.parseInt(process.env.RWP_SSG_TIMEOUT || '15000', 10) || 15000));

if(!/^[a-z0-9][a-z0-9_-]{0,63}$/i.test(theme)){
    throw new Error('RWP_THEME must be a valid WordPress theme slug.');
}
const outputDirectory = path.resolve(
    readArgument(
        'output',
        path.join(configsRoot, '..', 'dist', 'wp-content', 'themes', theme, 'assets', 'render', 'static')
    )
);
const rendererPath = path.resolve(
    readArgument(
        'renderer',
        path.join(configsRoot, '..', 'dist', 'wp-content', 'themes', theme, 'assets', 'render', 'server.cjs')
    )
);

if(process.env.RWP_SSG_ALLOW_EXTERNAL_OUTPUT !== '1'){
    const outputRelativePath = path.relative(projectRoot, outputDirectory);

    if(outputRelativePath.startsWith('..') || path.isAbsolute(outputRelativePath)){
        throw new Error('Static output must remain inside the ReactWP project. Set RWP_SSG_ALLOW_EXTERNAL_OUTPUT=1 only for a reviewed external deployment path.');
    }
}

if(!siteUrl){
    throw new Error('A WordPress URL is required. Set RWP_SITE_URL or pass --site=https://example.com.');
}

const rendererModule = require(rendererPath);
const render = rendererModule.render || rendererModule.default;

if(typeof render !== 'function'){
    throw new Error(`ReactWP renderer not found in ${rendererPath}.`);
}

const responseText = async (response, maxBytes) => {
    const declaredLength = Number.parseInt(response.headers.get('content-length') || '0', 10);

    if(Number.isFinite(declaredLength) && declaredLength > maxBytes){
        throw new Error(`Response exceeds ${maxBytes} bytes: ${response.url}`);
    }

    if(!response.body){
        return '';
    }

    const reader = response.body.getReader();
    const chunks = [];
    let bytes = 0;

    while(true){
        const { done, value } = await reader.read();

        if(done){
            break;
        }

        bytes += value.byteLength;

        if(bytes > maxBytes){
            await reader.cancel();
            throw new Error(`Response exceeds ${maxBytes} bytes: ${response.url}`);
        }

        chunks.push(value);
    }

    return Buffer.concat(chunks.map((chunk) => Buffer.from(chunk))).toString('utf8');
};

const requestJson = async (value, redirects = 0) => {
    const url = new URL(value);
    const siteOrigin = new URL(siteUrl).origin;

    if(url.origin !== siteOrigin){
        throw new Error(`Static generation refused a cross-origin API request: ${url}`);
    }

    const response = await fetch(url, {
        redirect: 'manual',
        signal: AbortSignal.timeout(requestTimeout),
        headers: {
            Accept: 'application/json'
        }
    });

    if(response.status >= 300 && response.status < 400 && response.headers.has('location')){
        if(redirects >= 3){
            throw new Error(`Too many API redirects: ${url}`);
        }

        return requestJson(new URL(response.headers.get('location'), url), redirects + 1);
    }

    if(!response.ok){
        throw new Error(`${response.status} ${response.statusText}: ${url}`);
    }

    const contentType = (response.headers.get('content-type') || '').toLowerCase();

    if(!contentType.includes('application/json')){
        throw new Error(`Expected an application/json response: ${url}`);
    }

    return JSON.parse(await responseText(response, maxJsonBytes));
};

const isSafeRoutePath = (value) => {
    const pathValue = String(value || '');

    let decoded = '';

    try{
        decoded = decodeURIComponent(pathValue);
    } catch(_error){
        return false;
    }

    return pathValue.length > 0
        && pathValue.length <= 2048
        && pathValue.startsWith('/')
        && !pathValue.startsWith('//')
        && !pathValue.includes('\\')
        && !pathValue.includes('?')
        && !pathValue.includes('#')
        && decoded.startsWith('/')
        && !decoded.startsWith('//')
        && !decoded.includes('\\')
        && !/[\u0000-\u001f\u007f]/.test(decoded);
};

const isSafeRouteSearch = (value) => {
    const search = String(value || '');

    return search === '' || (
        search.length <= 2048
        && search.startsWith('?')
        && !search.includes('#')
        && !search.includes('\\')
        && !/[\u0000-\u001f\u007f]/.test(search)
    );
};

const assertSafeDirectory = async (directory, label) => {
    const resolved = path.resolve(directory);

    if(resolved === path.parse(resolved).root){
        throw new Error(`${label} cannot be a filesystem root.`);
    }

    try{
        const stats = await lstat(resolved);

        if(stats.isSymbolicLink()){
            throw new Error(`${label} cannot be a symbolic link: ${resolved}`);
        }
    } catch(error){
        if(error?.code !== 'ENOENT'){
            throw error;
        }
    }

    return resolved;
};

const routeKey = (route, language) => {
    return `${String(route.lang || language || 'en').toLowerCase()}:${route.path || '/'}${route.search || ''}`;
};

const routeFile = (key) => {
    return `${createHash('sha256').update(key).digest('hex')}.html`;
};

const bootstrapEndpoint = `${siteUrl}/wp-json/reactwp/v1/bootstrap`;
const sitemapEndpoint = `${siteUrl}/wp-json/reactwp/v1/sitemap`;
const bootstrap = await requestJson(bootstrapEndpoint);
const sitemap = await requestJson(sitemapEndpoint);
const paths = new Set([
    bootstrap.route?.path || '/',
    ...(Array.isArray(sitemap.items) ? sitemap.items.map((item) => item.path).filter(Boolean) : [])
]);

if(paths.size > maxRoutes){
    throw new Error(`The sitemap contains ${paths.size} routes; the configured maximum is ${maxRoutes}.`);
}

const routePayloads = [];

for(const routePath of paths){
    if(!isSafeRoutePath(routePath)){
        throw new Error(`The sitemap returned an unsafe route path: ${String(routePath)}`);
    }

    const payload = routePath === bootstrap.route?.path
        ? bootstrap
        : await requestJson(`${bootstrapEndpoint}?view=${encodeURIComponent(routePath)}`);
    const route = payload.route;

    if(route?.render?.mode === 'static'){
        if(!isSafeRoutePath(route.path) || !isSafeRouteSearch(route.search || '')){
            throw new Error(`The route payload returned an unsafe path or search value: ${String(route.path)}`);
        }

        routePayloads.push(payload);
    }
}

const fragmentsDirectory = path.join(outputDirectory, 'fragments');
await assertSafeDirectory(outputDirectory, 'Static output directory');
await mkdir(outputDirectory, { recursive: true });
const outputRealPath = await realpath(outputDirectory);
const fragmentsRelativePath = path.relative(outputRealPath, path.resolve(fragmentsDirectory));

if(fragmentsRelativePath.startsWith('..') || path.isAbsolute(fragmentsRelativePath)){
    throw new Error('Static fragments must remain inside the output directory.');
}

await assertSafeDirectory(fragmentsDirectory, 'Static fragments directory');
await rm(fragmentsDirectory, { recursive: true, force: true });
await mkdir(fragmentsDirectory, { recursive: true });

const generatedAt = new Date().toISOString();
const generatedAtUnix = Date.now() / 1000;
const entries = {};

for(const payload of routePayloads){
    const route = payload.route;
    const result = await render(payload, {
        path: route.path,
        search: route.search || ''
    });

    if(!result || typeof result.html !== 'string' || Buffer.byteLength(result.html) > maxHtmlBytes){
        throw new Error(`Renderer output for ${route.path} is invalid or exceeds ${maxHtmlBytes} bytes.`);
    }

    const key = routeKey(route, bootstrap.site?.language);
    const filename = routeFile(key);

    await writeFile(path.join(fragmentsDirectory, filename), result.html, 'utf8');
    entries[key] = {
        key,
        path: route.path,
        search: route.search || '',
        lang: /^[a-z0-9_-]{1,32}$/i.test(String(route.lang || ''))
            ? String(route.lang)
            : 'en',
        template: /^[A-Za-z][A-Za-z0-9_.-]{0,127}$/.test(String(result.template || ''))
            ? String(result.template)
            : 'Default',
        file: `fragments/${filename}`,
        generatedAt,
        generatedAtUnix,
        cacheVersion: bootstrap.system?.cacheVersion || '1',
        tags: [...new Set((Array.isArray(result.tags) ? result.tags : [])
            .slice(0, 200)
            .map((tag) => String(tag).trim().toLowerCase())
            .filter((tag) => /^[a-z0-9_-]+:[a-z0-9_.-]+$/.test(tag)))]
    };
}

const manifest = {
    version: 1,
    theme,
    siteUrl,
    generatedAt,
    generatedAtUnix,
    cacheVersion: bootstrap.system?.cacheVersion || '1',
    entries
};
const manifestPath = path.join(outputDirectory, 'manifest.json');
const temporaryManifestPath = `${manifestPath}.${process.pid}.tmp`;

await writeFile(temporaryManifestPath, `${JSON.stringify(manifest, null, 2)}\n`, 'utf8');
await rm(manifestPath, { force: true });
await rename(temporaryManifestPath, manifestPath);

process.stdout.write(`ReactWP generated ${routePayloads.length} static route${routePayloads.length === 1 ? '' : 's'} in ${outputDirectory}.\n`);
