(function(){
"use strict";
function ready(fn){if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",fn);else fn();}
ready(function(){
  var root=document.getElementById("fsc-real-adu");
  if(!root)return;
  var main=document.querySelector("main");
  var footer=document.querySelector("footer.site-footer")||document.querySelector("footer");
  if(main&&!main.contains(root))main.appendChild(root);
  else if(footer&&root.compareDocumentPosition(footer)&Node.DOCUMENT_POSITION_PRECEDING){footer.parentNode.insertBefore(root,footer);}

  var frame=document.getElementById("fsc-adu-3d-frame");
  var canvas=document.getElementById("fsc-adu-own-canvas");
  var loading=document.getElementById("fsc-adu-3d-loading");
  var noWebGL=document.getElementById("fsc-adu-3d-no-webgl");
  var fallback=document.getElementById("fsc-adu-fallback");
  var fallbackModel=document.getElementById("fsc-adu-v2");
  var fallbackBtn=document.getElementById("fsc-adu-3d-open-fallback");
  var title=document.getElementById("fsc-adu-3d-title");
  var description=document.getElementById("fsc-adu-3d-description");
  var consult=document.getElementById("fsc-adu-own-consult");
  if(!canvas||!frame)return;
  if(fallbackModel&&fallback)fallback.appendChild(fallbackModel);
  var configs={
    studio:{key:"studio",title:"Modern Studio",size:"Approx. 420 sq ft · Studio / 1 bathroom",w:13,d:10,h:4.1,roof:"flat",exterior:0xe2d1bd,trim:0x3d4d55,wood:0x9d7656,glass:0x78a7b9,style:"wood"},
    courtyard:{key:"courtyard",title:"Courtyard One",size:"Approx. 600 sq ft · 1 bedroom / 1 bathroom",w:16,d:12,h:4.3,roof:"flat",exterior:0xe9e1d3,trim:0x475a62,wood:0x916b53,glass:0x88b1bd,style:"white"},
    family:{key:"family",title:"Family Nest",size:"Approx. 820 sq ft · 2 bedrooms / 1 bathroom",w:20,d:13,h:4.2,roof:"gable",exterior:0xd8e0db,trim:0x324b5f,wood:0x987b5f,glass:0x77a7bd,style:"sage"}
  };
  var chosen=configs.studio,THREE=null,renderer=null,scene=null,camera=null,building=null,roofGroup=null;
  var yaw=-.71,elev=.56,radius=32,roofOff=false,drag=null,pending=false,resizeObserver=null,loadGuard=null;
  function setLabel(){
    if(title)title.innerHTML=chosen.title.replace(" "," <em>")+" </em>";
    if(description)description.textContent=chosen.size+". Drag or swipe to rotate · Scroll or pinch to zoom.";
    if(consult)consult.href="/atelier/contact/?adu_concept="+encodeURIComponent(chosen.key)+"#consultation";
    root.querySelectorAll("[data-fsc-3d-model]").forEach(function(b){
      var selected=b.dataset.fsc3dModel===chosen.key;
      b.classList.toggle("is-current",selected);b.setAttribute("aria-pressed",String(selected));
    });
    var roofBtn=root.querySelector('[data-fsc-3d-action="roof"]');
    if(roofBtn){roofBtn.textContent=roofOff?"Roof on":"Roof off";roofBtn.setAttribute("aria-pressed",String(roofOff));}
  }
  function showFallback(){
    if(loadGuard)window.clearTimeout(loadGuard);
    if(loading)loading.hidden=true;
    if(noWebGL)noWebGL.hidden=false;
    if(canvas)canvas.hidden=true;
    if(fallback){
      fallback.hidden=false;
      if(fallbackModel)fallbackModel.hidden=false;
    }
    window.dispatchEvent(new Event("resize"));
  }
  if(fallbackBtn)fallbackBtn.addEventListener("click",function(){
    if(fallback){fallback.hidden=false;if(fallbackModel)fallbackModel.hidden=false;window.dispatchEvent(new Event("resize"));}
  });
  setLabel();
  var glAvailable=false;
  try{
    var test=document.createElement("canvas");
    glAvailable=!!(window.WebGLRenderingContext&&(test.getContext("webgl2")||test.getContext("webgl")));
  }catch(_){}
  if(!glAvailable){showFallback();return;}

  async function start(){
    try{
      try{THREE=await import("https://cdn.jsdelivr.net/npm/three@0.160.1/build/three.module.js");}
      catch(_){THREE=await import("https://unpkg.com/three@0.160.1/build/three.module.js");}
      renderer=new THREE.WebGLRenderer({canvas:canvas,antialias:true,alpha:true,powerPreference:"low-power"});
      renderer.setPixelRatio(Math.min(window.devicePixelRatio||1,1.7));
      renderer.outputColorSpace=THREE.SRGBColorSpace;
      renderer.toneMapping=THREE.ACESFilmicToneMapping;
      renderer.toneMappingExposure=1.38;
      scene=new THREE.Scene();
      scene.background=new THREE.Color(0xe8f0f0);
      scene.fog=new THREE.Fog(0xe8f0f0,63,115);
      camera=new THREE.PerspectiveCamera(43,1,.1,250);
      scene.add(new THREE.HemisphereLight(0xffffff,0x94aab0,2.9));
      var sun=new THREE.DirectionalLight(0xfff4dc,3.0);sun.position.set(-24,38,21);scene.add(sun);
      var fill=new THREE.DirectionalLight(0xdbf0ff,1.15);fill.position.set(16,12,-19);scene.add(fill);
      var ground=new THREE.Mesh(new THREE.PlaneGeometry(150,150),new THREE.MeshStandardMaterial({color:0xcbd9c9,roughness:1}));
      ground.rotation.x=-Math.PI/2;ground.position.y=-.4;scene.add(ground);
      build(chosen);
      size();
      if(loadGuard)window.clearTimeout(loadGuard);
      if(loading)loading.hidden=true;
      if(resizeObserver)resizeObserver.disconnect();
      if(window.ResizeObserver){resizeObserver=new ResizeObserver(size);resizeObserver.observe(frame);}
      window.addEventListener("resize",size,{passive:true});
      canvas.addEventListener("webglcontextlost",function(e){e.preventDefault();showFallback();});
    }catch(err){
      if(window.console)console.warn("5 Star local ADU 3D unavailable",err && err.message);
      showFallback();
    }
  }
  function material(hex,rough){return new THREE.MeshStandardMaterial({color:hex,roughness:rough===undefined?.83:rough,metalness:rough===.25?.08:0});}
  function addBox(group,w,h,d,x,y,z,mat,rot){
    var o=new THREE.Mesh(new THREE.BoxGeometry(w,h,d),mat);o.position.set(x,y,z);
    if(rot)o.rotation.z=rot;group.add(o);return o;
  }
  function sphere(group,x,y,z,r,mat){
    var m=new THREE.Mesh(new THREE.IcosahedronGeometry(r,1),mat);m.position.set(x,y,z);group.add(m);return m;
  }
  function plant(group,x,z,r){
    var bark=material(0x877861),leaf=material(0x648f6e);
    addBox(group,.14,r*2,.14,x,r,z,bark);
    sphere(group,x,r*2.6,z,r,leaf);
    sphere(group,x-r*.43,r*2.35,z,r*.64,material(0x7eaa84));
  }
  function glassPane(group,x,y,z,w,h,alongX,mat,frameMat){
    if(alongX){
      addBox(group,w,h,.085,x,y,z,frameMat);
      addBox(group,w-.14,h-.14,.11,x,y,z+.035,mat);
      addBox(group,.085,h,.13,x,y,z+.08,frameMat);
      addBox(group,w,.065,.13,x,y,z+.08,frameMat);
    }else{
      addBox(group,.085,h,w,x,y,z,frameMat);
      addBox(group,.11,h-.14,w-.14,x+.035,y,z,mat);
      addBox(group,.13,h,.085,x+.08,y,z,frameMat);
    }
  }
  function furnish(group,conf){
    var floorWood=material(0xc8ad8d),ink=material(0x4d5a5e),fabric=material(0x9aaba9),linen=material(0xebe8db),counter=material(0xf4eee2),frame=material(0xb98b67);
    var w=conf.w,d=conf.d,h=conf.h;
    // Interior zoned floor, visible when roof is lifted.
    addBox(group,w-.43,.09,d-.43,0,.45,0,floorWood);
    // Kitchen along rear-left wall, overhead cabinets and appliances.
    addBox(group,w*.34,.78,.77,-w*.23,.92,-d*.37,material(0xd3b79a));
    addBox(group,w*.34,.09,.85,-w*.23,1.34,-d*.37,counter);
    addBox(group,w*.32,.78,.34,-w*.23,2.85,-d*.43,material(0xe2d7c5));
    addBox(group,.67,.07,.45,-w*.17,1.4,-d*.32,ink);
    // Living zone sofa, coffee table, rug.
    addBox(group,w*.24,.56,1.35,-w*.25,.82,d*.19,fabric);
    addBox(group,w*.24,.8,.24,-w*.25,1.12,d*.52,fabric);
    addBox(group,1.3,.12,1.1,w*.04,.64,d*.25,frame);
    addBox(group,2.65,.025,2.15,-w*.11,.5,d*.21,linen);
    // Bath wet-area boundary at back-right.
    var bath=material(0xd2e2e2);
    addBox(group,.13,2.7,d*.30,w*.23,1.85,-d*.35,material(0xd9d5ca));
    addBox(group,w*.27,2.7,.13,w*.37,1.85,-d*.2,material(0xd9d5ca));
    addBox(group,1.2,.12,1.35,w*.36,.56,-d*.37,bath);
    if(conf.key!=="studio"){
      // Bedroom zone and bed.
      addBox(group,w*.31,2.8,.13,w*.07,1.85,-d*.05,material(0xd7d4c9));
      addBox(group,2.5,.47,2.9,w*.27,.78,-d*.30,material(0xb8a18b));
      addBox(group,2.38,.28,2.72,w*.27,1.15,-d*.30,linen);
      addBox(group,1.2,.2,.67,w*.27,1.4,-d*.44,counter);
    }
    if(conf.key==="family"){
      addBox(group,.12,2.9,d*.46,-w*.05,1.88,-d*.27,material(0xd7d4c9));
      addBox(group,2.2,.54,2.7,-w*.26,.79,-d*.3,material(0xc2aa8d));
      addBox(group,2.05,.24,2.52,-w*.26,1.2,-d*.3,linen);
    }
    // Front terrace + side planting.
    addBox(group,w*.56,.12,2.7,w*.1,.16,d/2+1.5,material(0xa78264));
    for(var i=0;i<7;i++)addBox(group,.075,.13,2.7,-w*.18+i*w*.095,.29,d/2+1.5,material(0x92765a));
    plant(group,-w*.8,d*.78,.75);
    plant(group,w*.76,-d*.45,1.05);
    for(var j=0;j<7;j++){
      sphere(group,-w*.72+j*.52,.04,d*.79+(j%2)*.30,.42,material(j%2?0x789c75:0x6d9271));
    }
  }
  function build(conf){
    if(building){scene.remove(building);building.traverse(function(o){
      if(o.isMesh){o.geometry.dispose();}
    });}
    building=new THREE.Group();scene.add(building);
    roofGroup=new THREE.Group();building.add(roofGroup);
    var w=conf.w,d=conf.d,h=conf.h;
    var outside=material(conf.exterior),edge=material(conf.trim),wood=material(conf.wood),glass=material(conf.glass,.25);
    var white=material(0xf6f4e9),roofMat=material(conf.trim);
    // Foundation.
    addBox(building,w+.4,.49,d+.45,0,.12,0,material(0xaebbb4));
    // Walls in simple high-quality architectural massing.
    addBox(building,w,.22,d,0,.36,0,material(0xdfd8c9));
    addBox(building,w,h,.2,0,h/2+.42,-d/2,outside);
    addBox(building,w,h,.2,0,h/2+.42,d/2,outside);
    addBox(building,.2,h,d,-w/2,h/2+.42,0,outside);
    addBox(building,.2,h,d,w/2,h/2+.42,0,outside);
    // Continuous facade accent with glass lines and inset door.
    addBox(building,w*.35,h*.91,.12,-w*.27,h*.47+.42,d/2+.145,wood);
    glassPane(building,-w*.27,h*.58+.42,d/2+.24,w*.26,h*.48,true,glass,edge);
    glassPane(building,w*.08,h*.61+.42,d/2+.24,w*.24,h*.52,true,glass,edge);
    addBox(building,w*.21,h*.69,.10,w*.35,h*.345+.42,d/2+.20,edge);
    addBox(building,w*.182,h*.655,.125,w*.35,h*.33+.42,d/2+.255,wood);
    addBox(building,.078,.07,.10,w*.423,1.6,d/2+.33,white);
    glassPane(building,w/2+.23,h*.64+.42,-d*.18,d*.34,h*.47,false,glass,edge);
    glassPane(building,-w/2-.23,h*.65+.42,-d*.08,d*.29,h*.44,false,glass,edge);
    // Side cladding to give character without relying on third-party designs.
    for(var slat=0;slat<8;slat++){
      addBox(building,.072,h*.9,.09,-w*.47+slat*.17,h*.47+.42,d/2+.26,material(slat%2?0xa88363:0x8f6d50));
    }
    // Roof forms: flat parapet or gabled.
    if(conf.roof==="gable"){
      var slope=Math.atan2(1.55,w/2);
      var half=(w/2+.8)/Math.cos(slope);
      addBox(roofGroup,half,.24,d+1.0,-w/4,h+.42+.8,0,roofMat,slope);
      addBox(roofGroup,half,.24,d+1.0,w/4,h+.42+.8,0,roofMat,-slope);
      // Ridge
      addBox(roofGroup,.19,.16,d+1.05,0,h+.42+1.57,0,edge);
    }else{
      addBox(roofGroup,w+.9,.28,d+.85,0,h+.55,0,roofMat);
      addBox(roofGroup,w+1,.11,.18,0,h+.79,d/2+.37,material(0x273d4c));
    }
    furnish(building,conf);
    roofGroup.visible=!roofOff;
    radius=Math.max(26,Math.max(w,d)*1.95);
    updateCamera();
  }
  function updateCamera(){
    if(!camera)return;
    elev=Math.max(.17,Math.min(1.28,elev));
    radius=Math.max(12,Math.min(72,radius));
    var c=Math.cos(elev);
    camera.position.set(Math.sin(yaw)*radius*c,radius*Math.sin(elev)+1.7,Math.cos(yaw)*radius*c);
    camera.lookAt(0,1.5,0);
    requestRender();
  }
  function render(){if(renderer&&scene&&camera)renderer.render(scene,camera);}
  function requestRender(){
    if(pending)return;
    pending=true;requestAnimationFrame(function(){pending=false;render();});
  }
  function size(){
    if(!renderer||!camera)return;
    var w=Math.max(300,frame.clientWidth),h=Math.max(290,frame.clientHeight);
    renderer.setSize(w,h,false);camera.aspect=w/h;camera.updateProjectionMatrix();requestRender();
  }
  function choose(key){
    if(!configs[key])return;
    chosen=configs[key];yaw=-.71;elev=.56;roofOff=false;setLabel();
    if(scene)build(chosen);
    if(fallbackModel){
      var map={studio:"studio",courtyard:"courtyard",family:"family"};
      var button=fallbackModel.querySelector('[data-adu-model="'+map[key]+'"]');
      if(button)button.click();
    }
  }
  root.querySelectorAll("[data-fsc-3d-model]").forEach(function(button){
    button.addEventListener("click",function(){choose(button.dataset.fsc3dModel);});
  });
  root.querySelectorAll("[data-fsc-3d-action]").forEach(function(button){
    button.addEventListener("click",function(){
      var action=button.dataset.fsc3dAction;
      if(action==="roof"){roofOff=!roofOff;if(roofGroup)roofGroup.visible=!roofOff;setLabel();requestRender();}
      else if(action==="left"){yaw-=.23;updateCamera();}
      else if(action==="right"){yaw+=.23;updateCamera();}
      else if(action==="zoom-in"){radius*=.88;updateCamera();}
      else if(action==="zoom-out"){radius*=1.13;updateCamera();}
      else if(action==="reset"){yaw=-.71;elev=.56;radius=Math.max(26,Math.max(chosen.w,chosen.d)*1.95);roofOff=false;if(roofGroup)roofGroup.visible=true;setLabel();updateCamera();}
    });
  });
  canvas.addEventListener("pointerdown",function(e){
    if(e.pointerType==="mouse"&&e.button!==0)return;
    drag={id:e.pointerId,x:e.clientX,y:e.clientY};
    try{canvas.setPointerCapture(e.pointerId)}catch(_){}
  });
  canvas.addEventListener("pointermove",function(e){
    if(!drag||drag.id!==e.pointerId)return;
    var dx=e.clientX-drag.x,dy=e.clientY-drag.y;drag.x=e.clientX;drag.y=e.clientY;
    yaw-=dx*.008;elev=Math.max(.2,Math.min(1.25,elev+dy*.006));updateCamera();
  });
  canvas.addEventListener("pointerup",function(e){if(drag&&drag.id===e.pointerId)drag=null;});
  canvas.addEventListener("pointercancel",function(){drag=null;});
  canvas.addEventListener("lostpointercapture",function(){drag=null;});
  canvas.addEventListener("wheel",function(e){e.preventDefault();radius*=e.deltaY>0?1.08:.92;updateCamera();},{passive:false});
  canvas.addEventListener("keydown",function(e){
    if(["ArrowLeft","ArrowRight","ArrowUp","ArrowDown","+","=","-"].indexOf(e.key)<0)return;
    e.preventDefault();
    if(e.key==="ArrowLeft")yaw-=.17;
    else if(e.key==="ArrowRight")yaw+=.17;
    else if(e.key==="ArrowUp")elev+=.1;
    else if(e.key==="ArrowDown")elev-=.1;
    else if(e.key==="-")radius*=1.1;else radius*=.9;
    updateCamera();
  });
  window.addEventListener("pagehide",function(){if(renderer){renderer.dispose();renderer=null;}},{once:true});
  // A blocked external 3D library must never trap visitors behind a spinner.
  loadGuard=window.setTimeout(function(){if(loading&&!loading.hidden)showFallback();},10000);
  start();
});
})();