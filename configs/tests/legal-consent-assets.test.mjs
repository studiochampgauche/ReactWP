import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import vm from 'node:vm';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const projectRoot = path.resolve(__dirname, '../..');
const scriptPath = path.join(
  projectRoot,
  'src/plugins/universal-legal-pages/template/assets/js/consent-manager.js'
);
const stylesheetPath = path.join(
  projectRoot,
  'src/plugins/universal-legal-pages/template/assets/css/consent-manager.css'
);
const adminStylesheetPath = path.join(
  projectRoot,
  'src/plugins/universal-legal-pages/template/assets/css/admin-consent.css'
);
const adminScriptPath = path.join(
  projectRoot,
  'src/plugins/universal-legal-pages/template/assets/js/admin-consent.js'
);
const pluginPath = path.join(
  projectRoot,
  'src/plugins/universal-legal-pages/template/init.php'
);
const routeTransitionPath = path.join(
  projectRoot,
  'src/themes/reactwp/js/inc/useRouteTransition.js'
);
const script = fs.readFileSync(scriptPath, 'utf8');
const stylesheet = fs.readFileSync(stylesheetPath, 'utf8');
const adminStylesheet = fs.readFileSync(adminStylesheetPath, 'utf8');
const adminScript = fs.readFileSync(adminScriptPath, 'utf8');
const pluginSource = fs.readFileSync(pluginPath, 'utf8');
const routeTransition = fs.readFileSync(routeTransitionPath, 'utf8');

test('PHP public config and browser manager use the same consent API version', () => {
  const browserVersion = script.match(/var API_VERSION = (\d+);/);
  const phpVersion = pluginSource.match(/'version'\s*=>\s*(\d+),/);

  assert.ok(browserVersion, 'The browser consent API version is missing.');
  assert.ok(phpVersion, 'The PHP public consent version is missing.');
  assert.equal(Number(phpVersion[1]), Number(browserVersion[1]), 'PHP and JavaScript consent versions diverged.');
});

const consentStrings = (title, revisit) => ({
  title,
  message: `${title} message`,
  legalLinksLabel: 'Legal documents',
  actions: {
    acceptAll: 'Accept all',
    rejectAll: 'Reject all',
    customize: 'Customize',
    save: 'Save choices',
    close: 'Close',
    revisit
  },
  dialog: {
    title: `${title} dialog`,
    description: `${title} dialog description`
  },
  categories: {
    necessary: {label: 'Necessary', description: 'Necessary description'},
    preferences: {label: 'Preferences', description: 'Preferences description'},
    analytics: {label: 'Analytics', description: 'Analytics description'},
    marketing: {label: 'Marketing', description: 'Marketing description'},
    external: {label: 'External content', description: 'External description'}
  },
  services: {
    title: 'Services',
    description: 'Choose services',
    blocked: 'Blocked content',
    allow: 'Allow service',
    unclassified: 'Unclassified service'
  },
  terms: {
    label: 'I agree',
    description: 'Terms description',
    requiredError: 'Terms required'
  },
  gpc: {notice: 'GPC notice'},
  error: {generic: 'Generic error'}
});

const consentCategories = () => [
  {id: 'necessary', label: 'Necessary', description: 'Necessary description'},
  {id: 'preferences', label: 'Preferences', description: 'Preferences description'},
  {id: 'analytics', label: 'Analytics', description: 'Analytics description'},
  {id: 'marketing', label: 'Marketing', description: 'Marketing description'},
  {id: 'external', label: 'External content', description: 'External description'}
];

class FakeTextNode {
  constructor(value){
    this.nodeType = 3;
    this.textContent = value;
    this.parentNode = null;
  }
}

class FakeElement {
  constructor(tagName, ownerDocument){
    this.nodeType = 1;
    this.tagName = String(tagName).toUpperCase();
    this.ownerDocument = ownerDocument;
    this.parentNode = null;
    this.childNodes = [];
    this.attributes = Object.create(null);
    this.listeners = Object.create(null);
    this.style = {};
    this.className = '';
    this.id = '';
    this.hidden = false;
    this.checked = false;
    this.disabled = false;
    this.indeterminate = false;
    this.shadowRoot = null;
    this._textContent = '';
  }

  get children(){
    return this.childNodes.filter((node) => node.nodeType === 1);
  }

  get firstChild(){
    return this.childNodes[0] || null;
  }

  get isConnected(){
    if(this === this.ownerDocument.documentElement || this === this.ownerDocument.body) return true;
    if(this.parentNode instanceof FakeShadowRoot) return this.parentNode.host.isConnected;
    return Boolean(this.parentNode && this.parentNode.isConnected);
  }

  get textContent(){
    if(this.childNodes.length){
      return this.childNodes.map((node) => node.textContent || '').join('');
    }
    return this._textContent;
  }

  set textContent(value){
    this.childNodes = [];
    this._textContent = String(value);
  }

  appendChild(node){
    if(node.parentNode && typeof node.parentNode.removeChild === 'function') node.parentNode.removeChild(node);
    this._textContent = '';
    this.childNodes.push(node);
    node.parentNode = this;
    return node;
  }

  removeChild(node){
    const index = this.childNodes.indexOf(node);
    if(index >= 0) this.childNodes.splice(index, 1);
    node.parentNode = null;
    return node;
  }

  setAttribute(name, value){
    this.attributes[name] = String(value);
    if(name === 'id') this.id = String(value);
    if(name === 'class') this.className = String(value);
  }

  getAttribute(name){
    if(name === 'id' && this.id) return this.id;
    if(name === 'class' && this.className) return this.className;
    return Object.prototype.hasOwnProperty.call(this.attributes, name) ? this.attributes[name] : null;
  }

  hasAttribute(name){
    return this.getAttribute(name) !== null;
  }

  removeAttribute(name){
    delete this.attributes[name];
  }

  addEventListener(type, listener){
    this.listeners[type] = this.listeners[type] || [];
    this.listeners[type].push(listener);
  }

  dispatch(type, properties = {}){
    const event = {target: this, preventDefault(){}, ...properties};
    for(const listener of this.listeners[type] || []) listener(event);
  }

  attachShadow(){
    const root = new FakeShadowRoot(this, this.ownerDocument);
    this.shadowRoot = root;
    return root;
  }

  matches(selector){
    return selector.split(',').some((part) => this.matchesSingle(part.trim()));
  }

  matchesSingle(selector){
    if(!selector) return false;
    if(selector.startsWith('#')) return this.id === selector.slice(1);
    if(selector.startsWith('.')) return this.className.split(/\s+/u).includes(selector.slice(1));

    const notDisabled = selector.endsWith(':not([disabled])');
    if(notDisabled){
      if(this.disabled || this.hasAttribute('disabled')) return false;
      selector = selector.slice(0, -16);
    }

    const tag = selector.match(/^[a-z]+/iu)?.[0];
    if(tag && this.tagName !== tag.toUpperCase()) return false;
    const attributes = [...selector.matchAll(/\[([^=\]]+)(?:="([^"]*)")?\]/gu)];
    return attributes.every((match) => {
      const value = this.getAttribute(match[1]);
      return value !== null && (typeof match[2] === 'undefined' || value === match[2]);
    });
  }

  closest(selector){
    let current = this;
    while(current && current.nodeType === 1){
      if(current.matches(selector)) return current;
      current = current.parentNode instanceof FakeShadowRoot ? current.parentNode.host : current.parentNode;
    }
    return null;
  }

  querySelectorAll(selector){
    const matches = [];
    const visit = (node) => {
      for(const child of node.childNodes || []){
        if(child.nodeType !== 1) continue;
        if(child.matches(selector)) matches.push(child);
        visit(child);
      }
    };
    visit(this);
    return matches;
  }

  querySelector(selector){
    return this.querySelectorAll(selector)[0] || null;
  }

  contains(candidate){
    if(candidate === this) return true;
    return this.childNodes.some((child) => child === candidate || (child.contains && child.contains(candidate)));
  }

  focus(){
    const root = this.getRootNode();
    if(root instanceof FakeShadowRoot) root.activeElement = this;
    else this.ownerDocument.activeElement = this;
  }

  getRootNode(){
    let current = this;
    while(current.parentNode) current = current.parentNode;
    return current;
  }
}

class FakeShadowRoot extends FakeElement {
  constructor(host, ownerDocument){
    super('#shadow-root', ownerDocument);
    this.nodeType = 11;
    this.host = host;
    this.activeElement = null;
  }

  get isConnected(){ return this.host.isConnected; }
}

class FakeDocument {
  constructor(initialCookie = ''){
    this.readyState = 'complete';
    this.listeners = Object.create(null);
    this.cookieValues = Object.create(null);
    this.documentElement = new FakeElement('html', this);
    this.documentElement.lang = 'en';
    this.body = new FakeElement('body', this);
    this.head = new FakeElement('head', this);
    this.documentElement.appendChild(this.head);
    this.documentElement.appendChild(this.body);
    this.activeElement = this.body;
    if(initialCookie){
      const separator = initialCookie.indexOf('=');
      this.cookieValues[initialCookie.slice(0, separator)] = initialCookie.slice(separator + 1);
    }
  }

  createElement(tagName){ return new FakeElement(tagName, this); }
  createTextNode(value){ return new FakeTextNode(value); }
  addEventListener(type, listener){
    this.listeners[type] = this.listeners[type] || [];
    this.listeners[type].push(listener);
  }
  dispatchEvent(event){
    for(const listener of this.listeners[event.type] || []) listener(event);
    return true;
  }
  querySelectorAll(selector){
    const values = [];
    if(this.documentElement.matches(selector)) values.push(this.documentElement);
    return values.concat(this.documentElement.querySelectorAll(selector));
  }
  querySelector(selector){ return this.querySelectorAll(selector)[0] || null; }
  getElementById(id){ return this.querySelector(`#${id}`); }
  get cookie(){
    return Object.entries(this.cookieValues).map(([key, value]) => `${key}=${value}`).join('; ');
  }
  set cookie(value){
    const pair = value.split(';', 1)[0];
    const separator = pair.indexOf('=');
    const key = pair.slice(0, separator);
    const content = pair.slice(separator + 1);
    if(/Max-Age=0|Expires=Thu, 01 Jan 1970/iu.test(value)) delete this.cookieValues[key];
    else this.cookieValues[key] = content;
  }
}

const createConsentConfig = (overrides = {}) => ({
  version: 4,
  cookieName: 'ulp_consent_1',
  cookiePath: '/',
  durationDays: 180,
  policyVersion: '1',
  serviceRegistryVersion: '0123456789abcdef01234567',
  respectGpc: true,
  showRevisitButton: true,
  termsRequired: false,
  stylesheetUrl: 'https://example.test/consent-manager.css',
  legalLinks: [],
  termsLink: null,
  integrations: {},
  integrationCategories: {
    googleAnalytics: 'analytics',
    googleTagManager: 'analytics',
    googleAds: 'marketing',
    metaPixel: 'marketing'
  },
  categories: consentCategories(),
  services: [],
  strings: consentStrings('Privacy choices', 'Cookie settings'),
  ...overrides
});

const createPlaceholder = (document, serviceId, source) => {
  const placeholder = document.createElement('div');
  placeholder.setAttribute('data-ulc-service', serviceId);
  placeholder.setAttribute('data-ulc-src', source);
  return placeholder;
};

const runConsentManager = ({config, gpc = false, cookie = '', vendorCookies = {}, placeholders = () => [], managerNonce = ''}) => {
  const document = new FakeDocument(cookie);
  if(managerNonce){
    document.currentScript = document.createElement('script');
    document.currentScript.setAttribute('nonce', managerNonce);
  }
  Object.assign(document.cookieValues, vendorCookies);
  for(const placeholder of placeholders(document)) document.body.appendChild(placeholder);
  const observers = [];
  class FakeMutationObserver {
    constructor(callback){ this.callback = callback; this.disconnected = false; observers.push(this); }
    observe(target, options){ this.target = target; this.options = options; }
    disconnect(){ this.disconnected = true; }
    trigger(records){ this.callback(records); }
  }
  const windowListeners = Object.create(null);
  const window = {
    location: {
      protocol: 'https:',
      href: 'https://example.test/',
      hostname: 'example.test',
      reloadCount: 0,
      reload(){ this.reloadCount += 1; }
    },
    UniversalLegalConsentConfig: config,
    MutationObserver: FakeMutationObserver,
    HTMLElement: function HTMLElement(){},
    addEventListener(type, listener){
      windowListeners[type] = windowListeners[type] || [];
      windowListeners[type].push(listener);
    },
    setTimeout(callback){ callback(); return 1; }
  };
  class FakeCustomEvent {
    constructor(type, options){ this.type = type; this.detail = options?.detail; }
  }

  vm.runInNewContext(script, {
    window,
    document,
    navigator: {globalPrivacyControl: gpc},
    URL,
    Date,
    CustomEvent: FakeCustomEvent,
    setTimeout: window.setTimeout,
    clearTimeout(){}
  });

  return {window, document, observer: observers[0], windowListeners};
};

const serviceFixture = ({id, category, domain, purpose = category === 'unclassified' ? 'external' : category, kind = 'iframe', managed = true}) => ({
  id,
  label: id.replaceAll('-', ' '),
  category,
  purpose,
  domains: [domain],
  kind,
  managed
});

const readStoredConsent = (document) => {
  const encoded = document.cookieValues['__Host-ulp_consent_1'];
  return encoded ? JSON.parse(decodeURIComponent(encoded)) : null;
};

test('browser defaults fall back to English when localized consent copy is unavailable', () => {
  const runtime = runConsentManager({
    config: createConsentConfig({strings: {}})
  });
  const shadow = runtime.document.querySelector('[data-universal-legal-consent-root]').shadowRoot;

  assert.equal(shadow.querySelector('.ulc-banner__title').textContent, 'Your privacy choices');
  assert.equal(
    shadow.querySelector('.ulc-banner__message').textContent,
    'We use cookies required for the site to work and, with your consent, analytics and marketing tools.'
  );
  assert.equal(shadow.querySelector('[data-action="accept-all"]').textContent, 'Accept all');
  assert.equal(shadow.querySelector('[data-action="reject-all"]').textContent, 'Reject all');
  assert.equal(shadow.querySelector('[data-action="open-preferences"]').textContent, 'Customize');

  runtime.window.UniversalLegalConsent.openPreferences();
  assert.equal(shadow.querySelector('.ulc-dialog__title').textContent, 'Consent preferences');
  assert.equal(shadow.querySelector('.ulc-dialog__footer').querySelector('.ulc-button--primary').textContent, 'Save my choices');

  for(const formerFrenchFallback of [
    'Vos choix de confidentialité',
    'Tout accepter',
    'Tout refuser',
    'Préférences de confidentialité',
    'Impossible d’enregistrer ce choix. Veuillez réessayer.'
  ]){
    assert.equal(script.includes(formerFrenchFallback), false, `French browser fallback remains: ${formerFrenchFallback}`);
  }
});

test('legal documents open in isolated tabs without replacing the consent page', () => {
  const runtime = runConsentManager({
    config: createConsentConfig({
      legalLinks: [{label: 'Privacy policy', url: 'https://example.test/legal/privacy/'}],
      termsRequired: true,
      termsLink: {label: 'Terms', url: 'https://example.test/legal/terms/'}
    })
  });
  const shadow = runtime.document.querySelector('[data-universal-legal-consent-root]').shadowRoot;
  const legalLinks = shadow.querySelectorAll('.ulc-link');

  assert.ok(legalLinks.length >= 3, 'Banner, dialog, and explicit-acceptance links were not all rendered.');
  assert.ok(shadow.querySelector('#ulc-terms-confirmation-link'), 'The explicit-acceptance legal link is missing.');

  for(const legalLink of legalLinks){
    assert.equal(legalLink.getAttribute('target'), '_blank');
    assert.equal(legalLink.getAttribute('rel'), 'noopener noreferrer');
  }
});

test('consent manager keeps untrusted configuration out of executable HTML sinks', () => {
  const forbiddenSinks = [
    '.innerHTML',
    '.outerHTML',
    'insertAdjacentHTML',
    'document.write',
    'document.writeln',
    'eval(',
    'new Function('
  ];

  for(const sink of forbiddenSinks){
    assert.equal(script.includes(sink), false, `Forbidden browser sink found: ${sink}`);
  }

  assert.equal(script.includes('console.'), false, 'The consent manager must not log visitor state or vendor failures.');
  assert.match(script, /element\.textContent = text;/, 'Configured copy must render through textContent.');
  assert.match(script, /url\.protocol === 'http:' \|\| url\.protocol === 'https:'/u, 'Legal links must allow only HTTP(S).');
  assert.doesNotMatch(script, /url\.origin !== window\.location\.origin/u, 'WordPress asset-CDN stylesheet URLs must remain portable.');
  assert.match(script, /&& !url\.username[\s\S]*&& !url\.password/u, 'HTTP(S) URLs containing credentials must be rejected.');
  assert.match(script, /raw\.termsRequired[\s\S]*!termsLink/u, 'Required terms must fail closed without a valid terms link.');
  assert.match(script, /MAX_LEGAL_LINKS = 1000/u, 'The public legal-link list must remove the former 20-page cap while retaining a technical abuse bound.');
  assert.match(script, /raw\.length > MAX_LEGAL_LINKS/u, 'The public legal-link list must remain technically bounded.');
  assert.match(script, /seen\[link\.url\]/u, 'Duplicate public legal links must invalidate the configuration.');
  assert.match(script, /createElement\('nav', className\)/u, 'Selected legal pages must render as a labelled navigation region.');
  assert.match(script, /config\.strings\.legalLinksLabel/u, 'The legal-link navigation must expose an accessible name.');
  assert.match(script, /anchor\.setAttribute\('target', '_blank'\);/u, 'Legal documents must open without replacing the consent page.');
  assert.match(script, /anchor\.setAttribute\('rel', 'noopener noreferrer'\);/u, 'New legal-document tabs must not retain an opener or referrer relationship.');
  assert.match(script, /OPEN_TRIGGER_ATTRIBUTE = 'data-ulc-open'/u, 'Theme-owned consent triggers need a stable declarative attribute.');
  assert.match(script, /document\.addEventListener\('click', handleDocumentClick\)/u, 'Custom triggers must work through one delegated document listener.');
  assert.match(script, /openPreferences\(trigger\)/u, 'The custom trigger must use the same public preferences API.');
  assert.match(script, /showPreferences\(request, resolvedTrigger\)/u, 'The external trigger must be preserved for focus restoration.');
  assert.match(script, /event\.defaultPrevented[\s\S]*event\.metaKey[\s\S]*event\.shiftKey/u, 'Modified or already handled clicks must retain normal browser behavior.');
  assert.match(script, /setLanguage: setLanguage/u, 'ReactWP needs a stable public language-switch API.');
  assert.match(script, /config\.stringTranslations\[code\]/u, 'Language switches must prefer the complete validated translation allowlist.');
  assert.match(script, /elements\.bannerTitle\.textContent = config\.strings\.title;/u, 'A route-language switch must update the visible banner title through textContent.');
  assert.match(script, /elements\.bannerMessage\.textContent = config\.strings\.message;/u, 'A route-language switch must update the visible banner message through textContent.');
  assert.match(script, /elements\.revisit\.textContent = config\.strings\.actions\.revisit;/u, 'The permanent cookie-button label must update with the route language.');
  assert.match(script, /elements\.dialogTitle\.textContent = config\.strings\.dialog\.title;/u, 'The preferences popup title must update with the route language.');
  assert.match(script, /elements\.dialogClose\.setAttribute\('aria-label', config\.strings\.actions\.close\)/u, 'The close button accessible label must update with the route language.');
  assert.match(script, /elements\.categoryLabels\[category\]\.textContent/u, 'All category labels must update without rebuilding the dialog.');
  assert.match(script, /elements\.termsError\.textContent = config\.strings\.terms\.requiredError/u, 'The terms error must update with the route language.');
  assert.match(script, /region\.setAttribute\('aria-label', config\.strings\.legalLinksLabel\)/u, 'Legal navigation accessible names must update with the route language.');
  assert.match(script, /normalizeLinkTranslations\(raw\.linkTranslations\)/u, 'Localized consent links must cross the same browser trust boundary as the global links.');
  assert.match(script, /config\.linkTranslations\[code\]\.legalLinks/u, 'Route-language switches must select the matching legal-link bundle.');
  assert.match(script, /while\(region\.firstChild\)/u, 'Visible legal links must be rebuilt when the route language changes.');
  assert.match(script, /elements\.termsLink\.href = config\.termsLink\.url/u, 'The explicit-acceptance link must update with the route language.');
  assert.match(script, /MAX_LANGUAGES = 32/u, 'The browser translation map must support large ReactWP language sets while retaining a technical bound.');
  assert.match(script, /codes\.length > MAX_LANGUAGES/u, 'Every browser translation map must remain bounded to ReactWP’s language limit.');
  assert.match(script, /unicodeLength\(value\) > CONSENT_STRING_LIMITS\[path\]/u, 'Every localized string must retain its server-side Unicode length bound in the browser.');
  assert.match(script, /hasExactKeys\(raw\.actions/u, 'Localized copy must use an exact nested allowlist.');
  assert.match(script, /hasExactKeys\(raw\.services, \['title', 'description', 'blocked', 'allow', 'unclassified'\]\)/u, 'Service copy must use the same exact localized allowlist.');
  assert.match(script, /code\.split\(\/\[-_\]\/\)\[0\]/u, 'Regional route languages must use the same primary-language fallback as PHP.');
  assert.match(script, /hasServiceConsent: hasServiceConsent/u, 'Consumers need a stable individual-service consent API.');
  assert.match(script, /MAX_SERVICES = 32/u, 'The public service registry and adapter count must remain bounded.');
  assert.match(script, /MAX_SERVICE_DOMAINS = 8/u, 'Service-domain bounds must match the backend contract.');
  assert.match(script, /unicodeLength\(label\) > 80/u, 'Service labels must match the backend Unicode length bound.');
  assert.equal(script.includes("!/^[a-f0-9]{24}$/.test(raw.serviceRegistryVersion)"), true, 'Registry versions must follow the backend hash contract exactly.');
});

test('known integrations are bounded and consent-gated', () => {
  const vendorUrls = [...script.matchAll(/https:\/\/[^'"\s)]+/gu)].map((match) => match[0]);

  assert.deepEqual(
    [...new Set(vendorUrls)].sort(),
    [
      'https://connect.facebook.net/en_US/fbevents.js',
      'https://player.vimeo.com/video/',
      'https://www.googletagmanager.com/gtag/js?id=',
      'https://www.googletagmanager.com/gtm.js?id=',
      'https://www.youtube-nocookie.com/embed/'
    ],
    'Only the reviewed Google and Meta script origins may be hard-coded.'
  );

  assert.ok(
    script.indexOf('initializeGoogleConsentMode();') < script.indexOf('registerKnownIntegrations();'),
    'Google consent defaults must be initialized before known integrations are registered.'
  );
  assert.match(script, /window\.gtag\('consent', 'default', googleConsentState\(null\)\);/u);
  assert.match(script, /analytics_storage:/u);
  assert.match(script, /ad_storage:/u);
  assert.match(script, /ad_user_data:/u);
  assert.match(script, /ad_personalization:/u);
  assert.match(script, /functionality_storage:/u);
  assert.match(script, /personalization_storage:/u);
  assert.match(script, /security_storage: 'granted'/u);
  assert.match(
    script,
    /id: 'google-tag-manager',[\s\S]*?categories: \[config\.integrationCategories\.googleTagManager\],[\s\S]*?purpose: 'analytics',[\s\S]*?mode: 'all'/u,
    'GTM must load only after its explicitly assigned category is accepted.'
  );
  assert.match(
    script,
    /id: 'google-ads-remarketing',[\s\S]*?serviceId: 'google-ads-remarketing',[\s\S]*?categories: \[\],[\s\S]*?mode: 'all'/u,
    'Standalone Google Ads remarketing must require marketing consent.'
  );
  assert.equal(
    (script.match(/googletagmanager\.com\/gtag\/js\?id=/gu) || []).length,
    1,
    'GA4 and Google Ads must share one reviewed Google tag loader.'
  );
  assert.match(script, /configuredGoogleDestinations\[destinationId\]/u, 'Each Google destination must be configured at most once.');
  assert.match(script, /window\.gtag\('config', destinationId\);/u, 'The standalone AW identifier must be configured as a Google tag destination.');
  assert.match(script, /if\(!consent && !alwaysRequired\)\{\s*return;\s*\}/u, 'Only integrations assigned to Necessary may run without a consent object.');
  assert.match(script, /integrationHasConsent\(integration, consent\)/u, 'Every integration load must pass its category check.');
  assert.match(script, /serviceId: 'google-analytics-4'/u, 'The direct GA4 integration must use its individual service decision.');
  assert.match(script, /serviceId: 'meta-pixel'/u, 'Meta must use its individual service decision.');
  assert.doesNotMatch(script, /id: 'google-tag-manager',[\s\S]{0,120}serviceId:/u, 'GTM remains a category adapter governed by its explicit assignment and Consent Mode.');
  assert.match(script, /CATEGORY_NAMES\.indexOf\(category\)/u, 'Integration adapters may also use the always-active Necessary category.');
  assert.match(script, /service\.domains\.length !== 1[\s\S]*service\.domains\[0\] !== scriptHostname/u, 'Configured scripts must match their exact registered service host.');
  assert.match(script, /script\.textContent = definition\.initCode;/u, 'Trusted init code must be assigned without an HTML parser.');
  assert.match(script, /script\.nonce = managerScriptNonce;/u, 'Configured scripts must inherit an approved manager-script CSP nonce when one exists.');
});

test('Google consent defaults are queued locally before any optional network request', () => {
  const fakeDocument = {
    readyState: 'loading',
    addEventListener(){},
    cookie: ''
  };
  const fakeNavigator = {globalPrivacyControl: false};
  const fakeWindow = {
    location: {
      protocol: 'https:',
      href: 'https://example.test/'
    },
    UniversalLegalConsentConfig: {
      version: 4,
      cookieName: 'ulp_consent_1',
      cookiePath: '/',
      durationDays: 180,
      policyVersion: '1',
      serviceRegistryVersion: '0123456789abcdef01234567',
      respectGpc: true,
      showRevisitButton: true,
      termsRequired: false,
      stylesheetUrl: 'https://example.test/consent-manager.css',
      legalLinks: [],
      termsLink: null,
      integrations: {
        googleAnalytics: '',
        googleTagManager: '',
        googleAds: 'AW-1234567890',
        metaPixel: ''
      },
      integrationCategories: {
        googleAnalytics: 'analytics',
        googleTagManager: 'analytics',
        googleAds: 'marketing',
        metaPixel: 'marketing'
      },
      categories: consentCategories(),
      services: [{
        id: 'google-ads-remarketing',
        label: 'Google Ads',
        category: 'marketing',
        purpose: 'marketing',
        domains: ['www.googletagmanager.com'],
        kind: 'integration',
        managed: true
      }],
      strings: {}
    }
  };

  vm.runInNewContext(script, {
    window: fakeWindow,
    document: fakeDocument,
    navigator: fakeNavigator,
    URL,
    Date,
    setTimeout,
    clearTimeout
  });

  assert.equal(typeof fakeWindow.UniversalLegalConsent.openPreferences, 'function', 'The preferences dialog must remain available through the public API.');
  assert.equal(fakeWindow.UniversalLegalConsent.openPreferences(), true, 'A preferences request made before DOM readiness should be queued.');
  assert.equal(fakeWindow.UniversalLegalConsent.setLanguage('fr'), false, 'Standalone configuration must reject unavailable language switches.');
  assert.equal(fakeWindow.dataLayer.length, 1, 'Only the local denied-by-default consent command may exist before a choice.');
  assert.equal(fakeWindow.dataLayer[0][0], 'consent');
  assert.equal(fakeWindow.dataLayer[0][1], 'default');
  assert.equal(fakeWindow.dataLayer[0][2].analytics_storage, 'denied');
  assert.equal(fakeWindow.dataLayer[0][2].ad_storage, 'denied');
  assert.equal(fakeWindow.dataLayer[0][2].ad_user_data, 'denied');
  assert.equal(fakeWindow.dataLayer[0][2].ad_personalization, 'denied');
  assert.equal(fakeWindow.dataLayer[0][2].security_storage, 'granted');
  assert.equal(typeof fakeDocument.head, 'undefined', 'No script node may be appended before consent.');
});

test('Google Consent Mode follows accepted direct services inside mixed categories', () => {
  const services = [
    serviceFixture({id: 'google-analytics-4', category: 'analytics', domain: 'www.googletagmanager.com', kind: 'integration'}),
    serviceFixture({id: 'other-analytics', category: 'analytics', domain: 'analytics.example.test', kind: 'integration'}),
    serviceFixture({id: 'google-ads-remarketing', category: 'marketing', domain: 'www.googletagmanager.com', kind: 'integration'}),
    serviceFixture({id: 'meta-pixel', category: 'marketing', domain: 'connect.facebook.net', kind: 'pixel'})
  ];
  const runtime = runConsentManager({
    config: createConsentConfig({
      services,
      integrations: {
        googleAnalytics: 'G-ABCD1234',
        googleAds: 'AW-1234567890',
        googleTagManager: '',
        metaPixel: ''
      }
    })
  });
  const manager = runtime.document.querySelector('[data-universal-legal-consent-root]');
  const shadow = manager.shadowRoot;

  runtime.window.UniversalLegalConsent.openPreferences();
  shadow.querySelector('#ulc-service-google-analytics-4').checked = true;
  shadow.dispatch('change', {target: shadow.querySelector('#ulc-service-google-analytics-4')});
  shadow.querySelector('#ulc-service-google-ads-remarketing').checked = true;
  shadow.dispatch('change', {target: shadow.querySelector('#ulc-service-google-ads-remarketing')});
  shadow.dispatch('submit', {target: shadow.querySelector('form'), preventDefault(){}});

  const stored = readStoredConsent(runtime.document);
  const updates = runtime.window.dataLayer.filter((entry) => entry[0] === 'consent' && entry[1] === 'update');
  const update = updates[updates.length - 1];

  assert.equal(stored.categories.analytics, false, 'The analytics category must remain mixed while another service is refused.');
  assert.equal(stored.categories.marketing, false, 'The marketing category must remain mixed while Meta is refused.');
  assert.equal(stored.analytics, true, 'The technical analytics signal must follow the accepted GA4 service.');
  assert.equal(stored.marketing, true, 'The technical marketing signal must follow the accepted Google Ads service.');
  assert.equal(update[2].analytics_storage, 'granted', 'Accepted direct GA4 must receive analytics storage even in a mixed category.');
  assert.equal(update[2].ad_storage, 'granted', 'Accepted direct Google Ads must receive ad storage even in a mixed category.');
  assert.equal(update[2].ad_user_data, 'granted');
  assert.equal(update[2].ad_personalization, 'granted');
  assert.equal(runtime.window.UniversalLegalConsent.hasServiceConsent('other-analytics'), false);
  assert.equal(runtime.window.UniversalLegalConsent.hasServiceConsent('meta-pixel'), false);
});

test('ReactWP language changes are validated and survive pre-initialization routing', () => {
  const fakeDocument = {
    readyState: 'loading',
    addEventListener(){},
    cookie: ''
  };
  const fakeWindow = {
    location: {
      protocol: 'https:',
      href: 'https://example.test/'
    },
    UniversalLegalConsent: {
      queue: [],
      pendingLanguage: 'fr'
    },
    UniversalLegalConsentConfig: {
      version: 4,
      cookieName: 'ulp_consent_1',
      cookiePath: '/',
      durationDays: 180,
      policyVersion: '1',
      serviceRegistryVersion: '0123456789abcdef01234567',
      respectGpc: true,
      showRevisitButton: true,
      termsRequired: false,
      stylesheetUrl: 'https://example.test/consent-manager.css',
      legalLinks: [],
      termsLink: null,
      integrations: {},
      integrationCategories: {
        googleAnalytics: 'analytics',
        googleTagManager: 'analytics',
        googleAds: 'marketing',
        metaPixel: 'marketing'
      },
      categories: consentCategories(),
      services: [],
      currentLanguage: 'en',
      stringTranslations: {
        fr: consentStrings('Choix français', 'Gérer les témoins'),
        en: consentStrings('English choices', 'Cookie settings')
      },
      bannerTranslations: {
        fr: {title: '😀'.repeat(120), message: 'Message français.'},
        en: {title: 'English choices', message: 'English message.'}
      },
      linkTranslations: {
        fr: {
          legalLinks: [{url: 'https://example.test/fr/confidentialite', label: 'Confidentialité'}],
          termsLink: null
        },
        en: {
          legalLinks: [{url: 'https://example.test/en/privacy', label: 'Privacy'}],
          termsLink: null
        }
      },
      strings: {
        title: 'English choices',
        message: 'English message.'
      }
    }
  };

  vm.runInNewContext(script, {
    window: fakeWindow,
    document: fakeDocument,
    navigator: {globalPrivacyControl: false},
    URL,
    Date,
    setTimeout,
    clearTimeout
  });

  assert.equal(fakeWindow.UniversalLegalConsent.setLanguage('fr'), true, 'A configured ReactWP language must be accepted before DOM initialization.');
  assert.equal(fakeWindow.UniversalLegalConsent.setLanguage('FR'), true, 'Language codes should be normalized consistently with the PHP contract.');
  assert.equal(fakeWindow.UniversalLegalConsent.setLanguage('fr-CA'), true, 'A regional ReactWP route must fall back to its configured primary language.');
  assert.equal(fakeWindow.UniversalLegalConsent.setLanguage('es'), false, 'An unknown route language must not mutate consent copy.');
  assert.equal(fakeWindow.UniversalLegalConsent.setLanguage('<script>'), false, 'Malformed language input must not reach the rendered interface.');
});

test('ReactWP route language switches update ordered legal links and terms in place', () => {
  const runtime = runConsentManager({
    config: createConsentConfig({
      termsRequired: true,
      currentLanguage: 'en',
      legalLinks: [{url: 'https://example.test/en/privacy', label: 'Privacy'}],
      termsLink: {url: 'https://example.test/en/terms', label: 'Terms'},
      stringTranslations: {
        fr: consentStrings('Choix français', 'Gérer les témoins'),
        en: consentStrings('English choices', 'Cookie settings')
      },
      linkTranslations: {
        fr: {
          legalLinks: [
            {url: 'https://example.test/fr/conditions', label: 'Conditions'},
            {url: 'https://example.test/fr/confidentialite', label: 'Confidentialité'}
          ],
          termsLink: {url: 'https://example.test/fr/conditions', label: 'Conditions'}
        },
        en: {
          legalLinks: [{url: 'https://example.test/en/privacy', label: 'Privacy'}],
          termsLink: {url: 'https://example.test/en/terms', label: 'Terms'}
        }
      }
    })
  });
  const shadow = runtime.document.querySelector('[data-universal-legal-consent-root]').shadowRoot;
  const legalNavigation = shadow.querySelector('.ulc-legal-links');
  const termsLink = shadow.querySelector('#ulc-terms-confirmation-link');

  assert.deepEqual(legalNavigation.children.map((link) => link.textContent), ['Privacy'], 'Initial links must follow the active English language.');
  assert.equal(termsLink.href, 'https://example.test/en/terms');
  assert.equal(runtime.window.UniversalLegalConsent.setLanguage('fr-CA'), true);
  assert.deepEqual(legalNavigation.children.map((link) => link.textContent), ['Conditions', 'Confidentialité'], 'French links did not update in their configured order.');
  assert.deepEqual(legalNavigation.children.map((link) => link.href), ['https://example.test/fr/conditions', 'https://example.test/fr/confidentialite']);
  assert.equal(termsLink.textContent, 'Conditions');
  assert.equal(termsLink.href, 'https://example.test/fr/conditions');
});

test('consent persistence is exact, bounded, and privacy-aware', () => {
  for(const key of [
    'version',
    'policyVersion',
    'serviceRegistryVersion',
    'necessary',
    'preferences',
    'analytics',
    'marketing',
    'external',
    'categories',
    'services',
    'terms',
    'gpc',
    'timestamp'
  ]){
    assert.match(script, new RegExp(`'${key}'`), `Consent key missing: ${key}`);
  }

  assert.match(script, /keys\.length !== expectedKeys\.length/u, 'Unknown cookie keys must invalidate the cookie.');
  assert.match(
    script,
    /!config\.termsRequired[\s\S]*value\.terms === true[\s\S]*noGrantedCategories\(value\.categories\) && noGrantedServices\(value\.services\)/u,
    'A required-terms refusal may persist, but it must never grant an optional category.'
  );
  assert.match(script, /timestamp <= now \+ futureTolerance/u, 'Future timestamps must be bounded.');
  assert.match(script, /timestamp >= now - maximumAge/u, 'Expired consent must be rejected.');
  assert.match(script, /; SameSite=Lax/u);
  assert.match(script, /\? '__Host-' \+ config\.cookieName/u, 'HTTPS consent cookies must use the __Host- prefix.');
  assert.match(script, /consentCookiePath = window\.location\.protocol === 'https:'[\s\S]*\? '\/'/u, 'HTTPS consent cookies must use the root path.');
  assert.match(script, /cookie \+= '; Secure'/u);
  assert.match(script, /service\.purpose === 'marketing' && forceMarketingOff/u, 'GPC must follow protected service behavior instead of category names.');
  assert.match(script, /window\.location\.reload\(\)/u, 'Revocation must remove already loaded adapters through a reload.');
});

test('consent interface is isolated and includes responsive accessibility states', () => {
  assert.equal((script.match(/attachShadow\(/gu) || []).length, 2, 'The manager and declarative embeds each need an isolated Shadow DOM boundary.');
  assert.doesNotMatch(script, /host\.style\./u, 'The Shadow host reset must come from the external stylesheet, not inline style attributes.');
  assert.match(script, /role', 'dialog'/u);
  assert.match(script, /aria-modal/u);
  assert.match(script, /event\.key === 'Escape'/u);
  assert.match(script, /event\.key !== 'Tab'/u);
  assert.match(script, /'inert' in window\.HTMLElement\.prototype/u);
  assert.match(script, /preventScroll: true/u);
  assert.match(script, /focusAfterConsentSave\(consentSaveTrigger\);/u, 'Saving a banner or dialog choice must move focus away from hidden controls.');
  assert.match(script, /preferredTarget && preferredTarget\.isConnected[\s\S]*focusElement\(preferredTarget\)/u, 'A custom trigger must regain focus after saving dialog choices.');
  assert.match(script, /document\.querySelector\('main, \[role="main"\], h1'\) \|\| document\.body/u, 'Sites without a revisit button need a visible document focus fallback.');

  assert.match(stylesheet, /:host\s*\{/u);
  assert.match(stylesheet, /@media \(max-width: 36rem\)/u);
  assert.match(stylesheet, /@media \(max-height: 36rem\)/u);
  assert.match(stylesheet, /@media \(prefers-reduced-motion: reduce\)/u);
  assert.match(stylesheet, /@media \(forced-colors: active\)/u);
  assert.match(stylesheet, /@media print/u);
  assert.match(stylesheet, /min-height: 2\.75rem/u, 'Primary controls must provide a roughly 44px target.');
  assert.match(stylesheet, /\.ulc-dialog__close\s*\{[\s\S]*?width: 2\.75rem;[\s\S]*?height: 2\.75rem;[\s\S]*?font-size: 0;/u, 'The close control must keep a fixed square target without relying on font metrics for its icon.');
  assert.match(stylesheet, /\.ulc-dialog__close::before,[\s\S]*?\.ulc-dialog__close::after\s*\{[\s\S]*?inset-block-start: 50%;[\s\S]*?inset-inline-start: 50%;[\s\S]*?transform: translate\(-50%, -50%\) rotate\(45deg\);/u, 'The close icon must remain geometrically centered in its control.');
  assert.match(stylesheet, /\.ulc-category__label\s*\{[\s\S]*min-height: 1\.5rem;/u, 'Category labels must align with their 24px native checkbox without adding an empty text row.');
  assert.match(stylesheet, /\.ulc-terms__label\s*\{[\s\S]*display: inline;[\s\S]*line-height: inherit;/u, 'The required-terms label must share the surrounding sentence baseline instead of creating a tall inline box.');
  assert.match(stylesheet, /\.ulc-terms__label-line\s*\{[\s\S]*line-height: 1\.5;/u, 'The required-terms sentence must align with its 24px checkbox.');
  assert.match(stylesheet, /\.ulc-terms__label-line \.ulc-link\s*\{[\s\S]*display: inline;[\s\S]*min-height: 0;[\s\S]*line-height: inherit;[\s\S]*vertical-align: baseline;/u, 'The terms link must not expand the confirmation line or shift its baseline.');
  assert.match(stylesheet, /\.ulc-service label\.ulc-service__label\s*\{[\s\S]*min-height: 2\.75rem/u, 'Individual service choices need comfortable native checkbox targets.');
  assert.match(stylesheet, /\.ulc-embed__iframe\s*\{[\s\S]*aspect-ratio: 16 \/ 9/u, 'Blocked embeds must reserve their media geometry.');

  assert.match(adminStylesheet, /\.ulp-admin__page-order\s*\{[\s\S]*grid-template-columns: minmax\(0, 1fr\);[\s\S]*width: 100%;/u, 'Displayed and published legal pages must remain stacked at every viewport width.');
  assert.match(adminStylesheet, /\.ulp-admin__page-bucket \+ \.ulp-admin__page-bucket\s*\{[\s\S]*padding-top: 24px;[\s\S]*border-top:/u, 'Published pages need a clear visual separation below displayed pages.');
  assert.equal((pluginSource.match(/data-ulp-section-select(?=[\s>])/gu) || []).length, 1, 'The consent settings screen needs one compact native section selector.');
  assert.equal((pluginSource.match(/<option value="ulp-settings-panel-/gu) || []).length, 6, 'Every consent settings section needs one selector option.');
  assert.equal((pluginSource.match(/data-ulp-section-panel(?=[\s>])/gu) || []).length, 6, 'Every section option needs one persistent form panel.');
  for(const description of [
    'Enable the consent interface, define when choices expire or must be renewed, respect Global Privacy Control, and choose how visitors reopen their preferences.',
    'Edit every visitor-facing label and message used by the banner, preferences dialog, service controls, confirmation, and errors.',
    'Manage the categories shown to visitors: add, remove, order, name, and describe them for each available language. Necessary always remains.',
    'Review external scripts and embeds found during administrator visits. Name and categorize entries; detected scripts still need a trusted adapter before the plugin can control them.',
    'Choose and order the legal pages shown in the consent interface, then optionally require visitors to confirm a selected document.',
    'Configure built-in services or custom scripts, assign the category that controls when each one loads, and never enter secrets or private API keys.'
  ]){
    assert.ok(pluginSource.includes(`esc_html_e('${description}', 'universal-legal-pages')`), `Section header does not describe its controls: ${description}`);
  }
  assert.equal((pluginSource.match(/ulp-admin-section--initially-hidden/gu) || []).length, 5, 'Every server-rendered section after the first must start outside the JavaScript first paint.');
  assert.doesNotMatch(pluginSource, /data-ulp-section-navigation\s+hidden/u, 'The section selector must not wait for plugin JavaScript to remove a hidden attribute.');
  assert.doesNotMatch(pluginSource, /data-ulp-section-tab(?=[\s>])/u, 'The crowded top-level section tabs must not remain.');
  assert.match(pluginSource, /<\/div>\s*<footer class="ulp-admin__actions">/u, 'The save action must remain outside the switched panels and submit the complete form.');
  assert.match(adminStylesheet, /\.ulp-admin__section-navigation\s*\{[\s\S]*display: none;[\s\S]*grid-template-columns: minmax\(180px, 0\.3fr\) minmax\(0, 1fr\)/u, 'The section selector needs a calm labelled navigation row and a no-JavaScript fallback on wide screens.');
  assert.match(adminStylesheet, /\.js \.ulp-admin__section-navigation\s*\{[\s\S]*display: grid;/u, 'WordPress JavaScript state must expose the section selector before the plugin script initializes.');
  assert.match(adminStylesheet, /\.js \.ulp-admin-section--initially-hidden,[\s\S]*\.ulp-admin-section\[hidden\]\s*\{[\s\S]*display: none;/u, 'Server-initialized and interactively inactive panels must leave the layout and keyboard order.');
  assert.match(adminScript, /panel\.classList\.remove\('ulp-admin-section--initially-hidden'\);[\s\S]*panel\.hidden = panelIndex !== activeIndex;/u, 'The section switcher must hand initial CSS state over to native hidden state.');
  assert.match(adminStylesheet, /\.ulp-admin \.ulp-admin__section-navigation select\s*\{[\s\S]*min-height: 48px/u, 'The native section selector needs a comfortable pointer target.');
  assert.match(adminStylesheet, /@media \(max-width: 782px\)[\s\S]*\.ulp-admin__section-navigation\s*\{[\s\S]*grid-template-columns: minmax\(0, 1fr\)/u, 'The section selector must stack without horizontal scrolling on narrow screens.');
  assert.match(adminStylesheet, /\.ulp-admin__hero-copy\s*\{[\s\S]*?max-width: 690px;/u, 'The simplified hero copy must retain its requested reading width.');
  assert.doesNotMatch(adminStylesheet, /\.ulp-admin__summary/u, 'Removed summary styles must not linger in the admin stylesheet.');
  assert.doesNotMatch(adminStylesheet, /\.ulp-admin__mark/u, 'Removed mark styles must not linger in the admin stylesheet.');
  assert.match(adminStylesheet, /\.ulp-admin__credit\s*\{[\s\S]*?justify-content: flex-start;/u, 'The Studio Champ Gauche credit must remain left-aligned.');
  assert.match(adminStylesheet, /\.ulp-admin > \.notice-success\s*\{[\s\S]*?min-height: 56px;[\s\S]*?border-left-width: 6px;[\s\S]*?font-weight/u, 'A successful settings save needs a prominent black-and-white confirmation treatment.');
  assert.match(pluginSource, /settings_errors\(\);/u, 'The consent screen must render WordPress general notices, including its successful-save confirmation.');
  assert.match(adminStylesheet, /\.ulp-admin__save-notice\s*\{[\s\S]*?position: fixed;[\s\S]*?z-index: 100000;/u, 'The asynchronous save result must remain visible from the bottom of the long settings form.');
  assert.match(adminStylesheet, /\.ulp-admin__save-notice\[hidden\]\s*\{[\s\S]*?display: none;/u, 'The custom save notice must stay out of the interface until a save starts.');
  assert.match(pluginSource, /check_ajax_referer\(self::AJAX_SAVE_ACTION, 'ulp_save_nonce', false\)/u, 'The asynchronous settings mutation needs a dedicated CSRF nonce.');
  assert.match(pluginSource, /if\(!current_user_can\('manage_options'\)\)/u, 'The asynchronous settings mutation needs server-side administrator authorization.');
  assert.match(adminStylesheet, /\.ulp-admin__page-item label\s*\{[\s\S]*min-height: 44px/u, 'Legal-page choices need usable pointer targets.');
  assert.match(adminStylesheet, /\.ulp-admin \.ulp-admin__page-order-button\s*\{[\s\S]*width: 44px[\s\S]*height: 44px/u, 'Keyboard ordering controls need usable pointer targets.');
  assert.match(pluginSource, /<details class="ulp-admin__copy-group" name=/u, 'Copy groups should use native, mutually exclusive accordion disclosures.');
  assert.doesNotMatch(pluginSource, /<details class="ulp-admin__copy-group"[^>]*\sopen(?:\s|>)/u, 'No copy group should be opened by default.');
  assert.match(pluginSource, /class="ulp-admin__copy-summary-description"/u, 'Each accordion summary should explain the fields it contains.');
  assert.match(pluginSource, /<fieldset class="ulp-admin__copy-unit/u, 'Related copy fields should be grouped into labelled editing units.');
  assert.match(adminStylesheet, /\.ulp-admin__copy-sections\s*\{[\s\S]*grid-template-columns: minmax\(0, 1fr\)/u, 'Copy editing units must remain stacked in one column at every viewport width.');
  assert.match(adminStylesheet, /\.ulp-admin__copy-unit\s*\{[\s\S]*box-sizing: border-box;[\s\S]*width: 100%;/u, 'Every copy editing unit must occupy the complete available width.');
  assert.match(adminStylesheet, /\.ulp-admin__copy-group > summary\s*\{[\s\S]*min-height: 72px/u, 'Accordion summaries need a clear, comfortable activation target.');
  assert.match(adminStylesheet, /\.ulp-admin__services input\[type='text'\],[\s\S]*min-height: 44px/u, 'Service classification fields need usable targets.');
  assert.doesNotMatch(adminStylesheet, /\.ulp-admin-section--copy/u, 'The copy editor must not override the shared two-column section layout.');
  assert.match(adminStylesheet, /\.ulp-admin-section\s*\{[\s\S]*--ulp-admin-section-padding-block: 30px;/u, 'Integrations and every other settings section need the same desktop vertical inset.');
  assert.match(adminStylesheet, /\.ulp-admin-section__header\s*\{[\s\S]*padding-block: var\(--ulp-admin-section-padding-block\);/u, 'Every section header must use the shared vertical inset.');
  assert.match(adminStylesheet, /\.ulp-admin-section__body\s*\{[\s\S]*padding-block: var\(--ulp-admin-section-padding-block\);/u, 'Every section body must use the shared vertical inset.');
  assert.match(adminStylesheet, /@media \(max-width: 782px\)[\s\S]*\.ulp-admin-section\s*\{[\s\S]*--ulp-admin-section-padding-block: 24px;/u, 'Stacked settings sections must retain one shared compact vertical inset.');
  assert.match(adminStylesheet, /\.ulp-admin-section__body > \.form-table:first-child tr:first-child > th,[\s\S]*padding-top: 0;/u, 'A first table row must not add extra space above the shared section inset.');
  assert.match(adminStylesheet, /\.ulp-admin-section__body > \.form-table:last-child tr:last-child > th,[\s\S]*padding-bottom: 0;/u, 'A last table row must not add extra space below the shared section inset.');
  assert.doesNotMatch(pluginSource, /ulp-admin-section__body--copy/u, 'The copy editor must not retain a section-specific vertical padding exception.');
  assert.match(adminStylesheet, /\.ulp-admin__language-editor\s*\{[\s\S]*max-width: none/u, 'The language editor must use the width available in its dedicated section.');
  assert.match(adminStylesheet, /\.ulp-admin__copy-grid\s*\{[\s\S]*grid-template-columns: repeat\(2, minmax\(0, 1fr\)\)/u, 'The complete copy editor should use two readable columns on wide screens.');
  assert.match(adminStylesheet, /\.ulp-admin__custom-integration\s*\{[\s\S]*grid-template-columns: repeat\(2, minmax\(0, 1fr\)\)/u, 'Custom integration fields should use the available settings column on wide screens.');
  assert.match(adminStylesheet, /\.ulp-admin__custom-integrations > legend\s*\{[\s\S]*width: 100%;[\s\S]*padding: 28px 0 0;[\s\S]*border-top: 2px solid var\(--ulp-admin-border\);/u, 'The custom-integration divider must sit above its full-width legend instead of beside the title.');
  assert.match(adminStylesheet, /\.ulp-admin__category-card\s*\{[\s\S]*grid-template-columns: minmax\(0, 1fr\)/u, 'Category cards should contain only their visitor-facing copy in one readable column.');
  assert.doesNotMatch(pluginSource, /Technical purpose/u, 'Consent categories must not expose an internal technical-purpose field.');
  assert.doesNotMatch(adminScript, /data-ulc-category-purpose/u, 'The category editor must not recreate the removed technical-purpose field.');
  assert.match(pluginSource, /render_integration_category_field\(\$options, 'ga4_category'/u, 'Predefined integrations must expose an explicit consent-category assignment.');
  assert.match(pluginSource, /foreach\(\['necessary'\] as \$id\)/u, 'Fresh installs must expose only the required consent category.');
  assert.match(pluginSource, /esc_html_e\('Not selected', 'universal-legal-pages'\)/u, 'Every integration category select needs an explicit unassigned choice.');
  assert.match(adminScript, /if\(kind === 'integration'\)\{[\s\S]*?emptyOption\.value = '';[\s\S]*?emptyOption\.textContent = notSelectedLabel;/u, 'Dynamic category refreshes must preserve the unassigned integration choice.');
  assert.match(adminScript, /var allowed = definitions\.slice\(\);/u, 'Dynamic consent-category selectors must retain the required category.');
  assert.doesNotMatch(pluginSource, /data-ulc-category-select="integration" required/u, 'Custom integrations must be saveable while their category remains unassigned.');
  assert.doesNotMatch(pluginSource, /\$field_name \. '\[purpose\]'/u, 'Custom integrations must derive behavior from their selected category instead of submitting another field.');
  assert.doesNotMatch(pluginSource, /Privacy behavior/u, 'The redundant custom-integration privacy-behavior control must not remain visible.');
  assert.match(adminStylesheet, /\.ulp-admin__custom-integrations \[data-ulc-add-custom\]\s*\{[\s\S]*min-height: 44px/u, 'The custom integration add control needs a usable pointer target.');
  assert.match(
    adminStylesheet,
    /\.ulp-admin__terms-setting:not\(:has\(#ulp-terms-required:checked\)\) \.ulp-admin__dependent-field/u,
    'The terms-document select should appear only when explicit acknowledgement is enabled.'
  );
  assert.doesNotMatch(
    adminStylesheet,
    /\.ulp-admin__language-panel--links \.ulp-admin__dependent-field/u,
    'The confirmation-page fields must not remain separated inside the long legal-page panels.'
  );
  assert.match(
    adminStylesheet,
    /\.ulp-admin__terms-language-editor\.is-enhanced \.ulp-admin__language-tabs\s*\{[\s\S]*flex-wrap: nowrap;[\s\S]*overflow-x: auto;/u,
    'Large confirmation-language sets should use one horizontally scrollable tab rail.'
  );
  assert.match(
    adminStylesheet,
    /\.ulp-admin__language-panel--terms\s*\{[\s\S]*padding: 16px;[\s\S]*border-width: 1px;/u,
    'Only the active confirmation-page language should occupy one compact panel.'
  );
  assert.match(
    adminStylesheet,
    /@media \(max-width: 560px\)[\s\S]*\.ulp-admin__page-order\s*\{[\s\S]*grid-template-columns: minmax\(0, 1fr\)/u,
    'The legal-page checklist must reflow to one column on narrow screens.'
  );
  assert.match(
    adminStylesheet,
    /@media \(max-width: 560px\)[\s\S]*\.ulp-admin__copy-grid\s*\{[\s\S]*grid-template-columns: minmax\(0, 1fr\)/u,
    'The copy editor must reflow to one column on narrow screens.'
  );
  assert.match(
    adminStylesheet,
    /@media \(max-width: 560px\)[\s\S]*\.ulp-admin__copy-sections\s*\{[\s\S]*grid-template-columns: minmax\(0, 1fr\)/u,
    'Grouped editing units must stack cleanly on narrow screens.'
  );
  assert.match(
    adminStylesheet,
    /@media \(max-width: 560px\)[\s\S]*\.ulp-admin__custom-integration\s*\{[\s\S]*grid-template-columns: minmax\(0, 1fr\)/u,
    'Custom integrations must reflow to one column on narrow screens.'
  );
  assert.match(
    adminStylesheet,
    /@media \(max-width: 560px\)[\s\S]*\.ulp-admin__terms-language-editor\.is-enhanced \.ulp-admin__language-tabs\s*\{[\s\S]*display: flex;[\s\S]*grid-template-columns: none;/u,
    'Confirmation-page tabs must stay compact instead of becoming one long language column on narrow screens.'
  );
});

test('ReactWP consent language controls are progressive, accessible, and route-aware', () => {
  assert.doesNotMatch(pluginSource, /ulp-(?:link-)?language-editor-description|The languages come from ReactWP > Site settings\./u, 'Multilingual editors must not repeat the removed ReactWP language notices.');
  assert.match(pluginSource, /Confirmation page language/u, 'The confirmation-page tablist needs its own accessible label.');
  assert.match(adminScript, /document\.querySelectorAll\('\[data-ulp-language-editor\]'\)/u, 'The admin behavior must remain scoped to language editors.');
  assert.match(adminScript, /editor\.classList\.add\('is-enhanced'\)/u, 'Language panels may collapse only after JavaScript enhancement succeeds.');
  assert.match(adminScript, /tab\.setAttribute\('aria-selected'/u, 'Tab selection state must be exposed to assistive technology.');
  assert.match(adminScript, /tab\.setAttribute\('tabindex'/u, 'Language tabs must use roving keyboard focus.');
  assert.match(adminScript, /panels\[tabIndex\]\.hidden = !selected;/u, 'Inactive language panels must be hidden only after enhancement.');
  assert.match(adminScript, /event\.key === 'ArrowLeft'/u);
  assert.match(adminScript, /event\.key === 'ArrowRight'/u);
  assert.match(adminScript, /event\.key === 'Home'/u);
  assert.match(adminScript, /event\.key === 'End'/u);
  assert.match(adminScript, /event\.preventDefault\(\);/u);
  assert.equal(adminScript.includes('.innerHTML'), false, 'The language editor must not introduce an executable HTML sink.');
  assert.equal(adminScript.includes('console.'), false, 'The language editor must not log localized settings.');
  assert.match(adminScript, /document\.querySelectorAll\('\[data-ulp-page-order\]'\)/u, 'The admin script must initialize the ordered page selector.');
  assert.match(adminScript, /selectedList\.addEventListener\('dragstart'/u, 'Selected legal pages must support drag-and-drop ordering.');
  assert.match(adminScript, /data-ulp-page-up/u, 'The ordered list must provide a keyboard alternative to dragging.');
  assert.match(adminScript, /data-ulp-page-down/u, 'The ordered list must provide both keyboard directions.');
  assert.match(adminScript, /status\.textContent/u, 'Order changes must be announced to assistive technology.');
  assert.match(adminScript, /document\.querySelectorAll\('\[data-ulc-custom-integrations\]'\)/u, 'The admin script must initialize each custom integration editor independently.');
  assert.match(adminScript, /template\.content\.cloneNode\(true\)/u, 'New custom integration rows must clone trusted server markup.');
  assert.match(adminScript, /value\.replaceAll\('__INDEX__', String\(nextIndex\)\)/u, 'Every dynamic field must receive a unique Settings API index.');
  assert.match(adminScript, /cards\(\)\.length >= maximum/u, 'The browser editor must enforce the same bounded integration count as the backend.');
  assert.match(adminScript, /announce\('data-added-message'/u, 'Dynamic integration changes need an assistive status announcement.');
  assert.match(adminScript, /announce\('data-removed-message'/u, 'Custom integration removals need an assistive status announcement.');
  assert.match(adminScript, /function reindexCards\(\)/u, 'The complete custom-integration list must be reindexed after an item is removed.');
  assert.match(adminScript, /name\.replace\(\/\\\[custom_integrations\\\]\\\[\[0-9\]\+\\\]\//u, 'Submitted custom integration names must remain contiguous for exact backend validation.');
  assert.match(adminScript, /form\.addEventListener\('submit', reindexCards\)/u, 'The custom list must be normalized immediately before Settings API submission.');

  assert.match(adminStylesheet, /\.ulp-admin__language-editor\.is-enhanced \.ulp-admin__language-tabs/u, 'Tabs must be visible only after progressive enhancement.');
  assert.match(adminStylesheet, /\.ulp-admin__language-tab\s*\{[\s\S]*min-height: 44px/u, 'Language tabs need usable pointer targets.');
  assert.doesNotMatch(adminStylesheet, /\.ulp-admin__language-tab code|\.ulp-admin__language-panel h3 code/u, 'Removed language-code badges must not retain dedicated styles.');
  assert.match(adminStylesheet, /\.ulp-admin__language-error\s*\{/u, 'Localized validation guidance needs a visible scoped error treatment.');
  assert.match(adminStylesheet, /@media \(forced-colors: active\)[\s\S]*\.ulp-admin__language-tab/u, 'Language tabs must retain visible state in forced colors.');

  assert.match(routeTransition, /window\.UniversalLegalConsent\?\.setLanguage\?\.\(language\);/u, 'ReactWP route transitions must synchronize the consent banner language without a reload.');
  assert.match(routeTransition, /\[currentRoute\?\.lang\]/u, 'Consent copy must update only when the route language changes.');
});

test('ReactWP admin language tabs expose and operate one panel at a time after enhancement', () => {
  const createTab = (selected) => ({
    attributes: {
      'aria-selected': selected ? 'true' : 'false',
      tabindex: selected ? '0' : '-1'
    },
    listeners: {},
    focused: false,
    addEventListener(type, listener){
      this.listeners[type] = listener;
    },
    getAttribute(name){
      return this.attributes[name];
    },
    setAttribute(name, value){
      this.attributes[name] = value;
    },
    focus(){
      this.focused = true;
    }
  });
  const tabs = [createTab(true), createTab(false)];
  const panels = [{hidden: false}, {hidden: false}];
  const classes = new Set();
  const editor = {
    classList: {
      add(value){
        classes.add(value);
      }
    },
    querySelectorAll(selector){
      return selector === '[data-ulp-language-tab]' ? tabs : panels;
    }
  };

  vm.runInNewContext(adminScript, {
    document: {
      querySelectorAll(selector){
        return selector === '[data-ulp-language-editor]' ? [editor] : [];
      }
    },
    Array
  });

  assert.equal(classes.has('is-enhanced'), true, 'The editor was not marked as enhanced.');
  assert.deepEqual(panels.map((panel) => panel.hidden), [false, true], 'The initial active language panel was not preserved.');

  let prevented = false;
  tabs[0].listeners.keydown({
    key: 'ArrowRight',
    preventDefault(){
      prevented = true;
    }
  });

  assert.equal(prevented, true, 'Handled tab keys must prevent page-level scrolling.');
  assert.equal(tabs[0].attributes['aria-selected'], 'false');
  assert.equal(tabs[1].attributes['aria-selected'], 'true');
  assert.equal(tabs[1].attributes.tabindex, '0');
  assert.equal(tabs[1].focused, true, 'Keyboard navigation must move focus to the activated language.');
  assert.deepEqual(panels.map((panel) => panel.hidden), [true, false], 'Keyboard navigation did not swap the visible language panel.');

  tabs[0].listeners.click();
  assert.equal(tabs[0].attributes['aria-selected'], 'true', 'Click activation did not update the selected language tab.');
  assert.deepEqual(panels.map((panel) => panel.hidden), [false, true], 'Click activation did not update the visible language panel.');
});

test('consent settings sections use one persistent native selector with global validation', () => {
  const select = {
    options: [{}, {}, {}],
    selectedIndex: 0,
    listeners: {},
    addEventListener(type, listener){
      this.listeners[type] = listener;
    }
  };
  const fields = [{value: 'draft value'}, {value: ''}, {value: ''}];
  const panels = fields.map((field) => ({
    hidden: false,
    initialClassRemoved: false,
    classList: {
      remove(value){
        if(value === 'ulp-admin-section--initially-hidden') this.owner.initialClassRemoved = true;
      },
      owner: null
    },
    contains(target){ return target === field; }
  }));
  panels.forEach((panel) => { panel.classList.owner = panel; });
  const navigation = {};
  const formListeners = {};
  const form = {
    addEventListener(type, listener, options){
      formListeners[type] = {listener, options};
    }
  };
  const classes = new Set();
  const container = {
    classList: {
      add(value){ classes.add(value); }
    },
    closest(selector){ return selector === 'form' ? form : null; },
    querySelector(selector){
      if(selector === '[data-ulp-section-navigation]') return navigation;
      if(selector === '[data-ulp-section-select]') return select;
      return null;
    },
    querySelectorAll(selector){
      if(selector === '[data-ulp-section-panel]') return panels;
      return [];
    }
  };

  vm.runInNewContext(adminScript, {
    window: {
      setTimeout(callback){ callback(); }
    },
    document: {
      querySelectorAll(selector){
        return selector === '[data-ulp-section-switcher]' ? [container] : [];
      }
    },
    Array
  });

  assert.equal(classes.has('is-enhanced'), true);
  assert.deepEqual(panels.map((panel) => panel.initialClassRemoved), [true, true, true], 'Interactive hidden state must replace the first-paint CSS class.');
  assert.deepEqual(panels.map((panel) => panel.hidden), [false, true, true]);

  select.selectedIndex = 1;
  select.listeners.change();
  assert.deepEqual(panels.map((panel) => panel.hidden), [true, false, true]);
  assert.equal(fields[0].value, 'draft value', 'Switching sections must preserve unsaved form values.');

  assert.equal(formListeners.invalid.options, true, 'Invalid fields must be detected during the capture phase.');
  formListeners.invalid.listener({target: fields[2]});
  assert.equal(select.selectedIndex, 2, 'The section selector must follow the panel containing the invalid field.');
  assert.deepEqual(panels.map((panel) => panel.hidden), [true, true, false], 'The panel containing the first invalid field must become visible.');
});

test('consent settings save asynchronously and expose live success and error notices', async () => {
  const formAttributes = {
    'data-ulp-ajax-url': 'https://example.test/wp-admin/admin-ajax.php',
    'data-ulp-ajax-action': 'ulp_save_consent_settings',
    'data-ulp-saving-label': 'Saving settings…',
    'data-ulp-network-error': 'Unable to save settings.'
  };
  const formListeners = {};
  const submit = {tagName: 'BUTTON', textContent: 'Save settings', disabled: false};
  const customId = {value: ''};
  const customCard = {
    querySelector(selector){ return selector === '[data-ulc-custom-id]' ? customId : null; }
  };
  const form = {
    listeners: formListeners,
    attributes: formAttributes,
    addEventListener(type, listener){ this.listeners[type] = listener; },
    getAttribute(name){ return this.attributes[name] || ''; },
    setAttribute(name, value){ this.attributes[name] = value; },
    removeAttribute(name){ delete this.attributes[name]; },
    querySelector(selector){ return selector === '[type="submit"]' ? submit : null; },
    querySelectorAll(selector){ return selector === '[data-ulc-custom-card]' ? [customCard] : []; }
  };
  const noticeClasses = new Set();
  const noticeMessage = {textContent: ''};
  const dismiss = {
    listeners: {},
    addEventListener(type, listener){ this.listeners[type] = listener; }
  };
  const notice = {
    hidden: true,
    focused: false,
    attributes: {},
    classList: {
      add(...values){ values.forEach((value) => noticeClasses.add(value)); },
      remove(...values){ values.forEach((value) => noticeClasses.delete(value)); }
    },
    querySelector(selector){
      if(selector === '[data-ulp-save-message]') return noticeMessage;
      if(selector === '[data-ulp-save-dismiss]') return dismiss;
      return null;
    },
    setAttribute(name, value){ this.attributes[name] = value; },
    focus(){ this.focused = true; }
  };
  const serverNotice = {hidden: false};
  const requests = [];
  const responses = [
    {success: true, data: {message: 'Settings saved.', errors: [], customIntegrations: [{index: 0, id: 'custom-1234567890abcdef'}]}},
    {success: false, data: {message: 'Validation failed.', errors: [{code: 'invalid_policy_version', message: 'Invalid version.'}], customIntegrations: []}}
  ];

  class TestFormData {
    constructor(owner){ this.owner = owner; this.values = {}; }
    set(name, value){ this.values[name] = value; }
  }

  vm.runInNewContext(adminScript, {
    document: {
      querySelector(selector){
        if(selector === '[data-ulp-settings-form]') return form;
        if(selector === '[data-ulp-save-notice]') return notice;
        return null;
      },
      querySelectorAll(selector){
        return selector === '.ulp-admin > [id^="setting-error-"]' ? [serverNotice] : [];
      }
    },
    fetch(url, options){
      requests.push({url, options});
      const payload = responses.shift();
      return Promise.resolve({json: () => Promise.resolve(payload)});
    },
    FormData: TestFormData,
    Array
  });

  let prevented = false;
  formListeners.submit({
    defaultPrevented: false,
    preventDefault(){ prevented = true; }
  });

  assert.equal(prevented, true, 'The enhanced form must prevent its full-page fallback submission.');
  assert.equal(submit.disabled, true, 'The submit control must prevent duplicate saves while the request is pending.');
  assert.equal(form.attributes['aria-busy'], 'true');
  assert.equal(noticeMessage.textContent, 'Saving settings…');
  assert.equal(serverNotice.hidden, true, 'A stale server-rendered save notice must not compete with the live result.');

  await new Promise((resolve) => setImmediate(resolve));

  assert.equal(requests[0].url, 'https://example.test/wp-admin/admin-ajax.php');
  assert.equal(requests[0].options.credentials, 'same-origin');
  assert.equal(requests[0].options.body.values.action, 'ulp_save_consent_settings');
  assert.equal(notice.hidden, false);
  assert.equal(noticeMessage.textContent, 'Settings saved.');
  assert.equal(noticeClasses.has('ulp-admin__save-notice--success'), true);
  assert.equal(customId.value, 'custom-1234567890abcdef', 'The canonical custom integration ID must be retained for the next asynchronous save.');
  assert.equal(submit.disabled, false);
  assert.equal(submit.textContent, 'Save settings');
  assert.equal('aria-busy' in form.attributes, false);

  formListeners.submit({defaultPrevented: false, preventDefault(){}});
  await new Promise((resolve) => setImmediate(resolve));

  assert.equal(noticeMessage.textContent, 'Invalid version.');
  assert.equal(noticeClasses.has('ulp-admin__save-notice--error'), true);
  assert.equal(notice.attributes.role, 'alert');
  assert.equal(notice.focused, true, 'Validation errors should move focus to the custom notice.');

  dismiss.listeners.click();
  assert.equal(notice.hidden, true, 'The custom save notice must be dismissible.');
});

test('legal-page choices keep form order across buttons, selection changes, and dragging', () => {
  const list = (role) => ({
    role,
    children: [],
    listeners: {},
    querySelectorAll(){ return this.children.slice(); },
    addEventListener(type, listener){ this.listeners[type] = listener; },
    appendChild(item){
      if(item.parentNode){
        item.parentNode.children = item.parentNode.children.filter((child) => child !== item);
      }
      this.children.push(item);
      item.parentNode = this;
    },
    insertBefore(item, reference){
      if(item.parentNode){
        item.parentNode.children = item.parentNode.children.filter((child) => child !== item);
      }
      const index = reference ? this.children.indexOf(reference) : -1;
      this.children.splice(index >= 0 ? index : this.children.length, 0, item);
      item.parentNode = this;
    }
  });
  const selected = list('selected');
  const available = list('available');
  const createItem = (id, label, checked) => {
    const position = {textContent: ''};
    const toggle = {
      checked,
      closest(selector){
        return selector === '[data-ulp-page-toggle]' ? this : selector === '[data-ulp-page-item]' ? item : null;
      }
    };
    const up = {
      disabled: false,
      focused: false,
      closest(selector){
        return selector === '[data-ulp-page-up]' ? this : selector === '[data-ulp-page-item]' ? item : null;
      },
      focus(){ this.focused = true; }
    };
    const down = {
      disabled: false,
      focused: false,
      closest(selector){
        return selector === '[data-ulp-page-down]' ? this : selector === '[data-ulp-page-item]' ? item : null;
      },
      focus(){ this.focused = true; }
    };
    const classes = new Set();
    const item = {
      id,
      parentNode: null,
      attributes: {'data-page-id': id, 'data-page-label': label},
      classList: {add(value){ classes.add(value); }, remove(value){ classes.delete(value); }},
      querySelector(selector){
        if(selector === '[data-ulp-page-position]') return position;
        if(selector === '[data-ulp-page-up]') return up;
        if(selector === '[data-ulp-page-down]') return down;
        return null;
      },
      closest(selector){ return selector === '[data-ulp-page-item]' ? this : null; },
      getAttribute(name){ return this.attributes[name] || ''; },
      setAttribute(name, value){ this.attributes[name] = value; },
      removeAttribute(name){ delete this.attributes[name]; },
      getBoundingClientRect(){ return {top: 0, height: 20}; },
      get previousElementSibling(){
        const index = this.parentNode.children.indexOf(this);
        return index > 0 ? this.parentNode.children[index - 1] : null;
      },
      get nextElementSibling(){
        const index = this.parentNode.children.indexOf(this);
        return index >= 0 ? this.parentNode.children[index + 1] || null : null;
      },
      position,
      toggle,
      up,
      down,
      classes
    };
    return item;
  };
  const privacy = createItem('10', 'Confidentialité', true);
  const terms = createItem('11', 'Conditions', true);
  const cookies = createItem('12', 'Cookies', false);
  selected.appendChild(privacy);
  selected.appendChild(terms);
  available.appendChild(cookies);
  const selectedEmpty = {hidden: false};
  const availableEmpty = {hidden: false};
  const status = {textContent: ''};
  const listeners = {};
  const pageOrder = {
    attributes: {
      'data-added-message': 'ajoutée',
      'data-removed-message': 'retirée',
      'data-moved-message': 'déplacée'
    },
    classList: {add(){}},
    querySelector(selector){
      return {
        '[data-ulp-selected-pages]': selected,
        '[data-ulp-available-pages]': available,
        '[data-ulp-selected-empty]': selectedEmpty,
        '[data-ulp-available-empty]': availableEmpty,
        '[data-ulp-page-status]': status
      }[selector] || null;
    },
    addEventListener(type, listener){ listeners[type] = listener; },
    getAttribute(name){ return this.attributes[name] || ''; },
    contains(){ return true; }
  };

  vm.runInNewContext(adminScript, {
    document: {
      querySelectorAll(selector){ return selector === '[data-ulp-page-order]' ? [pageOrder] : []; }
    },
    Array,
    String
  });

  listeners.click({target: privacy.down});
  assert.deepEqual(selected.children.map((item) => item.id), ['11', '10'], 'The down button did not change submitted DOM order.');
  assert.equal(privacy.down.focused, true, 'Keyboard ordering must preserve focus on the activated button.');

  terms.toggle.checked = false;
  listeners.change({target: terms.toggle});
  assert.deepEqual(selected.children.map((item) => item.id), ['10'], 'Unchecking a page did not remove it from the ordered selection.');
  assert.match(status.textContent, /Conditions — retirée/u);

  cookies.toggle.checked = true;
  listeners.change({target: cookies.toggle});
  const dataTransfer = {effectAllowed: '', dropEffect: '', setData(){}};
  selected.listeners.dragstart({target: cookies, dataTransfer});
  selected.listeners.dragover({target: cookies, dataTransfer, clientY: 0, preventDefault(){}});
  assert.deepEqual(selected.children.map((item) => item.id), ['10', '12'], 'Hovering the dragged page itself must not send it to the end of the list.');
  selected.listeners.dragover({target: privacy, dataTransfer, clientY: 0, preventDefault(){}});
  selected.listeners.dragend();
  assert.deepEqual(selected.children.map((item) => item.id), ['12', '10'], 'Dragging did not change submitted DOM order.');
  assert.deepEqual(selected.children.map((item) => item.position.textContent), ['1', '2'], 'Visible positions were not refreshed after dragging.');
});

test('removing a non-final custom integration reindexes the complete submitted list', () => {
  const listeners = {};
  const createField = (attributes) => ({
    attributes: {...attributes},
    getAttribute(name){ return this.attributes[name] || ''; },
    setAttribute(name, value){ this.attributes[name] = String(value); }
  });
  const createCard = (index, labelText) => {
    const label = createField({
      name: `universal_legal_pages_options[custom_integrations][${index}][label]`,
      id: `ulp-custom-integration-${index}-label`
    });
    label.value = labelText;
    label.focus = () => {};
    const category = createField({
      name: `universal_legal_pages_options[custom_integrations][${index}][category]`,
      id: `ulp-custom-integration-${index}-category`
    });
    const forLabel = createField({for: `ulp-custom-integration-${index}-label`});
    const title = {textContent: labelText};
    const attributes = {};
    const fields = [label, category];
    const idElements = [label, category, forLabel];
    const card = {
      parentNode: null,
      getAttribute(name){ return attributes[name] || ''; },
      setAttribute(name, value){ attributes[name] = String(value); },
      querySelector(selector){
        if(selector === '[data-ulc-custom-title]') return title;
        if(selector === '[data-ulc-custom-label]') return label;
        return null;
      },
      querySelectorAll(selector){
        if(selector === '[name]') return fields;
        if(selector === '[id], label[for]') return idElements;
        return [];
      }
    };
    label.closest = (selector) => selector === '[data-ulc-custom-label]' ? label : selector === '[data-ulc-custom-card]' ? card : null;
    const remove = {
      closest(selector){
        if(selector === '[data-ulc-remove-custom]') return this;
        if(selector === '[data-ulc-custom-card]') return card;
        return null;
      }
    };
    return {card, fields, remove};
  };
  const first = createCard(0, 'First');
  const second = createCard(1, 'Second');
  const cards = [first.card, second.card];
  const list = {
    querySelectorAll(selector){ return selector === '[data-ulc-custom-card]' ? cards.slice() : []; },
    addEventListener(type, listener){ listeners[type] = listener; },
    contains(){ return true; },
    removeChild(card){ cards.splice(cards.indexOf(card), 1); card.parentNode = null; }
  };
  first.card.parentNode = list;
  second.card.parentNode = list;
  const addButton = {disabled: false, setAttribute(){}, addEventListener(){}, focus(){}};
  const form = {addEventListener(type, listener){ listeners[`form-${type}`] = listener; }};
  const editor = {
    classList: {add(){}},
    getAttribute(name){ return name === 'data-max' ? '12' : ''; },
    querySelector(selector){
      return {
        '[data-ulc-custom-list]': list,
        '[data-ulc-custom-template]': {content: {cloneNode(){ return null; }}},
        '[data-ulc-add-custom]': addButton,
        '[data-ulc-custom-status]': {textContent: ''}
      }[selector] || null;
    },
    closest(selector){ return selector === 'form' ? form : null; }
  };

  vm.runInNewContext(adminScript, {
    document: {
      querySelectorAll(selector){ return selector === '[data-ulc-custom-integrations]' ? [editor] : []; }
    },
    Array,
    String,
    Number
  });

  listeners.click({target: first.remove});
  assert.equal(cards.length, 1);
  assert.ok(
    second.fields.every((field) => field.getAttribute('name').includes('[custom_integrations][0]')),
    'Removing the first card must not leave a sparse Settings API index.'
  );
  listeners['form-submit']();
  assert.ok(second.fields.every((field) => field.getAttribute('name').includes('[custom_integrations][0]')));
});

test('service consent cookie uses the exact registry, invalidates old hashes, and stays below browser limits', () => {
  const services = Array.from({length: 32}, (_, index) => serviceFixture({
    id: `service-${index}`,
    category: index % 2 === 0 ? 'analytics' : 'external',
    domain: `service-${index}.example.test`,
    kind: 'integration'
  }));
  const runtime = runConsentManager({config: createConsentConfig({services})});
  const manager = runtime.document.querySelector('[data-universal-legal-consent-root]');
  const acceptAll = manager.shadowRoot.querySelector('[data-action="accept-all"]');

  manager.shadowRoot.dispatch('click', {target: acceptAll});
  const stored = readStoredConsent(runtime.document);

  assert.ok(stored, 'Accept all did not persist a consent record.');
  assert.deepEqual(Object.keys(stored.services).sort(), services.map(({id}) => id).sort(), 'The cookie must contain exactly the activatable service ids.');
  assert.equal(stored.serviceRegistryVersion, '0123456789abcdef01234567');
  assert.ok(runtime.document.cookie.length < 4096, `The maximum 32-service cookie is too large (${runtime.document.cookie.length} bytes).`);

  const forged = {
    ...stored,
    serviceRegistryVersion: 'fedcba9876543210fedcba98'
  };
  const staleRuntime = runConsentManager({
    config: createConsentConfig({services}),
    cookie: `__Host-ulp_consent_1=${encodeURIComponent(JSON.stringify(forged))}`
  });
  assert.equal(staleRuntime.window.UniversalLegalConsent.getConsent(), null, 'A consent cookie from an older service registry must be invalidated.');

  const extraService = {...stored, services: {...stored.services, injected: true}};
  const injectedRuntime = runConsentManager({
    config: createConsentConfig({services}),
    cookie: `__Host-ulp_consent_1=${encodeURIComponent(JSON.stringify(extraService))}`
  });
  assert.equal(injectedRuntime.window.UniversalLegalConsent.getConsent(), null, 'Unknown service keys must invalidate the complete cookie.');

  const inconsistent = {...stored, analytics: false};
  const inconsistentRuntime = runConsentManager({
    config: createConsentConfig({services}),
    cookie: `__Host-ulp_consent_1=${encodeURIComponent(JSON.stringify(inconsistent))}`
  });
  assert.equal(inconsistentRuntime.window.UniversalLegalConsent.getConsent(), null, 'Category shortcuts must agree with their complete service selection.');
});

test('GPC forces every marketing service off while leaving non-marketing services selectable', () => {
  const services = [
    serviceFixture({id: 'google-ads-remarketing', category: 'external', purpose: 'marketing', domain: 'www.googletagmanager.com', kind: 'integration'}),
    serviceFixture({id: 'youtube', category: 'external', domain: 'www.youtube.com'})
  ];
  const runtime = runConsentManager({config: createConsentConfig({services}), gpc: true});
  const manager = runtime.document.querySelector('[data-universal-legal-consent-root]');
  const acceptAll = manager.shadowRoot.querySelector('[data-action="accept-all"]');

  assert.equal(runtime.window.UniversalLegalConsent.registerIntegration({
    id: 'mismatched-marketing-adapter',
    serviceId: 'youtube',
    categories: ['marketing'],
    load(){ throw new Error('A mismatched adapter must never load.'); }
  }), false, 'A marketing adapter must not bind to an external-content service and bypass GPC.');
  assert.equal(runtime.window.UniversalLegalConsent.registerIntegration({
    id: 'misspelled-marketing-adapter',
    categories: ['external', 'markting'],
    purpose: 'marketing',
    load(){ throw new Error('An adapter with an unknown category must never load.'); }
  }), false, 'Unknown categories must reject the complete adapter instead of weakening its gate.');
  assert.equal(runtime.window.UniversalLegalConsent.registerIntegration({
    id: 'invalid-mode-adapter',
    categories: ['external'],
    purpose: 'external',
    mode: 'some',
    load(){ throw new Error('An adapter with an unknown mode must never load.'); }
  }), false, 'Unknown adapter modes must fail closed.');

  manager.shadowRoot.dispatch('click', {target: acceptAll});
  const stored = readStoredConsent(runtime.document);

  assert.equal(stored.marketing, false);
  assert.equal(stored.services['google-ads-remarketing'], false);
  assert.equal(stored.services.youtube, true);
  assert.equal(runtime.window.UniversalLegalConsent.hasServiceConsent('google-ads-remarketing'), false);
  assert.equal(runtime.window.UniversalLegalConsent.hasServiceConsent('youtube'), true);
  assert.equal(runtime.window.UniversalLegalConsent.hasConsent('necessary'), true);
  assert.equal(runtime.window.UniversalLegalConsent.hasConsent(['external', 'markting'], 'any'), false, 'A typo must not weaken a public category gate.');
  assert.equal(runtime.window.UniversalLegalConsent.hasConsent(['external', 'external'], 'any'), false, 'Duplicate categories must fail closed.');
  assert.equal(runtime.window.UniversalLegalConsent.hasConsent(['external'], 'some'), false, 'Unknown public gate modes must fail closed.');
});

test('GPC cleans vendor cookies from a previously granted marketing choice', () => {
  const services = [
    serviceFixture({id: 'google-ads-remarketing', category: 'marketing', domain: 'www.googletagmanager.com', kind: 'integration'}),
    serviceFixture({id: 'meta-pixel', category: 'marketing', domain: 'connect.facebook.net', kind: 'pixel'}),
    serviceFixture({id: 'youtube', category: 'external', domain: 'www.youtube.com'})
  ];
  const config = createConsentConfig({services});
  const acceptedRuntime = runConsentManager({config});
  const acceptedManager = acceptedRuntime.document.querySelector('[data-universal-legal-consent-root]');
  acceptedManager.shadowRoot.dispatch('click', {
    target: acceptedManager.shadowRoot.querySelector('[data-action="accept-all"]')
  });
  const acceptedCookie = acceptedRuntime.document.cookieValues['__Host-ulp_consent_1'];
  const gpcRuntime = runConsentManager({
    config,
    gpc: true,
    cookie: `__Host-ulp_consent_1=${acceptedCookie}`,
    vendorCookies: {_fbp: 'meta-cookie', _gcl_au: 'ads-cookie'}
  });
  const stored = readStoredConsent(gpcRuntime.document);

  assert.equal(stored.marketing, false);
  assert.equal(stored.services['google-ads-remarketing'], false);
  assert.equal(stored.services['meta-pixel'], false);
  assert.equal(gpcRuntime.document.cookieValues._fbp, undefined, 'GPC must expire known Meta cookies from the prior grant.');
  assert.equal(gpcRuntime.document.cookieValues._gcl_au, undefined, 'GPC must expire known Google Ads cookies from the prior grant.');
});

test('individual revocation cleans only the known vendor that lost consent in a mixed category', () => {
  const services = [
    serviceFixture({id: 'google-analytics-4', category: 'analytics', domain: 'www.googletagmanager.com', kind: 'integration'}),
    serviceFixture({id: 'other-analytics', category: 'analytics', domain: 'analytics.example.test', kind: 'integration'}),
    serviceFixture({id: 'meta-pixel', category: 'marketing', domain: 'connect.facebook.net', kind: 'pixel'}),
    serviceFixture({id: 'other-marketing', category: 'marketing', domain: 'marketing.example.test', kind: 'integration'})
  ];
  const runtime = runConsentManager({
    config: createConsentConfig({
      services,
      integrations: {
        googleAnalytics: 'G-ABCD1234',
        googleAds: '',
        googleTagManager: '',
        metaPixel: '1234567890'
      }
    })
  });
  const manager = runtime.document.querySelector('[data-universal-legal-consent-root]');
  const shadow = manager.shadowRoot;

  runtime.window.UniversalLegalConsent.openPreferences();
  shadow.querySelector('#ulc-service-google-analytics-4').checked = true;
  shadow.dispatch('change', {target: shadow.querySelector('#ulc-service-google-analytics-4')});
  shadow.querySelector('#ulc-service-meta-pixel').checked = true;
  shadow.dispatch('change', {target: shadow.querySelector('#ulc-service-meta-pixel')});
  shadow.dispatch('submit', {target: shadow.querySelector('form'), preventDefault(){}});
  runtime.document.cookie = '_ga=analytics-cookie';
  runtime.document.cookie = '_fbp=meta-cookie';

  runtime.window.UniversalLegalConsent.openPreferences();
  shadow.querySelector('#ulc-service-google-analytics-4').checked = false;
  shadow.dispatch('change', {target: shadow.querySelector('#ulc-service-google-analytics-4')});
  shadow.dispatch('submit', {target: shadow.querySelector('form'), preventDefault(){}});
  assert.equal(runtime.document.cookieValues._ga, undefined, 'Revoking GA4 in a mixed category must expire known analytics cookies.');
  assert.equal(runtime.document.cookieValues._fbp, 'meta-cookie', 'Revoking GA4 must not clear an accepted Meta service cookie.');

  runtime.window.UniversalLegalConsent.openPreferences();
  shadow.querySelector('#ulc-service-meta-pixel').checked = false;
  shadow.dispatch('change', {target: shadow.querySelector('#ulc-service-meta-pixel')});
  shadow.dispatch('submit', {target: shadow.querySelector('form'), preventDefault(){}});
  const lastMetaConsent = runtime.window.fbq.queue.filter((entry) => entry[0] === 'consent').slice(-1)[0];
  assert.equal(runtime.document.cookieValues._fbp, undefined, 'Revoking Meta in a mixed category must expire known Meta cookies.');
  assert.equal(lastMetaConsent[1], 'revoke', 'Revoking Meta individually must send its available vendor signal.');
});

test('reporting-only services never become visitor consent controls', () => {
  const services = [
    serviceFixture({
      id: 'script-report',
      category: 'analytics',
      domain: 'scripts.example.test',
      kind: 'script',
      managed: false
    })
  ];
  const runtime = runConsentManager({
    config: createConsentConfig({services}),
    placeholders: (document) => [
      createPlaceholder(document, 'script-report', 'https://scripts.example.test/embed')
    ]
  });
  const manager = runtime.document.querySelector('[data-universal-legal-consent-root]');
  const placeholder = runtime.document.querySelector('[data-ulc-service][data-ulc-src]');

  runtime.window.UniversalLegalConsent.openPreferences();
  assert.equal(manager.shadowRoot.querySelector('#ulc-service-script-report'), null, 'An unmanaged script inventory entry must not appear as an activatable service.');
  assert.equal(placeholder.shadowRoot.querySelector('button'), null, 'An unmanaged service placeholder must not expose an allow action.');
  assert.equal(runtime.window.UniversalLegalConsent.hasServiceConsent('script-report'), false);
  assert.equal(runtime.window.UniversalLegalConsent.registerIntegration({
    id: 'script-report-adapter',
    serviceId: 'script-report',
    load(){}
  }), false, 'An unmanaged inventory relation must not become controllable through the browser API alone.');
});

test('detected services stay out of visitor preferences until an administrator classifies them', () => {
  const unapprovedRuntime = runConsentManager({
    config: createConsentConfig({
      services: [
        serviceFixture({
          id: 'detected-video',
          category: 'unclassified',
          domain: 'video.example.test',
          managed: true
        })
      ]
    })
  });
  const unapprovedManager = unapprovedRuntime.document.querySelector('[data-universal-legal-consent-root]');
  const unapprovedShadow = unapprovedManager.shadowRoot;

  unapprovedRuntime.window.UniversalLegalConsent.openPreferences();
  assert.equal(unapprovedShadow.querySelector('.ulc-services__intro'), null, 'An unapproved detected service exposed the services heading and description.');
  assert.equal(unapprovedShadow.querySelector('.ulc-category--unclassified'), null, 'An unclassified service group was offered to the visitor.');
  assert.equal(unapprovedShadow.querySelector('#ulc-service-detected-video'), null, 'An unapproved detected service received a visitor control.');
  assert.equal(unapprovedShadow.textContent.includes('detected-video'), false, 'An unapproved detected service leaked into visitor-facing copy.');

  const approvedRuntime = runConsentManager({
    config: createConsentConfig({
      services: [serviceFixture({id: 'youtube', category: 'external', domain: 'www.youtube.com'})]
    })
  });
  const approvedManager = approvedRuntime.document.querySelector('[data-universal-legal-consent-root]');

  approvedRuntime.window.UniversalLegalConsent.openPreferences();
  assert.ok(approvedManager.shadowRoot.querySelector('.ulc-services__intro'), 'An approved individual service did not expose the service-choice introduction.');
  assert.ok(approvedManager.shadowRoot.querySelector('#ulc-service-youtube'), 'An approved individual service did not receive a visitor control.');
});

test('preferences omit reject all when the required category is the only choice', () => {
  const necessaryOnlyRuntime = runConsentManager({
    config: createConsentConfig({
      categories: [
        {id: 'necessary', label: 'Necessary', description: 'Necessary description'}
      ],
      integrationCategories: {
        googleAnalytics: '',
        googleTagManager: '',
        googleAds: '',
        metaPixel: ''
      }
    })
  });
  const necessaryOnlyManager = necessaryOnlyRuntime.document.querySelector('[data-universal-legal-consent-root]');

  necessaryOnlyRuntime.window.UniversalLegalConsent.openPreferences();
  const necessaryOnlyDialog = necessaryOnlyManager.shadowRoot.querySelector('#ulc-preferences-dialog');
  assert.equal(necessaryOnlyDialog.querySelector('[data-action="reject-all"]'), null, 'The dialog offered Reject all when no optional category existed.');
  assert.ok(necessaryOnlyDialog.querySelector('.ulc-button--primary'), 'The dialog lost its global save action when Reject all was omitted.');

  const optionalRuntime = runConsentManager({config: createConsentConfig()});
  const optionalManager = optionalRuntime.document.querySelector('[data-universal-legal-consent-root]');

  optionalRuntime.window.UniversalLegalConsent.openPreferences();
  assert.ok(optionalManager.shadowRoot.querySelector('#ulc-preferences-dialog').querySelector('[data-action="reject-all"]'), 'The dialog omitted Reject all while optional categories were available.');
});

test('individual services gate adapters and embeds without granting their whole category', () => {
  const services = [
    serviceFixture({id: 'youtube', category: 'external', domain: 'www.youtube.com'}),
    serviceFixture({id: 'vimeo', category: 'external', domain: 'player.vimeo.com'}),
    serviceFixture({id: 'google-analytics-4', category: 'analytics', domain: 'www.googletagmanager.com', kind: 'integration'}),
    serviceFixture({id: 'external-unknown', category: 'unclassified', domain: 'unknown.test', kind: 'unknown', managed: false})
  ];
  let loaded = 0;
  const runtime = runConsentManager({
    config: createConsentConfig({services}),
    placeholders: (document) => [
      createPlaceholder(document, 'youtube', 'https://www.youtube.com/embed/abc12345?rel=0&autoplay=1&cc_lang_pref=fr'),
      createPlaceholder(document, 'youtube', 'https://evil.test/embed/abc12345'),
      createPlaceholder(document, 'external-unknown', 'https://unknown.test/embed/item'),
      createPlaceholder(document, 'youtube', 'https://www.youtube.com/watch?v=abc12345'),
      createPlaceholder(document, 'youtube', 'https://user@www.youtube.com/embed/abc12345')
    ]
  });
  const [youtube, wrongHost, unknown, wrongPath, credentialed] = runtime.document.querySelectorAll('[data-ulc-service][data-ulc-src]');

  assert.equal(runtime.window.UniversalLegalConsent.registerIntegration({
    id: 'Uppercase-Adapter',
    serviceId: 'youtube',
    load(){}
  }), false, 'Integration ids must use the strict lowercase grammar.');
  assert.equal(runtime.window.UniversalLegalConsent.registerIntegration({
    id: 'unknown-adapter',
    serviceId: 'missing-service',
    load(){}
  }), false, 'Unknown service ids must fail closed.');
  assert.equal(runtime.window.UniversalLegalConsent.registerIntegration({
    id: 'unclassified-adapter',
    serviceId: 'external-unknown',
    load(){}
  }), false, 'Unclassified services must never become activatable through an adapter.');
  assert.equal(runtime.window.UniversalLegalConsent.registerIntegration({
    id: 'mismatched-service-adapter',
    serviceId: 'youtube',
    categories: ['analytics'],
    load(){}
  }), false, 'A service adapter declaring a contradictory category must fail closed.');
  assert.equal(runtime.window.UniversalLegalConsent.registerIntegration({
    id: 'youtube-adapter',
    serviceId: 'youtube',
    categories: ['external'],
    load(){ loaded += 1; }
  }), true);
  assert.equal(youtube.shadowRoot.querySelector('iframe'), null, 'The iframe must not exist before individual consent.');
  assert.equal(wrongHost.shadowRoot.querySelector('button'), null, 'A source outside the exact service host allowlist must not be activatable.');
  assert.equal(unknown.shadowRoot.querySelector('button'), null, 'An unclassified service must remain blocked without an allow button.');
  assert.equal(wrongPath.shadowRoot.querySelector('button'), null, 'A YouTube URL outside the reviewed embed path must fail closed.');
  assert.equal(credentialed.shadowRoot.querySelector('button'), null, 'Credential-bearing iframe URLs must fail closed.');

  youtube.shadowRoot.querySelector('button').dispatch('click');
  const iframe = youtube.shadowRoot.querySelector('iframe');
  const stored = readStoredConsent(runtime.document);

  assert.equal(loaded, 1, 'The service adapter did not load after its individual grant.');
  assert.ok(iframe, 'The iframe was not created after its individual grant.');
  assert.equal(iframe.src, 'https://www.youtube-nocookie.com/embed/abc12345?rel=0&autoplay=1&cc_lang_pref=fr');
  assert.equal(iframe.referrerPolicy, 'no-referrer');
  assert.equal(iframe.allowFullscreen, true);
  assert.equal(stored.services.youtube, true);
  assert.equal(stored.services.vimeo, false);
  assert.equal(stored.services['google-analytics-4'], false, 'Allowing YouTube must not grant Google Analytics.');

  youtube.setAttribute('data-ulc-src', 'https://evil.test/embed/abc12345');
  runtime.observer.trigger([{type: 'attributes', target: youtube}]);
  assert.equal(youtube.shadowRoot.querySelector('iframe'), null, 'A data-src changed to an unapproved host after consent must remove the iframe.');
  assert.equal(youtube.shadowRoot.querySelector('button'), null, 'A tampered data-src must fail closed.');
});

test('configured integrations load their reviewed script and init code only after individual consent', async () => {
  const integrationId = 'custom-0123456789abcdef';
  const initCode = '</script><script>window.pwned=1</script><!--';
  const services = [
    serviceFixture({
      id: integrationId,
      category: 'external',
      domain: 'widgets.example.test',
      kind: 'integration'
    }),
    serviceFixture({id: 'google-analytics-4', category: 'analytics', domain: 'www.googletagmanager.com', kind: 'integration'})
  ];
  const runtime = runConsentManager({
    managerNonce: 'approved-csp-nonce',
    config: createConsentConfig({
      services,
      customIntegrations: [{
        id: integrationId,
        scriptUrl: 'https://widgets.example.test/client.js?site=public-id',
        initCode
      }]
    })
  });
  const manager = runtime.document.querySelector('[data-universal-legal-consent-root]');
  const shadow = manager.shadowRoot;

  assert.equal(runtime.document.getElementById(`ulp-${integrationId}`), null, 'The custom script must not exist before consent.');
  assert.equal(runtime.document.getElementById(`ulp-${integrationId}-init`), null, 'Init code must not exist before consent.');
  assert.equal(runtime.window.pwned, undefined, 'Configured code must not execute before consent.');

  runtime.window.UniversalLegalConsent.openPreferences();
  const customToggle = shadow.querySelector(`#ulc-service-${integrationId}`);
  assert.ok(customToggle, 'The custom integration must be offered as an individual service.');
  customToggle.checked = true;
  shadow.dispatch('change', {target: customToggle});
  shadow.dispatch('submit', {target: shadow.querySelector('form'), preventDefault(){}});

  const externalScript = runtime.document.getElementById(`ulp-${integrationId}`);
  const stored = readStoredConsent(runtime.document);
  assert.ok(externalScript, 'The consented custom integration did not create its script node.');
  assert.equal(externalScript.getAttribute('src'), 'https://widgets.example.test/client.js?site=public-id');
  assert.equal(externalScript.getAttribute('data-ulc-custom-integration'), integrationId);
  assert.equal(externalScript.nonce, 'approved-csp-nonce', 'The external script must inherit the manager script nonce.');
  assert.equal(runtime.document.getElementById(`ulp-${integrationId}-init`), null, 'Init code must wait for the external script load event.');
  assert.equal(stored.services[integrationId], true);
  assert.equal(stored.services['google-analytics-4'], false, 'Accepting a custom service must not grant another service.');

  externalScript.onload();
  await Promise.resolve();
  const initScript = runtime.document.getElementById(`ulp-${integrationId}-init`);
  assert.ok(initScript, 'The configured init code was not appended after the external script loaded.');
  assert.equal(initScript.textContent, initCode, 'Init code must reach the executable node byte-for-byte through textContent.');
  assert.equal(initScript.nonce, 'approved-csp-nonce', 'The init script must inherit the manager script nonce.');
  assert.equal(runtime.document.querySelectorAll(`#ulp-${integrationId}-init`).length, 1, 'Script-like text must not create additional DOM nodes.');
});

test('a failed configured script request never runs init code and remains retryable', async () => {
  const integrationId = 'custom-0011223344556677';
  const services = [serviceFixture({
    id: integrationId,
    category: 'preferences',
    domain: 'chat.example.test',
    kind: 'integration'
  })];
  const runtime = runConsentManager({
    config: createConsentConfig({
      services,
      customIntegrations: [{
        id: integrationId,
        scriptUrl: 'https://chat.example.test/widget.js',
        initCode: 'window.chatStarted = true;'
      }]
    })
  });
  const manager = runtime.document.querySelector('[data-universal-legal-consent-root]');
  const shadow = manager.shadowRoot;
  runtime.window.UniversalLegalConsent.openPreferences();
  const toggle = shadow.querySelector(`#ulc-service-${integrationId}`);
  toggle.checked = true;
  shadow.dispatch('change', {target: toggle});
  shadow.dispatch('submit', {target: shadow.querySelector('form'), preventDefault(){}});

  const failedScript = runtime.document.getElementById(`ulp-${integrationId}`);
  failedScript.onerror();
  await Promise.resolve();
  await Promise.resolve();
  assert.equal(runtime.document.getElementById(`ulp-${integrationId}`), null, 'A failed custom script node must be removed.');
  assert.equal(runtime.document.getElementById(`ulp-${integrationId}-init`), null, 'Init code must not run after a failed external request.');

  runtime.window.UniversalLegalConsent.openPreferences();
  shadow.dispatch('submit', {target: shadow.querySelector('form'), preventDefault(){}});
  assert.ok(runtime.document.getElementById(`ulp-${integrationId}`), 'A later consent application must retry the failed integration.');
});

test('configured integrations fail closed for malformed, insecure, duplicate, oversized, or unregistered definitions', () => {
  const integrationId = 'custom-0123456789abcdef';
  const service = serviceFixture({
    id: integrationId,
    category: 'external',
    domain: 'widgets.example.test',
    kind: 'integration'
  });
  const definition = {
    id: integrationId,
    scriptUrl: 'https://widgets.example.test/client.js',
    initCode: ''
  };
  const invalidDefinitions = [
    [{...definition, scriptUrl: 'http://widgets.example.test/client.js'}],
    [{...definition, scriptUrl: 'https://user:secret@widgets.example.test/client.js'}],
    [{...definition, scriptUrl: 'https://widgets.example.test/client.js#fragment'}],
    [{...definition, scriptUrl: 'https://widgets.example.test/client.js#'}],
    [{...definition, scriptUrl: 'https://widgets.example.test/client.js?#'}],
    [{...definition, scriptUrl: 'https:\\widgets.example.test\\client.js'}],
    [{...definition, scriptUrl: 'https://evil.test/client.js'}],
    [{...definition, initCode: 'é'.repeat(4097)}],
    [{...definition, unexpected: true}],
    [definition, {...definition, id: 'custom-fedcba9876543210'}]
  ];

  for(const customIntegrations of invalidDefinitions){
    const runtime = runConsentManager({config: createConsentConfig({services: [service], customIntegrations})});
    assert.equal(runtime.window.UniversalLegalConsent, undefined, 'An invalid custom integration configuration must disable the manager.');
    assert.equal(runtime.document.querySelector('[data-universal-legal-consent-root]'), null);
  }

  const unregistered = runConsentManager({
    config: createConsentConfig({services: [], customIntegrations: [definition]})
  });
  assert.equal(unregistered.window.UniversalLegalConsent, undefined, 'A custom integration without a matching managed service must fail closed.');
});

test('an unassigned predefined integration is valid configuration but remains inactive', () => {
  const runtime = runConsentManager({
    config: createConsentConfig({
      categories: [
        {id: 'necessary', label: 'Necessary', description: 'Necessary description'}
      ],
      integrations: {
        googleAnalytics: 'G-ABCD1234',
        googleTagManager: 'GTM-ABCD1234',
        googleAds: 'AW-1234567890',
        metaPixel: '1234567890'
      },
      integrationCategories: {
        googleAnalytics: '',
        googleTagManager: '',
        googleAds: '',
        metaPixel: ''
      },
      services: []
    })
  });

  assert.equal(typeof runtime.window.UniversalLegalConsent.openPreferences, 'function', 'An intentional empty assignment must not invalidate the complete manager configuration.');
  runtime.window.UniversalLegalConsent.openPreferences();
  const manager = runtime.document.querySelector('[data-universal-legal-consent-root]');
  manager.shadowRoot.dispatch('click', {target: manager.shadowRoot.querySelector('[data-action="accept-all"]')});
  assert.equal(runtime.document.getElementById('ulp-google-tag'), null);
  assert.equal(runtime.document.getElementById('ulp-google-tag-manager'), null);
  assert.equal(runtime.document.getElementById('ulp-meta-pixel'), null);
});

test('the necessary category activates predefined and custom integrations without an optional choice', () => {
  const customId = 'custom-0123456789abcdef';
  const runtime = runConsentManager({
    gpc: true,
    config: createConsentConfig({
      integrations: {
        googleAnalytics: 'G-ABCD1234',
        googleTagManager: 'GTM-ABCD1234',
        googleAds: 'AW-1234567890',
        metaPixel: '1234567890'
      },
      integrationCategories: {
        googleAnalytics: 'necessary',
        googleTagManager: 'necessary',
        googleAds: 'necessary',
        metaPixel: 'necessary'
      },
      services: [
        serviceFixture({id: 'google-analytics-4', category: 'necessary', purpose: 'analytics', domain: 'www.google-analytics.com', kind: 'integration'}),
        serviceFixture({id: 'google-ads-remarketing', category: 'necessary', purpose: 'marketing', domain: 'www.googletagmanager.com', kind: 'integration'}),
        serviceFixture({id: 'meta-pixel', category: 'necessary', purpose: 'marketing', domain: 'connect.facebook.net', kind: 'pixel'}),
        serviceFixture({id: customId, category: 'necessary', purpose: 'necessary', domain: 'required.example.test', kind: 'integration'})
      ],
      customIntegrations: [{
        id: customId,
        scriptUrl: 'https://required.example.test/loader.js',
        initCode: ''
      }]
    })
  });

  assert.ok(runtime.document.getElementById('ulp-google-tag'), 'A required Google adapter must load without optional consent.');
  assert.ok(runtime.document.getElementById('ulp-google-tag-manager'), 'Required GTM must load without optional consent.');
  assert.ok(runtime.document.getElementById('ulp-meta-pixel'), 'A required Meta adapter must not be blocked as an optional marketing service.');
  assert.ok(runtime.document.getElementById(`ulp-${customId}`), 'A required custom integration must load without optional consent.');
});

test('GPC keeps configured marketing integrations unloaded', () => {
  const integrationId = 'custom-fedcba9876543210';
  const services = [serviceFixture({
    id: integrationId,
    category: 'marketing',
    domain: 'ads.example.test',
    kind: 'integration'
  })];
  const runtime = runConsentManager({
    gpc: true,
    config: createConsentConfig({
      services,
      customIntegrations: [{
        id: integrationId,
        scriptUrl: 'https://ads.example.test/tag.js',
        initCode: ''
      }]
    })
  });
  const manager = runtime.document.querySelector('[data-universal-legal-consent-root]');

  manager.shadowRoot.dispatch('click', {target: manager.shadowRoot.querySelector('[data-action="accept-all"]')});
  assert.equal(readStoredConsent(runtime.document).services[integrationId], false);
  assert.equal(runtime.document.getElementById(`ulp-${integrationId}`), null, 'GPC must prevent a custom marketing script request.');
});

test('custom consent categories persist independently and switch their ReactWP copy', () => {
  const customId = 'category-0123456789abcdef';
  const englishCategories = [
    {id: 'necessary', label: 'Essential', description: 'Required for this site.'},
    {id: customId, label: 'Anonymous statistics', description: 'Measures site usage.'}
  ];
  const frenchCategories = [
    {id: 'necessary', label: 'Essentiels', description: 'Requis pour ce site.'},
    {id: customId, label: 'Statistiques anonymes', description: 'Mesure l’utilisation du site.'}
  ];
  const services = [serviceFixture({
    id: 'custom-analytics-service',
    category: customId,
    purpose: 'analytics',
    domain: 'analytics.example.test',
    kind: 'integration'
  })];
  const runtime = runConsentManager({
    config: createConsentConfig({
      currentLanguage: 'en',
      categories: englishCategories,
      services,
      integrationCategories: {
        googleAnalytics: customId,
        googleTagManager: customId,
        googleAds: customId,
        metaPixel: customId
      },
      stringTranslations: {
        en: consentStrings('Privacy choices', 'Cookie settings'),
        fr: consentStrings('Choix de confidentialité', 'Gérer les témoins')
      },
      categoryTranslations: {
        en: englishCategories,
        fr: frenchCategories
      }
    })
  });
  const shadow = runtime.document.querySelector('[data-universal-legal-consent-root]').shadowRoot;

  runtime.window.UniversalLegalConsent.openPreferences();
  const category = shadow.querySelector(`#ulc-category-${customId}`);
  assert.ok(category, 'A custom category must be rendered as a visitor choice.');
  category.checked = true;
  shadow.dispatch('change', {target: category});
  shadow.dispatch('submit', {target: shadow.querySelector('form'), preventDefault(){}});

  const stored = readStoredConsent(runtime.document);
  assert.equal(stored.categories[customId], true);
  assert.equal(stored.analytics, true, 'The compatibility analytics state must be derived from the accepted service behavior.');
  assert.equal(runtime.window.UniversalLegalConsent.hasConsent(customId), true);
  assert.equal(runtime.window.UniversalLegalConsent.hasConsent('analytics'), false, 'A deleted default category identifier must not remain callable.');

  assert.equal(runtime.window.UniversalLegalConsent.setLanguage('fr'), true);
  assert.equal(category.parentNode.querySelector('.ulc-category__label').textContent, 'Statistiques anonymes');
});

test('category choices cascade to services and expose mixed state after an individual change', () => {
  const services = [
    serviceFixture({id: 'youtube', category: 'external', domain: 'www.youtube.com'}),
    serviceFixture({id: 'vimeo', category: 'external', domain: 'player.vimeo.com'})
  ];
  const runtime = runConsentManager({config: createConsentConfig({services})});
  const manager = runtime.document.querySelector('[data-universal-legal-consent-root]');
  const shadow = manager.shadowRoot;

  runtime.window.UniversalLegalConsent.openPreferences();
  const category = shadow.querySelector('#ulc-category-external');
  const youtube = shadow.querySelector('#ulc-service-youtube');
  const vimeo = shadow.querySelector('#ulc-service-vimeo');
  category.checked = true;
  shadow.dispatch('change', {target: category});
  assert.equal(youtube.checked, true);
  assert.equal(vimeo.checked, true);
  assert.equal(category.indeterminate, false);

  vimeo.checked = false;
  shadow.dispatch('change', {target: vimeo});
  assert.equal(category.checked, false);
  assert.equal(category.indeterminate, true, 'A partially accepted category must expose the native mixed state.');

  const form = shadow.querySelector('form');
  shadow.dispatch('submit', {target: form, preventDefault(){}});
  const stored = readStoredConsent(runtime.document);
  assert.equal(stored.services.youtube, true);
  assert.equal(stored.services.vimeo, false);
  assert.equal(stored.categories.external, false, 'A mixed service selection must not claim full category consent.');
  assert.equal(stored.external, true, 'The compatibility external signal must follow the accepted service behavior.');
});

test('the bounded observer enhances route-added declarative placeholders but ignores raw iframes', () => {
  const services = [serviceFixture({id: 'vimeo', category: 'external', domain: 'player.vimeo.com'})];
  const runtime = runConsentManager({config: createConsentConfig({services})});
  const rawIframe = runtime.document.createElement('iframe');
  rawIframe.src = 'https://player.vimeo.com/video/123456';
  runtime.document.body.appendChild(rawIframe);
  runtime.observer.trigger([{type: 'childList', addedNodes: [rawIframe]}]);
  assert.equal(rawIframe.shadowRoot, null, 'The observer must not pretend to block an already-created raw iframe.');
  assert.equal(rawIframe.src, 'https://player.vimeo.com/video/123456');

  const placeholder = createPlaceholder(runtime.document, 'vimeo', 'https://player.vimeo.com/video/123456?dnt=1');
  runtime.document.body.appendChild(placeholder);
  runtime.observer.trigger([{type: 'childList', addedNodes: [placeholder]}]);
  assert.ok(placeholder.shadowRoot, 'A route-added declarative placeholder was not enhanced.');
  assert.equal(placeholder.shadowRoot.querySelector('iframe'), null);
  placeholder.shadowRoot.querySelector('button').dispatch('click');
  assert.equal(placeholder.shadowRoot.querySelector('iframe').src, 'https://player.vimeo.com/video/123456?dnt=1');

  for(const listener of runtime.windowListeners.pagehide || []) listener();
  assert.equal(runtime.observer.disconnected, true, 'The global placeholder observer must clean up on pagehide.');
});

test('required terms open preferences for only the requested service and restore embed context focus', () => {
  const services = [
    serviceFixture({id: 'youtube', category: 'external', domain: 'www.youtube.com'}),
    serviceFixture({id: 'vimeo', category: 'external', domain: 'player.vimeo.com'})
  ];
  const runtime = runConsentManager({
    config: createConsentConfig({
      services,
      termsRequired: true,
      termsLink: {url: 'https://example.test/terms', label: 'Terms'}
    }),
    placeholders: (document) => [
      createPlaceholder(document, 'youtube', 'https://www.youtube.com/embed/abc12345')
    ]
  });
  const placeholder = runtime.document.querySelector('[data-ulc-service][data-ulc-src]');
  const manager = runtime.document.querySelector('[data-universal-legal-consent-root]');
  const shadow = manager.shadowRoot;
  placeholder.shadowRoot.querySelector('button').dispatch('click');

  const youtube = shadow.querySelector('#ulc-service-youtube');
  const vimeo = shadow.querySelector('#ulc-service-vimeo');
  const terms = shadow.querySelector('#ulc-terms-confirmation');
  assert.equal(youtube.checked, true);
  assert.equal(vimeo.checked, false, 'The embed action must not preselect sibling services.');
  assert.equal(shadow.activeElement, terms, 'Required terms must receive focus before the service can be saved.');

  terms.checked = true;
  shadow.dispatch('change', {target: terms});
  shadow.dispatch('submit', {target: shadow.querySelector('form'), preventDefault(){}});
  assert.equal(runtime.document.activeElement, placeholder, 'Focus must return to the stable originating embed context.');
  assert.ok(placeholder.shadowRoot.querySelector('iframe'));
});
