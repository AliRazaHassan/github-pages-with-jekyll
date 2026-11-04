(function(){
  "use strict";
  function init(){
    var section=document.getElementById("fsc-real-adu");
    var disclosure=document.getElementById("fsc-adu-prototype-disclosure");
    var viewer=document.getElementById("fsc-adu-v2");
    if(section&&disclosure&&viewer){
      disclosure.appendChild(viewer);
      viewer.removeAttribute("hidden");
    }
  }
  if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",init);else init();
})();