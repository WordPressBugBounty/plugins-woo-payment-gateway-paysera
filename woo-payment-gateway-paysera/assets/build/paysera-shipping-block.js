!function(){"use strict";var e=window.wp.element,t=window.wp.blocks,a=(0,e.forwardRef)((function({icon:t,size:a=24,...r},o){return(0,e.cloneElement)(t,{width:a,height:a,...r,ref:o})})),r=window.React,o=window.wp.primitives,n=(0,r.createElement)(o.SVG,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"},(0,r.createElement)(o.Path,{fillRule:"evenodd",d:"M5 5.5h14a.5.5 0 01.5.5v1.5a.5.5 0 01-.5.5H5a.5.5 0 01-.5-.5V6a.5.5 0 01.5-.5zM4 9.232A2 2 0 013 7.5V6a2 2 0 012-2h14a2 2 0 012 2v1.5a2 2 0 01-1 1.732V18a2 2 0 01-2 2H6a2 2 0 01-2-2V9.232zm1.5.268V18a.5.5 0 00.5.5h12a.5.5 0 00.5-.5V9.5h-13z",clipRule:"evenodd"}));function i(){return i=Object.assign?Object.assign.bind():function(e){for(var t=1;t<arguments.length;t++){var a=arguments[t];for(var r in a)({}).hasOwnProperty.call(a,r)&&(e[r]=a[r])}return e},i.apply(null,arguments)}var s=window.wp.blockEditor,l=JSON.parse('{"apiVersion":2,"name":"paysera/shipping-methods","version":"1.0.0","title":"Paysera Shipping Methods","category":"woocommerce","description":"Add Paysera terminal selection to shipping methods.","supports":{"html":false,"align":false,"multiple":false,"reusable":false},"parent":["woocommerce/checkout-shipping-methods-block"],"attributes":{"lock":{"type":"object","default":{"remove":true,"move":true}},"text":{"type":"string","source":"html","selector":".wp-block-paysera-shipping","default":""}},"textdomain":"paysera"}');(0,t.registerBlockType)(l,{icon:{src:(0,e.createElement)(a,{icon:n})},edit:()=>{const t=(0,s.useBlockProps)();return(0,e.createElement)("div",i({},t,{style:{display:"block"}}))},save:t=>{let{attributes:a}=t;const{text:r}=a;return(0,e.createElement)("div",s.useBlockProps.save(),(0,e.createElement)(s.RichText.Content,{value:r}))}})}();