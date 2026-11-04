(function(){
"use strict";
function ready(fn){if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",fn);else fn();}
ready(function(){
  var root=document.getElementById("fsc-real-adu");
  if(!root)return;

  // wp_footer historically placed the ADU showcase AFTER the site's footer.
  // Render before the footer server-side, then enforce correct layout in all themes.
  var main=document.querySelector("main");
  var footer=document.querySelector("footer.site-footer")||document.querySelector("footer");
  if(main&&!main.contains(root))main.appendChild(root);
  else if(footer&&footer.compareDocumentPosition(root)&Node.DOCUMENT_POSITION_FOLLOWING){
    footer.parentNode.insertBefore(root,footer);
  }

  var fallback=document.getElementById("fsc-adu-fallback");
  var viewer=document.getElementById("fsc-adu-v2");
  if(fallback&&viewer)fallback.appendChild(viewer);

  var iframe=document.getElementById("fsc-adu-sketchfab");
  var title=document.getElementById("fsc-adu-3d-title");
  var subtitle=document.getElementById("fsc-adu-3d-description");
  var source=document.getElementById("fsc-adu-open-model");
  var noWebGL=document.getElementById("fsc-adu-3d-no-webgl");
  var fallbackToggle=document.getElementById("fsc-adu-lightweight-toggle");
  var fallbackOpen=document.getElementById("fsc-adu-3d-open-fallback");

  var models={
    driftwood:{
      name:"The Driftwood",
      id:"bca7d791d8d041f2a3e95f70ed6a3bfd",
      note:"24 × 24 ft · Architectural ADU design by Volstrukt.",
      url:"https://sketchfab.com/3d-models/the-driftwood-24-x-24-adu-model-bca7d791d8d041f2a3e95f70ed6a3bfd"
    },
    meadows:{
      name:"The Meadows",
      id:"31993c69b3744b0e9bfccc8366247007",
      note:"24 × 24 ft · Two-bedroom / two-bathroom architectural design by Volstrukt.",
      url:"https://sketchfab.com/3d-models/24-x-24-2b2b-dbl-shed-the-meadows-31993c69b3744b0e9bfccc8366247007"
    },
    pacific:{
      name:"The Pacific",
      id:"07e20b74888145e392cd603feaa738de",
      note:"32 × 32 ft · Two-bedroom / one-bathroom architectural design by Volstrukt.",
      url:"https://sketchfab.com/3d-models/the-pacific-32-x-32-shed-2bed1bath-07e20b74888145e392cd603feaa738de"
    }
  };

  function supportsWebGL(){
    try{
      var c=document.createElement("canvas");
      return !!(window.WebGLRenderingContext && (c.getContext("webgl2")||c.getContext("webgl")||c.getContext("experimental-webgl")));
    }catch(_){return false;}
  }
  function activateFallback(){
    if(!fallback||!fallbackToggle)return;
    fallback.hidden=false;
    fallbackToggle.setAttribute("aria-expanded","true");
    fallbackToggle.textContent="Hide lightweight 3D viewer";
    if(viewer)viewer.hidden=false;
    var canvas=document.getElementById("fsc-adu-canvas");
    if(canvas){window.dispatchEvent(new Event("resize"));}
  }
  function hideFallback(){
    if(!fallback||!fallbackToggle)return;
    fallback.hidden=true;
    fallbackToggle.setAttribute("aria-expanded","false");
    fallbackToggle.textContent="Use lightweight 3D viewer";
  }
  function choose(key){
    var m=models[key];
    if(!m||!iframe)return;
    root.querySelectorAll("[data-fsc-sketchfab-model]").forEach(function(button){
      var selected=button.getAttribute("data-fsc-sketchfab-model")===key;
      button.classList.toggle("is-current",selected);
      button.setAttribute("aria-pressed",selected?"true":"false");
    });
    if(title)title.innerHTML=m.name==="The Driftwood"?"The Driftwood <em>ADU</em>":m.name+" <em>ADU</em>";
    if(subtitle)subtitle.textContent=m.note+" Drag to orbit · scroll to zoom.";
    if(source)source.href=m.url;
    iframe.title="Interactive 3D ADU architectural model: "+m.name+" by Volstrukt on Sketchfab";
    var newUrl="https://sketchfab.com/models/"+m.id+"/embed?autostart=0&ui_infos=1&ui_controls=1&ui_watermark=1";
    if(iframe.getAttribute("src")!==newUrl)iframe.setAttribute("src",newUrl);
  }
  root.querySelectorAll("[data-fsc-sketchfab-model]").forEach(function(button){
    button.addEventListener("click",function(){choose(button.dataset.fscSketchfabModel);});
  });
  if(fallbackToggle){
    fallbackToggle.addEventListener("click",function(){
      if(fallback.hidden)activateFallback();
      else hideFallback();
    });
  }
  if(fallbackOpen)fallbackOpen.addEventListener("click",activateFallback);
  if(!supportsWebGL()){
    if(noWebGL)noWebGL.hidden=false;
    if(iframe)iframe.style.visibility="hidden";
    activateFallback();
  }
  choose("driftwood");
});
})();