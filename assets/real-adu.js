(function(){
"use strict";
function ready(fn){if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",fn);else fn();}
ready(function(){
  var root=document.getElementById("fsc-real-adu");
  if(!root)return;
  var main=document.querySelector("main");
  var footer=document.querySelector("footer.site-footer")||document.querySelector("footer");
  if(main&&!main.contains(root))main.appendChild(root);
  else if(footer&&root.compareDocumentPosition(footer)&Node.DOCUMENT_POSITION_PRECEDING)footer.parentNode.insertBefore(root,footer);

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
  if(fallback&&fallbackModel)fallback.appendChild(fallbackModel);

  var configs={
    "glass-house":{key:"glass-house",title:"Glass House",subtitle:"Modern glazed pavilion · Furnished interior · 3D design reference",contact:"glass-house"},
    "modern-house":{key:"modern-house",title:"Modern House",subtitle:"Contemporary two-storey architectural reference · Not a standard ADU plan",contact:"modern-house"},
    "bungalow":{key:"bungalow",title:"Craftsman Bungalow",subtitle:"Single-storey Craftsman-inspired cottage · Architectural 3D reference",contact:"bungalow"}
  };
  var selection="glass-house";
  var THREE=null,GLTFLoader=null,renderer=null,scene=null,camera=null,loader=null,ground=null,loadedGroup=null;
  var cache=new Map(),yaw=-.64,elev=.49,radius=24,wireframe=false,sceneSize=null;
  var activePointers=new Map(),lastPinch=0,pending=false,loadSequence=0,resizeObserver=null,guard=null;
  var ready3d=false,failed=false;

  function label(){
    var cfg=configs[selection];
    if(title){
      var pieces=cfg.title.split(" ");
      var tail=pieces.pop();
      title.textContent="";
      title.appendChild(document.createTextNode((pieces.join(" ")||"3D")+" "));
      var em=document.createElement("em");em.textContent=tail;title.appendChild(em);
    }
    if(description)description.textContent=cfg.subtitle+". Drag to rotate · pinch or scroll to zoom.";
    if(consult)consult.href="/atelier/contact/?adu_concept="+encodeURIComponent(cfg.contact)+"#consultation";
    root.querySelectorAll("[data-fsc-3d-model]").forEach(function(button){
      var chosen=button.getAttribute("data-fsc-3d-model")===selection;
      button.classList.toggle("is-current",chosen);
      button.setAttribute("aria-pressed",String(chosen));
    });
    var wf=root.querySelector('[data-fsc-3d-action="wireframe"]');
    if(wf){wf.textContent=wireframe?"Shaded view":"Wireframe";wf.setAttribute("aria-pressed",String(wireframe));}
  }
  function status(message){
    if(loading){loading.textContent=message;loading.hidden=false;}
  }
  function fallbackMode(){
    failed=true;
    if(guard){window.clearTimeout(guard);guard=null;}
    if(loading)loading.hidden=true;
    if(noWebGL)noWebGL.hidden=false;
    canvas.hidden=true;
    if(fallback){
      fallback.hidden=false;
      if(fallbackModel)fallbackModel.hidden=false;
    }
    window.dispatchEvent(new Event("resize"));
  }
  if(fallbackBtn)fallbackBtn.addEventListener("click",function(){
    if(fallback){
      fallback.hidden=false;if(fallbackModel)fallbackModel.hidden=false;
      window.dispatchEvent(new Event("resize"));
    }
  });
  function beginGuard(){
    if(guard)window.clearTimeout(guard);
    guard=window.setTimeout(function(){if(!failed&&loading&&!loading.hidden)fallbackMode();},18000);
  }
  function stopGuard(){
    if(guard){window.clearTimeout(guard);guard=null;}
  }
  function requestRender(){
    if(pending||!renderer||!camera||!scene)return;
    pending=true;
    requestAnimationFrame(function(){
      pending=false;
      if(renderer&&scene&&camera)renderer.render(scene,camera);
    });
  }
  function resize(){
    if(!renderer||!camera)return;
    var width=Math.max(240,frame.clientWidth),height=Math.max(280,frame.clientHeight);
    renderer.setSize(width,height,false);
    camera.aspect=width/height;
    camera.updateProjectionMatrix();
    requestRender();
  }
  function updateCamera(){
    if(!camera)return;
    elev=Math.max(.12,Math.min(1.35,elev));
    radius=Math.max(2.5,Math.min(180,radius));
    var horizontal=Math.cos(elev)*radius;
    camera.position.set(Math.sin(yaw)*horizontal,Math.sin(elev)*radius,Math.cos(yaw)*horizontal);
    camera.lookAt(0,0,0);
    requestRender();
  }
  function setMaterialsWireframe(obj,flag){
    if(!obj)return;
    obj.traverse(function(mesh){
      if(!mesh.isMesh||!mesh.material)return;
      var mats=Array.isArray(mesh.material)?mesh.material:[mesh.material];
      mats.forEach(function(mat){if(mat&&"wireframe" in mat)mat.wireframe=flag;});
    });
  }
  function applyModel(asset){
    if(!scene||!THREE)return;
    if(loadedGroup)scene.remove(loadedGroup);
    loadedGroup=asset;
    scene.add(loadedGroup);
    var box=new THREE.Box3().setFromObject(loadedGroup);
    if(box.isEmpty())throw Error("Model contains no visible mesh");
    var center=box.getCenter(new THREE.Vector3());
    var size=box.getSize(new THREE.Vector3());
    // Normalise around the origin; scale stays in original real-world metres.
    loadedGroup.position.sub(center);
    sceneSize=size;
    if(ground)ground.position.y=-size.y/2-.13;
    var maxDimension=Math.max(size.x,size.y,size.z);
    radius=Math.max(10,maxDimension*2.0);
    yaw=-.64;elev=.49;
    setMaterialsWireframe(loadedGroup,wireframe);
    camera.near=.03;
    camera.far=Math.max(150,maxDimension*15);
    camera.updateProjectionMatrix();
    updateCamera();
    if(loading)loading.hidden=true;
    stopGuard();
  }
  async function activate(key){
    if(!configs[key])return;
    selection=key;wireframe=false;label();
    if(failed){
      var old=fallbackModel&&fallbackModel.querySelector('[data-adu-model="'+(key==="glass-house"?"studio":key==="modern-house"?"courtyard":"family")+'"]');
      if(old)old.click();
      return;
    }
    if(!ready3d)return;
    var sequence=++loadSequence;
    var url=window.fscADUModelUrls&&window.fscADUModelUrls[key];
    if(!url) {status("3D model path not configured.");fallbackMode();return;}
    var absolute=new URL(url,window.location.href);
    if(absolute.origin!==window.location.origin){status("Invalid external model source.");fallbackMode();return;}
    status("Loading "+configs[key].title+"…");
    beginGuard();
    try{
      if(!cache.has(key)){
        var promise=new Promise(function(resolve,reject){
          loader.load(absolute.href,function(gltf){resolve(gltf.scene);},function(progress){
            if(sequence!==loadSequence||!progress.lengthComputable)return;
            var pct=Math.min(99,Math.round(progress.loaded/progress.total*100));
            status("Loading "+configs[key].title+" · "+pct+"%");
          },function(err){reject(err);});
        });
        cache.set(key,promise);
      }
      var obj=await cache.get(key);
      if(sequence!==loadSequence||failed)return;
      applyModel(obj);
    }catch(e){
      if(sequence!==loadSequence)return;
      if(window.console)console.warn("5 Star model could not load",key,e&&e.message);
      cache.delete(key);
      fallbackMode();
    }
  }
  function reset(){
    if(!sceneSize||!camera)return;
    yaw=-.64;elev=.49;
    radius=Math.max(10,Math.max(sceneSize.x,sceneSize.y,sceneSize.z)*2.0);
    updateCamera();
  }
  root.querySelectorAll("[data-fsc-3d-model]").forEach(function(button){
    button.addEventListener("click",function(){activate(button.dataset.fsc3dModel);});
  });
  root.querySelectorAll("[data-fsc-3d-action]").forEach(function(button){
    button.addEventListener("click",function(){
      var action=button.dataset.fsc3dAction;
      if(action==="wireframe"){
        wireframe=!wireframe;
        setMaterialsWireframe(loadedGroup,wireframe);
        label();requestRender();
      }else if(action==="left"){yaw-=.22;updateCamera();}
      else if(action==="right"){yaw+=.22;updateCamera();}
      else if(action==="zoom-in"){radius*=.88;updateCamera();}
      else if(action==="zoom-out"){radius*=1.13;updateCamera();}
      else if(action==="reset"){reset();}
      else if(action==="fullscreen"){
        if(document.fullscreenElement&&document.exitFullscreen)document.exitFullscreen();
        else if(frame.requestFullscreen)frame.requestFullscreen().catch(function(){});
      }
    });
  });
  canvas.addEventListener("pointerdown",function(e){
    if(e.pointerType==="mouse"&&e.button!==0)return;
    activePointers.set(e.pointerId,{x:e.clientX,y:e.clientY});
    try{canvas.setPointerCapture(e.pointerId);}catch(_){}
  });
  canvas.addEventListener("pointermove",function(e){
    if(!activePointers.has(e.pointerId))return;
    var old=activePointers.get(e.pointerId),dx=e.clientX-old.x,dy=e.clientY-old.y;
    activePointers.set(e.pointerId,{x:e.clientX,y:e.clientY});
    if(activePointers.size===1){yaw-=dx*.008;elev+=dy*.006;updateCamera();}
    else if(activePointers.size===2){
      var arr=Array.from(activePointers.values());
      var dist=Math.hypot(arr[0].x-arr[1].x,arr[0].y-arr[1].y);
      if(lastPinch>0&&dist>20){radius*=lastPinch/dist;updateCamera();}
      lastPinch=dist;
    }
  });
  function endPointer(e){activePointers.delete(e.pointerId);if(activePointers.size<2)lastPinch=0;}
  canvas.addEventListener("pointerup",endPointer);
  canvas.addEventListener("pointercancel",endPointer);
  canvas.addEventListener("lostpointercapture",endPointer);
  canvas.addEventListener("wheel",function(e){
    e.preventDefault();radius*=e.deltaY>0?1.09:.91;updateCamera();
  },{passive:false});
  canvas.addEventListener("keydown",function(e){
    if(["ArrowLeft","ArrowRight","ArrowUp","ArrowDown","+","=","-"].indexOf(e.key)<0)return;
    e.preventDefault();
    if(e.key==="ArrowLeft")yaw-=.17;
    else if(e.key==="ArrowRight")yaw+=.17;
    else if(e.key==="ArrowUp")elev+=.11;
    else if(e.key==="ArrowDown")elev-=.11;
    else if(e.key==="-")radius*=1.11;else radius*=.9;
    updateCamera();
  });
  window.addEventListener("pagehide",function(){
    if(renderer){renderer.dispose();renderer=null;}
    if(resizeObserver)resizeObserver.disconnect();
  },{once:true});
  label();
  var canWebgl=false;
  try{
    var t=document.createElement("canvas");
    canWebgl=!!(window.WebGLRenderingContext&&(t.getContext("webgl2")||t.getContext("webgl")));
  }catch(_){}
  if(!canWebgl){fallbackMode();return;}
  async function start(){
    beginGuard();
    status("Preparing interactive 3D viewer…");
    try{
      // Both modules use the same version of Three.js via the on-page import map.
      THREE=await import("three");
      var tools=await import("three/addons/loaders/GLTFLoader.js");
      GLTFLoader=tools.GLTFLoader;
      renderer=new THREE.WebGLRenderer({canvas:canvas,alpha:true,antialias:true,powerPreference:"low-power"});
      renderer.setPixelRatio(Math.min(window.devicePixelRatio||1,1.7));
      renderer.outputColorSpace=THREE.SRGBColorSpace;
      renderer.toneMapping=THREE.ACESFilmicToneMapping;
      renderer.toneMappingExposure=1.33;
      scene=new THREE.Scene();
      scene.background=new THREE.Color(0xe7eff0);
      scene.fog=new THREE.Fog(0xe7eff0,100,250);
      camera=new THREE.PerspectiveCamera(43,1,.03,200);
      scene.add(new THREE.HemisphereLight(0xffffff,0xb9c9c2,3.1));
      var sun=new THREE.DirectionalLight(0xffe9ca,3.2);
      sun.position.set(-18,40,25);scene.add(sun);
      var other=new THREE.DirectionalLight(0xc4dfef,1.7);
      other.position.set(22,19,-22);scene.add(other);
      ground=new THREE.Mesh(new THREE.PlaneGeometry(300,300),new THREE.MeshStandardMaterial({color:0xd6e1d9,roughness:1}));
      ground.rotation.x=-Math.PI/2;scene.add(ground);
      loader=new GLTFLoader();
      ready3d=true;
      resize();
      if(window.ResizeObserver){resizeObserver=new ResizeObserver(resize);resizeObserver.observe(frame);}
      else window.addEventListener("resize",resize,{passive:true});
      canvas.addEventListener("webglcontextlost",function(e){e.preventDefault();fallbackMode();});
      await activate(selection);
    }catch(err){
      if(window.console)console.warn("5 Star GLB viewer unavailable",err&&err.message);
      fallbackMode();
    }
  }
  start();
});
})();