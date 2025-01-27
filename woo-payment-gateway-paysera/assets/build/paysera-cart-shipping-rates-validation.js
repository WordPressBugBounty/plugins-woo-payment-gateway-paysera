!function(){"use strict";var e=window.wp.plugins,t=window.wp.element,o=window.wp.i18n,n=window.React,c=window.wp.data;(0,e.registerPlugin)("paysera-cart-shipping-rates-validation",{render:()=>{const e="paysera-delivery-error",r="weight_error_message",{CART_STORE_KEY:s,VALIDATION_STORE_KEY:a}=window.wc.wcBlocksData,i=(0,n.useRef)(null),{selectShippingRate:l}=(0,c.useDispatch)(s),{setValidationErrors:p}=(0,c.useDispatch)(a),d=(0,c.select)(s).getShippingRates(),u=[],h=[];d.forEach((e=>{e.shipping_rates.filter((e=>{e.meta_data.length&&e.meta_data[0].key===r?u.push(e):h.push(e)}))})),(0,t.useEffect)((()=>{if(w()||g(),_()){const e=new MutationObserver((e=>{e.forEach((e=>{m(e)&&b()}))}));return i.current&&e.observe(document.body,{attributes:!0,childList:!0,subtree:!0}),()=>{e.disconnect()}}}),[]);const w=()=>0!==h.length,g=()=>{p({"shipping-option":{message:(0,o.__)("Cart weight is not sufficient","paysera"),hidden:!1}})},_=()=>u.length+h.length>0,m=e=>e.target.classList.value.includes("wc-block-components-shipping-rates-control"),b=()=>{const e=document.querySelectorAll(".wc-block-components-shipping-rates-control__package label.wc-block-components-radio-control__option, .wc-block-components-shipping-rates-control__package .wc-block-components-radio-control__option-layout");e.forEach((t=>{u.forEach((o=>{if(f(t,o)||1===e.length){const e=t.querySelector("input");null!==e&&(e.setAttribute("disabled","disabled"),e.checked=!1),k(t)||o.meta_data.forEach((e=>{E(e)&&t?.querySelector(".wc-block-components-radio-control__label")?.append(v(e.value))}))}}))})),y()},f=(e,t)=>!0===e?.getAttribute("for")?.includes(t.rate_id),k=t=>0!==t?.querySelectorAll("."+e).length,E=e=>e.key===r,y=()=>{let e=u.find((e=>!0===e.selected));void 0!==e&&(e.selected=!1,l(h[0]?.rate_id))},v=t=>{let o=document.createElement("div");return o.textContent=t,o.classList.add(e),o};return(0,t.createElement)("span",{ref:i})},scope:"woocommerce-checkout"})}();