(function(){
"use strict";
function init(){
 var root=document.getElementById("fsc-real-adu");if(!root)return;
 var models=JSON.parse(root.getAttribute("data-models")||"[]");
 var cards=Array.from(root.querySelectorAll("[data-adu-real-model]"));
 var main=root.querySelector("#fsc-real-main-photo");
 var thumbs=root.querySelector("#fsc-real-thumbs");
 var title=root.querySelector("#fsc-real-model-name"),details=root.querySelector("#fsc-real-model-stats"),source=root.querySelector("#fsc-real-source"),ask=root.querySelector("#fsc-real-ask");
 var active=models[0];
 function photo(src,label,first){
   var img=document.createElement("img");img.loading=first?"eager":"lazy";img.decoding="async";img.alt=label;img.src=src;img.referrerPolicy="no-referrer-when-downgrade";img.addEventListener("error",function(){img.alt="Image unavailable — view original ADU model details";if(img===main)img.style.visibility="hidden";},{once:true});return img;
 }
 function display(url,label,button){
   main.src=url;main.alt=label;main.style.visibility="visible";
   thumbs.querySelectorAll("button").forEach(function(b){b.setAttribute("aria-pressed",String(b===button));b.classList.toggle("active",b===button);});
 }
 function select(id){
   var m=models.find(function(v){return v.id===id;});if(!m)return;
   active=m;
   cards.forEach(function(c){var yes=c.dataset.aduRealModel===id;c.classList.toggle("selected",yes);c.setAttribute("aria-pressed",String(yes));});
   title.textContent=m.name;
   details.textContent=m.area+" sq ft  ·  "+m.beds+" bedroom"+(m.beds>1?"s":"")+"  ·  "+m.baths+" bathroom";
   source.href=m.source;ask.href="/atelier/contact/?adu_concept="+encodeURIComponent(m.id)+"#consultation";
   main.style.visibility="visible";
   thumbs.textContent="";
   var items=[
    {u:m.cover,label:m.name+" exterior design",title:"Exterior"},
    {u:m.plan,label:m.name+" original model floor plan or gallery view",title:"Floor plan / gallery"},
    {u:m.modern,label:m.name+" modern exterior variant",title:"Modern style"}
   ];
   items.forEach(function(t,i){
    var b=document.createElement("button");b.type="button";b.className="fsc-real-thumb"+(i===0?" active":"");b.setAttribute("aria-label","View "+t.title);b.setAttribute("aria-pressed",String(i===0));
    b.appendChild(photo(t.u,t.label,false));
    var txt=document.createElement("span");txt.textContent=t.title;b.appendChild(txt);
    b.addEventListener("click",function(){display(t.u,t.label,b);});
    thumbs.appendChild(b);
   });
   main.src=m.cover;main.alt=m.name+" exterior rendering from Blue Heaven catalogue";
 }
 cards.forEach(function(b){b.addEventListener("click",function(){select(b.dataset.aduRealModel);root.querySelector("#fsc-real-model-name").scrollIntoView({behavior:"smooth",block:"nearest"});});});
 select(active.id);
 var old=document.getElementById("fsc-adu-v2");
 if(old){
  var disclosure=document.getElementById("fsc-adu-prototype-disclosure");
  if(disclosure){
   disclosure.appendChild(old);
   old.hidden=false;
  }
 }
}
if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",init);else init();
})();