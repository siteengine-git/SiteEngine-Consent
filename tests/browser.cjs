const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync(require('node:path').join(__dirname, '../siteengine-consent.php'), 'utf8');
const script = source.match(/<script id="se-consent-script">\s*([\s\S]*?)\s*<\/script>/)[1];
let passed = 0;
function check(value, name) { assert.ok(value, name); passed++; console.log(`PASS ${name}`); }
function harness(shared = new Map(), storageThrows = false, cookieThrows = false) {
  const requests = [], listeners = {}, events = [], cookies = new Map(), nodes = new Map();
  let n = 0;
  const document = {
    documentElement: { style: { setProperty() {} } },
    readyState: 'loading', referrer: '', body: { appendChild(node) { nodes.set(node.id, node); } },
    addEventListener(name, callback) { listeners[name] = callback; },
    dispatchEvent(event) { events.push(event); },
    querySelectorAll() { return []; }, getElementById(id) { return nodes.get(id) || null; },
    createElement() { return { setAttribute() {}, textContent: '' }; }
  };
  Object.defineProperty(document, 'cookie', {
    get() { if (cookieThrows) throw Error('denied'); return [...cookies].map(([k, v]) => `${k}=${v}`).join('; '); },
    set(value) { if (cookieThrows) throw Error('denied'); const [key, ...v] = value.split(';')[0].split('='); cookies.set(key, v.join('=')); }
  });
  const config = { cookieName: 'se_consent', cookieDays: 390, version: '0.6.1-rc.1', policyVersion: 'fixture-policy', bannerVersion: 'fixture-banner', ajaxUrl: '/synthetic-ajax', nonce: 'synthetic-nonce', formFields: {} };
  const window = {
    SEConsentConfig: config, location: { href: 'https://example.invalid/form' }, dataLayer: [],
    crypto: { randomUUID() { n++; return `00000000-0000-4000-8000-${String(n).padStart(12, '0')}`; } },
    localStorage: { getItem(k) { if (storageThrows) throw Error('denied'); return shared.get(k) || null; }, setItem(k, v) { if (storageThrows) throw Error('denied'); shared.set(k, v); } },
    addEventListener(name, callback) { listeners[name] = callback; },
    fetch(url, options) { return new Promise((resolve, reject) => requests.push({ resolve, reject, options })); },
    setTimeout() {}, console
  };
  const instrumented = script.replace('window.SiteEngineConsent = window.SiteEngineConsent || {};', 'window.__fixture = { populateConsentFields: populateConsentFields }; window.SiteEngineConsent = window.SiteEngineConsent || {};');
  vm.runInNewContext(instrumented, { window, document, console, Date, Math, JSON, Promise, URLSearchParams, CustomEvent: class { constructor(type, args) { this.type = type; this.detail = args.detail; } }, isFinite });
  return { window, document, config, shared, requests, listeners, events, api: window.SiteEngineConsent };
}
function ack(payload) {
  return { success: true, data: { contract_version: 1, event_id: payload.event_id, event_acknowledged: true, row_id: 1, receipt_id: 'sc1_' + 'a'.repeat(60), audit_token: 'synthetic-server-ack', expires_at: Math.floor(Date.now() / 1000) + 300,
    method: payload.method, policy_version: payload.policy_version, banner_version: payload.banner_version, created_at: new Date().toISOString(), consent: payload.consent, google_consent: payload.google_consent } };
}
async function settle(request, value) { request.resolve({ json: async () => value }); for (let i = 0; i < 8; i++) await Promise.resolve(); }
(async () => {
  const h = harness();
  const accept = h.api.acceptAll(), reject = h.api.rejectNonEssential();
  check(accept.event_id !== reject.event_id && reject.receipt_id === '' && reject.audit_token === '', 'each choice gets a new event; pending rejection carries no old authority');
  check(h.window.dataLayer.filter(x => x[0] === 'consent').at(-1)[2].ad_user_data === 'denied', 'rejection applies locally before acknowledgement');
  await settle(h.requests[1], ack(reject)); await settle(h.requests[0], ack(accept));
  check(h.api.state.event_id === reject.event_id && h.api.state.consent.advertising === false, 'late accept acknowledgement cannot overwrite newer rejected state');
  const accept2 = h.api.acceptAll(); await settle(h.requests[2], ack(accept2));
  check(h.api.state.event_id === accept2.event_id && h.api.state.audit_status === 'saved', 'later explicit accept can be acknowledged independently');
  const failure = h.api.rejectNonEssential(); h.requests[3].reject(Error('network down')); for (let i = 0; i < 8; i++) await Promise.resolve();
  check(h.api.state.audit_status === 'failed' && h.api.state.receipt_id === '' && h.api.state.audit_token === '', 'network failure preserves rejection and no authoritative identity');
  const retryPromise = h.api.retryAudit();
  check(JSON.parse(new URLSearchParams(h.requests[4].options.body).get('payload')).event_id === failure.event_id, 'retry retains the exact interaction identity');
  await settle(h.requests[4], ack(failure)); await retryPromise;
  check(h.api.state.audit_status === 'saved' && h.api.state.consent.advertising === false, 'successful retry confirms same rejected choice');
  for (const code of ['invalid_nonce', 'audit_busy', 'audit_failed']) {
    h.api.acceptAll(); await settle(h.requests.at(-1), { success: false, data: { code } });
    check(h.api.state.audit_status === 'failed' && !h.api.state.receipt_id, `${code} never creates authority`);
  }
  const unsigned = harness(new Map([['se_consent_state', JSON.stringify({ consent: { advertising: true }, receipt_id: 'legacy' })]]));
  check(unsigned.api.getConsent() === null, 'unsigned legacy state cannot restore accepted consent');
  const saved = JSON.parse(h.shared.get('se_consent_state')); saved.audit_status = 'saved'; saved.audit_token = 'fixture'; saved.receipt_id = 'fixture'; saved.expires_at = Math.floor(Date.now() / 1000) - 1;
  h.shared.set('se_consent_state', JSON.stringify(saved)); check(h.api.getConsent() === null, 'expired acknowledgement requires fresh choice');
  saved.expires_at = Math.floor(Date.now() / 1000) + 300; saved.policy_version = 'old-policy';
  h.shared.set('se_consent_state', JSON.stringify(saved)); check(h.api.getConsent() === null, 'policy mismatch cannot restore old state');
  saved.policy_version = h.config.policyVersion; saved.banner_version = 'old-banner';
  h.shared.set('se_consent_state', JSON.stringify(saved)); check(h.api.getConsent() === null, 'banner mismatch cannot restore old state');
  const deniedStorage = harness(new Map(), true, true); const pending = deniedStorage.api.rejectNonEssential();
  await settle(deniedStorage.requests[0], ack(pending));
  check(deniedStorage.api.state.audit_status === 'saved', 'storage refusal preserves in-memory choice without breaking business page');
  const cross = harness(); const first = cross.api.acceptAll();
  const other = { ...first, event_id: '00000000-0000-4000-8000-000000000099', consent: { necessary: true, analytics: false, advertising: false, personalization: false } };
  cross.shared.set('se_consent_state', JSON.stringify(other)); cross.listeners.storage({ key: 'se_consent_state' });
  await settle(cross.requests[0], ack(first));
  check(cross.api.state.event_id === other.event_id && !cross.api.state.consent.advertising, 'cross-tab choice invalidates older in-flight acknowledgement');
  const formHarness = harness();
  formHarness.config.formFields = { receipt_id: 'se_consent_receipt_id', ad_user_data: 'se_google_ad_user_data' };
  const fields = { se_consent_receipt_id: { value: 'hardcoded-old-id', setAttribute(name, value) { this[name] = value; } }, se_google_ad_user_data: { value: 'granted', setAttribute(name, value) { this[name] = value; } } };
  const form = { querySelector(selector) { return fields[selector.slice(7, -2)] || null; }, setAttribute() {}, appendChild(field) { fields[field.name] = field; } };
  formHarness.window.__fixture.populateConsentFields(form);
  check(fields.se_consent_receipt_id.value === '' && fields.se_google_ad_user_data.value === '', 'unsigned/missing browser state clears hardcoded hidden-field defaults');
  const formGrant = formHarness.api.acceptAll(); formHarness.window.__fixture.populateConsentFields(form);
  check(fields.se_consent_receipt_id.value === '', 'form before audit acknowledgement carries no receipt authority');
  await settle(formHarness.requests[0], ack(formGrant)); formHarness.window.__fixture.populateConsentFields(form);
  check(fields.se_consent_receipt_id.value === ack(formGrant).data.receipt_id, 'form receives genuine acknowledged receipt after success');
  formHarness.api.rejectNonEssential(); formHarness.window.__fixture.populateConsentFields(form);
  check(fields.se_consent_receipt_id.value === '' && fields.se_google_ad_user_data.value === 'denied', 'new rejection clears former granting hidden receipt immediately');
  const longUrl = harness(); longUrl.window.location.href += 'a'.repeat(12000); longUrl.api.rejectNonEssential();
  check(longUrl.document.cookie.length < 4096, 'long page metadata stays outside consent cookie budget');
  const mixed = harness(); const interaction = mixed.api.acceptAll(); const wrong = ack(interaction); wrong.data.event_id = 'different-interaction';
  await settle(mixed.requests[0], wrong);
  check(mixed.api.state.audit_status === 'failed' && !mixed.api.state.receipt_id, 'misrouted acknowledgement cannot attach a different interaction');
  console.log(`Browser checks: ${passed} passed. VM DOM/storage/transport doubles; no browser/site acceptance.`);
})().catch(error => { console.error(error); process.exitCode = 1; });
