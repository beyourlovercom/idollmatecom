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
    const ctx = await browser.newContext({viewport:{width:1366,height:900},acceptDownloads:false});
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
      if (resp.status()>=400) {
        const d=await resp.json().catch(()=>({}));
        item.error_code=String(d.code||'').slice(0,90);
        item.error_message=String(d.message||'').slice(0,120);
      }
      events.push(item);
    });
    const product=await page.goto(origin+'/product/idollmate-r7-variable-qa-not-for-sale/',
      {waitUntil:'domcontentloaded',timeout:35000});
    if (product.status()!==200 || page.url().indexOf(origin)!==0) throw Error('Product URL not valid Dev QA page');
    await page.selectOption('select[name=attribute_material]','TPE');
    await page.waitForFunction(()=>!!document.querySelector('input[name=tmcp_checkbox_0_0].tcenabled'),
      null,{timeout:18000});
    await page.locator('input[name=tmcp_checkbox_0_0]').check({force:true});
    await page.locator('button.single_add_to_cart_button').click();
    await sleep(1500);
    const ch=await page.goto(origin+'/checkout/',{waitUntil:'domcontentloaded',timeout:35000});
    if (ch.status()!==200) throw Error('Checkout HTTP '+ch.status());
    await page.getByText('R7 TEST ONLY - DO NOT PAY').waitFor({timeout:20000});
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
        checkout={status:c?.getCheckoutStatus?.(), errors: c?.getCheckoutError?.()?.message||null};
      }catch(e){checkout={read_error:String(e).slice(0,100)};}
      try{
        const p=data?.select('wc/store/payment');
        payment={status:p?.getPaymentStatus?.(), active:p?.getActivePaymentMethod?.()};
      }catch(e){payment={read_error:String(e).slice(0,100)};}
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
      const prices=(document.body.innerText.match(/\\$\\s*[\\d,.]+/g)||[]).slice(-14);
      const overview=(document.querySelector('.wc-block-components-order-summary,.wc-block-components-totals-wrapper')?.innerText||'').slice(-600);
      return {url:location.pathname,checkout,payment,boxes,missing,cartTotals,customer,prices,overview,has_QA_gateway:document.body.innerText.includes('R7 TEST ONLY - DO NOT PAY'),
        has_expected_total:document.body.innerText.includes('$1,348.00')};
    });
    const before=await state();
    console.log('R7_CHECKOUT_BEFORE',JSON.stringify(before));
    if(!before.has_QA_gateway||!before.has_expected_total||before.missing.length)
      throw Error('Dev QA checkout preconditions unmet; not submitting');
    events.length=0;
    await page.locator('.wc-block-components-checkout-place-order-button').click({timeout:12000});
    await sleep(8500);
    console.log('R7_CHECKOUT_AFTER',JSON.stringify(await state()));
    console.log('R7_CHECKOUT_EVENTS',JSON.stringify(events.slice(-45)));
    console.log('R7_CHECKOUT_ORDER_RECEIVED',/order-received|order-confirmation/.test(page.url()));
    await ctx.close();
  } finally {await browser.close();}
})().catch(e=>{console.error('R7_DIAGNOSTIC_FAILED',String(e.stack||e).slice(0,750));process.exitCode=1;});
