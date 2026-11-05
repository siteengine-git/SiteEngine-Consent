const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict'),path=require('node:path');
// Reuse the retained browser transport/storage double; only model UI boundaries here.
let prefix=fs.readFileSync(path.join(__dirname,'browser.cjs'),'utf8').split('(async () => {')[0];
prefix=prefix.replace('populateConsentFields: populateConsentFields','populateConsentFields: populateConsentFields, render: render, open: openModal, close: closeModal, setToggle: setToggleValue, status: auditStatus');
prefix=prefix.replace('return { window, document, config, shared, requests, listeners, events, api:', 'return { nodes, window, document, config, shared, requests, listeners, events, api:');
const {harness}=vm.runInNewContext(prefix+';({harness})',{require,__dirname,console,URLSearchParams});
let passed=0;function check(ok,label){assert.ok(ok,label);passed++;console.log('PASS '+label)}
function ui(options={}){
 const h=harness();h.config.preferenceControls=options;h.document.body.children=[];h.document.body.style={overflow:'auto'};
 function el(id='') {const attrs=new Map(),classes=new Set();return {id,offsetWidth:80,offsetHeight:40,inert:false,hidden:false,style:{setProperty(k,v){this[k]=v}},children:[],classList:{add(k){classes.add(k)},remove(k){classes.delete(k)},contains(k){return classes.has(k)}},setAttribute(k,v){attrs.set(k,String(v))},getAttribute(k){return attrs.has(k)?attrs.get(k):null},removeAttribute(k){attrs.delete(k)},appendChild(n){this.children.push(n)},querySelectorAll(){return this.children.filter(n=>n.id!=='se-consent-modal-title')},contains(n){return this===n||this.children.includes(n)},focus(){h.document.activeElement=this}}}
 h.document.createElement=()=>el();h.document.body.appendChild=n=>{h.nodes.set(n.id,n);h.document.body.children.push(n)};
 h.document.querySelector=s=>s.includes('persistent')?(options.own?{}:null):null;
 h.document.contains=n=>h.document.body.children.includes(n)||h.modal?.children.includes(n);
 h.window.setTimeout=cb=>cb();h.window.__fixture.render();
 h.modal=h.nodes.get('se-consent-modal');h.heading=el('se-consent-modal-title');h.first=el('first');h.last=el('last');h.modal.children=[h.heading,h.first,h.last];h.nodes.set(h.heading.id,h.heading);h.modal.setAttribute('aria-hidden','true');
 return h;
}
for(const method of ['acceptAll','rejectNonEssential']){
 const h=ui();const launcher=h.nodes.get('se-consent-launcher');check(launcher.hidden,'launcher waits while initial banner is visible');h.api[method]();check(!launcher.hidden,method+' leaves a persistent launcher');
 const before=h.shared.get('se_consent_state'),requests=h.requests.length;const custom=h.document.createElement();h.document.body.appendChild(custom);custom.setAttribute('aria-hidden','false');custom.inert=true;launcher.focus();h.window.__fixture.open(launcher);
 check(h.document.activeElement===h.heading,'opening focuses named heading');check(custom.inert&&custom.getAttribute('aria-hidden')==='true','background isolated');
 h.listeners.keydown({key:'Tab',preventDefault(){},shiftKey:false});check(h.document.activeElement===h.first,'Tab moves from heading to first control');
 h.last.focus();let prevented=false;h.listeners.keydown({key:'Tab',preventDefault(){prevented=true},shiftKey:false});check(prevented&&h.document.activeElement===h.first,'Tab cycles within modal');
 h.listeners.keydown({key:'Escape',preventDefault(){}});check(h.document.activeElement===launcher,'Escape returns focus to initiating launcher');
 check(h.modal.getAttribute('aria-hidden')==='true'&&!h.modal.classList.contains('se-consent-visible'),'closed dialog remains hidden to assistive technology');
 check(custom.inert&&custom.getAttribute('aria-hidden')==='false'&&h.document.body.style.overflow==='auto','preexisting background state restored exactly');
 check(h.shared.get('se_consent_state')===before&&h.requests.length===requests,'open/close records no choice or request');
 h.window.__fixture.status('pending');let status=h.nodes.get('se-consent-confirmation');check(status.getAttribute('role')==='status'&&status.textContent.includes('Waiting for server confirmation'),'pending is explicit live status');
 h.window.__fixture.status('failed');check(status.textContent.includes('failed')&&status.children.at(-1).textContent==='Retry confirmation','failed feedback retains retry');
}
check(!ui({mode:'custom'}).nodes.has('se-consent-launcher'),'explicit custom mode suppresses duplicate launcher');
check(!ui({own:true}).nodes.has('se-consent-launcher'),'declared persistent site control suppresses duplicate launcher');
const feedback=ui();const inline=feedback.document.createElement();inline.id='se-consent-modal-status';feedback.nodes.set(inline.id,inline);feedback.window.__fixture.status('failed');check(inline.textContent.includes('Close this dialog to retry'),'failed feedback is also available inside modal');
const css=fs.readFileSync(path.join(__dirname,'../siteengine-consent.php'),'utf8');check(css.includes('var(--se-consent-control-bottom,88px) + 64px')&&css.includes('#se-consent-confirmation[inert]{visibility:hidden}'),'floating feedback reserves launcher space and hides behind active modal');
const h=ui({side:'left',bottom:900});check(h.nodes.get('se-consent-launcher').className.includes('left')&&h.nodes.get('se-consent-launcher').style['--se-consent-control-bottom']==='320px','side and bounded clearance applied');
const php=fs.readFileSync(path.join(__dirname,'../siteengine-consent.php'),'utf8');check(php.includes('env(safe-area-inset-bottom')&&php.includes('100dvh')&&php.includes('visibility:hidden'),'safe areas, dynamic viewport and hidden banner supported');
new vm.Script(php.match(/<script id="se-consent-script">\s*([\s\S]*?)\s*<\/script>/)[1]);
console.log(`${passed} focused UI checks passed; isolated DOM/storage boundary, not installed browser or screen-reader certification.`);
