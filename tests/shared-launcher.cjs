const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict'),path=require('node:path');
// Reuse the existing preferences/browser boundary doubles, without executing their suites.
const prefix=fs.readFileSync(path.join(__dirname,'preferences.cjs'),'utf8').split('for(const method')[0];
const {ui}=vm.runInNewContext(prefix+';({ui})',{require,__dirname,console,URLSearchParams});
let checks=0,brokerSerial=0;function check(ok,label){assert.ok(ok,label);checks++;console.log('PASS '+label)}
function clock(){let now=0,seq=0;const timers=new Map();return {now:()=>now,setTimeout(fn,delay){const id=++seq;timers.set(id,{fn,at:now+Number(delay)});return id},clearTimeout(id){timers.delete(id)},advance(ms){const end=now+ms;let guard=0;for(;;){const next=[...timers].filter(([,v])=>v.at<=end).sort((a,b)=>a[1].at-b[1].at)[0];if(!next)break;if(++guard>1000)throw Error('timer loop');now=next[1].at;timers.delete(next[0]);next[1].fn()}now=end},timers};}
function broker(c){const prefix='broker-'+(++brokerSerial);let current=null,seq=0,generation=0,state={contractVersion:1,registrationId:null,generation:0,status:'unavailable',reason:'initial',expiresAt:0};const history=[];
 const b={contractVersion:1,allowConfirm:true,emit(){},calls:0,history,getState(){return {...state}},override(value){state={...state,...value};b.emit()},register(provider){b.calls++;if(current)return current.provider===provider?current.handle:null;const record={provider,readyAtRegistration:provider.isReady(),id:prefix+'-'+(++seq),active:true};record.handle={registrationId:record.id,confirm(){if(current!==record||!record.active||!b.allowConfirm||!provider.isReady())return false;state={contractVersion:1,registrationId:record.id,generation:++generation,status:'ready',reason:'confirmed',expiresAt:c.now()+6000};b.emit();return true},withdraw(){if(current!==record||!record.active)return;record.active=false;current=null;state={contractVersion:1,registrationId:null,generation:++generation,status:'unavailable',reason:'withdrawn',expiresAt:0};b.emit()}};current=record;history.push(record);state={contractVersion:1,registrationId:record.id,generation:++generation,status:'registered',reason:'registered',expiresAt:0};b.emit();return record.handle},renew(){if(!current||!current.active||!current.provider.isReady())return;state={...state,expiresAt:c.now()+6000};b.emit()},revoke(){if(current)current.handle.withdraw()},current:()=>current};return b;
}
function fixture({first=false,mode,own=false,allowConfirm=true,observer=false}={}){const c=clock(),b=broker(c);b.allowConfirm=allowConfirm;let observed=[];class Observer{constructor(fn){this.fn=fn;observed.push(this)}observe(){}disconnect(){}}
 const h=ui({mode,own,runtime:{broker:first?b:undefined,performance:{now:c.now},setTimeout:c.setTimeout,clearTimeout:c.clearTimeout,MutationObserver:observer?Observer:undefined}});
 b.emit=()=>h.window.dispatchEvent({type:'se-shield-shared-launcher-state',detail:b.getState()});
 function api(){h.window.dispatchEvent({type:'se-shield-shared-launcher-api',detail:{contractVersion:1}})}
 if(!first){h.window.SEShieldSharedLauncher=b;api()}
 c.advance(200);const initialBannerVisible=h.nodes.get('se-consent-banner').classList.contains('se-consent-visible');h.window.__fixture.hideBanner();
 const launcher=h.document.createElement();launcher.id='shield-launcher';launcher.tabIndex=0;h.document.body.appendChild(launcher);
 const manage=h.document.createElement();manage.setAttribute('data-se-consent-action','manage');h.nodes.get('se-consent-banner').children.push(manage);
 return {...h,c,b,initialBannerVisible,attach:api,shield:launcher,observed,show:()=>!h.nodes.get('se-consent-launcher')?.hidden,provider:()=>b.current()?.provider,handle:()=>b.current()?.handle};
}
function snapshot(h){return JSON.stringify({stored:[...h.shared],requests:h.requests.length,consent:h.api.state,events:h.events.map(e=>e.type)})}
for(const first of [false,true]){const h=fixture({first});check(h.b.calls===1&&h.handle(),'both initialization orders register exactly once ('+first+')');check(h.b.history[0].readyAtRegistration===!first&&h.initialBannerVisible,'explicit render readiness and independent initial banner ('+first+')');check(!h.show(),'matching confirmed identity and live lease suppress standalone ('+first+')');const before=snapshot(h);h.shield.focus();check(h.provider().open(h.shield)===true,'provider verifies existing modal open ('+first+')');check(h.document.activeElement===h.heading&&h.shield.inert,'modal owns focus/background while Shield target inert');h.b.renew();check(h.provider().isReady()&&!h.show(),'own modal inert interval preserves readiness');h.window.__fixture.close();check(h.document.activeElement===h.shield&&!h.shield.inert&&h.document.body.style.overflow==='auto','restore background/scroll before persistent launcher focus');check(snapshot(h)===before,'open/close and lease signaling cause zero consent mutations');}
{
 const h=fixture({allowConfirm:false});check(h.show(),'registered/pending is not usable confirmation');h.b.allowConfirm=true;h.attach();check(!h.show(),'explicit positive confirmation allows suppression');const handle=h.handle();for(let i=0;i<5;i++)h.attach();check(h.handle()===handle&&h.b.calls===1,'duplicate API notification reuses active registration');
}
for(const corrupt of [{registrationId:'stale-registration'},{contractVersion:2},{generation:-1},{generation:0},{expiresAt:Infinity},{expiresAt:999999}]){const h=fixture();h.b.override(corrupt);check(h.show(),'invalid/stale authoritative readiness restores fallback '+JSON.stringify(corrupt));}
{
 const h=fixture(),old=h.b.history[0],replacement=broker(h.c);replacement.emit=()=>h.window.dispatchEvent({type:'se-shield-shared-launcher-state',detail:{registrationId:'ignored-payload'}});h.window.SEShieldSharedLauncher=replacement;h.attach();check(!h.show()&&old!==replacement.current(),'replacement broker gets independent registration');const next=replacement.current().handle.registrationId;old.handle.withdraw();check(replacement.getState().registrationId===next&&!h.show(),'old handle cannot revoke replacement');check(old.provider.open(h.shield)===false&&!h.show(),'stale provider cannot open or revoke replacement');h.window.dispatchEvent({type:'se-shield-shared-launcher-state',detail:{registrationId:old.id,generation:0}});check(!h.show(),'stale event payload is ignored in favor of current authoritative state');
}
{
 const h=fixture(),owned=h.nodes.get('se-consent-launcher');let focused=0,layout=0;const focus=h.shield.focus;h.shield.focus=()=>{focused++;focus()};Object.defineProperty(h.modal,'offsetWidth',{get(){layout++;return 80}});for(let i=0;i<20;i++){h.c.advance(2000);h.b.renew()}check(!h.show()&&h.nodes.get('se-consent-launcher')===owned&&!focused&&!layout,'renewals do not focus, rebuild or measure modal layout');h.c.advance(6000);check(h.show(),'six-second silent broker expiry restores fallback');h.b.renew();check(!h.show(),'fresh valid renewal of current registration restores readiness');h.b.revoke();check(h.show(),'explicit revocation restores standalone');
}
{
 const h=fixture(),old=h.b.history[0];h.window.dispatchEvent({type:'pagehide'});check(h.show()&&!old.active,'pagehide withdraws and cancels suppression');h.window.dispatchEvent({type:'pageshow'});check(!h.show()&&h.handle().registrationId!==old.id,'pageshow re-registers and confirms a fresh identity');h.document.visibilityState='hidden';h.listeners.visibilitychange();check(h.show(),'hidden document restores independent fallback');h.document.visibilityState='visible';h.listeners.visibilitychange();check(!h.show(),'visible document explicitly reconfirms');
}
for(const failure of ['undefined','throw','partial']){const h=fixture(),before=snapshot(h),open=h.api.openPreferences;h.api.openPreferences=function(target){if(failure==='undefined')return;if(failure==='partial')open(target);throw Error('synthetic opening failure')};check(h.provider().open(h.shield)===false,'failed/undefined opening is not success '+failure);check(h.show()&&h.modal.getAttribute('aria-hidden')==='true'&&!h.shield.inert&&h.document.body.style.overflow==='auto','partial opening cleanup restores own modal/background '+failure);check(snapshot(h)===before,'failed opening causes zero consent mutations '+failure);}
{
 const h=fixture();h.provider().open(h.shield);const before=snapshot(h);h.document.body.children=h.document.body.children.filter(n=>n!==h.shield);h.window.__fixture.close();check(h.show()&&h.document.activeElement===h.nodes.get('se-consent-launcher'),'removed focus-return launcher restores and focuses own fallback');check(snapshot(h)===before,'removed-target fallback records no choice');
}
{
 const h=fixture();h.shield.disabled=true;check(h.provider().open(h.shield)===false&&h.show(),'disabled initiator fails safely before opening');
}
{
 const h=fixture();h.api.openPreferences(h.shield);const before=h.document.activeElement;check(h.provider().open(h.shield)===false&&h.document.activeElement===before,'already-open modal is not retargeted by new shared activation');h.window.__fixture.close();
}
{
 const h=fixture({mode:'custom',own:true});const custom=h.document.createElement();custom.tabIndex=0;h.document.body.appendChild(custom);const query=h.document.querySelectorAll;h.document.querySelectorAll=s=>s.includes('data-se-consent-persistent')?[custom]:query(s);h.b.revoke();check(!h.nodes.has('se-consent-launcher')||h.nodes.get('se-consent-launcher').hidden,'reachable explicit custom entry is preserved on revocation');custom.hidden=true;h.window.dispatchEvent({type:'pageshow'});h.b.revoke();check(h.show(),'unreachable custom marker cannot prevent owned fallback');
}
{
 const h=fixture({observer:true});check(h.observed.length===1,'one scoped owned-body lifecycle observer');h.document.body.children=h.document.body.children.filter(n=>n!==h.modal);h.nodes.delete('se-consent-modal');h.observed[0].fn();check(h.show()&&!h.b.current(),'removed Consent modal withdraws readiness');
}
{
 const h=fixture();h.window.performance=undefined;h.b.renew();check(h.show(),'missing monotonic lease capability never suppresses fallback');h.window.SEShieldSharedLauncher={contractVersion:9};h.attach();check(h.show(),'incompatible broker keeps independent entry');
}
{
 const h=fixture(),previous=h.handle().registrationId;h.b.revoke();h.attach();check(!h.show()&&h.handle().registrationId!==previous,'revoked registration recovers with a new identity on API notification');
}
{
 const h=fixture();h.handle().registrationId='mutated-handle';h.b.override({registrationId:'mutated-handle'});check(h.show(),'registration identity is pinned, not read from a mutated handle');
}
{
 const h=fixture();check(h.nodes.get('se-consent-banner').classList.contains('se-consent-visible')===false,'fixture dismissed banner without recording a choice');
 const showBefore=h.handle();h.window.dispatchEvent({type:'se-shield-shared-launcher-state',detail:{status:'unavailable',registrationId:null}});check(!h.show()&&h.handle()===showBefore,'forged stale event cannot override current authoritative state');
}
{
 const h=fixture();let count=0;const open=h.api.openPreferences;h.api.openPreferences=target=>{count++;return open(target)};check(h.provider().open(h.shield)===true&&count===1,'provider calls legacy opener exactly once, then verifies presentation');h.window.__fixture.close();
}
{
 const h=fixture();h.provider().open(h.shield);h.c.advance(6000);check(h.show()&&h.document.activeElement===h.heading,'lease expiry during modal restores fallback without moving modal focus');h.window.__fixture.close();
}
{
 const h=fixture();h.api.openPreferences=()=>{};const target=h.shield;target.parentElement={hidden:true};check(h.provider().open(target)===false&&h.show(),'hidden panel action cannot be used as persistent return target');
}
{
 const h=fixture();h.b.getState=()=>{throw Error('synthetic read failure')};h.window.dispatchEvent({type:'se-shield-shared-launcher-state',detail:{}});check(h.show(),'authoritative state read exception restores standalone without throwing');
}
{
 const h=fixture();const set=h.shield.setAttribute;let once=true;h.shield.setAttribute=(name,value)=>{if(name==='aria-hidden'&&once){once=false;throw Error('synthetic mid-isolation failure')}return set(name,value)};
 check(h.provider().open(h.shield)===false&&h.show()&&!h.shield.inert&&h.document.body.style.overflow==='auto','mid-isolation failure restores captured background and scroll');
}
{
 const h=fixture();h.shield.getAttribute=()=>{throw Error('synthetic malformed target')};check(h.provider().open(h.shield)===false&&h.show(),'malformed target does not throw across provider boundary');
}
console.log(`${checks} focused shared-launcher checks passed; synthetic broker, DOM and monotonic clock; no installed acceptance claimed.`);
