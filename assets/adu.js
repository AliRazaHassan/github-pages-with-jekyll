(function(){
"use strict";
function ready(fn){if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",fn);else fn();}
ready(function(){
var stage=document.getElementById("fsc-adu-v2");
if(!stage)return;
var main=document.querySelector("main");
if(main&&stage.parentNode!==main)main.appendChild(stage);
var canvas=document.getElementById("fsc-adu-canvas");
if(!canvas)return;
var ctx=canvas.getContext("2d",{alpha:true});
if(!ctx)return;
var models={
studio:{
id:"studio",title:"Studio Retreat",subtitle:"Compact living with a warm modern exterior.",
area:"420 sq ft",layout:"Studio · 1 bathroom",footprint:"20 × 21 ft",style:"Warm modern",w:20,d:21,
description:"A flexible open room for living, sleeping and dining, with a compact kitchen and private bathroom.",
features:["Open-plan living and sleeping","Compact kitchen wall","Private bathroom and optional entry deck"],
walls:"#e3d4c1",roof:"#394a4f",accent:"#a57b5e",window:"#90b3c6",
rooms:[
{label:"Living + Sleep",x:-10,z:-10.5,w:13,d:21,color:"#e9e1d3"},
{label:"Bath",x:3,z:-10.5,w:7,d:8,color:"#d5e0e4"},
{label:"Kitchen",x:3,z:-2.5,w:7,d:12.5,color:"#f2e7d3"}],
partitions:[{x:3,z:-10.5,w:.25,d:21},{x:3,z:-2.5,w:7,d:.25}]
},
courtyard:{
id:"courtyard",title:"Courtyard One",subtitle:"A calm one-bedroom concept with generous glazing.",
area:"600 sq ft",layout:"1 bedroom · 1 bathroom",footprint:"24 × 25 ft",style:"Modern courtyard",w:24,d:25,
description:"A distinct bedroom and open living zone, designed around daylight and a thoughtful relationship with the outside.",
features:["Separate bedroom","Bright open living + kitchen","Entry terrace and garden-facing glazing"],
walls:"#efe3d3",roof:"#4a535c",accent:"#9f6e54",window:"#8dafbf",
rooms:[
{label:"Bedroom",x:-12,z:-12.5,w:16,d:12,color:"#e9e0d2"},
{label:"Bath",x:4,z:-12.5,w:8,d:12,color:"#d4e1e7"},
{label:"Living + Kitchen",x:-12,z:-.5,w:24,d:13,color:"#f2e7d5"}],
partitions:[{x:4,z:-12.5,w:.3,d:12},{x:-12,z:-.5,w:24,d:.25}]
},
family:{
id:"family",title:"Family Nest",subtitle:"Two-bedroom flexibility with a generous shared living area.",
area:"820 sq ft",layout:"2 bedrooms · 1 bathroom",footprint:"28 × 29 ft (approx.)",style:"Contemporary family",w:28,d:29.3,
description:"An adaptable concept for multigenerational living or a guest suite, with two private sleeping rooms and a shared living area.",
features:["Two private bedroom zones","Shared bath and open kitchen","Easy access to living and gathering space"],
walls:"#dee6e0",roof:"#354b5d",accent:"#a88365",window:"#8fadb9",
rooms:[
{label:"Bedroom 1",x:-14,z:-14.65,w:12,d:13,color:"#dde8e2"},
{label:"Bedroom 2",x:-2,z:-14.65,w:12,d:13,color:"#e5e3d5"},
{label:"Bath",x:10,z:-14.65,w:4,d:13,color:"#d5e0e9"},
{label:"Living + Kitchen",x:-14,z:-1.65,w:28,d:16.3,color:"#f0e7d9"}],
partitions:[{x:-2,z:-14.65,w:.25,d:13},{x:10,z:-14.65,w:.25,d:13},{x:-14,z:-1.65,w:28,d:.25}]
}
};
var model=models.studio,mode="3d",yaw=.65,pitch=.59,zoom=1,drag=null,dpr=1,framePending=false;
function el(id){return document.getElementById(id);}
function clamp(v,lo,hi){return Math.max(lo,Math.min(hi,v));}
function resize(){
 var rect=canvas.getBoundingClientRect();
 if(!rect.width||!rect.height)return;
 dpr=Math.min(window.devicePixelRatio||1,2);
 canvas.width=Math.round(rect.width*dpr);
 canvas.height=Math.round(rect.height*dpr);
 draw();
}
function queue(){if(framePending)return;framePending=true;requestAnimationFrame(function(){framePending=false;draw();});}
function project(x,y,z){
 var cx=Math.cos(yaw),sx=Math.sin(yaw);
 var side=x*cx-z*sx;
 var near=x*sx+z*cx;
 var sy=near*Math.sin(pitch)-y*Math.cos(pitch);
 var w=canvas.width/dpr,h=canvas.height/dpr;
 var base=Math.min(w/(model.w*1.65+model.d*.7),h/(model.d*1.3+13))*zoom;
 return [w/2+side*base,h*.68+sy*base,near];
}
function polygon(points,fill,stroke,width){
 var pr=points.map(function(p){return project(p[0],p[1],p[2]);});
 ctx.beginPath();ctx.moveTo(pr[0][0],pr[0][1]);
 for(var i=1;i<pr.length;i++)ctx.lineTo(pr[i][0],pr[i][1]);
 ctx.closePath();ctx.fillStyle=fill;ctx.fill();
 if(stroke){ctx.strokeStyle=stroke;ctx.lineWidth=width||.8;ctx.stroke();}
}
function shade(hex,n){
 hex=hex.replace("#","");var nums=[0,2,4].map(function(i){return parseInt(hex.slice(i,i+2),16)});
 return "#"+nums.map(function(x){return clamp(Math.round(x*n),0,255).toString(16).padStart(2,"0")}).join("");
}
function draw3d(){
 var w=canvas.width/dpr,h=canvas.height/dpr;
 ctx.clearRect(0,0,w,h);
 var sky=ctx.createLinearGradient(0,0,0,h);
 sky.addColorStop(0,"#edf2f3");sky.addColorStop(.67,"#dce9e9");sky.addColorStop(1,"#ccdcd3");
 ctx.fillStyle=sky;ctx.fillRect(0,0,w,h);
 // Projected site and shadow.
 polygon([[-model.w*.83,-.42,-model.d*.84],[model.w*.83,-.42,-model.d*.84],[model.w*.83,-.42,model.d*.95],[-model.w*.83,-.42,model.d*.95]],"#c7d7ca","#abc6b6",1);
 for(var stripe=-3;stripe<=3;stripe++){
  var xx=stripe*model.w/5;
  polygon([[xx,-.38,-model.d*.76],[xx+.035,-.38,-model.d*.76],[xx+.035,-.38,model.d*.83],[xx,-.38,model.d*.83]],"#b5cdbd");
 }
 polygon([[-model.w*.53,-.33,-model.d*.53],[model.w*.55,-.33,-model.d*.53],[model.w*.66,-.33,model.d*.76],[-model.w*.43,-.33,model.d*.76]],"#a3b1a57e");
 var faces=[];
 function addFace(pts,col,stroke,lineWidth){
  var dep=pts.reduce(function(t,p){return t+project(p[0],p[1],p[2])[2]},0)/pts.length;
  faces.push({p:pts,c:col,d:dep,stroke:stroke||null,lineWidth:lineWidth||.8});
 }
 function box(x0,y0,z0,x1,y1,z1,c){
  addFace([[x0,y0,z0],[x1,y0,z0],[x1,y1,z0],[x0,y1,z0]],shade(c,.76));
  addFace([[x0,y0,z1],[x1,y0,z1],[x1,y1,z1],[x0,y1,z1]],shade(c,1.05));
  addFace([[x0,y0,z0],[x0,y0,z1],[x0,y1,z1],[x0,y1,z0]],shade(c,.8));
  addFace([[x1,y0,z0],[x1,y0,z1],[x1,y1,z1],[x1,y1,z0]],shade(c,.91));
  addFace([[x0,y1,z0],[x1,y1,z0],[x1,y1,z1],[x0,y1,z1]],shade(c,1.16));
 }
 box(-model.w/2,-.15,-model.d/2,model.w/2,.38,model.d/2,"#b9b9ac");
 if(mode==="3d"){
  box(-model.w/2,.38,-model.d/2,model.w/2,9.15,model.d/2,model.walls);
  // Front entry deck.
  box(-model.w*.28,-.06,model.d/2,model.w*.4,.3,model.d/2+3.3,"#a78d78");
  // Facade windows and door. Drawn as coplanar 3D quads, depth sorted.
  addFace([[-model.w*.42,2,model.d/2+.06],[-model.w*.06,2,model.d/2+.06],[-model.w*.06,7.3,model.d/2+.06],[-model.w*.42,7.3,model.d/2+.06]],model.window,"#dfe7e3",1.6);
  addFace([[-model.w*.42,4.6,model.d/2+.07],[-model.w*.06,4.6,model.d/2+.07]],"#e9f4f8");
  addFace([[model.w*.14,.35,model.d/2+.10],[model.w*.38,.35,model.d/2+.10],[model.w*.38,7.5,model.d/2+.10],[model.w*.14,7.5,model.d/2+.10]],model.accent,"#eff2f0",1.6);
  addFace([[model.w*.34,3.1,model.d/2+.14],[model.w*.345,3.1,model.d/2+.14],[model.w*.345,3.8,model.d/2+.14],[model.w*.34,3.8,model.d/2+.14]],"#f8e9cb");
  addFace([[model.w/2+.06,2,-model.d*.34],[model.w/2+.06,2,model.d*.18],[model.w/2+.06,7.0,model.d*.18],[model.w/2+.06,7.0,-model.d*.34]],model.window,"#e0eceb",1.5);
  for(var i=0;i<6;i++){
   var x=-model.w*.03+i*.29;
   addFace([[x,.38,model.d/2+.12],[x+.09,.38,model.d/2+.12],[x+.09,9.1,model.d/2+.12],[x,9.1,model.d/2+.12]],shade(model.accent,.86));
  }
  box(-model.w/2-.7,9.05,-model.d/2-.7,model.w/2+.7,9.75,model.d/2+.75,model.roof);
 }else{
  // Roof-off cutaway view exposes the illustrative room organization.
  box(-model.w/2,.36,-model.d/2,model.w/2,.56,model.d/2,"#eadfcb");
  var wt=.27;
  box(-model.w/2,.56,-model.d/2,-model.w/2+wt,4.8,model.d/2,model.walls);
  box(model.w/2-wt,.56,-model.d/2,model.w/2,4.8,model.d/2,model.walls);
  box(-model.w/2,.56,-model.d/2,model.w/2,4.8,-model.d/2+wt,model.walls);
  box(-model.w/2,.56,model.d/2-wt,model.w/2,2.1,model.d/2,model.walls);
  model.rooms.forEach(function(r){
   var palette=r.label.toLowerCase().includes("bath")?"#c7d8dd":r.label.toLowerCase().includes("bed")?"#e2d9ce":"#e4d5bd";
   addFace([[r.x,.59,r.z],[r.x+r.w,.59,r.z],[r.x+r.w,.59,r.z+r.d],[r.x,.59,r.z+r.d]],palette);
  });
  model.partitions.forEach(function(p){box(p.x,.62,p.z,p.x+p.w,3.2,p.z+p.d,"#a7b3ac");});
  // Lightweight furniture blocks indicate use without representing construction specifications.
  model.rooms.forEach(function(r){
   if(/bed/i.test(r.label)){
    box(r.x+r.w*.24,.63,r.z+r.d*.22,r.x+r.w*.7,1.1,r.z+r.d*.67,"#b9a38e");
   }else if(/living/i.test(r.label)){
    box(r.x+r.w*.20,.63,r.z+r.d*.55,r.x+r.w*.51,1.36,r.z+r.d*.73,"#7899a0");
   }else if(/kitchen/i.test(r.label)){
    box(r.x+r.w*.15,.63,r.z+r.d*.22,r.x+r.w*.84,1.2,r.z+r.d*.35,"#d3b79a");
   }else if(/bath/i.test(r.label)){
    box(r.x+r.w*.18,.63,r.z+r.d*.15,r.x+r.w*.75,1.1,r.z+r.d*.49,"#f5f7f3");
   }
  });
 }
 faces.sort(function(a,b){return a.d-b.d;});
 faces.forEach(function(f){polygon(f.p,f.c,f.stroke,f.lineWidth);});
 // Landscape silhouettes (purely decorative).
 [[-model.w*.68,-model.d*.7,15],[model.w*.68,-model.d*.52,14],[-model.w*.64,model.d*.61,12]].forEach(function(t){
  var p=project(t[0],0,t[1]);var radius=Math.max(10,t[2]*zoom*.62);
  ctx.beginPath();ctx.ellipse(p[0],p[1]-radius*.65,radius,radius*.73,0,0,Math.PI*2);
  ctx.fillStyle="#87a79c";ctx.fill();ctx.strokeStyle="#6d9084";ctx.lineWidth=1.3;ctx.stroke();
 });
 // Simple scale label on viewer lower left.
 ctx.fillStyle="#466275";ctx.font="600 12px system-ui";ctx.textAlign="left";
 ctx.fillText(model.footprint+" concept footprint",18,h-20);
}
function plan(){
 var w=canvas.width/dpr,h=canvas.height/dpr;
 ctx.clearRect(0,0,w,h);
 ctx.fillStyle="#edf3f3";ctx.fillRect(0,0,w,h);
 var scale=Math.min((w-100)/model.w,(h-115)/model.d)*clamp(zoom,.75,1.35);
 var cx=w/2,cy=h/2+12;
 function px(x){return cx+x*scale;}
 function py(z){return cy+z*scale;}
 var x0=-model.w/2,z0=-model.d/2;
 ctx.fillStyle="#f8f7f3";ctx.fillRect(px(x0),py(z0),model.w*scale,model.d*scale);
 ctx.strokeStyle="#173c54";ctx.lineWidth=4;ctx.strokeRect(px(x0),py(z0),model.w*scale,model.d*scale);
 model.rooms.forEach(function(r){
  ctx.fillStyle=r.color;
  ctx.fillRect(px(r.x),py(r.z),r.w*scale,r.d*scale);
  ctx.strokeStyle="#506e7c";ctx.lineWidth=1.5;
  ctx.strokeRect(px(r.x),py(r.z),r.w*scale,r.d*scale);
  var fs=clamp(Math.min(r.w*scale*.15,14),9,14);
  ctx.font="700 "+fs+"px system-ui";ctx.textAlign="center";ctx.fillStyle="#254859";
  var label=r.label.replace(" + "," + ");
  if(label.length>12){
   var txt=label.split(" + ");
   txt.forEach(function(t,i){ctx.fillText(t,px(r.x+r.w/2),py(r.z+r.d/2)+(i-(txt.length-1)/2)*17);});
  }else ctx.fillText(label,px(r.x+r.w/2),py(r.z+r.d/2)+4);
 });
 ctx.font="700 12px system-ui";ctx.fillStyle="#bd1b2a";ctx.textAlign="center";
 ctx.fillText("ENTRY / FRONT",cx,py(model.d/2)+25);
 ctx.fillStyle="#36576a";ctx.font="12px system-ui";
 ctx.fillText(model.w+" ft approx.",cx,py(z0)-17);
 ctx.save();ctx.translate(px(x0)-26,cy);ctx.rotate(-Math.PI/2);
 ctx.fillText((Math.round(model.d*10)/10)+" ft approx.",0,0);ctx.restore();
 ctx.font="12px system-ui";ctx.fillStyle="#526b79";ctx.textAlign="left";
 ctx.fillText("Conceptual room zoning · not a construction plan",18,h-18);
}
function draw(){if(!canvas.width)return;ctx.setTransform(dpr,0,0,dpr,0,0);if(mode==="plan")plan();else draw3d();}
function select(id){
 if(!models[id])return;model=models[id];yaw=.65;pitch=.59;zoom=1;
 stage.querySelectorAll("[data-adu-model]").forEach(function(b){var active=b.dataset.aduModel===id;b.classList.toggle("is-current",active);b.setAttribute("aria-pressed",String(active));});
 el("fsc-adu-model-title").textContent=model.title;
 el("fsc-adu-model-subtitle").textContent=model.subtitle;
 el("fsc-adu-spec-title").textContent=model.title;
 el("fsc-adu-spec-description").textContent=model.description;
 el("fsc-adu-area").textContent=model.area;
 el("fsc-adu-layout").textContent=model.layout;
 el("fsc-adu-footprint").textContent=model.footprint;
 el("fsc-adu-style").textContent=model.style;
 var list=el("fsc-adu-features");list.textContent="";
 model.features.forEach(function(f){var li=document.createElement("li");li.textContent=f;list.appendChild(li);});
 var consult=el("fsc-adu-consult");consult.href="/atelier/contact/?adu_concept="+encodeURIComponent(id)+"#consultation";
 consult.setAttribute("aria-label","Ask about "+model.title+" ADU concept");
 try{var loc=new URL(window.location.href);loc.searchParams.set("concept",id);history.replaceState(null,"",loc.pathname+loc.search+loc.hash);}catch(_){}
 queue();
}
function setMode(next){
 if(["3d","open","plan"].indexOf(next)<0)return;
 mode=next;
 stage.querySelectorAll("[data-adu-mode]").forEach(function(b){var active=b.dataset.aduMode===next;b.classList.toggle("is-current",active);b.setAttribute("aria-pressed",String(active));});
 var planMode=next==="plan";el("fsc-adu-left").disabled=planMode;el("fsc-adu-right").disabled=planMode;
 queue();
}
stage.querySelectorAll("[data-adu-model]").forEach(function(b){b.addEventListener("click",function(){select(b.dataset.aduModel);});});
stage.querySelectorAll("[data-adu-mode]").forEach(function(b){b.addEventListener("click",function(){setMode(b.dataset.aduMode);});});
el("fsc-adu-left").addEventListener("click",function(){yaw-=.25;queue();});
el("fsc-adu-right").addEventListener("click",function(){yaw+=.25;queue();});
el("fsc-adu-zoom-in").addEventListener("click",function(){zoom=clamp(zoom*1.12,.65,2.1);queue();});
el("fsc-adu-zoom-out").addEventListener("click",function(){zoom=clamp(zoom/1.12,.65,2.1);queue();});
el("fsc-adu-reset").addEventListener("click",function(){yaw=.65;pitch=.59;zoom=1;queue();});
canvas.addEventListener("pointerdown",function(e){
 if(mode==="plan")return;
 drag={id:e.pointerId,x:e.clientX,y:e.clientY};try{canvas.setPointerCapture(e.pointerId);}catch(_){}
});
canvas.addEventListener("pointermove",function(e){
 if(!drag||drag.id!==e.pointerId||mode==="plan")return;
 var dx=e.clientX-drag.x,dy=e.clientY-drag.y;drag.x=e.clientX;drag.y=e.clientY;
 yaw+=dx*.010;pitch=clamp(pitch+dy*.006,.27,1.12);queue();
});
function stop(e){if(drag&&drag.id===e.pointerId)drag=null;}
canvas.addEventListener("pointerup",stop);canvas.addEventListener("pointercancel",stop);canvas.addEventListener("lostpointercapture",function(){drag=null;});
canvas.addEventListener("wheel",function(e){
 e.preventDefault();zoom=clamp(zoom*(e.deltaY>0?.94:1.06),.65,2.1);queue();
},{passive:false});
canvas.addEventListener("keydown",function(e){
 if(e.key==="ArrowLeft"||e.key==="ArrowRight"){
  e.preventDefault();if(mode!=="plan")yaw+=e.key==="ArrowLeft"?-.18:.18;queue();
 }else if(e.key==="+"||e.key==="="||e.key==="-"){
  e.preventDefault();zoom=clamp(zoom*(e.key==="-"?.9:1.1),.65,2.1);queue();
 }
});
var initial="studio";
try{initial=new URLSearchParams(location.search).get("concept")||"studio";}catch(_){}
select(models[initial]?initial:"studio");
if(window.ResizeObserver){new ResizeObserver(resize).observe(canvas.parentElement);}else window.addEventListener("resize",resize,{passive:true});
resize();
});
})();
