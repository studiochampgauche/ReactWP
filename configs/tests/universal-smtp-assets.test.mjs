import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import vm from 'node:vm';
import {fileURLToPath} from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const projectRoot = path.resolve(__dirname, '../..');
const scriptPath = path.join(
  projectRoot,
  'src/plugins/universal-smtp/template/assets/js/admin-smtp.js'
);
const stylesheetPath = path.join(
  projectRoot,
  'src/plugins/universal-smtp/template/assets/css/admin-smtp.css'
);
const script = fs.readFileSync(scriptPath, 'utf8');
const stylesheet = fs.readFileSync(stylesheetPath, 'utf8');

class FakeClassList {
  constructor(){ this.values = new Set(); }
  add(...values){ values.forEach((value) => this.values.add(value)); }
  remove(...values){ values.forEach((value) => this.values.delete(value)); }
  contains(value){ return this.values.has(value); }
  toggle(value, force){
    const enabled = typeof force === 'boolean' ? force : !this.values.has(value);
    if(enabled) this.values.add(value);
    else this.values.delete(value);
    return enabled;
  }
}

class FakeElement {
  constructor(tagName = 'div'){
    this.tagName = tagName.toUpperCase();
    this.children = [];
    this.parentNode = null;
    this.listeners = new Map();
    this.attributes = new Map();
    this.classList = new FakeClassList();
    this.textContent = '';
    this.value = '';
    this.checked = false;
    this.disabled = false;
    this.hidden = false;
    this.focused = false;
  }

  append(...children){
    children.forEach((child) => {
      child.parentNode = this;
      this.children.push(child);
    });
    return this;
  }

  setAttribute(name, value){ this.attributes.set(name, String(value)); }
  getAttribute(name){ return this.attributes.has(name) ? this.attributes.get(name) : null; }
  hasAttribute(name){ return this.attributes.has(name); }
  removeAttribute(name){ this.attributes.delete(name); }
  addEventListener(type, listener){
    const listeners = this.listeners.get(type) || [];
    listeners.push(listener);
    this.listeners.set(type, listeners);
  }
  dispatch(type, additions = {}){
    const event = {
      target: this,
      defaultPrevented: false,
      preventDefault(){ this.defaultPrevented = true; },
      ...additions
    };
    for(const listener of this.listeners.get(type) || []) listener(event);
    return event;
  }
  focus(){ this.focused = true; }

  matches(selector){
    if(selector.includes(',')) return selector.split(',').some((part) => this.matches(part.trim()));
    if(selector.startsWith('.')) return this.classList.contains(selector.slice(1));
    if(/^[a-z]+$/iu.test(selector)) return this.tagName === selector.toUpperCase();

    const attribute = selector.match(/^\[([^\]$=]+)(\$?=)?(?:"([^"]*)")?\]$/u);
    if(!attribute) return false;
    const [, name, operator, expected = ''] = attribute;
    const actual = this.getAttribute(name);
    if(actual === null) return false;
    if(!operator) return true;
    if(operator === '=') return actual === expected;
    return actual.endsWith(expected);
  }

  descendants(){
    return this.children.flatMap((child) => [child, ...child.descendants()]);
  }
  querySelectorAll(selector){ return this.descendants().filter((element) => element.matches(selector)); }
  querySelector(selector){ return this.querySelectorAll(selector)[0] || null; }
}

class FakeFormData {
  constructor(form){
    this.values = new Map();
    for(const field of form.querySelectorAll('[name]')){
      if(field.disabled) continue;
      this.values.set(field.getAttribute('name'), field.value);
    }
  }
  set(name, value){ this.values.set(name, value); }
  get(name){ return this.values.get(name); }
}

const element = (tag, attributes = {}) => {
  const result = new FakeElement(tag);
  for(const [name, value] of Object.entries(attributes)){
    if(name === 'class') result.classList.add(...value.split(/\s+/u));
    else if(name === 'text') result.textContent = value;
    else if(name === 'value') result.value = value;
    else if(name === 'checked') result.checked = value;
    else result.setAttribute(name, value);
  }
  return result;
};

const createFixture = ({ajaxUrl = '/wp-admin/admin-ajax.php', passwordManaged = false} = {}) => {
  const root = element('div', {class: 'usmtp-admin'});
  const notice = element('div', {'data-usmtp-notice': '', tabindex: '-1'});
  notice.hidden = true;
  const noticeMessage = element('p', {'data-usmtp-notice-message': ''});
  const dismiss = element('button', {'data-usmtp-notice-dismiss': ''});
  notice.append(noticeMessage, dismiss);

  const settingsForm = element('form', {'data-usmtp-settings-form': ''});
  const host = element('input', {name: 'universal_smtp_options[host]', value: 'smtp.example.test'});
  const auth = element('input', {
    name: 'universal_smtp_options[authenticate]',
    checked: true,
    value: '1'
  });
  const authFields = element('div', {'data-usmtp-auth-fields': ''});
  const authType = element('select', {name: 'universal_smtp_options[auth_type]', value: 'login'});
  const username = element('input', {name: 'universal_smtp_options[username]', value: 'mailer'});
  const password = element('input', {name: 'universal_smtp_options[password]', value: 'not-a-real-secret'});
  password.disabled = passwordManaged;
  const clearPassword = element('input', {
    name: 'universal_smtp_options[clear_password]',
    value: '1'
  });
  const passwordStatus = element('span', {'data-usmtp-password-status': ''});
  const configuredState = element('span', {'data-usmtp-password-state': 'configured'});
  const emptyState = element('span', {'data-usmtp-password-state': 'empty'});
  passwordStatus.append(configuredState, emptyState);
  const save = element('button', {'data-usmtp-save': '', text: 'Save settings'});
  authFields.append(authType, username, password);
  if(!passwordManaged) authFields.append(clearPassword);
  authFields.append(passwordStatus);
  settingsForm.append(host, auth, authFields, save);

  const testForm = element('form', {'data-usmtp-test-form': ''});
  const recipient = element('input', {name: 'recipient', value: 'owner@example.test'});
  const sendTest = element('button', {'data-usmtp-test': '', text: 'Send test'});
  testForm.append(recipient, sendTest);
  root.append(notice, settingsForm, testForm);

  const requests = [];
  const responses = [];
  const fetch = (_url, options) => {
    requests.push(options.body);
    const response = responses.shift();
    if(response instanceof Error) return Promise.reject(response);
    return Promise.resolve({json: () => Promise.resolve(response)});
  };
  const document = {
    readyState: 'complete',
    querySelector: (selector) => selector === '.usmtp-admin' ? root : null,
    addEventListener(){}
  };
  const window = {
    universalSmtpAdmin: {
      ajaxUrl,
      saveNonce: 'save-nonce',
      testNonce: 'test-nonce',
      optionName: 'universal_smtp_options',
      messages: {
        saving: 'Saving…',
        testing: 'Sending…',
        genericError: 'Something went wrong.'
      }
    }
  };

  vm.runInNewContext(script, {window, document, fetch, FormData: FakeFormData});

  return {
    root,
    notice,
    noticeMessage,
    dismiss,
    settingsForm,
    host,
    auth,
    authFields,
    password,
    clearPassword,
    passwordStatus,
    configuredState,
    emptyState,
    save,
    testForm,
    recipient,
    sendTest,
    requests,
    responses
  };
};

const settle = async () => {
  await new Promise((resolve) => setImmediate(resolve));
  await new Promise((resolve) => setImmediate(resolve));
};

test('authentication and write-only password states initialize without a delayed reveal', () => {
  const fixture = createFixture();

  assert.equal(fixture.root.classList.contains('is-enhanced'), true);
  assert.equal(fixture.authFields.hidden, false);
  fixture.auth.checked = false;
  fixture.auth.dispatch('change');
  assert.equal(fixture.authFields.hidden, false, 'Password management must remain reachable when authentication is off.');
  assert.equal(fixture.authFields.classList.contains('is-authentication-disabled'), true);
  assert.equal(fixture.password.disabled, true);
  assert.equal(fixture.clearPassword.disabled, false, 'A stale stored password must remain clearable.');
  assert.equal(fixture.password.value, 'not-a-real-secret', 'Hiding authentication must not destroy an entered replacement.');

  fixture.auth.checked = true;
  fixture.auth.dispatch('change');
  fixture.clearPassword.checked = true;
  fixture.clearPassword.dispatch('change');
  assert.equal(fixture.password.disabled, true, 'Explicit clearing must take precedence over replacement.');

  fixture.password.value = 'replacement';
  fixture.password.dispatch('input');
  assert.equal(fixture.clearPassword.checked, false);
  assert.equal(fixture.password.disabled, false);
});

test('a constant-managed password remains unavailable across authentication changes', () => {
  const fixture = createFixture({passwordManaged: true});

  assert.equal(fixture.password.disabled, true);
  fixture.auth.checked = false;
  fixture.auth.dispatch('change');
  fixture.auth.checked = true;
  fixture.auth.dispatch('change');
  assert.equal(fixture.password.disabled, true, 'Client state must not unlock a server-managed credential field.');
});

test('settings save uses the AJAX contract, clears the secret, and restores its control', async () => {
  const fixture = createFixture();
  fixture.responses.push({
    success: true,
    data: {message: 'Settings saved.', passwordConfigured: true}
  });

  const event = fixture.settingsForm.dispatch('submit');
  assert.equal(event.defaultPrevented, true);
  assert.equal(fixture.save.disabled, true);
  assert.equal(fixture.noticeMessage.textContent, 'Saving…');
  assert.equal(fixture.requests[0].get('action'), 'universal_smtp_save_settings');
  assert.equal(fixture.requests[0].get('nonce'), 'save-nonce');
  const repeatedEvent = fixture.settingsForm.dispatch('submit');
  assert.equal(repeatedEvent.defaultPrevented, true);
  assert.equal(fixture.requests.length, 1, 'A pending settings request must not be duplicated.');
  await settle();

  assert.equal(fixture.notice.classList.contains('usmtp-admin__notice--success'), true);
  assert.equal(fixture.noticeMessage.textContent, 'Settings saved.');
  assert.equal(fixture.save.disabled, false);
  assert.equal(fixture.password.value, '', 'A successfully saved credential must not linger in the DOM.');
  assert.equal(fixture.passwordStatus.getAttribute('data-status'), 'configured');
  assert.equal(fixture.configuredState.hidden, false);
  assert.equal(fixture.emptyState.hidden, true);
});

test('settings validation errors preserve fields, mark their control, and focus the notice', async () => {
  const fixture = createFixture();
  fixture.responses.push({
    success: false,
    data: {
      message: 'Review the settings.',
      fieldErrors: {host: 'Enter an SMTP host.'},
      errors: [{code: 'invalid_host', field: 'host', message: 'Enter an SMTP host.'}]
    }
  });

  fixture.settingsForm.dispatch('submit');
  await settle();

  assert.equal(fixture.notice.classList.contains('usmtp-admin__notice--error'), true);
  assert.equal(fixture.notice.focused, true);
  assert.equal(fixture.host.getAttribute('aria-invalid'), 'true');
  assert.equal(fixture.host.value, 'smtp.example.test');
  assert.equal(fixture.password.value, 'not-a-real-secret', 'A rejected save must preserve the replacement for correction.');
});

test('test email handles success, field errors, and a network failure without duplicate state', async () => {
  const fixture = createFixture();
  fixture.responses.push({success: true, data: {message: 'Test sent.'}});
  fixture.testForm.dispatch('submit');
  assert.equal(fixture.sendTest.disabled, true);
  assert.equal(fixture.requests[0].get('action'), 'universal_smtp_send_test');
  assert.equal(fixture.requests[0].get('nonce'), 'test-nonce');
  const repeatedEvent = fixture.testForm.dispatch('submit');
  assert.equal(repeatedEvent.defaultPrevented, true);
  assert.equal(fixture.requests.length, 1, 'A pending test request must not be duplicated.');
  await settle();
  assert.equal(fixture.noticeMessage.textContent, 'Test sent.');
  assert.equal(fixture.sendTest.disabled, false);

  fixture.notice.focused = false;
  fixture.responses.push({
    success: false,
    data: {fieldErrors: {recipient: 'Enter a valid recipient.'}, errors: []}
  });
  fixture.testForm.dispatch('submit');
  await settle();
  assert.equal(fixture.recipient.getAttribute('aria-invalid'), 'true');
  assert.equal(fixture.notice.focused, true);

  fixture.responses.push(new Error('Network details must stay private.'));
  fixture.testForm.dispatch('submit');
  await settle();
  assert.equal(fixture.noticeMessage.textContent, 'Something went wrong.');
  assert.equal(fixture.notice.textContent.includes('Network details'), false);
  assert.equal(fixture.sendTest.disabled, false);
});

test('missing AJAX configuration preserves the native no-JavaScript submission path', () => {
  const fixture = createFixture({ajaxUrl: ''});
  const saveEvent = fixture.settingsForm.dispatch('submit');
  const testEvent = fixture.testForm.dispatch('submit');

  assert.equal(saveEvent.defaultPrevented, false);
  assert.equal(testEvent.defaultPrevented, false);
  assert.equal(fixture.requests.length, 0);
});

test('admin styles provide scoped monochrome, responsive, focus, and reduced-motion states', () => {
  assert.match(stylesheet, /^\.usmtp-admin\s*\{/mu);
  assert.match(stylesheet, /--usmtp-ink:\s*#050505;/u);
  assert.match(stylesheet, /\.usmtp-admin-section\s*\{[\s\S]*?width:\s*100%;/u);
  assert.match(stylesheet, /\.usmtp-admin__field-grid\s*\{[\s\S]*?grid-template-columns:/u);
  assert.match(stylesheet, /\.usmtp-admin__checkbox-list\s*\{/u);
  assert.match(stylesheet, /\.usmtp-admin__input-suffix\s*\{/u);
  assert.match(stylesheet, /\.usmtp-admin__last-status\s*\{/u);
  assert.match(stylesheet, /\.usmtp-admin__dependent\.is-authentication-disabled/u);
  assert.match(stylesheet, /padding-block:\s*var\(--usmtp-section-space\);/u);
  assert.match(stylesheet, /min-height:\s*44px;/u);
  assert.match(stylesheet, /:focus-visible/u);
  assert.match(stylesheet, /@media \(max-width:\s*782px\)/u);
  assert.match(stylesheet, /@media \(max-width:\s*560px\)/u);
  assert.match(stylesheet, /@media \(prefers-reduced-motion:\s*reduce\)/u);
  assert.match(stylesheet, /\.usmtp-admin__credit\s*\{[\s\S]*?justify-content:\s*flex-start;/u);
});

test('admin script uses form bodies and does not expose credentials through logs or URLs', () => {
  assert.match(script, /new FormData\(form\)/u);
  assert.match(script, /credentials:\s*'same-origin'/u);
  assert.match(script, /passwordInput\.value\s*=\s*'';/u);
  assert.doesNotMatch(script, /console\.(?:log|debug|info|warn|error)/u);
  assert.doesNotMatch(script, /URLSearchParams/u);
});
