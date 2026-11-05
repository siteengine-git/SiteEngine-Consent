const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict'),path=require('node:path');
// Reuse the existing browser/storage/capture boundary. Model DOM ownership, not layout or AT.
let prefix=fs.readFileSync(path.join(__dirname,'browser.cjs'),'utf8').split('(async () => {')[0];
prefix=prefix.replace('populateConsentFields: populateConsentFields','populateConsentFields: populateConsentFields, render: render, close: closeModal, status: auditStatus, hideBanner: hideBanner');
const {harness}=vm.runInNewContext(prefix+';({harness})',{require,__dirname,console,URLSearchParams});
let count=0;function check(value,label){assert.ok(value,label);count++;console.log('PASS '+label)}
function fixture({first=false,version=2,mountFailure=false,confirmFailure=false,appendFailure=false,closeFailure=false}={}){
 let now=0,serial=0;const timers=new Map();const h=harness(undefined,false,false,{performance:{now:()=>now},setTimeout(fn,delay){const id=++serial;timers.set(id,{fn,at:now+Number(delay)});return id},clearTimeout(id){timers.delete(id)}});
 class Node {
  constructor(tag='div'){this.tagName=tag.toUpperCase();this.ownerDocument=h.document;this.parentNode=null;this.children=[];this.attrs=new Map();this.classes=new Set();this.style={setProperty(k,v){this[k]=v}};this.hidden=false;this.inert=false;this.tabIndex=0;this._text='';this.classList={add:k=>this.classes.add(k),remove:k=>this.classes.delete(k),contains:k=>this.classes.has(k)};}
  get id(){return this.attrs.get('id')||''}set id(v){this.attrs.set('id',v)}get parentElement(){return this.parentNode}get childNodes(){return this.children}get offsetWidth(){return this.hidden?0:80}get offsetHeight(){return this.hidden?0:40}
  setAttribute(k,v){this.attrs.set(k,String(v));if(k==='tabindex')this.tabIndex=Number(v)}getAttribute(k){return this.attrs.get(k)??null}removeAttribute(k){this.attrs.delete(k)}
  appendChild(n){if(n.parentNode)n.parentNode.children=n.parentNode.children.filter(v=>v!==n);this.children.push(n);n.parentNode=this;return n}
  remove(){if(this.parentNode)this.parentNode.children=this.parentNode.children.filter(v=>v!==this);this.parentNode=null}
  contains(n){return this===n||this.children.some(c=>c.contains(n))}focus(){h.document.activeElement=this}
  set textContent(v){for(const n of this.children)n.parentNode=null;this.children=[];this._text=v}get textContent(){return this._text+this.children.map(c=>c.textContent).join('')}
  matches(s){if(s.startsWith('#'))return this.id===s.slice(1);if(s.startsWith('.'))return this.classes.has(s.slice(1));const m=s.match(/^\[([^=\]]+)(?:="([^"]+)")?\]$/);if(m)return this.attrs.has(m[1])&&(m[2]===undefined||this.getAttribute(m[1])===m[2]);return false}
  querySelectorAll(s){const selectors=s.split(',');const all=[];for(const c of this.children){if(selectors.some(v=>c.matches(v)))all.push(c);all.push(...c.querySelectorAll(s))}return all}
  querySelector(s){return this.querySelectorAll(s)[0]||null}closest(s){for(let n=this;n;n=n.parentNode)if(s.split(',').some(v=>n.matches(v)))return n;return null}
  set innerHTML(html){if(html.includes('id="se-consent-preferences-content"')){const root=new Node();root.id='se-consent-preferences-content';this.appendChild(root);for(const id of ['se-consent-modal-title','se-consent-modal-status','se-consent-draft-notice']){const el=new Node();el.id=id;if(id.endsWith('title'))el.tabIndex=-1;root.appendChild(el)}for(const c of ['necessary','analytics','advertising','personalization']){const toggle=new Node('button');toggle.setAttribute('data-se-consent-toggle',c);toggle.setAttribute('aria-checked',c==='necessary'?'true':'false');if(c==='necessary')toggle.setAttribute('aria-disabled','true');root.appendChild(toggle)}for(const action of ['save','reject','close']){const b=new Node('button');b.setAttribute('data-se-consent-action',action);root.appendChild(b)}}}
 }
 const body=new Node('body');body.style.overflow='auto';h.document.body=body;h.document.createElement=tag=>new Node(tag);h.document.contains=n=>body.contains(n);h.document.getElementById=id=>body.querySelector('#'+id);h.document.querySelector=s=>body.querySelector(s);h.document.querySelectorAll=s=>body.querySelectorAll(s);h.window.getComputedStyle=()=>({display:'block',visibility:'visible'});
 const host=new Node();body.appendChild(host);const slot=new Node();slot.hidden=true;host.appendChild(slot);const launcher=new Node('button');body.appendChild(launcher);
 if(appendFailure){const append=slot.appendChild.bind(slot);slot.appendChild=n=>{append(n);throw Error('isolated partial mount failure')}}
 let current=null,generation=0,registrationSerial=0,hostView='closed',openCalls=0,closeCalls=0;
 let state={contractVersion:version,registrationId:null,generation:0,status:'unavailable',expiresAt:0};
 function emit(){h.window.dispatchEvent({type:'se-shield-shared-launcher-state'})}
 function view(v){hostView=v;slot.hidden=v!=='active';return current.provider.setView(v)}
 function revoke(){const old=current;if(!old)return;hostView='closed';slot.hidden=true;old.provider.setView('closed');old.provider.unmount();old.active=false;current=null;state={contractVersion:version,registrationId:null,generation:++generation,status:'unavailable',expiresAt:0};emit()}
 const broker={contractVersion:version,getState:()=>({...state}),register(provider){if(current)return current.provider===provider?current.handle:null;const r={provider,active:true};current=r;const id='registration-'+(++registrationSerial);r.handle={registrationId:id,confirm(){if(!r.active||current!==r||confirmFailure||!provider.isReady())return false;const actions={close(){closeCalls++;if(closeFailure||current!==r)return false;view('closed');launcher.focus();return true},invalidate(){if(current===r)revoke()}};if(mountFailure||!provider.mount(slot,actions)||!provider.setView('closed'))return false;state={contractVersion:version,registrationId:id,generation:++generation,status:'ready',expiresAt:now+6000};emit();return true},withdraw(){if(current===r)revoke()},openConsent(initiator){openCalls++;if(!r.active||current!==r)return false;if(!view('active'))return false;h.document.getElementById('se-consent-modal-title').focus();return true}};state={contractVersion:version,registrationId:id,generation:++generation,status:'registered',expiresAt:0};return r.handle},renew(){if(current){state.expiresAt=now+6000;emit()}},revoke};
 if(first)h.window.SEShieldSharedLauncher=broker;
 h.window.__fixture.render();if(!first){h.window.SEShieldSharedLauncher=broker;h.window.dispatchEvent({type:'se-shield-shared-launcher-api'})}
 function advance(ms){now+=ms;for(const [id,t]of [...timers])if(t.at<=now){timers.delete(id);t.fn()}}
 advance(200);h.window.__fixture.hideBanner();
 function click(node){h.listeners.click({target:node,preventDefault(){}})}
 function toggle(category){return h.document.querySelector('[data-se-consent-toggle="'+category+'"]')}
 function snapshot(){return JSON.stringify({storage:[...h.shared],requests:h.requests.length,events:h.events.length})}
 return {...h,body,host,slot,launcher,broker,Node,advance,click,toggle,snapshot,view,record:()=>current,hostView:()=>hostView,openCalls:()=>openCalls,closeCalls:()=>closeCalls,visibleLauncher:()=>!h.document.getElementById('se-consent-launcher')?.hidden,root:()=>h.document.getElementById('se-consent-preferences-content'),modal:()=>h.document.getElementById('se-consent-modal'),state(v){state={...state,...v};emit()}};
}
for(const first of [false,true]){
 const h=fixture({first}),root=h.root(),before=h.snapshot(),provider=h.record().provider;
 check(root.parentNode===h.slot&&!h.visibleLauncher(),'both orders confirm one mounted original subtree '+first);
 check(!('open'in provider)&&provider.contractVersion===2,'provider implements v2 mounting API without v1 modal handoff');
 check(h.view('inactive')&&h.hostView()==='inactive','general Accessibility entry leaves Consent inactive');
 check(h.view('active'),'tab can select Consent');h.click(h.toggle('advertising'));check(h.toggle('advertising').getAttribute('aria-checked')==='true','existing delegated category handler operates in slot');
 h.view('inactive');h.view('active');check(h.toggle('advertising').getAttribute('aria-checked')==='true','unsaved draft survives tab switching');
 h.window.dispatchEvent({type:'se-shield-shared-launcher-api'});check(h.toggle('advertising').getAttribute('aria-checked')==='true','duplicate API announcement does not reconfirm/reset the active draft');
 check(h.root()===root&&h.body.style.overflow==='auto'&&!h.launcher.inert&&!h.modal().classList.contains('se-consent-visible'),'one root with no Consent modal/isolation/scroll lock');
 h.listeners.keydown({key:'Escape',preventDefault(){throw Error('Consent must not handle host Escape')}});check(h.hostView()==='active','Consent does not compete with host keyboard/focus owner');
 h.view('closed');h.view('active');check(h.toggle('advertising').getAttribute('aria-checked')==='false','closed session discards unsaved edits');
 h.api.openPreferences(h.launcher);check(h.openCalls()===1&&root.contains(h.document.activeElement),'public direct entry uses handle and focuses host heading');
 const cancel=h.root().querySelector('[data-se-consent-action="close"]');h.click(cancel);check(h.closeCalls()===1&&h.hostView()==='closed','existing Cancel delegates host close exactly once');
 check(h.snapshot()===before,'open/tab/draft/close creates zero persisted choices/captures');
 const old=h.record();h.broker.revoke();check(root===h.root()&&root.parentNode===h.modal()&&h.visibleLauncher(),'revocation restores same controls and standalone launcher');
 old.provider.unmount();check(root.parentNode===h.modal(),'duplicate unmount is harmless');
 h.api.openPreferences(h.launcher);check(h.modal().classList.contains('se-consent-visible')&&h.body.style.overflow==='hidden','standalone dialog restored after revocation');h.window.__fixture.close();
 check(h.snapshot()===before,'fallback open/close still records no choice');
}
for(const opts of [{version:1},{version:99},{mountFailure:true},{confirmFailure:true},{appendFailure:true}]){const h=fixture(opts);check(h.root().parentNode===h.modal()&&h.visibleLauncher(),'incompatible or failed host preserves standalone '+JSON.stringify(opts));h.api.openPreferences(h.launcher);check(h.modal().classList.contains('se-consent-visible'),'failed readiness direct entry falls back');}
{
 const h=fixture(),root=h.root();h.view('active');h.click(h.toggle('analytics'));h.host.remove();h.advance(6000);check(root.parentNode===h.modal()&&h.visibleLauncher(),'silent detached host expiry recovers retained original root');check(h.document.getElementById('se-consent-draft-notice').textContent.includes('discarded'),'failure discloses discarded unsaved draft');
}
for(const invalid of [{registrationId:'foreign'},{generation:0},{expiresAt:Infinity},{expiresAt:9000},{contractVersion:1},{status:'unavailable'}]){const h=fixture();h.state(invalid);check(h.root().parentNode===h.modal()&&h.visibleLauncher(),'invalid identity/generation/lease/status restores standalone '+JSON.stringify(invalid));}
{
 const h=fixture(),old=h.record();h.broker.revoke();h.window.dispatchEvent({type:'se-shield-shared-launcher-api'});const root=h.root();check(root.parentNode===h.slot&&h.record()!==old,'new registration mounts original root');old.provider.unmount();check(root.parentNode===h.slot,'stale unmount cannot affect replacement');check(old.provider.setView('active')===false,'stale view cannot affect replacement');
}
{
 const h=fixture();h.record().handle.openConsent=()=>false;h.api.openPreferences(h.launcher);check(h.root().parentNode===h.modal()&&h.modal().classList.contains('se-consent-visible'),'failed direct opening cleans host before standalone');
}
{
 const h=fixture(),provider=h.record().provider;h.broker.revoke();h.api.openPreferences(h.launcher);const slot=new h.Node();h.body.appendChild(slot);check(provider.mount(slot,{close(){return true},invalidate(){}})===false,'stale provider cannot mount while standalone is open');h.window.__fixture.close();
}
{
 const h=fixture(),before=h.snapshot();h.view('active');h.launcher.focus();h.record().provider.setView('inactive');check(h.document.activeElement===h.launcher&&h.snapshot()===before,'view transitions never move focus or write authority');
 h.broker.renew();h.advance(2000);check(h.root().parentNode===h.slot,'valid renewal retains same mounted subtree');
 h.record().provider.setView('active');h.record().handle.openConsent(h.launcher);h.advance(6000);check(h.root().parentNode===h.modal()&&h.document.activeElement!==h.document.getElementById('se-consent-modal-title'),'expiry returns focus out of recovered hidden controls');
}
{
 const h=fixture();h.record().provider.setView('active');h.click(h.root().querySelector('[data-se-consent-action="reject"]'));const state=JSON.parse(h.shared.get('se_consent_state'));check(Object.values(state.consent).filter(Boolean).length===1&&state.audit_status==='pending','explicit Reject preserves necessary-only categories and pending authority');
}
{
 const h=fixture({closeFailure:true}),before=h.snapshot();h.view('active');h.click(h.root().querySelector('[data-se-consent-action="close"]'));check(h.root().parentNode===h.modal()&&h.visibleLauncher()&&h.snapshot()===before,'failed host close revokes/restores without recording a choice');
}
{
 const h=fixture(),r=h.record(),root=h.root();check(r.provider.mount(h.slot,{close(){return true},invalidate(){}})===true&&h.root()===root,'duplicate same-slot mount does not clone controls');const foreign=new h.Node();h.body.appendChild(foreign);check(r.provider.mount(foreign,{close(){return true},invalidate(){}})===false&&root.parentNode===h.slot,'a second host cannot steal mounted controls');check(r.provider.setView('unknown')===false,'invalid view refused');
}
{
 const h=fixture();h.view('active');h.click(h.toggle('analytics'));h.click(h.root().querySelector('[data-se-consent-action="save"]'));
 const state=JSON.parse(h.shared.get('se_consent_state'));check(state.consent.analytics===true&&state.consent.advertising===false&&state.audit_status==='pending','existing explicit Save preserves independent categories and pending authority');check(h.hostView()==='closed'&&h.closeCalls()===1,'explicit save closes through host once');
 h.view('active');check(h.toggle('analytics').getAttribute('aria-checked')==='true','saved pending choice survives next session');h.window.__fixture.status('failed');const inline=h.document.getElementById('se-consent-modal-status');let calls=0;h.api.retryAudit=()=>{calls++};inline.children[0].onclick();check(calls===1&&inline.textContent.includes('failed'),'mounted failed feedback invokes existing retry API');h.window.__fixture.status('pending');check(inline.children.length===0&&inline.textContent.includes('Waiting'),'pending update removes stale retry control');
}
console.log(`${count} focused v2 checks passed; synthetic host/DOM/storage, no installed layout, screen-reader or real capture acceptance.`);
