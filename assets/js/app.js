window.APP_URL=window.APP_URL||(()=>{const marker='/auth/manage-admin/';const path=location.pathname.includes(marker)?location.pathname.split(marker)[0]:location.pathname.replace(/\/[^/]*$/,'');return location.origin+path.replace(/\/$/,'')})();
if(!document.querySelector('link[rel="manifest"]')){const manifest=document.createElement('link');manifest.rel='manifest';manifest.href=APP_URL+'/manifest.json';document.head.appendChild(manifest)}
if(!document.querySelector('link[rel="apple-touch-icon"]')){const appleIcon=document.createElement('link');appleIcon.rel='apple-touch-icon';appleIcon.href=APP_URL+'/assets/icons/icon-192.png';document.head.appendChild(appleIcon)}
[['apple-mobile-web-app-capable','yes'],['apple-mobile-web-app-status-bar-style','black-translucent'],['apple-mobile-web-app-title','HF Ops']].forEach(([name,content])=>{if(!document.querySelector(`meta[name="${name}"]`)){const meta=document.createElement('meta');meta.name=name;meta.content=content;document.head.appendChild(meta)}});
const $=(s,c=document)=>c.querySelector(s),$$=(s,c=document)=>[...c.querySelectorAll(s)];
const escapeHtml=value=>String(value??'').replace(/[&<>'"]/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));
const icons={menu:'☰',search:'⌕',moon:'◐',bell:'♢',grid:'▦',pulse:'∿',receipt:'▤',inbox:'⇩',minus:'−',trash:'♲',swap:'⇄',boxes:'▥',package:'⬡',tag:'◇',pin:'⌖',layers:'▱',truck:'▰',users:'♟',chart:'⌁',shield:'◆',history:'↶',settings:'⚙',user:'●',logout:'↪',plus:'＋',sliders:'≋',arrow:'→',fingerprint:'◎',cash:'◉',card:'▰',download:'⇩',printer:'▣'};
$$('[data-icon]').forEach(el=>el.textContent=icons[el.dataset.icon]||'•');
const sidebar=$('#sidebar');
const sidebarBackdrop=document.createElement('div');sidebarBackdrop.className='sidebar-backdrop';document.body.appendChild(sidebarBackdrop);
function setSidebar(open){if(!sidebar)return;if(innerWidth<1100){sidebar.classList.toggle('open',open);sidebarBackdrop.classList.toggle('open',open);document.body.classList.toggle('sidebar-open',open)}else{sidebar.classList.toggle('collapsed',!open);localStorage.sidebar=sidebar.classList.contains('collapsed')?'1':'0'}}
$('[data-sidebar]')?.addEventListener('click',()=>{if(innerWidth<1100)setSidebar(!sidebar.classList.contains('open'));else setSidebar(sidebar.classList.contains('collapsed'))});
sidebarBackdrop.addEventListener('click',()=>setSidebar(false));
$$('.sidebar a').forEach(a=>a.addEventListener('click',()=>{if(innerWidth<1100)setSidebar(false)}));
addEventListener('resize',()=>{if(innerWidth>=1100){sidebarBackdrop.classList.remove('open');document.body.classList.remove('sidebar-open')}else sidebar?.classList.remove('collapsed')});
if(localStorage.sidebar==='1'&&innerWidth>=1100)$('#sidebar')?.classList.add('collapsed');
$('[data-theme-toggle]')?.addEventListener('click',()=>{let t=document.documentElement.dataset.theme==='dark'?'light':'dark';document.documentElement.dataset.theme=t;localStorage.theme=t});
$('[data-password]')?.addEventListener('click',e=>{const i=e.currentTarget.parentElement.querySelector('input');i.type=i.type==='password'?'text':'password';e.currentTarget.textContent=i.type==='text'?'Hide':'Show'});
$('[data-quick-open]')?.addEventListener('click',()=>{navigator.vibrate?.(18);$('.quick-sheet').classList.add('open');$('.sheet-backdrop').classList.add('open')});
$('[data-sheet-close]')?.addEventListener('click',()=>{$('.quick-sheet').classList.remove('open');$('.sheet-backdrop').classList.remove('open')});
const command=$('.command');function toggleCommand(show){if(!command)return;command.hidden=!show;if(show)setTimeout(()=>$('input',command).focus(),10)}
$('.search-trigger')?.addEventListener('click',()=>toggleCommand(true));command?.addEventListener('click',e=>{if(e.target===command)toggleCommand(false)});document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key==='k'){e.preventDefault();toggleCommand(true)}if(e.key==='Escape')toggleCommand(false)});
let searchTimer;const commandInput=command?.querySelector('input');commandInput?.addEventListener('input',()=>{clearTimeout(searchTimer);const q=commandInput.value.trim();const results=$('.command-results',command);if(q.length<2){results.innerHTML='<p>Type at least two characters to search.</p>';return}searchTimer=setTimeout(async()=>{results.innerHTML='<p>Searching…</p>';try{const response=await fetch(`${APP_URL}/api/search.php?q=${encodeURIComponent(q)}`,{headers:{Accept:'application/json'}});const data=await response.json();results.innerHTML=data.results?.length?data.results.map(item=>`<a class="activity" href="${APP_URL}/products.php?action=edit&id=${Number(item.id)}"><span class="activity-icon">⌕</span><div><b>${escapeHtml(item.name)}</b><small>${escapeHtml(item.sku)} · Product</small></div><span>→</span></a>`).join(''):'<p>No matching records found.</p>'}catch(e){results.innerHTML='<p>Search is temporarily unavailable.</p>'}},220)});
$$('.toast').forEach(t=>setTimeout(()=>t.remove(),4500));
if('serviceWorker' in navigator&&window.APP_URL)window.addEventListener('load',()=>navigator.serviceWorker.register(APP_URL+'/service-worker.js').catch(()=>{}));
$$('[data-auto-submit]').forEach(el=>el.addEventListener('change',()=>el.form.submit()));
$$('[data-confirm]').forEach(el=>el.addEventListener('click',e=>{if(!confirm(el.dataset.confirm))e.preventDefault()}));
$$('[data-table-search]').forEach(input=>input.addEventListener('input',()=>{$$('tbody tr',input.closest('.table-card')).forEach(row=>row.hidden=!row.textContent.toLowerCase().includes(input.value.toLowerCase()))}));

// Mobile PWA installation invitation. Browsers require the final prompt to follow a user gesture.
let deferredInstallPrompt=null;
const isStandalone=matchMedia('(display-mode: standalone)').matches||window.navigator.standalone===true;
const isMobileDevice=matchMedia('(max-width: 820px)').matches||/Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
const isIOS=/iPhone|iPad|iPod/i.test(navigator.userAgent);
const installDismissedAt=Number(localStorage.getItem('hfInstallDismissedAt')||0);
const canInvite=()=>!isStandalone&&isMobileDevice&&(Date.now()-installDismissedAt>3*24*60*60*1000);
let installInvite=null;
function hideInstallInvite(remember=false){installInvite?.classList.remove('show');if(remember)localStorage.setItem('hfInstallDismissedAt',String(Date.now()))}
function showInstallInvite(mode='android'){
  if(!canInvite()||installInvite)return;
  installInvite=document.createElement('section');installInvite.className='install-invite';installInvite.setAttribute('role','dialog');installInvite.setAttribute('aria-label','Install HESH and FRN Operations');
  const iosText='Install this secure workspace on your iPhone for a full-screen app experience.';
  const androidText='Install this workspace for faster access, full-screen display and an app icon on your phone.';
  installInvite.innerHTML=`<div class="install-card"><button class="install-close" type="button" aria-label="Close">×</button><div class="install-mark">HF</div><div class="install-copy"><h3>Install HESH / FRN App</h3><p>${mode==='ios'?iosText:androidText}</p></div>${mode==='ios'?'<div class="install-steps">Tap the <b>Share</b> button in Safari, then choose <b>Add to Home Screen</b> and tap <b>Add</b>.</div>':''}<div class="install-actions"><button class="install-later" type="button">Not now</button><button class="install-primary" type="button">${mode==='ios'?'Got it':'Install App'}</button></div></div>`;
  document.body.appendChild(installInvite);requestAnimationFrame(()=>requestAnimationFrame(()=>installInvite.classList.add('show')));
  $('.install-close',installInvite).addEventListener('click',()=>hideInstallInvite(true));
  $('.install-later',installInvite).addEventListener('click',()=>hideInstallInvite(true));
  $('.install-primary',installInvite).addEventListener('click',async()=>{if(mode==='ios'){hideInstallInvite(true);return}if(!deferredInstallPrompt)return;deferredInstallPrompt.prompt();const choice=await deferredInstallPrompt.userChoice;deferredInstallPrompt=null;if(choice.outcome==='accepted')hideInstallInvite(false);else hideInstallInvite(true)});
}
addEventListener('beforeinstallprompt',event=>{event.preventDefault();deferredInstallPrompt=event;if(canInvite())setTimeout(()=>showInstallInvite('android'),900)});
addEventListener('appinstalled',()=>{localStorage.removeItem('hfInstallDismissedAt');hideInstallInvite(false)});
if(isIOS&&canInvite())addEventListener('load',()=>setTimeout(()=>showInstallInvite('ios'),1300));

// Passwordless fingerprint / Face ID access through WebAuthn passkeys.
const fromBase64Url=value=>{const normalized=value.replace(/-/g,'+').replace(/_/g,'/');const binary=atob(normalized+'==='.slice((normalized.length+3)%4));return Uint8Array.from(binary,c=>c.charCodeAt(0))};
const toBase64Url=value=>{let binary='';new Uint8Array(value).forEach(byte=>binary+=String.fromCharCode(byte));return btoa(binary).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'')};
const passkeyMessage=(text,error=false)=>{const box=$('[data-passkey-message]');if(!box)return;box.hidden=false;box.textContent=text;box.classList.toggle('error',error);box.classList.toggle('success',!error)};
async function jsonRequest(url,options={}){const response=await fetch(url,{headers:{Accept:'application/json','Content-Type':'application/json',...(options.headers||{})},...options});const data=await response.json().catch(()=>({message:'The server returned an invalid response.'}));if(!response.ok||!data.ok)throw new Error(data.message||'Request failed.');return data}
async function startPasskeyLogin(){
  const button=$('[data-passkey-login]');if(!button||button.disabled)return;
  if(!window.PublicKeyCredential||!window.isSecureContext){passkeyMessage('Fingerprint / Face ID requires HTTPS, or localhost while testing.',true);return}
  button.disabled=true;passkeyMessage('Confirm your fingerprint, Face ID or device screen lock…');
  try{
    const options=await jsonRequest(`${APP_URL}/api/passkey/login-options.php`);const publicKey=options.publicKey;
    publicKey.challenge=fromBase64Url(publicKey.challenge);publicKey.allowCredentials=(publicKey.allowCredentials||[]).map(item=>({...item,id:fromBase64Url(item.id)}));
    const credential=await navigator.credentials.get({publicKey});
    const payload={credentialId:toBase64Url(credential.rawId),clientDataJSON:toBase64Url(credential.response.clientDataJSON),authenticatorData:toBase64Url(credential.response.authenticatorData),signature:toBase64Url(credential.response.signature)};
    const result=await jsonRequest(`${APP_URL}/api/passkey/login.php`,{method:'POST',body:JSON.stringify(payload)});location.href=result.redirect;
  }catch(error){passkeyMessage(error.name==='NotAllowedError'?'Biometric check was cancelled. Tap the button when ready.':error.message,true);button.disabled=false}
}
$('[data-passkey-login]')?.addEventListener('click',startPasskeyLogin);
if($('[data-passkey-login]:not([disabled])'))addEventListener('load',()=>setTimeout(startPasskeyLogin,550),{once:true});

async function registerPasskey(){
  const card=$('[data-passkey-setup]'),button=$('[data-passkey-register]');if(!card||!button)return;
  if(!window.PublicKeyCredential||!window.isSecureContext){passkeyMessage('Open this website through HTTPS to activate fingerprint / Face ID.',true);return}
  button.disabled=true;passkeyMessage('Your device will now ask for fingerprint, Face ID or screen lock confirmation…');
  try{
    const options=await jsonRequest(`${APP_URL}/api/passkey/register-options.php`);const publicKey=options.publicKey;
    publicKey.challenge=fromBase64Url(publicKey.challenge);publicKey.user.id=fromBase64Url(publicKey.user.id);publicKey.excludeCredentials=(publicKey.excludeCredentials||[]).map(item=>({...item,id:fromBase64Url(item.id)}));
    const credential=await navigator.credentials.create({publicKey});const response=credential.response;
    const payload={csrf:card.dataset.csrf,credentialId:toBase64Url(credential.rawId),clientDataJSON:toBase64Url(response.clientDataJSON),attestationObject:toBase64Url(response.attestationObject),transports:response.getTransports?.()||[],deviceName:`${navigator.userAgentData?.platform||navigator.platform||'This device'} biometric`};
    const result=await jsonRequest(`${APP_URL}/api/passkey/register.php`,{method:'POST',body:JSON.stringify(payload)});passkeyMessage(result.message);setTimeout(()=>location.href=`${APP_URL}/profile.php`,900);
  }catch(error){passkeyMessage(error.name==='NotAllowedError'?'Device registration was cancelled. Tap Add this device when ready.':error.message,true);button.disabled=false}
}
$('[data-passkey-register]')?.addEventListener('click',registerPasskey);
if($('[data-passkey-setup="auto"]'))addEventListener('load',()=>setTimeout(registerPasskey,650),{once:true});

// Fast product picker for the simplified wastage entry screen.
const wastagePicker=$('[data-wastage-picker]');
if(wastagePicker){
  const branch=$('[name="branch_id"]',wastagePicker),brand=$('[name="brand_id"]',wastagePicker),search=$('[data-product-search]',wastagePicker),grid=$('[data-product-grid]',wastagePicker),productId=$('[name="product_id"]',wastagePicker),quantity=$('[name="quantity"]',wastagePicker),selection=$('[data-selection]',wastagePicker),submit=$('[data-wastage-submit]',wastagePicker);
  let products=[],timer;
  function clearSelection(){productId.value='';quantity.value='';quantity.removeAttribute('max');selection.innerHTML='<b>Select a product</b><span>Available quantity and unit will appear here.</span>';submit.disabled=true;$$('.product-pick',grid).forEach(card=>card.classList.remove('selected'))}
  function selectProduct(product,card){if(product.available<=0)return;productId.value=product.id;quantity.max=String(product.available);quantity.step=['pc','box','ctn','tray','pkt','btl'].includes(product.symbol)?'1':'0.001';selection.innerHTML=`<b>${escapeHtml(product.name)}</b><span>Available <strong>${Number(product.available).toLocaleString(undefined,{maximumFractionDigits:3})} ${escapeHtml(product.symbol)}</strong></span>`;submit.disabled=false;$$('.product-pick',grid).forEach(item=>item.classList.toggle('selected',item===card));quantity.focus()}
  async function loadProducts(){clearSelection();if(!branch.value||!brand.value){grid.innerHTML='<div class="empty compact">Choose branch and brand first.</div>';return}grid.innerHTML='<div class="product-loading">Loading products…</div>';try{const query=new URLSearchParams({branch_id:branch.value,brand_id:brand.value,q:search.value.trim()});const data=await jsonRequest(`${APP_URL}/api/stock-products.php?${query}`);products=data.products;grid.innerHTML=products.length?'':'<div class="empty compact">No products found. Add a product first.</div>';products.forEach(product=>{const card=document.createElement('button');card.type='button';card.className='product-pick';if(product.available<=0)card.classList.add('out');card.innerHTML=`<span class="product-avatar">${escapeHtml(product.name.slice(0,2).toUpperCase())}</span><span><b>${escapeHtml(product.name)}</b><small>${Number(product.available).toLocaleString(undefined,{maximumFractionDigits:3})} ${escapeHtml(product.symbol)} available</small></span><em>${escapeHtml(product.symbol)}</em>`;card.addEventListener('click',()=>selectProduct(product,card));grid.appendChild(card)})}catch(error){grid.innerHTML=`<div class="form-alert">${escapeHtml(error.message)}</div>`}}
  [branch,brand].forEach(el=>el.addEventListener('change',loadProducts));search.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(loadProducts,220)});loadProducts();
  wastagePicker.addEventListener('submit',event=>{const amount=Number(quantity.value),max=Number(quantity.max);if(!productId.value||amount<=0||amount>max){event.preventDefault();passkeyMessage('Choose a product and enter a wastage quantity not higher than available stock.',true)}});
}

// Raw-item receiving helper: show the selected unit and calculated receiving total.
const receivingForm=$('[data-receiving-form]');
if(receivingForm){
  const item=$('[name="product_id"]',receivingForm),qty=$('[name="quantity"]',receivingForm),cost=$('[name="unit_cost"]',receivingForm),unit=$('[data-receive-unit]',receivingForm),total=$('[data-receive-total]',receivingForm);
  function updateReceiving(){unit.textContent=item.selectedOptions[0]?.dataset.unit||'unit';total.textContent=`QAR ${(Number(qty.value||0)*Number(cost.value||0)).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}`}
  [item,qty,cost].forEach(field=>field.addEventListener('input',updateReceiving));item.addEventListener('change',updateReceiving);updateReceiving();
}

// End-of-day sales reconciliation. The channel split must equal the entered total.
const salesForm=$('[data-sales-summary]');
if(salesForm){
  const totalInput=$('[name="total_amount"]',salesForm),channelNames=['cash_amount','card_amount','talabat_amount','keeta_amount','snoonu_amount'],channelInputs=channelNames.map(name=>$(`[name="${name}"]`,salesForm)),channelTotal=$('[data-channel-total]',salesForm),difference=$('[data-sales-difference]',salesForm),status=$('[data-sales-status]',salesForm),box=$('[data-sales-reconcile]',salesForm),submit=$('[data-sales-submit]',salesForm);
  const qar=value=>`QAR ${value.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}`;
  function reconcileSales(){const total=Number(totalInput.value||0),channels=channelInputs.reduce((sum,input)=>sum+Number(input.value||0),0),diff=Math.round((total-channels)*100)/100;channelTotal.textContent=qar(channels);difference.textContent=qar(Math.abs(diff));const balanced=total>0&&Math.abs(diff)<=.01;box.classList.toggle('balanced',balanced);box.classList.toggle('unbalanced',!balanced&&total>0);status.textContent=balanced?'Balanced — ready to save':(diff>0?`${qar(diff)} still needs to be assigned`:`Channel amounts exceed total by ${qar(Math.abs(diff))}`);submit.disabled=!balanced}
  [totalInput,...channelInputs].forEach(input=>input.addEventListener('input',reconcileSales));reconcileSales();
}
