'use strict';
/**
 * R7-H isolated Dev diagnostic — execute ONLY with controlled QA BACS fixture.
 * No real merchant credentials, no external images, no screenshots, no secrets logged.
 * Requires node/playwright-core and an approved local Chrome installation.
 * Example: R7_ALLOW_OFFLINE_QA_SUBMIT=yes node scripts/uat/r7_checkout_block_diagnostic.cjs
 * The caller must enable and restore the disposable Dev fixture using a shell trap.
 */
if (process.env.R7_ALLOW_OFFLINE_QA_SUBMIT !== 'yes') {
  console.error('BLOCKED: explicit R7_ALLOW_OFFLINE_QA_SUBMIT=yes is required.');
  process.exit(2);
}
const chromium = require('playwright-core').chromium;
const origin = 'https://dev.idollmate.com';
const browserPath = process.env.R7_CHROME_BINARY;
if (!browserPath) {
  console.error('BLOCKED: R7_CHROME_BINARY must point to an authorized local Chrome executable.');
  process.exit(3);
}
const safePath = url => {
  try { return new URL(url).pathname.slice(0, 140); } catch { return 'invalid'; }
};
const sleep = ms => new Promise(r => setTimeout(r, ms));
(async () => {
  const browser = await chromium.launch({headless:true, executablePath:browserPath});
  const events = [];
  try {
    const ctx = await browser.newContext({viewport:{width:1366,height:900},acceptDownloads:false,
      extraHTTPHeaders:{'Cache-Control':'no-cache','Pragma':'no-cache'}});
    await ctx.route(/\.(?:jpe?g|png|svg|webp|gif|avif)(?:\?.*)?$/i, route => route.abort());
    const page = await ctx.newPage();
    page.on('pageerror', err => events.push({kind:'js_error',message:String(err.message).slice(0,200)}));
    page.on('request', req => {
      const path = safePath(req.url());
      if (req.method()==='POST' || path.includes('/wc/store/'))
        events.push({kind:'request',method:req.method(),path});
    });
    page.on('requestfailed', req => {
      events.push({kind:'network_failed',path:safePath(req.url()),reason:req.failure()?.errorText||'unknown'});
    });
    page.on('response', async resp => {
      const path=safePath(resp.url());
      if (!path.includes('/wc/store/') && !resp.url().includes('rest_route=')) return;
      const item={kind:'response',path,status:resp.status()};
      if (resp.status()>=400 || path.endsWith('/batch')) {
        const d=await resp.json().catch(()=>({}));
        item.error_code=String(d.code||'').slice(0,90);
        item.error_message=String(d.message||'').slice(0,120);
        if (Array.isArray(d.responses)) {
          item.batch=d.responses.slice(0,12).map(x=>({
            status:x.status,
            code:String(x.body?.code||'').slice(0,80),
            error:String(x.body?.message||'').slice(0,130)
          }));
        }
      }
      events.push(item);
    });
    const product=await page.goto(origin+'/product/idollmate-r7-variable-qa-not-for-sale/?r7qa=20261029f1',
      {waitUntil:'domcontentloaded',timeout:35000});
    if (product.status()!==200 || page.url().indexOf(origin)!==0) throw Error('Product URL not valid Dev QA page');
    await page.selectOption('select[name=attribute_material]','TPE');
    await page.waitForFunction(()=>!!document.querySelector('input[name=tmcp_checkbox_0_0].tcenabled'),
      null,{timeout:18000});
    await page.locator('input[name=tmcp_checkbox_0_0]').check({force:true});
    await page.locator('button.single_add_to_cart_button').click();
    await sleep(1900);
    const cartApiCount=async()=>page.evaluate(async()=>{
      try{
        const r=await fetch('/wp-json/wc/store/v1/cart',{credentials:'same-origin',cache:'no-store'});
        const j=await r.json();
        return {status:r.status(),items_count:j.items_count??null,items_length:Array.isArray(j.items)?j.items.length:null};
      }catch(e){return {error:String(e).slice(0,120)}}
    });
    const afterAdd=await page.evaluate(()=>({path:location.pathname,
      notice:[...document.querySelectorAll('.woocommerce-message,.woocommerce-error')].map(e=>e.innerText.slice(0,140)).slice(0,4),
      htmlItem:document.body.innerText.includes('iDollmate R7 Variable QA')}));
    const sessionCookieNames=(await ctx.cookies(origin)).map(x=>x.name).filter(x=>/woocommerce|wp_woo|session|wordpress/i.test(x));
    console.log('R7_AFTER_ADD_TO_CART',JSON.stringify({...afterAdd,store:await cartApiCount(),sessionCookieNames}));
    // Session gate: native Woo cart must persist real variation + add-on
    // BEFORE testing checkout; an empty cart redirects Checkout to /cart/.
    const cartResp=await page.goto(origin+'/cart/?r7qa=20261029f1',{waitUntil:'domcontentloaded',timeout:35000});
    await page.waitForFunction(()=>document.body.innerText.includes('iDollmate R7 Variable QA') &&
      document.body.innerText.includes('$1,148.00'),null,{timeout:15000}).catch(()=>null);
    const cartGate=await page.evaluate(()=>({
      path:location.pathname,empty:/your cart is currently empty/i.test(document.body.innerText),
      item:document.body.innerText.includes('iDollmate R7 Variable QA'),
      addOn:document.body.innerText.includes('Premium head'),
      amount:document.body.innerText.includes('$1,148.00')
    }));
    console.log('R7_CART_SESSION_GATE',JSON.stringify({http:cartResp.status(),...cartGate,
      store:await cartApiCount(),sessionCookieNames:(await ctx.cookies(origin)).map(x=>x.name).filter(x=>/woocommerce|wp_woo|session|wordpress/i.test(x)),
      cacheHeader:cartResp.headers()['x-litespeed-cache']||'',cfCache:cartResp.headers()['cf-cache-status']||''}));
    if(cartResp.status()!==200||cartGate.empty||!cartGate.item||!cartGate.addOn||!cartGate.amount)
      throw Error('QA cart session not persisted; checkout must not be submitted');
    const ch=await page.goto(origin+'/checkout/?r7qa=20261029f1',{waitUntil:'domcontentloaded',timeout:35000});
    if (ch.status()!==200) throw Error('Checkout HTTP '+ch.status());
    const gatewayReady=await page.getByText('R7 TEST ONLY - DO NOT PAY').waitFor({timeout:20000}).then(()=>true).catch(()=>false);
    if(!gatewayReady){
      const diagnostic=await page.evaluate(()=>({title:document.title,path:location.pathname,
        excerpt:document.body.innerText.slice(0,280),loaded:document.readyState,
        wcb:!!document.querySelector('.wc-block-checkout')})).catch(()=>({unavailable:true}));
      console.log('R7_GATEWAY_NOT_READY',JSON.stringify({status:ch.status(),
        xCache:ch.headers()['x-litespeed-cache']||'',...diagnostic}));
      throw Error('QA BACS gateway did not become ready; checkout submission intentionally blocked');
    }
    const fill=async(id,value)=>{
      const field=page.locator('#'+id);
      if(!await field.count()) throw Error('Missing Checkout Block input '+id);
      await field.fill(value);
    };
    await fill('email','r7-browser-order@example.invalid');
    await page.locator('#shipping-country').selectOption('US');
    await fill('shipping-first_name','R7');
    await fill('shipping-last_name','Browser QA');
    await fill('shipping-address_1','123 Example Street');
    await fill('shipping-city','Los Angeles');
    await page.locator('#shipping-state').selectOption('CA');
    await fill('shipping-postcode','90001');
    await fill('shipping-phone','2025550132');
    // Browser-realistic focus completion. Woo Blocks may flush address changes
    // when the last field loses focus, re-rendering the Place Order button.
    // Settle that state BEFORE clicking instead of relying on an initial lost click.
    await page.locator('#shipping-phone').blur();
    await sleep(1700);
    // Woo Blocks asynchronously calculates shipping after address edits.
    // Wait for the expected test-only total; never place an order on stale totals.
    const pricingReady=await page.waitForFunction(
      () => document.body.innerText.includes('$1,348.00'),
      null,{timeout:16000}
    ).then(()=>true).catch(()=>false);
    console.log('R7_PRICING_READY',pricingReady);
    const terms=page.locator('.wp-block-woocommerce-checkout-terms-block input[type=checkbox], .wc-block-checkout__terms input[type=checkbox]');
    if (await terms.count() && !await terms.first().isChecked()) await terms.first().check();
    const state=()=>page.evaluate(()=>{
      const data=window.wp?.data;
      let checkout={},payment={};
      try{
        const c=data?.select('wc/store/checkout');
        checkout={status:c?.getCheckoutStatus?.(), errors:c?.getCheckoutError?.()?.message||null, hasError:c?.hasError?.()};
      }catch(e){checkout={read_error:String(e).slice(0,100)};}
      try{
        const p=data?.select('wc/store/payment');
        payment={status:p?.getPaymentStatus?.(), active:p?.getActivePaymentMethod?.()};
      }catch(e){payment={read_error:String(e).slice(0,100)};}
      const val=data?.select('wc/store/validation');
      const valErrors=val?.getValidationErrors?.()||{};
      const hasValidationErrors=val?.hasValidationErrors?.();
      const btn=document.querySelector('.wc-block-components-checkout-place-order-button');
      const button={disabled:btn?.disabled,ariaDisabled:btn?.getAttribute('aria-disabled'),text:btn?.textContent?.trim(),
        reactOnClick: !!(btn && Object.keys(btn).some(k=>k.startsWith('__reactProps') && typeof btn[k]?.onClick==='function')),
        reactPropsKeys:btn?Object.keys(btn).filter(k=>k.startsWith('__reactProps')).length:0};
      const boxes=[...document.querySelectorAll('input[type=checkbox]')].map(el=>({
        id:el.id,checked:el.checked,required:el.required,
        label:(document.querySelector('label[for="'+el.id+'"]')?.textContent||el.closest('label')?.textContent||'').trim().slice(0,90)
      }));
      const missing=[...document.querySelectorAll('input:required, select:required')]
        .filter(x=>!x.checkValidity()).map(x=>x.id||x.name).slice(0,15);
      let cartTotals={},customer={};
      try {
        const c=data?.select('wc/store/cart');
        cartTotals={raw:c?.getCartTotals?.(),shipping: c?.getShippingRates?.()?.map?.(r=>r.name||r.package_name).slice(0,3)};
        const u=c?.getCartData?.();
        customer={country:u?.shipping_address?.country,state:u?.shipping_address?.state};
      } catch(e) { cartTotals={read_error:String(e).slice(0,110)}; }
      const prices=(document.body.innerText.match(/\$\s*[\d,.]+/g)||[]).slice(-14);
      const overview=(document.querySelector('.wc-block-components-order-summary,.wc-block-components-totals-wrapper')?.innerText||'').slice(-600);
      return {url:location.pathname,checkout,payment,validationErrors:valErrors,hasValidationErrors,button,boxes,missing,cartTotals,customer,prices,overview,has_QA_gateway:document.body.innerText.includes('R7 TEST ONLY - DO NOT PAY'),
        has_expected_total:document.body.innerText.includes('$1,348.00')};
    });
    // Woo Cart/Checkout Blocks submit async customer changes using a debounced
    // Store API batch. Avoid clicking a button whose React node is about to be
    // replaced during that pending update (the observed first-click no-op).
    await page.waitForLoadState('networkidle',{timeout:12000}).catch(()=>null);
    await sleep(800);
    const submitButton=page.locator('.wc-block-components-checkout-place-order-button');
    await submitButton.scrollIntoViewIfNeeded();
    const focusState=()=>page.evaluate(()=>{
      const button=document.querySelector('.wc-block-components-checkout-place-order-button');
      const a=document.activeElement;
      return {hasButton:!!button,disabled:button?.disabled,
        reactClick:!!button&&Object.keys(button).some(k=>k.startsWith('__reactProps')&&typeof button[k]?.onClick==='function'),
        focusIsButton:a===button,activeTag:a?.tagName||'',
        activeId:a?.id||'',activeClass:typeof a?.className==='string'?a.className.slice(0,110):'',
        buttonsOnPage:document.querySelectorAll('.wc-block-components-checkout-place-order-button').length};
    });
    await submitButton.focus();
    const immediate=await focusState();
    await sleep(600);
    let stability=await focusState();
    console.log('R7_BUTTON_FOCUS_TIMING',JSON.stringify({immediate,after600ms:stability}));
    // Only retry focus when no checkout submission has occurred. The document
    // remains on the same Checkout route and this does not click/order anything.
    if (!stability.focusIsButton && stability.hasButton && !stability.disabled) {
      await submitButton.focus();
      await sleep(250);
      stability=await focusState();
      console.log('R7_BUTTON_FOCUS_RETRY',JSON.stringify(stability));
    }
    if(!stability.hasButton||stability.disabled||!stability.reactClick||!stability.focusIsButton)throw Error('Checkout Place Order button not ready for first click');
    // The Woo checkout button and total transiently change during
    // debounced customer/shipping updates. Only submit after they settle.
    const isPayable=()=>page.evaluate(()=>{
      const b=document.querySelector('.wc-block-components-checkout-place-order-button');
      const c=window.wp?.data?.select('wc/store/cart')?.getCartTotals?.();
      return !!b&&!b.disabled&&c?.total_price==='134800'&&
        document.body.innerText.includes('$1,348.00');
    });
    await page.waitForFunction(()=>{
      const b=document.querySelector('.wc-block-components-checkout-place-order-button');
      const c=window.wp?.data?.select('wc/store/cart')?.getCartTotals?.();
      return !!b&&!b.disabled&&c?.total_price==='134800'&&
        document.body.innerText.includes('$1,348.00');
    },null,{timeout:18000});
    await sleep(900);
    if(!(await isPayable())) throw Error('Checkout UI price or button became unstable; no submit');
    const before=await state();
    console.log('R7_CHECKOUT_BEFORE',JSON.stringify(before));
    await page.evaluate(()=>{
      window.__r7StateTransitions=[];
      let last='';
      const observe=()=>{
        const data=window.wp?.data;
        const checkout=data?.select('wc/store/checkout');
        const payment=data?.select('wc/store/payment');
        const validation=data?.select('wc/store/validation');
        const state={checkout:checkout?.getCheckoutStatus?.(),payment:payment?.getPaymentStatus?.(),
          has_error:checkout?.hasError?.(),validation:validation?.getValidationErrors?.()};
        const fingerprint=JSON.stringify(state);
        if(fingerprint!==last && window.__r7StateTransitions.length<50){
          last=fingerprint;window.__r7StateTransitions.push(state);
        }
      };
      observe();
      window.__r7CheckoutUnsub=window.wp?.data?.subscribe(observe);
    });
    if(!before.has_QA_gateway||!before.has_expected_total||before.missing.length)
      throw Error('Dev QA checkout preconditions unmet; not submitting');
    events.length=0;
    page.on('framenavigated', frame=>{if(frame===page.mainFrame())events.push({kind:'navigation',path:safePath(frame.url())});});
    await page.evaluate(()=>{window.__r7ClickCount=0; document.addEventListener('click',e=>{
      if(e.target?.closest?.('.wc-block-components-checkout-place-order-button'))window.__r7ClickCount++;
    },{capture:true});});
    await page.locator('.wc-block-components-checkout-place-order-button').click({timeout:12000});
    await sleep(6500);
    const first=await state();
    const submitted=events.some(e=>e.kind==='request'&&e.path.includes('/wc/store/v1/checkout'));
    console.log('R7_CLICK_ONCE',JSON.stringify({status:first.checkout,validationErrors:first.validationErrors,
      button:first.button,clickCount:await page.evaluate(()=>window.__r7ClickCount),
      submitted,orderReceived:/order-received|order-confirmation/.test(page.url())}));
    console.log('R7_CHECKOUT_AFTER',JSON.stringify(await state()));
    console.log('R7_CHECKOUT_EVENTS',JSON.stringify(events.slice(-45)));
    console.log('R7_CHECKOUT_TRANSITIONS',JSON.stringify(await page.evaluate(()=>window.__r7StateTransitions||[])));
    if(await page.locator('.wc-block-components-checkout-place-order-button').count()){
      console.log('R7_BUTTON_FINAL',JSON.stringify(await page.locator('.wc-block-components-checkout-place-order-button').evaluate(el=>({
        disabled:el.disabled,ariaDisabled:el.getAttribute('aria-disabled'),text:el.textContent?.trim(),
        type:el.getAttribute('type'),pointerEvents:getComputedStyle(el).pointerEvents
      }))));
    }
    const orderReceived=/order-received|order-confirmation/.test(page.url());
    const checkoutPosts=events.filter(e=>e.kind==='request'&&e.method==='POST'&&e.path==='/wp-json/wc/store/v1/checkout');
    const checkout200=events.some(e=>e.kind==='response'&&e.path==='/wp-json/wc/store/v1/checkout'&&e.status===200);
    console.log('R7_CHECKOUT_ORDER_RECEIVED',orderReceived);
    console.log('R7_CHECKOUT_ACCEPTANCE',JSON.stringify({orderReceived,checkoutPosts:checkoutPosts.length,checkout200,pass:orderReceived&&checkoutPosts.length===1&&checkout200}));
    if(!orderReceived||checkoutPosts.length!==1||!checkout200){
      throw Error('First-click Woo Checkout acceptance NOT met; no automatic second click');
    }
    await ctx.close();
  } finally {await browser.close();}
})().catch(e=>{console.error('R7_DIAGNOSTIC_FAILED',String(e.stack||e).slice(0,750));process.exitCode=1;});
