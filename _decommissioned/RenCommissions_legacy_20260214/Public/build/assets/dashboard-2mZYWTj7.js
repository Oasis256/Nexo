/**
* @vue/shared v3.5.27
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/function oe(t){const e=Object.create(null);for(const s of t.split(","))e[s]=1;return s=>s in e}const ae={},xt=Object.assign,re=Object.prototype.hasOwnProperty,pt=(t,e)=>re.call(t,e),W=Array.isArray,at=t=>Nt(t)==="[object Map]",K=t=>typeof t=="function",ie=t=>typeof t=="string",tt=t=>typeof t=="symbol",F=t=>t!==null&&typeof t=="object",le=t=>(F(t)||K(t))&&K(t.then)&&K(t.catch),ce=Object.prototype.toString,Nt=t=>ce.call(t),de=t=>Nt(t).slice(8,-1),gt=t=>ie(t)&&t!=="NaN"&&t[0]!=="-"&&""+parseInt(t,10)===t,q=(t,e)=>!Object.is(t,e);let Dt;const yt=()=>Dt||(Dt=typeof globalThis<"u"?globalThis:typeof self<"u"?self:typeof window<"u"?window:typeof global<"u"?global:{});/**
* @vue/reactivity v3.5.27
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/let g,jt=0,Y,X;function ue(t,e=!1){if(t.flags|=8,e){t.next=X,X=t;return}t.next=Y,Y=t}function wt(){jt++}function _t(){if(--jt>0)return;if(X){let e=X;for(X=void 0;e;){const s=e.next;e.next=void 0,e.flags&=-9,e=s}}let t;for(;Y;){let e=Y;for(Y=void 0;e;){const s=e.next;if(e.next=void 0,e.flags&=-9,e.flags&1)try{e.trigger()}catch(o){t||(t=o)}e=s}}if(t)throw t}function pe(t){for(let e=t.deps;e;e=e.nextDep)e.version=-1,e.prevActiveLink=e.dep.activeLink,e.dep.activeLink=e}function me(t){let e,s=t.depsTail,o=s;for(;o;){const n=o.prevDep;o.version===-1?(o===s&&(s=n),Lt(o),ve(o)):e=o,o.dep.activeLink=o.prevActiveLink,o.prevActiveLink=void 0,o=n}t.deps=e,t.depsTail=s}function fe(t){for(let e=t.deps;e;e=e.nextDep)if(e.dep.version!==e.version||e.dep.computed&&(Bt(e.dep.computed)||e.dep.version!==e.version))return!0;return!!t._dirty}function Bt(t){if(t.flags&4&&!(t.flags&16)||(t.flags&=-17,t.globalVersion===G)||(t.globalVersion=G,!t.isSSR&&t.flags&128&&(!t.deps&&!t._dirty||!fe(t))))return;t.flags|=2;const e=t.dep,s=g,o=I;g=t,I=!0;try{pe(t);const n=t.fn(t._value);(e.version===0||q(n,t._value))&&(t.flags|=128,t._value=n,e.version++)}catch(n){throw e.version++,n}finally{g=s,I=o,me(t),t.flags&=-3}}function Lt(t,e=!1){const{dep:s,prevSub:o,nextSub:n}=t;if(o&&(o.nextSub=n,t.prevSub=void 0),n&&(n.prevSub=o,t.nextSub=void 0),s.subs===t&&(s.subs=o,!o&&s.computed)){s.computed.flags&=-5;for(let a=s.computed.deps;a;a=a.nextDep)Lt(a,!0)}!e&&!--s.sc&&s.map&&s.map.delete(s.key)}function ve(t){const{prevDep:e,nextDep:s}=t;e&&(e.nextDep=s,t.prevDep=void 0),s&&(s.prevDep=e,t.nextDep=void 0)}let I=!0;const Ht=[];function St(){Ht.push(I),I=!1}function kt(){const t=Ht.pop();I=t===void 0?!0:t}let G=0;class he{constructor(e,s){this.sub=e,this.dep=s,this.version=s.version,this.nextDep=this.prevDep=this.nextSub=this.prevSub=this.prevActiveLink=void 0}}class Rt{constructor(e){this.computed=e,this.version=0,this.activeLink=void 0,this.subs=void 0,this.map=void 0,this.key=void 0,this.sc=0,this.__v_skip=!0}track(e){if(!g||!I||g===this.computed)return;let s=this.activeLink;if(s===void 0||s.sub!==g)s=this.activeLink=new he(g,this),g.deps?(s.prevDep=g.depsTail,g.depsTail.nextDep=s,g.depsTail=s):g.deps=g.depsTail=s,Ut(s);else if(s.version===-1&&(s.version=this.version,s.nextDep)){const o=s.nextDep;o.prevDep=s.prevDep,s.prevDep&&(s.prevDep.nextDep=o),s.prevDep=g.depsTail,s.nextDep=void 0,g.depsTail.nextDep=s,g.depsTail=s,g.deps===s&&(g.deps=o)}return s}trigger(e){this.version++,G++,this.notify(e)}notify(e){wt();try{for(let s=this.subs;s;s=s.prevSub)s.sub.notify()&&s.sub.dep.notify()}finally{_t()}}}function Ut(t){if(t.dep.sc++,t.sub.flags&4){const e=t.dep.computed;if(e&&!t.dep.subs){e.flags|=20;for(let o=e.deps;o;o=o.nextDep)Ut(o)}const s=t.dep.subs;s!==t&&(t.prevSub=s,s&&(s.nextSub=t)),t.dep.subs=t}}const mt=new WeakMap,L=Symbol(""),ft=Symbol(""),Q=Symbol("");function w(t,e,s){if(I&&g){let o=mt.get(t);o||mt.set(t,o=new Map);let n=o.get(s);n||(o.set(s,n=new Rt),n.map=o,n.key=s),n.track()}}function D(t,e,s,o,n,a){const r=mt.get(t);if(!r){G++;return}const i=l=>{l&&l.trigger()};if(wt(),e==="clear")r.forEach(i);else{const l=W(t),c=l&&gt(s);if(l&&s==="length"){const p=Number(o);r.forEach((d,v)=>{(v==="length"||v===Q||!tt(v)&&v>=p)&&i(d)})}else switch((s!==void 0||r.has(void 0))&&i(r.get(s)),c&&i(r.get(Q)),e){case"add":l?c&&i(r.get("length")):(i(r.get(L)),at(t)&&i(r.get(ft)));break;case"delete":l||(i(r.get(L)),at(t)&&i(r.get(ft)));break;case"set":at(t)&&i(r.get(L));break}}_t()}function V(t){const e=f(t);return e===t?e:(w(e,"iterate",Q),M(t)?e:e.map(T))}function Pt(t){return w(t=f(t),"iterate",Q),t}function O(t,e){return A(t)?Z(Jt(t)?T(e):e):T(e)}const be={__proto__:null,[Symbol.iterator](){return dt(this,Symbol.iterator,t=>O(this,t))},concat(...t){return V(this).concat(...t.map(e=>W(e)?V(e):e))},entries(){return dt(this,"entries",t=>(t[1]=O(this,t[1]),t))},every(t,e){return P(this,"every",t,e,void 0,arguments)},filter(t,e){return P(this,"filter",t,e,s=>s.map(o=>O(this,o)),arguments)},find(t,e){return P(this,"find",t,e,s=>O(this,s),arguments)},findIndex(t,e){return P(this,"findIndex",t,e,void 0,arguments)},findLast(t,e){return P(this,"findLast",t,e,s=>O(this,s),arguments)},findLastIndex(t,e){return P(this,"findLastIndex",t,e,void 0,arguments)},forEach(t,e){return P(this,"forEach",t,e,void 0,arguments)},includes(...t){return ut(this,"includes",t)},indexOf(...t){return ut(this,"indexOf",t)},join(t){return V(this).join(t)},lastIndexOf(...t){return ut(this,"lastIndexOf",t)},map(t,e){return P(this,"map",t,e,void 0,arguments)},pop(){return z(this,"pop")},push(...t){return z(this,"push",t)},reduce(t,...e){return It(this,"reduce",t,e)},reduceRight(t,...e){return It(this,"reduceRight",t,e)},shift(){return z(this,"shift")},some(t,e){return P(this,"some",t,e,void 0,arguments)},splice(...t){return z(this,"splice",t)},toReversed(){return V(this).toReversed()},toSorted(t){return V(this).toSorted(t)},toSpliced(...t){return V(this).toSpliced(...t)},unshift(...t){return z(this,"unshift",t)},values(){return dt(this,"values",t=>O(this,t))}};function dt(t,e,s){const o=Pt(t),n=o[e]();return o!==t&&!M(t)&&(n._next=n.next,n.next=()=>{const a=n._next();return a.done||(a.value=s(a.value)),a}),n}const xe=Array.prototype;function P(t,e,s,o,n,a){const r=Pt(t),i=r!==t&&!M(t),l=r[e];if(l!==xe[e]){const d=l.apply(t,a);return i?T(d):d}let c=s;r!==t&&(i?c=function(d,v){return s.call(this,O(t,d),v,t)}:s.length>2&&(c=function(d,v){return s.call(this,d,v,t)}));const p=l.call(r,c,o);return i&&n?n(p):p}function It(t,e,s,o){const n=Pt(t);let a=s;return n!==t&&(M(t)?s.length>3&&(a=function(r,i,l){return s.call(this,r,i,l,t)}):a=function(r,i,l){return s.call(this,r,O(t,i),l,t)}),n[e](a,...o)}function ut(t,e,s){const o=f(t);w(o,"iterate",Q);const n=o[e](...s);return(n===-1||n===!1)&&Ae(s[0])?(s[0]=f(s[0]),o[e](...s)):n}function z(t,e,s=[]){St(),wt();const o=f(t)[e].apply(t,s);return _t(),kt(),o}const ge=oe("__proto__,__v_isRef,__isVue"),Vt=new Set(Object.getOwnPropertyNames(Symbol).filter(t=>t!=="arguments"&&t!=="caller").map(t=>Symbol[t]).filter(tt));function ye(t){tt(t)||(t=String(t));const e=f(this);return w(e,"has",t),e.hasOwnProperty(t)}class Kt{constructor(e=!1,s=!1){this._isReadonly=e,this._isShallow=s}get(e,s,o){if(s==="__v_skip")return e.__v_skip;const n=this._isReadonly,a=this._isShallow;if(s==="__v_isReactive")return!n;if(s==="__v_isReadonly")return n;if(s==="__v_isShallow")return a;if(s==="__v_raw")return o===(n?a?Oe:Wt:a?Ee:$t).get(e)||Object.getPrototypeOf(e)===Object.getPrototypeOf(o)?e:void 0;const r=W(e);if(!n){let l;if(r&&(l=be[s]))return l;if(s==="hasOwnProperty")return ye}const i=Reflect.get(e,s,$(e)?e:o);if((tt(s)?Vt.has(s):ge(s))||(n||w(e,"get",s),a))return i;if($(i)){const l=r&&gt(s)?i:i.value;return n&&F(l)?ht(l):l}return F(i)?n?ht(i):it(i):i}}class we extends Kt{constructor(e=!1){super(!1,e)}set(e,s,o,n){let a=e[s];const r=W(e)&&gt(s);if(!this._isShallow){const c=A(a);if(!M(o)&&!A(o)&&(a=f(a),o=f(o)),!r&&$(a)&&!$(o))return c||(a.value=o),!0}const i=r?Number(s)<e.length:pt(e,s),l=Reflect.set(e,s,o,$(e)?e:n);return e===f(n)&&(i?q(o,a)&&D(e,"set",s,o):D(e,"add",s,o)),l}deleteProperty(e,s){const o=pt(e,s);e[s];const n=Reflect.deleteProperty(e,s);return n&&o&&D(e,"delete",s,void 0),n}has(e,s){const o=Reflect.has(e,s);return(!tt(s)||!Vt.has(s))&&w(e,"has",s),o}ownKeys(e){return w(e,"iterate",W(e)?"length":L),Reflect.ownKeys(e)}}class _e extends Kt{constructor(e=!1){super(!0,e)}set(e,s){return!0}deleteProperty(e,s){return!0}}const Se=new we,ke=new _e,vt=t=>t,et=t=>Reflect.getPrototypeOf(t);function Re(t,e,s){return function(...o){const n=this.__v_raw,a=f(n),r=at(a),i=t==="entries"||t===Symbol.iterator&&r,l=t==="keys"&&r,c=n[t](...o),p=s?vt:e?Z:T;return!e&&w(a,"iterate",l?ft:L),xt(Object.create(c),{next(){const{value:d,done:v}=c.next();return v?{value:d,done:v}:{value:i?[p(d[0]),p(d[1])]:p(d),done:v}}})}}function st(t){return function(...e){return t==="delete"?!1:t==="clear"?void 0:this}}function Pe(t,e){const s={get(n){const a=this.__v_raw,r=f(a),i=f(n);t||(q(n,i)&&w(r,"get",n),w(r,"get",i));const{has:l}=et(r),c=e?vt:t?Z:T;if(l.call(r,n))return c(a.get(n));if(l.call(r,i))return c(a.get(i));a!==r&&a.get(n)},get size(){const n=this.__v_raw;return!t&&w(f(n),"iterate",L),n.size},has(n){const a=this.__v_raw,r=f(a),i=f(n);return t||(q(n,i)&&w(r,"has",n),w(r,"has",i)),n===i?a.has(n):a.has(n)||a.has(i)},forEach(n,a){const r=this,i=r.__v_raw,l=f(i),c=e?vt:t?Z:T;return!t&&w(l,"iterate",L),i.forEach((p,d)=>n.call(a,c(p),c(d),r))}};return xt(s,t?{add:st("add"),set:st("set"),delete:st("delete"),clear:st("clear")}:{add(n){!e&&!M(n)&&!A(n)&&(n=f(n));const a=f(this);return et(a).has.call(a,n)||(a.add(n),D(a,"add",n,n)),this},set(n,a){!e&&!M(a)&&!A(a)&&(a=f(a));const r=f(this),{has:i,get:l}=et(r);let c=i.call(r,n);c||(n=f(n),c=i.call(r,n));const p=l.call(r,n);return r.set(n,a),c?q(a,p)&&D(r,"set",n,a):D(r,"add",n,a),this},delete(n){const a=f(this),{has:r,get:i}=et(a);let l=r.call(a,n);l||(n=f(n),l=r.call(a,n)),i&&i.call(a,n);const c=a.delete(n);return l&&D(a,"delete",n,void 0),c},clear(){const n=f(this),a=n.size!==0,r=n.clear();return a&&D(n,"clear",void 0,void 0),r}}),["keys","values","entries",Symbol.iterator].forEach(n=>{s[n]=Re(n,t,e)}),s}function qt(t,e){const s=Pe(t,e);return(o,n,a)=>n==="__v_isReactive"?!t:n==="__v_isReadonly"?t:n==="__v_raw"?o:Reflect.get(pt(s,n)&&n in o?s:o,n,a)}const Te={get:qt(!1,!1)},Ce={get:qt(!0,!1)},$t=new WeakMap,Ee=new WeakMap,Wt=new WeakMap,Oe=new WeakMap;function De(t){switch(t){case"Object":case"Array":return 1;case"Map":case"Set":case"WeakMap":case"WeakSet":return 2;default:return 0}}function Ie(t){return t.__v_skip||!Object.isExtensible(t)?0:De(de(t))}function it(t){return A(t)?t:Ft(t,!1,Se,Te,$t)}function ht(t){return Ft(t,!0,ke,Ce,Wt)}function Ft(t,e,s,o,n){if(!F(t)||t.__v_raw&&!(e&&t.__v_isReactive))return t;const a=Ie(t);if(a===0)return t;const r=n.get(t);if(r)return r;const i=new Proxy(t,a===2?o:s);return n.set(t,i),i}function Jt(t){return A(t)?Jt(t.__v_raw):!!(t&&t.__v_isReactive)}function A(t){return!!(t&&t.__v_isReadonly)}function M(t){return!!(t&&t.__v_isShallow)}function Ae(t){return t?!!t.__v_raw:!1}function f(t){const e=t&&t.__v_raw;return e?f(e):t}const T=t=>F(t)?it(t):t,Z=t=>F(t)?ht(t):t;function $(t){return t?t.__v_isRef===!0:!1}function m(t){return Me(t,!1)}function Me(t,e){return $(t)?t:new Ne(t,e)}class Ne{constructor(e,s){this.dep=new Rt,this.__v_isRef=!0,this.__v_isShallow=!1,this._rawValue=s?e:f(e),this._value=s?e:T(e),this.__v_isShallow=s}get value(){return this.dep.track(),this._value}set value(e){const s=this._rawValue,o=this.__v_isShallow||M(e)||A(e);e=o?e:f(e),q(e,s)&&(this._rawValue=e,this._value=o?e:T(e),this.dep.trigger())}}class je{constructor(e,s,o){this.fn=e,this.setter=s,this._value=void 0,this.dep=new Rt(this),this.__v_isRef=!0,this.deps=void 0,this.depsTail=void 0,this.flags=16,this.globalVersion=G-1,this.next=void 0,this.effect=this,this.__v_isReadonly=!s,this.isSSR=o}notify(){if(this.flags|=16,!(this.flags&8)&&g!==this)return ue(this,!0),!0}get value(){const e=this.dep.track();return Bt(this),e&&(e.version=this.dep.version),this._value}set value(e){this.setter&&this.setter(e)}}function Be(t,e,s=!1){let o,n;return K(t)?o=t:(o=t.get,n=t.set),new je(o,n,s)}/**
* @vue/runtime-core v3.5.27
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/function zt(t,e,s,o){try{return o?t(...o):t()}catch(n){Xt(n,e,s)}}function Yt(t,e,s,o){if(K(t)){const n=zt(t,e,s,o);return n&&le(n)&&n.catch(a=>{Xt(a,e,s)}),n}if(W(t)){const n=[];for(let a=0;a<t.length;a++)n.push(Yt(t[a],e,s,o));return n}}function Xt(t,e,s,o=!0){const n=e?e.vnode:null,{errorHandler:a,throwUnhandledErrorInProduction:r}=e&&e.appContext.config||ae;if(e){let i=e.parent;const l=e.proxy,c=`https://vuejs.org/error-reference/#runtime-${s}`;for(;i;){const p=i.ec;if(p){for(let d=0;d<p.length;d++)if(p[d](t,l,c)===!1)return}i=i.parent}if(a){St(),zt(a,null,10,[t,l,c]),kt();return}}Le(t,s,n,o,r)}function Le(t,e,s,o=!0,n=!1){if(n)throw t;console.error(t)}function N(t,e){return K(t)?xt({name:t.name},e,{setup:t}):t}yt().requestIdleCallback;yt().cancelIdleCallback;function He(t,e,s=lt,o=!1){if(s){const n=s[t]||(s[t]=[]),a=e.__weh||(e.__weh=(...r)=>{St();const i=Ve(s),l=Yt(e,s,t,r);return i(),kt(),l});return o?n.unshift(a):n.push(a),a}}const Ue=t=>(e,s=lt)=>{(!Tt||t==="sp")&&He(t,(...o)=>e(...o),s)},U=Ue("m");let lt=null,bt;{const t=yt(),e=(s,o)=>{let n;return(n=t[s])||(n=t[s]=[]),n.push(o),a=>{n.length>1?n.forEach(r=>r(a)):n[0](a)}};bt=e("__VUE_INSTANCE_SETTERS__",s=>lt=s),e("__VUE_SSR_SETTERS__",s=>Tt=s)}const Ve=t=>{const e=lt;return bt(t),t.scope.on(),()=>{t.scope.off(),bt(e)}};let Tt=!1;const nt=(t,e)=>Be(t,e,Tt),At="rc-dashboard-2026-02-14-f",rt=[{label:"Today",value:"today"},{label:"This Week",value:"this_week"},{label:"This Month",value:"this_month"},{label:"Last Month",value:"last_month"},{label:"Last 30 Days",value:"last_30_days"},{label:"This Year",value:"this_year"},{label:"All Time",value:"all_time"}],Ke=[{label:"All Statuses",value:"all"},{label:"Pending",value:"pending"},{label:"Paid",value:"paid"},{label:"Voided",value:"voided"},{label:"Cancelled",value:"cancelled"}],Gt=[{label:"Percentage",value:"percentage"},{label:"Fixed Amount",value:"fixed"},{label:"On-The-House",value:"on_the_house"}];function qe(){const t=document.querySelector('meta[name="csrf-token"]');return(t==null?void 0:t.content)||""}async function ct(t,e,s){const n=qe(),a=Ot(e);return await new Promise((r,i)=>{const l=new XMLHttpRequest;l.open(t,a,!0),l.withCredentials=!0,l.timeout=12e3,l.setRequestHeader("Accept","application/json"),l.setRequestHeader("X-Requested-With","XMLHttpRequest"),n&&l.setRequestHeader("X-CSRF-TOKEN",n),s!==void 0&&l.setRequestHeader("Content-Type","application/json"),l.onload=()=>{const c=l.responseText||"";let p=c;try{p=c?JSON.parse(c):{}}catch{i(new Error(`Non-JSON response for ${a}`));return}if(l.status<200||l.status>=300){const d=(p==null?void 0:p.message)||(typeof p=="string"?p.slice(0,160):null)||`Request failed (${l.status}) for ${a}`;i(new Error(d));return}r(p)},l.onerror=()=>i(new Error(`Network error for ${a}`)),l.ontimeout=()=>i(new Error(`Request timeout for ${a}`)),l.onabort=()=>i(new Error(`Request aborted for ${a}`));try{l.send(s!==void 0?JSON.stringify(s):null)}catch(c){i(new Error((c==null?void 0:c.message)||`Network error for ${a}`))}})}function _(t){return ct("GET",t)}function H(t,e){return ct("POST",t,e)}function $e(t,e){return ct("PUT",t,e)}function We(t){return ct("DELETE",t)}function B(t){if(typeof t!="string")return t;try{return JSON.parse(t)}catch{return t}}function Fe(t){return!t||typeof t!="object"||Array.isArray(t)?!1:typeof t.status=="number"?!0:"body"in t||"response"in t||"headers"in t||"config"in t||"statusCode"in t||"statusText"in t||"ok"in t}function Mt(t){let e=B(t);for(let s=0;s<8;s++){if(e==null)return e;if(Fe(e)){if(e.body!==void 0){const o=B(e.body);if(o!==e){e=o;continue}}if(e.response!==void 0){const o=B(e.response);if(o!==e){e=o;continue}}if(e.data!==void 0){const o=B(e.data);if(o!==e){e=o;continue}}}if(typeof e=="object"&&"data"in e&&e.data!==void 0){const o=B(e.data);if(o===e)break;e=o;continue}break}return e}function S(t,e){var o,n;const s=[t,t==null?void 0:t.data,t==null?void 0:t.body,t==null?void 0:t.response,(o=t==null?void 0:t.response)==null?void 0:o.data,(n=t==null?void 0:t.response)==null?void 0:n.body];for(const a of s){const r=Mt(a);if(r!=null){if(typeof r=="object"&&!Array.isArray(r)&&"status"in r&&"data"in r){const i=Mt(r.data);if(i!=null)return i}return r}}return e}function Ct(t,e,s){var o;return(t==null?void 0:t[e])!==void 0?t[e]:((o=t==null?void 0:t.data)==null?void 0:o[e])!==void 0?t.data[e]:s}function k(t){var o,n,a,r,i,l,c,p,d,v,C,j;const e=B(t);if(((e==null?void 0:e.status)??((o=e==null?void 0:e.data)==null?void 0:o.status)??((n=e==null?void 0:e.body)==null?void 0:n.status)??((r=(a=e==null?void 0:e.body)==null?void 0:a.data)==null?void 0:r.status)??((i=e==null?void 0:e.response)==null?void 0:i.status)??((c=(l=e==null?void 0:e.response)==null?void 0:l.data)==null?void 0:c.status)??((d=(p=e==null?void 0:e.response)==null?void 0:p.body)==null?void 0:d.status)??((j=(C=(v=e==null?void 0:e.response)==null?void 0:v.body)==null?void 0:C.data)==null?void 0:j.status))==="error")throw new Error(h(e,"Request failed."))}function h(t,e){return(t==null?void 0:t.message)||e}function E(t){const e=B(t);if(e==null)return e;if(Array.isArray(e))return{type:"array",length:e.length};if(typeof e!="object")return{type:typeof e,value:String(e).slice(0,120)};const s={type:"object",keys:Object.keys(e).slice(0,12)};if(e.status!==void 0&&(s.status=e.status),e.data!==void 0){const o=e.data;s.dataType=Array.isArray(o)?"array":typeof o,Array.isArray(o)&&(s.dataLength=o.length),o&&typeof o=="object"&&!Array.isArray(o)&&(s.dataKeys=Object.keys(o).slice(0,12))}return s}function Et(t){return t==="paid"?"bg-green-100 text-green-700":t==="pending"?"bg-amber-100 text-amber-700":t==="voided"?"bg-red-100 text-red-700":"bg-slate-100 text-slate-700"}function Qt(t){const e=Gt.find(s=>s.value===t);return e?e.label:t}function Zt(){return window.renCommissionsRoutes||{}}function Ot(t){try{const e=new URL(t,window.location.origin);if(e.origin===window.location.origin||/^https?:\/\//i.test(t))return`${e.pathname}${e.search}${e.hash}`}catch{}return t}function Je(t,e){var s;return Ot(((s=Zt().web)==null?void 0:s[t])||e)}function b(t,e){var s;return Ot(((s=Zt().api)==null?void 0:s[t])||e)}function J(t,e){return Object.entries(e).reduce((s,[o,n])=>{const a=`__${o.toUpperCase()}__`;return s.split(a).join(encodeURIComponent(String(n)))},t)}function R(t,e){const s=new URLSearchParams;Object.entries(e).forEach(([n,a])=>{a!=null&&a!==""&&s.set(n,String(a))});const o=s.toString();return o?`${t}${t.includes("?")?"&":"?"}${o}`:t}async function ot(t,e,s){return await Promise.race([t,new Promise((o,n)=>{window.setTimeout(()=>n(new Error(`Timeout: ${s}`)),e)})])}async function te(t,e){try{const o=(await new Promise((n,a)=>{Popup.show(nsPromptPopup,{title:t,message:e,type:"textarea",input:"",resolve:n,reject:a})})||"").trim();return o===""?(nsSnackBar.info("Reason is required."),null):o}catch{return null}}async function ee(t,e){return await new Promise(s=>{Popup.show(nsConfirmPopup,{title:t,message:e,onAction:o=>s(!!o)})})}async function se(t){var r;const e=((r=document.querySelector('meta[name="csrf-token"]'))==null?void 0:r.content)||"",s=await fetch(b("commission_export","/api/rencommissions/commissions/export"),{method:"POST",credentials:"include",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":e},body:JSON.stringify({entries:t})});if(!s.ok)throw new Error("Export failed.");const o=await s.blob(),n=window.URL.createObjectURL(o),a=document.createElement("a");a.href=n,a.download="commissions-export.csv",document.body.appendChild(a),a.click(),a.remove(),window.URL.revokeObjectURL(n)}const ze=N({name:"NsRcSummaryCards",props:{summary:{type:Object,required:!0},loading:{type:Boolean,required:!0}},template:`
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
            <div class="ns-box rounded-lg p-4">
                <div class="text-xs uppercase tracking-wider text-secondary mb-1">Total</div>
                <div v-if="loading" class="animate-pulse h-7 bg-input-background rounded"></div>
                <div v-else class="text-2xl font-bold">{{ summary.total?.formatted || '0' }}</div>
                <div class="text-xs text-secondary mt-1">{{ summary.total?.count || 0 }} records</div>
            </div>
            <div class="ns-box rounded-lg p-4">
                <div class="text-xs uppercase tracking-wider text-secondary mb-1">Pending</div>
                <div v-if="loading" class="animate-pulse h-7 bg-input-background rounded"></div>
                <div v-else class="text-2xl font-bold text-warning-tertiary">{{ summary.pending?.formatted || '0' }}</div>
                <div class="text-xs text-secondary mt-1">{{ summary.pending?.count || 0 }} pending</div>
            </div>
            <div class="ns-box rounded-lg p-4">
                <div class="text-xs uppercase tracking-wider text-secondary mb-1">Paid</div>
                <div v-if="loading" class="animate-pulse h-7 bg-input-background rounded"></div>
                <div v-else class="text-2xl font-bold text-success-tertiary">{{ summary.paid?.formatted || '0' }}</div>
                <div class="text-xs text-secondary mt-1">{{ summary.paid?.count || 0 }} paid</div>
            </div>
            <div class="ns-box rounded-lg p-4">
                <div class="text-xs uppercase tracking-wider text-secondary mb-1">Average</div>
                <div v-if="loading" class="animate-pulse h-7 bg-input-background rounded"></div>
                <div v-else class="text-2xl font-bold text-info-tertiary">{{ summary.average?.formatted || '0' }}</div>
                <div class="text-xs text-secondary mt-1">per line item</div>
            </div>
        </div>
    `}),Ye=N({name:"NsRencommissionsDashboard",components:{SummaryCards:ze},setup(){const t=m(!0),e=m({}),s=m("this_month"),o=m({total:{amount:0,count:0,formatted:"0"},pending:{amount:0,count:0,formatted:"0"},paid:{amount:0,count:0,formatted:"0"},average:{amount:0,formatted:"0"},period:"this_month"}),n=m([]),a=m([]),r=m([]),i=nt(()=>Math.max(1,...r.value.map(u=>u.total))),l=nt(()=>{var u;return((u=rt.find(y=>y.value===s.value))==null?void 0:u.label)||"This Month"}),c=nt(()=>n.value.filter(u=>u.status==="pending").slice(0,5)),p=nt(()=>n.value.filter(u=>u.status==="paid").slice(0,6)),d=m(!1),v=async()=>{t.value=!0,e.value={phase:"load_started",at:new Date().toISOString(),period:s.value,loading:t.value,build:At},window.setTimeout(()=>{e.value.watchdog12s={at:new Date().toISOString(),loading:t.value,phase:e.value.phase,build:At}},12e3);try{try{const u=await ot(_(R(b("dashboard_summary","/api/rencommissions/dashboard/summary"),{period:s.value})),12e3,"summary");e.value.summaryResponse=E(u),k(u),o.value=S(u,o.value),e.value.summaryData=E(o.value)}catch(u){e.value.summaryError=h(u,"Unable to load summary."),nsSnackBar.error(h(u,"Unable to load summary."))}try{const u=await ot(_(R(b("dashboard_recent","/api/rencommissions/dashboard/recent"),{limit:8})),12e3,"recent");e.value.recentResponse=E(u),k(u),n.value=S(u,[]),e.value.recentData=E(n.value)}catch{}try{const u=await ot(_(R(b("dashboard_leaderboard","/api/rencommissions/dashboard/leaderboard"),{period:s.value,limit:6})),12e3,"leaderboard");e.value.leaderboardResponse=E(u),k(u),a.value=S(u,[]),e.value.leaderboardData=E(a.value)}catch{}try{const u=await ot(_(R(b("dashboard_trends","/api/rencommissions/dashboard/trends"),{period:s.value,group_by:"day"})),12e3,"trends");e.value.trendsResponse=E(u),k(u),r.value=S(u,[]),e.value.trendsData=E(r.value)}catch{}}catch(u){e.value.loadError=h(u,"Dashboard load crashed."),nsSnackBar.error(h(u,"Dashboard load crashed."))}finally{t.value=!1,e.value.phase="load_settled",e.value.loading=t.value,e.value.at=new Date().toISOString()}},C=async u=>{try{await H(J(b("commission_mark_paid","/api/rencommissions/commissions/__ID__/mark-paid"),{id:u}),{}),nsSnackBar.success("Commission marked as paid."),await v()}catch(y){nsSnackBar.error(h(y,"Unable to mark paid."))}},j=async u=>{const y=await te("Void Commission","Why are you voiding this commission?");if(y)try{await H(J(b("commission_void","/api/rencommissions/commissions/__ID__/void"),{id:u}),{reason:y}),nsSnackBar.success("Commission voided."),await v()}catch(ne){nsSnackBar.error(h(ne,"Unable to void commission."))}},x=()=>{d.value||(d.value=!0,v())};return U(()=>{x()}),x(),{loading:t,period:s,summary:o,recent:n,leaderboard:a,trends:r,maxTrend:i,selectedPeriodLabel:l,pendingPreview:c,paymentPreview:p,webRoute:Je,PERIOD_OPTIONS:rt,statusClass:Et,markPaid:C,voidWithReason:j,load:v,debug:e}},template:`
        <div style="display:flex;flex-direction:column;row-gap:1rem;">
            <div class="flex flex-wrap items-center justify-end gap-2">
                <a class="ns-button" :href="webRoute('commissions', '/dashboard/rencommissions/commissions')">View All</a>
                <a class="ns-button" :href="webRoute('types', '/dashboard/rencommissions/types')">Commission Types</a>
                <select class="ns-select px-2 py-1 text-xs" v-model="period" @change="load()">
                    <option v-for="option in PERIOD_OPTIONS" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
                <button class="ns-button" @click="load()">Refresh</button>
            </div>

            <summary-cards :summary="summary" :loading="loading"></summary-cards>

            <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));column-gap:1rem;row-gap:1rem;align-items:start;">
                <div class="ns-box rounded-lg" style="grid-column: span 2 / span 2;">
                    <div class="p-4 border-b border-box-edge flex items-center justify-between">
                        <h3 class="font-semibold text-lg">Recent Commissions</h3>
                        <a :href="webRoute('payment_history', '/dashboard/rencommissions/payment-history')" class="text-sm text-info-tertiary hover:underline">View All</a>
                    </div>
                    <div class="p-3 space-y-2">
                        <div v-if="loading" v-for="n in 5" :key="'recent-loading-' + n" class="h-10 rounded bg-input-background animate-pulse"></div>
                        <div v-else-if="recent.length === 0" class="p-8 text-center text-secondary">No commissions available.</div>
                        <div v-else v-for="row in recent" :key="row.id" class="rounded border border-box-edge p-3 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="font-medium">{{ row.product_name }}</div>
                                <div class="text-xs text-secondary">{{ row.order_code }} | {{ row.created_at_human }}</div>
                            </div>
                            <div class="text-sm text-secondary">{{ row.earner_name }}</div>
                            <div class="font-semibold">{{ row.formatted_amount }}</div>
                            <div>
                                <span :class="['px-2 py-1 rounded text-xs font-medium capitalize', statusClass(row.status)]">{{ row.status }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button v-if="row.status === 'pending'" class="ns-button success px-2 py-1 text-xs" @click="markPaid(row.id)">Mark Paid</button>
                                <button v-if="row.status === 'pending'" class="ns-button error px-2 py-1 text-xs" @click="voidWithReason(row.id)">Void</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ns-box rounded-lg">
                    <div class="p-4 border-b border-box-edge flex items-center justify-between">
                        <h3 class="font-semibold text-lg">Top Earners</h3>
                        <span class="text-xs text-info-tertiary">{{ selectedPeriodLabel }}</span>
                    </div>
                    <div class="p-3 space-y-2">
                        <div v-if="loading" v-for="n in 5" :key="'leaders-loading-' + n" class="h-10 rounded bg-input-background animate-pulse"></div>
                        <div v-else-if="leaderboard.length === 0" class="p-6 text-center text-secondary">No ranking data.</div>
                        <div v-else v-for="row in leaderboard" :key="row.earner_id" class="rounded border border-box-edge p-3 flex items-center gap-3">
                            <div class="w-7 h-7 rounded-full bg-input-background flex items-center justify-center text-xs font-semibold">{{ row.rank }}</div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium truncate">{{ row.earner_name }}</div>
                                <div class="text-xs text-secondary">{{ row.commission_count }} commissions</div>
                            </div>
                            <div class="font-semibold text-success-tertiary shrink-0">{{ row.formatted_amount }}</div>
                        </div>
                    </div>
                </div>

                <div class="ns-box rounded-lg">
                    <div class="p-4 border-b border-box-edge flex items-center justify-between">
                        <h3 class="font-semibold text-lg">Pending Payouts</h3>
                        <a :href="webRoute('pending_payouts', '/dashboard/rencommissions/pending-payouts')" class="text-xs text-info-tertiary hover:underline">View All</a>
                    </div>
                    <div class="p-3 space-y-2">
                        <div v-if="loading" v-for="n in 5" :key="'payout-loading-' + n" class="h-10 rounded bg-input-background animate-pulse"></div>
                        <div v-else-if="pendingPreview.length === 0" class="p-6 text-center text-secondary">No pending payouts.</div>
                        <div v-else v-for="row in pendingPreview" :key="'pending-' + row.id" class="rounded border border-box-edge p-3 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-medium truncate">{{ row.earner_name }}</div>
                                <div class="text-xs text-secondary truncate">{{ row.product_name }}</div>
                            </div>
                            <div class="font-semibold text-warning-tertiary shrink-0">{{ row.formatted_amount }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ns-box rounded-lg">
                <div class="p-4 border-b border-box-edge flex items-center justify-between">
                    <h3 class="font-semibold text-lg">Payment History</h3>
                    <a :href="webRoute('payment_history', '/dashboard/rencommissions/payment-history')" class="text-xs text-info-tertiary hover:underline">View All</a>
                </div>
                <div class="p-3 space-y-2">
                    <div v-if="loading" v-for="n in 5" :key="'history-loading-' + n" class="h-10 rounded bg-input-background animate-pulse"></div>
                    <div v-else-if="paymentPreview.length === 0" class="p-6 text-center text-secondary">No payment history.</div>
                    <div v-else v-for="row in paymentPreview" :key="'history-' + row.id" class="rounded border border-box-edge p-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="font-medium truncate">{{ row.product_name }}</div>
                            <div class="text-xs text-secondary">{{ row.order_code }} | {{ row.created_at_human }}</div>
                        </div>
                        <div class="text-sm text-secondary shrink-0">{{ row.earner_name }}</div>
                        <div class="font-semibold text-success-tertiary shrink-0">{{ row.formatted_amount }}</div>
                    </div>
                </div>
            </div>

            <div class="ns-box rounded-lg">
                <div class="p-4 border-b border-box-edge flex items-center justify-between">
                    <h3 class="font-semibold text-lg">Commission Trends</h3>
                    <span class="text-xs text-info-tertiary">{{ selectedPeriodLabel }}</span>
                </div>
                <div class="p-4">
                    <div v-if="!loading && trends.length === 0" class="h-44 flex items-center justify-center text-secondary">No trend data.</div>
                    <div v-else class="h-44 flex items-end gap-1 overflow-x-auto">
                        <div v-for="row in trends" :key="row.date" class="w-4 shrink-0">
                            <div class="bg-info-tertiary rounded-t transition-all duration-300"
                                :style="{ height: ((row.total / maxTrend) * 100) + '%', minHeight: row.total > 0 ? '3px' : '0' }"
                                :title="row.date + ' => ' + row.total"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ns-box rounded-lg">
                <div class="p-4 border-b border-box-edge">
                    <h3 class="font-semibold text-sm text-info-tertiary">Dashboard Debug</h3>
                </div>
                <pre class="p-4 text-xs overflow-auto">{{ JSON.stringify(debug, null, 2) }}</pre>
            </div>

        </div>
    `}),Xe=N({name:"NsRencommissionsAllCommissionsComponent",setup(){const t=m(!1),e=m(1),s=m(1),o=m(0),n=m([]),a=m(!1),r=m([]),i=it({status:"all",period:"this_month",search:""}),l=async()=>{t.value=!0;try{const x=new URLSearchParams({page:String(e.value),limit:"20",status:i.status,period:i.period,search:i.search}),u=await _(R(b("dashboard_commissions","/api/rencommissions/dashboard/commissions"),Object.fromEntries(x.entries())));k(u),n.value=S(u,[]);const y=Ct(u,"pagination",{});s.value=(y==null?void 0:y.last_page)||1,o.value=(y==null?void 0:y.total)||0,r.value=[]}catch(x){nsSnackBar.error(h(x,"Failed to load commissions."))}finally{t.value=!1}},c=()=>{if(r.value.length===n.value.length){r.value=[];return}r.value=n.value.map(x=>x.id)},p=async x=>{try{await H(J(b("commission_mark_paid","/api/rencommissions/commissions/__ID__/mark-paid"),{id:x}),{}),nsSnackBar.success("Commission marked as paid."),await l()}catch(u){nsSnackBar.error(h(u,"Unable to mark paid."))}},d=async x=>{const u=await te("Void Commission","Please provide a void reason.");if(u)try{await H(J(b("commission_void","/api/rencommissions/commissions/__ID__/void"),{id:x}),{reason:u}),nsSnackBar.success("Commission voided."),await l()}catch(y){nsSnackBar.error(h(y,"Unable to void commission."))}},v=async()=>{if(r.value.length===0){nsSnackBar.info("Select at least one commission.");return}try{await H(b("commission_bulk_action","/api/rencommissions/commissions/bulk-action"),{action:"bulk_mark_paid",entries:r.value.map(x=>({id:x}))}),nsSnackBar.success("Bulk mark paid completed."),await l()}catch(x){nsSnackBar.error(h(x,"Bulk action failed."))}},C=async()=>{try{const x=r.value.length>0?r.value:n.value.map(u=>u.id);await se(x.map(u=>({id:u}))),nsSnackBar.success("CSV exported.")}catch(x){nsSnackBar.error(h(x,"Export failed."))}},j=()=>{a.value||(a.value=!0,l())};return U(()=>{j()}),j(),{loading:t,page:e,lastPage:s,total:o,rows:n,selected:r,filters:i,STATUS_OPTIONS:Ke,PERIOD_OPTIONS:rt,statusClass:Et,methodLabel:Qt,load:l,toggleAll:c,markPaid:p,voidOne:d,bulkMarkPaid:v,exportSelected:C}},template:`
        <div class="ns-box rounded-lg">
            <div class="p-4 border-b border-box-edge flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-semibold text-lg">All Commissions</h3>
                <div class="flex items-center gap-2 flex-wrap">
                    <input class="ns-input px-2 py-1" placeholder="Search order/product/earner" v-model="filters.search" />
                    <select class="ns-select px-2 py-1" v-model="filters.status">
                        <option v-for="option in STATUS_OPTIONS" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                    <select class="ns-select px-2 py-1" v-model="filters.period">
                        <option v-for="option in PERIOD_OPTIONS" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                    <button class="ns-button" @click="page = 1; load()">Apply</button>
                    <button class="ns-button success" @click="bulkMarkPaid">Bulk Mark Paid</button>
                    <button class="ns-button info" @click="exportSelected">Export CSV</button>
                </div>
            </div>

            <div class="overflow-auto">
                <table class="w-full text-sm">
                    <thead class="bg-input-background">
                        <tr>
                            <th class="p-3 text-left"><input type="checkbox" @change="toggleAll" :checked="rows.length > 0 && selected.length === rows.length" /></th>
                            <th class="p-3 text-left">Date</th>
                            <th class="p-3 text-left">Order</th>
                            <th class="p-3 text-left">Product</th>
                            <th class="p-3 text-left">Earner</th>
                            <th class="p-3 text-left">Type</th>
                            <th class="p-3 text-right">Amount</th>
                            <th class="p-3 text-center">Status</th>
                            <th class="p-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!loading && rows.length === 0">
                            <td colspan="9" class="p-8 text-center text-secondary">No commissions found.</td>
                        </tr>
                        <tr v-for="row in rows" :key="row.id" class="border-b border-box-edge">
                            <td class="p-3"><input type="checkbox" :value="row.id" v-model="selected" /></td>
                            <td class="p-3">{{ row.created_at_human }}</td>
                            <td class="p-3">{{ row.order_code }}</td>
                            <td class="p-3">{{ row.product_name }}</td>
                            <td class="p-3">{{ row.earner_name }}</td>
                            <td class="p-3">{{ methodLabel(row.commission_type) }}</td>
                            <td class="p-3 text-right font-semibold">{{ row.formatted_amount }}</td>
                            <td class="p-3 text-center">
                                <span :class="['px-2 py-1 rounded text-xs capitalize', statusClass(row.status)]">{{ row.status }}</span>
                            </td>
                            <td class="p-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <button v-if="row.status === 'pending'" class="ns-button success px-2 py-1 text-xs" @click="markPaid(row.id)">Mark Paid</button>
                                    <button v-if="row.status === 'pending'" class="ns-button error px-2 py-1 text-xs" @click="voidOne(row.id)">Void</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-box-edge flex items-center justify-between">
                <button class="ns-button" :disabled="page <= 1" @click="page = Math.max(1, page - 1); load()">Previous</button>
                <div class="text-sm text-secondary">Page {{ page }} of {{ lastPage }} ({{ total }} total)</div>
                <button class="ns-button" :disabled="page >= lastPage" @click="page = Math.min(lastPage, page + 1); load()">Next</button>
            </div>
        </div>
    `}),Ge=N({name:"NsRencommissionsTypesManagementComponent",setup(){const t=m(!1),e=m([]),s=m(null),o=m(!1),n=it({name:"",description:"",calculation_method:"percentage",default_value:0,min_value:null,max_value:null,is_active:!0,priority:0}),a=()=>{s.value=null,n.name="",n.description="",n.calculation_method="percentage",n.default_value=0,n.min_value=null,n.max_value=null,n.is_active=!0,n.priority=0},r=async()=>{t.value=!0;try{const d=await _(b("dashboard_types","/api/rencommissions/dashboard/types"));k(d),e.value=S(d,[])}catch(d){nsSnackBar.error(h(d,"Failed to load commission types."))}finally{t.value=!1}},i=d=>{s.value=d.id,n.name=d.name,n.description=d.description||"",n.calculation_method=d.calculation_method,n.default_value=d.default_value||0,n.min_value=d.min_value,n.max_value=d.max_value,n.is_active=!!d.is_active,n.priority=d.priority||0},l=async()=>{if(!n.name.trim()){nsSnackBar.info("Type name is required.");return}try{s.value?(await $e(J(b("dashboard_type_update","/api/rencommissions/dashboard/types/__ID__"),{id:s.value}),n),nsSnackBar.success("Commission type updated.")):(await H(b("dashboard_types","/api/rencommissions/dashboard/types"),n),nsSnackBar.success("Commission type created.")),a(),await r()}catch(d){nsSnackBar.error(h(d,"Unable to save commission type."))}},c=async d=>{if(await ee("Delete Commission Type",`Delete "${d.name}"? This action cannot be undone.`))try{await We(J(b("dashboard_type_delete","/api/rencommissions/dashboard/types/__ID__"),{id:d.id})),nsSnackBar.success("Commission type deleted."),await r()}catch(C){nsSnackBar.error(h(C,"Unable to delete commission type."))}},p=()=>{o.value||(o.value=!0,r())};return U(()=>{p()}),p(),{loading:t,rows:e,form:n,editingId:s,METHOD_OPTIONS:Gt,methodLabel:Qt,load:r,edit:i,save:l,remove:c,resetForm:a}},template:`
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 ns-box rounded-lg">
                <div class="p-4 border-b border-box-edge flex items-center justify-between">
                    <h3 class="font-semibold text-lg">Commission Types</h3>
                    <button class="ns-button" @click="load">Refresh</button>
                </div>
                <div class="overflow-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-input-background">
                            <tr>
                                <th class="p-3 text-left">Name</th>
                                <th class="p-3 text-left">Method</th>
                                <th class="p-3 text-right">Default</th>
                                <th class="p-3 text-center">Active</th>
                                <th class="p-3 text-right">Priority</th>
                                <th class="p-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!loading && rows.length === 0">
                                <td colspan="6" class="p-8 text-center text-secondary">No commission types found.</td>
                            </tr>
                            <tr v-for="row in rows" :key="row.id" class="border-b border-box-edge">
                                <td class="p-3">
                                    <div class="font-medium">{{ row.name }}</div>
                                    <div class="text-xs text-secondary">{{ row.description }}</div>
                                </td>
                                <td class="p-3">{{ methodLabel(row.calculation_method) }}</td>
                                <td class="p-3 text-right">{{ row.default_value }}</td>
                                <td class="p-3 text-center">
                                    <span :class="row.is_active ? 'text-success-tertiary' : 'text-secondary'">{{ row.is_active ? 'Yes' : 'No' }}</span>
                                </td>
                                <td class="p-3 text-right">{{ row.priority }}</td>
                                <td class="p-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button class="ns-button px-2 py-1 text-xs" @click="edit(row)">Edit</button>
                                        <button v-if="!row.is_system" class="ns-button error px-2 py-1 text-xs" @click="remove(row)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="ns-box rounded-lg">
                <div class="p-4 border-b border-box-edge">
                    <h3 class="font-semibold text-lg">{{ editingId ? 'Edit Type' : 'Create Type' }}</h3>
                </div>
                <div class="p-4 space-y-3">
                    <div>
                        <label class="text-xs text-secondary">Name</label>
                        <input class="ns-input w-full mt-1" v-model="form.name" />
                    </div>
                    <div>
                        <label class="text-xs text-secondary">Description</label>
                        <textarea class="ns-input w-full mt-1" rows="2" v-model="form.description"></textarea>
                    </div>
                    <div>
                        <label class="text-xs text-secondary">Method</label>
                        <select class="ns-select w-full mt-1" v-model="form.calculation_method">
                            <option v-for="option in METHOD_OPTIONS" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-secondary">Default</label>
                            <input type="number" class="ns-input w-full mt-1" v-model.number="form.default_value" />
                        </div>
                        <div>
                            <label class="text-xs text-secondary">Priority</label>
                            <input type="number" class="ns-input w-full mt-1" v-model.number="form.priority" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-secondary">Min</label>
                            <input type="number" class="ns-input w-full mt-1" v-model.number="form.min_value" />
                        </div>
                        <div>
                            <label class="text-xs text-secondary">Max</label>
                            <input type="number" class="ns-input w-full mt-1" v-model.number="form.max_value" />
                        </div>
                    </div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.is_active" />
                        <span class="text-sm">Active</span>
                    </label>
                    <div class="flex items-center gap-2 pt-2">
                        <button class="ns-button info" @click="save">{{ editingId ? 'Update' : 'Create' }}</button>
                        <button class="ns-button" @click="resetForm">Reset</button>
                    </div>
                </div>
            </div>
        </div>
    `}),Qe=N({name:"NsRencommissionsStaffEarningsComponent",setup(){const t=m(!1),e=m("this_month"),s=m([]),o=m(!1),n=async()=>{t.value=!0;try{const r=await _(R(b("dashboard_staff_earnings","/api/rencommissions/dashboard/staff-earnings"),{period:e.value}));k(r),s.value=S(r,[])}catch(r){nsSnackBar.error(h(r,"Failed to load earnings."))}finally{t.value=!1}},a=()=>{o.value||(o.value=!0,n())};return U(()=>{a()}),a(),{loading:t,period:e,rows:s,load:n,PERIOD_OPTIONS:rt}},template:`
        <div class="ns-box rounded-lg">
            <div class="p-4 border-b border-box-edge flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-semibold text-lg">Staff Earnings</h3>
                <div class="flex items-center gap-2">
                    <button v-for="option in PERIOD_OPTIONS"
                        :key="option.value"
                        class="ns-button px-3 py-1 text-xs"
                        :class="{ info: period === option.value }"
                        @click="period = option.value; load()">
                        {{ option.label }}
                    </button>
                </div>
            </div>
            <div class="overflow-auto">
                <table class="w-full text-sm">
                    <thead class="bg-input-background">
                        <tr>
                            <th class="text-left p-3">Staff</th>
                            <th class="text-right p-3">Total</th>
                            <th class="text-right p-3">Pending</th>
                            <th class="text-right p-3">Paid</th>
                            <th class="text-right p-3">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!loading && rows.length === 0">
                            <td colspan="5" class="p-6 text-center text-secondary">No data for this period.</td>
                        </tr>
                        <tr v-for="row in rows" :key="row.earner_id" class="border-b border-box-edge">
                            <td class="p-3">
                                <div class="font-medium">{{ row.earner_name }}</div>
                                <div class="text-xs text-secondary">{{ row.earner_email }}</div>
                            </td>
                            <td class="p-3 text-right font-semibold">{{ row.formatted_total }}</td>
                            <td class="p-3 text-right text-warning-tertiary">{{ row.formatted_pending }}</td>
                            <td class="p-3 text-right text-success-tertiary">{{ row.formatted_paid }}</td>
                            <td class="p-3 text-right">{{ row.commission_count }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    `}),Ze=N({name:"NsRencommissionsPendingPayoutsComponent",setup(){const t=m(!1),e=m([]),s=m([]),o=m(!1),n=async()=>{t.value=!0;try{const i=await _(R(b("dashboard_staff_earnings","/api/rencommissions/dashboard/staff-earnings"),{period:"all_time"}));k(i);const l=S(i,[]);e.value=l.filter(c=>c.pending>0),s.value=[]}catch(i){nsSnackBar.error(h(i,"Failed to load pending payouts."))}finally{t.value=!1}},a=async()=>{const i=e.value.filter(c=>s.value.includes(c.earner_id)).map(c=>({earner_id:c.earner_id}));if(i.length===0){nsSnackBar.info("Select at least one earner first.");return}if(await ee("Bulk Mark Paid","Mark all pending commissions for selected earners as paid?"))try{await H(b("commission_bulk_action","/api/rencommissions/commissions/bulk-action"),{action:"bulk_mark_paid_by_earner",entries:i}),nsSnackBar.success("Bulk payout processed."),await n()}catch(c){nsSnackBar.error(h(c,"Bulk payout failed."))}},r=()=>{o.value||(o.value=!0,n())};return U(()=>{r()}),r(),{loading:t,rows:e,selectedIds:s,load:n,markSelectedPaid:a}},template:`
        <div class="ns-box rounded-lg">
            <div class="p-4 border-b border-box-edge flex items-center justify-between">
                <h3 class="font-semibold text-lg">Pending Payouts</h3>
                <div class="flex items-center gap-2">
                    <button class="ns-button" @click="load">Refresh</button>
                    <button class="ns-button success" @click="markSelectedPaid">Bulk Mark Paid</button>
                </div>
            </div>
            <div class="divide-y divide-box-edge">
                <div v-if="!loading && rows.length === 0" class="p-8 text-center text-secondary">No pending payouts.</div>
                <label v-for="row in rows" :key="row.earner_id" class="p-4 flex items-center justify-between gap-3 cursor-pointer hover:bg-input-background">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" :value="row.earner_id" v-model="selectedIds" />
                        <div>
                            <div class="font-medium">{{ row.earner_name }}</div>
                            <div class="text-xs text-secondary">{{ row.commission_count }} commissions</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xl font-semibold text-warning-tertiary">{{ row.formatted_pending }}</div>
                    </div>
                </label>
            </div>
        </div>
    `}),ts=N({name:"NsRencommissionsPaymentHistoryComponent",setup(){const t=m(!1),e=m(1),s=m(1),o=m(0),n=m([]),a=m(!1),r=async()=>{t.value=!0;try{const c=await _(R(b("dashboard_recent","/api/rencommissions/dashboard/recent"),{limit:20,page:e.value,status:"paid"}));k(c),n.value=S(c,[]);const p=Ct(c,"pagination",{});s.value=(p==null?void 0:p.last_page)||1,o.value=(p==null?void 0:p.total)||0}catch(c){nsSnackBar.error(h(c,"Failed to load payment history."))}finally{t.value=!1}},i=async()=>{try{await se(n.value.map(c=>({id:c.id}))),nsSnackBar.success("CSV exported.")}catch(c){nsSnackBar.error(h(c,"Failed to export CSV."))}},l=()=>{a.value||(a.value=!0,r())};return U(()=>{l()}),l(),{loading:t,page:e,lastPage:s,total:o,rows:n,load:r,exportCurrent:i}},template:`
        <div class="ns-box rounded-lg">
            <div class="p-4 border-b border-box-edge flex items-center justify-between">
                <h3 class="font-semibold text-lg">Payment History</h3>
                <div class="flex items-center gap-2">
                    <button class="ns-button" @click="load">Refresh</button>
                    <button class="ns-button info" @click="exportCurrent">Export CSV</button>
                </div>
            </div>
            <div class="overflow-auto">
                <table class="w-full text-sm">
                    <thead class="bg-input-background">
                        <tr>
                            <th class="text-left p-3">Date</th>
                            <th class="text-left p-3">Earner</th>
                            <th class="text-left p-3">Order</th>
                            <th class="text-left p-3">Product</th>
                            <th class="text-right p-3">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!loading && rows.length === 0">
                            <td colspan="5" class="p-6 text-center text-secondary">No payment history yet.</td>
                        </tr>
                        <tr v-for="row in rows" :key="row.id" class="border-b border-box-edge">
                            <td class="p-3">{{ row.created_at_human }}</td>
                            <td class="p-3">{{ row.earner_name }}</td>
                            <td class="p-3">{{ row.order_code }}</td>
                            <td class="p-3">{{ row.product_name }}</td>
                            <td class="p-3 text-right font-semibold text-success-tertiary">{{ row.formatted_amount }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-box-edge flex items-center justify-between">
                <button class="ns-button" :disabled="page <= 1" @click="page = Math.max(1, page - 1); load()">Previous</button>
                <div class="text-sm text-secondary">Page {{ page }} of {{ lastPage }} ({{ total }} total)</div>
                <button class="ns-button" :disabled="page >= lastPage" @click="page = Math.min(lastPage, page + 1); load()">Next</button>
            </div>
        </div>
    `}),es=N({name:"NsRencommissionsMyCommissionsComponent",setup(){const t=m(!1),e=m(1),s=m(1),o=m([]),n=m(null),a=async()=>{t.value=!0;try{const[r,i]=await Promise.all([_(R(b("my_commissions","/api/rencommissions/my-commissions"),{page:e.value,per_page:20})),_(R(b("my_summary","/api/rencommissions/my-summary"),{period:"this_month"}))]);k(r),k(i),o.value=S(r,[]);const l=Ct(r,"pagination",{});s.value=(l==null?void 0:l.last_page)||1,n.value=S(i,null)}catch(r){nsSnackBar.error(h(r,"Failed to load your commissions."))}finally{t.value=!1}};return U(a),{loading:t,page:e,lastPage:s,rows:o,summary:n,load:a,statusClass:Et}},template:`
        <div class="space-y-6">
            <div v-if="summary" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="ns-box rounded-lg p-4">
                    <div class="text-secondary text-xs uppercase">Total</div>
                    <div class="text-2xl font-semibold mt-1">{{ summary.total?.formatted || '0' }}</div>
                </div>
                <div class="ns-box rounded-lg p-4">
                    <div class="text-secondary text-xs uppercase">Pending</div>
                    <div class="text-2xl font-semibold mt-1 text-warning-tertiary">{{ summary.pending?.formatted || '0' }}</div>
                </div>
                <div class="ns-box rounded-lg p-4">
                    <div class="text-secondary text-xs uppercase">Paid</div>
                    <div class="text-2xl font-semibold mt-1 text-success-tertiary">{{ summary.paid?.formatted || '0' }}</div>
                </div>
            </div>

            <div class="ns-box rounded-lg">
                <div class="p-4 border-b border-box-edge flex items-center justify-between">
                    <h3 class="font-semibold text-lg">My Commissions</h3>
                    <button class="ns-button" @click="load">Refresh</button>
                </div>
                <div class="overflow-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-input-background">
                            <tr>
                                <th class="text-left p-3">Date</th>
                                <th class="text-left p-3">Order</th>
                                <th class="text-left p-3">Product</th>
                                <th class="text-right p-3">Amount</th>
                                <th class="text-center p-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!loading && rows.length === 0">
                                <td colspan="5" class="p-6 text-center text-secondary">No commissions found.</td>
                            </tr>
                            <tr v-for="row in rows" :key="row.id" class="border-b border-box-edge">
                                <td class="p-3">{{ new Date(row.created_at).toLocaleDateString() }}</td>
                                <td class="p-3">{{ row.order?.code || 'N/A' }}</td>
                                <td class="p-3">{{ row.product?.name || 'Unknown' }}</td>
                                <td class="p-3 text-right font-semibold">{{ row.total_commission }}</td>
                                <td class="p-3 text-center">
                                    <span :class="['px-2 py-1 rounded text-xs capitalize', statusClass(row.status)]">{{ row.status }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-box-edge flex items-center justify-between">
                    <button class="ns-button" :disabled="page <= 1" @click="page = Math.max(1, page - 1); load()">Previous</button>
                    <div class="text-sm text-secondary">Page {{ page }} of {{ lastPage }}</div>
                    <button class="ns-button" :disabled="page >= lastPage" @click="page = Math.min(lastPage, page + 1); load()">Next</button>
                </div>
            </div>
        </div>
    `});typeof nsExtraComponents<"u"&&(nsExtraComponents["ns-rencommissions-dashboard"]=Ye,nsExtraComponents["ns-rencommissions-all-commissions-component"]=Xe,nsExtraComponents["ns-rencommissions-types-management-component"]=Ge,nsExtraComponents["ns-rencommissions-staff-earnings-component"]=Qe,nsExtraComponents["ns-rencommissions-pending-payouts-component"]=Ze,nsExtraComponents["ns-rencommissions-payment-history-component"]=ts,nsExtraComponents["ns-rencommissions-my-commissions-component"]=es);
