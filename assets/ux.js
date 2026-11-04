(function(){
"use strict";
function ready(fn){if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",fn);else fn();}
ready(function(){
 var cfg=window.FSC_UX||{},root=document.getElementById("fsc-ux-root");if(!root)return;
 var launch=document.getElementById("fsc-ux-launch"),dialog=document.getElementById("fsc-ux-dialog"),backdrop=document.getElementById("fsc-ux-backdrop"),closeBtn=document.getElementById("fsc-ux-close");
 var log=document.getElementById("fsc-ux-log"),questionForm=document.getElementById("fsc-ux-question"),questionInput=document.getElementById("fsc-ux-input"),leadForm=document.getElementById("fsc-ux-lead"),feedback=document.getElementById("fsc-ux-feedback"),quick=document.getElementById("fsc-ux-actions");
 var startedAt=0,selectedService="";
 function addMessage(kind,msg){var node=document.createElement("div");node.className="fsc-ux-message "+kind;node.textContent=msg;log.appendChild(node);log.scrollTop=log.scrollHeight;}
 function open(){startedAt=Math.floor(Date.now()/1000);dialog.hidden=false;backdrop.hidden=false;launch.setAttribute("aria-expanded","true");launch.hidden=true;document.body.classList.add("fsc-ux-dialog-open");setTimeout(function(){closeBtn.focus();},50);}
 function close(){dialog.hidden=true;backdrop.hidden=true;launch.hidden=false;launch.setAttribute("aria-expanded","false");document.body.classList.remove("fsc-ux-dialog-open");launch.focus();}
 function showForm(service){
   selectedService=service||selectedService||"";
   leadForm.hidden=false;questionForm.hidden=true;quick.hidden=true;feedback.textContent="";feedback.className="";
   if(selectedService){leadForm.elements.service.value=selectedService;}
   setTimeout(function(){leadForm.elements.location.focus();},40);
 }
 function back(){leadForm.hidden=true;questionForm.hidden=false;quick.hidden=false;questionInput.focus();}
 function choose(action){
   var mapping={kitchen:"Kitchen remodeling",bathroom:"Bathroom remodeling",whole:"Whole-home remodeling",adu:"ADU design & build"};
   if(mapping[action]){selectedService=mapping[action];addMessage("user",selectedService);addMessage("bot","Great. Share your project location, timing and preferred contact details so our team can discuss your plan. Exact prices require a professional review.");showForm(selectedService);return;}
   if(action==="estimate"){addMessage("user","I'd like a free estimate.");addMessage("bot","Let's get your project details. Our team can follow up using the contact method you provide.");showForm(selectedService);return;}
   if(action==="areas"){addMessage("bot","We serve the Sacramento area, including Carmichael, Elk Grove, Folsom, Roseville, Rocklin, Rancho Cordova, Davis and nearby communities. Share your city or ZIP to confirm availability.");return;}
 }
 function answer(message){
   var q=message.toLowerCase();addMessage("user",message);
   if(/(estimat|quote|schedule|consult|contact|book|start|call back)/.test(q)){addMessage("bot","We can help you request a free estimate. Please share a few project and contact details.");showForm(selectedService);return;}
   if(/(cost|price|budget|expensive|how much)/.test(q)){addMessage("bot","Remodeling costs depend on the room size, finishes, labor and scope. We don't publish unverified estimates. The team can provide a project-specific quote after reviewing your space.");return;}
   if(/(permit|license|approval)/.test(q)){addMessage("bot","Permits and approvals depend on your project and local jurisdiction. Our team can discuss the likely requirements during your consultation; final requirements should be confirmed with local authorities.");return;}
   if(/(adu|accessory dwelling)/.test(q)){addMessage("bot","For an ADU, the first steps are checking your property, available space and local rules. You can explore ADU models on the site or request a consultation.");selectedService="ADU design & build";return;}
   if(/(bath|shower|tub)/.test(q)){selectedService="Bathroom remodeling";addMessage("bot","We can discuss layout, shower or tub, storage, surfaces and accessibility for a bathroom remodel. Want to request a free estimate?");return;}
   if(/(kitchen|cabinet|island)/.test(q)){selectedService="Kitchen remodeling";addMessage("bot","We can help review cabinetry, layout, countertops, lighting and how you use your kitchen. Ask for a free estimate when you're ready.");return;}
   if(/(time|how long|duration|finish)/.test(q)){addMessage("bot","Timing depends on the scope, design decisions, material availability and any permits. Share your project details and desired timeline to discuss next steps.");return;}
   if(/(area|location|city|serve|sacramento)/.test(q)){choose("areas");return;}
   addMessage("bot","I can guide you through kitchen, bathroom, whole-home and ADU enquiries. For advice specific to your home, choose 'Get a free estimate' or call (916) 225-1015.");
 }
 launch.addEventListener("click",open);closeBtn.addEventListener("click",close);backdrop.addEventListener("click",close);
 document.addEventListener("keydown",function(e){if(dialog.hidden)return;if(e.key==="Escape")close();});
 quick.addEventListener("click",function(e){var target=e.target.closest("[data-fsc-action]");if(target)choose(target.getAttribute("data-fsc-action"));});
 document.getElementById("fsc-ux-back").addEventListener("click",back);
 questionForm.addEventListener("submit",function(e){e.preventDefault();var v=questionInput.value.trim();if(!v)return;questionInput.value="";answer(v.slice(0,500));});
 leadForm.addEventListener("submit",async function(e){
   e.preventDefault();
   var d=new FormData(leadForm),email=String(d.get("email")||"").trim(),phone=String(d.get("phone")||"").trim();
   var payload={service:String(d.get("service")||""),location:String(d.get("location")||"").trim(),timing:String(d.get("timing")||""),details:String(d.get("details")||""),name:String(d.get("name")||"").trim(),phone:phone,email:email,consent:d.get("consent")!==null,website:String(d.get("website")||""),started_at:startedAt};
   function fail(msg){feedback.className="error";feedback.textContent=msg;}
   if(!payload.service||payload.location.length<2||payload.name.length<2||!payload.consent){fail("Please select a service and provide your name, city and consent.");return;}
   if(!/^\S+@\S+\.\S+$/.test(email)&&phone.replace(/\D/g,"").length<7){fail("Please enter a valid email address or phone number.");return;}
   var submit=document.getElementById("fsc-ux-submit");submit.disabled=true;submit.textContent="Sending…";feedback.textContent="";
   try{
     var resp=await fetch(cfg.endpoint||"/wp-json/fsc/v1/inquiry",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify(payload)});
     var json=await resp.json().catch(function(){return {};});
     if(!resp.ok||!json.ok)throw Error(json.message||"The request could not be submitted.");
     feedback.className="ok";feedback.textContent=json.notification_sent?"Your request was saved and our email notification was queued.":"Your request was saved, but email notification was not confirmed. Please call if urgent.";
     addMessage("bot","Thank you, "+payload.name+"! Your project request was submitted. If you need an urgent response, call (916) 225-1015.");
     leadForm.reset();setTimeout(back,900);
   }catch(error){fail(error.message||"Could not submit. Please call (916) 225-1015 or email contact@5starconstructionsca.com.");}
   finally{submit.disabled=false;submit.textContent="Send project request";}
 });
 function syncContactLinks(){
   document.querySelectorAll('a[href^="mailto:service@blueheavenconstructions.com"]').forEach(function(a){a.href=a.href.replace("service@blueheavenconstructions.com","contact@5starconstructionsca.com");if(/@/.test(a.textContent))a.textContent="contact@5starconstructionsca.com";});
   document.querySelectorAll("a").forEach(function(a){
     if(/^get a free estimate/i.test((a.textContent||"").trim()))a.href="tel:+19162251015";
   });
 }


 // Featured project comparisons: replace fragile native/delegated drag handlers with
 // pointer-captured controls, keeping keyboard interaction and the last chosen split.
 function initFeaturedProjectSliders(){
   document.querySelectorAll(".project-card-media").forEach(function(media){
     if(media.dataset.fscFixedCompare==="1")return;
     var before=media.querySelector(".project-card-before img");
     var after=media.querySelector(".project-card-after img");
     if(!before||!after)return;
     media.dataset.fscFixedCompare="1";
     var card=media.closest(".project-card");
     var title=card&&card.querySelector("h3")?card.querySelector("h3").textContent.trim():"remodeling project";
     var handle=media.querySelector(".project-compare-handle");
     if(!handle){
       handle=document.createElement("span");
       handle.className="project-compare-handle";
       handle.textContent="↔";
       media.appendChild(handle);
     }
     media.appendChild(handle); // Keep the handle above both comparison image layers.
     handle.setAttribute("aria-hidden","true");
     var original=media.querySelector("input.project-compare-range");
     var slider=document.createElement("input");
     slider.type="range";slider.className="project-compare-range fsc-comparison-fixed";
     slider.min="0";slider.max="100";slider.step="1";slider.value="50";
     slider.setAttribute("aria-label","Compare before and after: "+title);
     slider.setAttribute("aria-valuetext","50 percent of the before image visible");
     slider.style.cssText="position:absolute;inset:0;display:block;width:100%;height:100%;margin:0;opacity:0;z-index:99;cursor:ew-resize;touch-action:pan-y;appearance:none;-webkit-appearance:none;";
     if(original)original.replaceWith(slider);else media.appendChild(slider);
     var current=50,activePointer=null;
     function setSplit(value){
       var v=Math.max(0,Math.min(100,Number(value)));
       if(!Number.isFinite(v))return;
       current=Math.round(v);
       slider.value=String(current);
       slider.setAttribute("aria-valuenow",String(current));
       slider.setAttribute("aria-valuetext",current+" percent of the before image visible");
       var right=(100-current)+"%";
       before.style.clipPath="inset(0 "+right+" 0 0)";
       before.style.webkitClipPath="inset(0 "+right+" 0 0)";
       handle.style.left="clamp(18px, "+current+"%, calc(100% - 18px))";
       media.style.setProperty("--compare",current+"%");
       media.style.setProperty("--split",current+"%");
     }
     function pointerPercent(e){
       var b=media.getBoundingClientRect();
       return b.width ? (e.clientX-b.left)/b.width*100 : current;
     }
     slider.addEventListener("pointerdown",function(e){
       if(e.button!==0&&e.pointerType==="mouse")return;
       activePointer=e.pointerId;
       e.preventDefault();e.stopImmediatePropagation();
       try{slider.focus({preventScroll:true})}catch(_){slider.focus()}
       try{slider.setPointerCapture(e.pointerId)}catch(_){}
       setSplit(pointerPercent(e));
     },true);
     slider.addEventListener("pointermove",function(e){
       if(activePointer!==e.pointerId)return;
       e.preventDefault();e.stopImmediatePropagation();
       setSplit(pointerPercent(e));
     },true);
     function endPointer(e){
       if(activePointer!==e.pointerId)return;
       e.preventDefault();e.stopImmediatePropagation();
       if(e.type==="pointerup")setSplit(pointerPercent(e));
       activePointer=null;
       try{if(slider.hasPointerCapture(e.pointerId))slider.releasePointerCapture(e.pointerId)}catch(_){}
     }
     slider.addEventListener("pointerup",endPointer,true);
     slider.addEventListener("pointercancel",endPointer,true);
     slider.addEventListener("lostpointercapture",function(){activePointer=null;});
     slider.addEventListener("input",function(e){
       e.stopImmediatePropagation();
       if(activePointer===null)setSplit(slider.value);
     },true);
     slider.addEventListener("change",function(e){e.stopImmediatePropagation();setSplit(slider.value)},true);
     slider.addEventListener("keydown",function(e){
       if(e.key==="Home"){e.preventDefault();setSplit(0)}
       else if(e.key==="End"){e.preventDefault();setSplit(100)}
       else if(e.key==="ArrowLeft"||e.key==="ArrowDown"){e.preventDefault();setSplit(current-1)}
       else if(e.key==="ArrowRight"||e.key==="ArrowUp"){e.preventDefault();setSplit(current+1)}
     },true);
     setSplit(50);
   });
 }
 initFeaturedProjectSliders();
 window.addEventListener("load",initFeaturedProjectSliders,{once:true});
 var contactForm=document.getElementById("five-star-enquiry-form");
 if(contactForm){
   var contactStarted=Math.floor(Date.now()/1000);
   var status=contactForm.querySelector(".five-star-form-status");
   contactForm.addEventListener("submit",async function(event){
     event.preventDefault();event.stopImmediatePropagation();
     if(!contactForm.checkValidity()){contactForm.reportValidity();return;}
     var formData=new FormData(contactForm);
     var rawService=String(formData.get("service")||"");
     var serviceMap={"Whole-house remodeling":"Whole-home remodeling","Commercial remodeling":"Other remodeling","Other / ask about availability":"Other remodeling"};
     var payload={
       service:serviceMap[rawService]||rawService,
       name:String(formData.get("name")||"").trim(),
       email:String(formData.get("email")||"").trim(),
       phone:String(formData.get("phone")||"").trim(),
       location:String(formData.get("location")||"").trim(),
       timing:"Contact page enquiry",
       details:"Requested service: "+rawService+"\n"+String(formData.get("message")||""),
       consent:formData.get("consent")!==null,
       website:String(formData.get("website")||""),
       started_at:contactStarted
     };
     var submit=contactForm.querySelector('button[type="submit"]');if(submit){submit.disabled=true;submit.textContent="Sending enquiry…";}
     if(status){status.textContent="Submitting your request…";}
     try{
       var response=await fetch(cfg.endpoint||"/wp-json/fsc/v1/inquiry",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify(payload)});
       var output=await response.json().catch(function(){return {};});
       if(!response.ok||!output.ok)throw Error(output.message||"Could not submit.");
       if(status){status.textContent=output.notification_sent?"Your enquiry was saved and email notification was queued.":"Your enquiry was saved. Email delivery is not confirmed; call (916) 225-1015 if urgent.";}
       contactForm.reset();contactStarted=Math.floor(Date.now()/1000);
     }catch(err){if(status)status.textContent=(err.message||"Submission failed.")+" You can call (916) 225-1015 or email contact@5starconstructionsca.com.";}
     finally{if(submit){submit.disabled=false;submit.textContent="Send project enquiry ↗";}}
   },true);
   var contactActions=document.querySelector(".contact-direct-actions a.button");
   if(contactActions){contactActions.href="tel:+19162251015";}
   var contactIntro=document.querySelector(".contact-direct p:not(.arq-contact-assurance)");
   if(contactIntro){contactIntro.textContent="Use the enquiry form above to tell us about your project, or call the 5 Star Constructions team directly."; }
   var contactAssurance=document.querySelector(".contact-direct .arq-contact-assurance");
   if(contactAssurance){contactAssurance.textContent="Project enquiries go to the 5 Star Constructions team. Your optional design brief stays on your device unless you choose to share it."; }
 }
 syncContactLinks();window.addEventListener("load",syncContactLinks);
 setTimeout(syncContactLinks,600);
 if(cfg.isHome){
   var heroAction=document.querySelector("section.arq-hero .arq-hero-actions a.arq-quiet-link");
   if(heroAction){heroAction.href="tel:+19162251015";heroAction.innerHTML="(916) 225-1015 <span aria-hidden='true'>↗</span>";heroAction.setAttribute("aria-label","Call 5 Star Constructions at (916) 225-1015");}
   var gallery=document.getElementById("fsc-ux-gallery");
   var material=document.querySelector("section.material-feature");
   if(gallery&&material){material.insertAdjacentElement("beforebegin",gallery);material.hidden=true;}
   else if(gallery){var anchor=document.querySelector("section.expertise");if(anchor)anchor.insertAdjacentElement("afterend",gallery);}
   var redundant=document.querySelector("section.client-review-cta");if(redundant)redundant.hidden=true;
   var filters=gallery?gallery.querySelector(".fsc-ux-filters"):null;
   if(filters)filters.addEventListener("click",function(e){
     var btn=e.target.closest("[data-fsc-filter]");if(!btn)return;
     var chosen=btn.getAttribute("data-fsc-filter");
     filters.querySelectorAll("button").forEach(function(b){var active=b===btn;b.classList.toggle("active",active);b.setAttribute("aria-pressed",active?"true":"false");});
     gallery.querySelectorAll(".fsc-ux-gallery-card").forEach(function(card){card.hidden=chosen!=="all"&&card.getAttribute("data-fsc-category")!==chosen;});
   });
 }
 if(location.pathname.indexOf("/bathroom-gallery")!==-1){
   var desc=document.querySelector(".client-gallery-intro > p:not(.gallery-disclosure)");
   if(desc&&/real bathroom transformations/i.test(desc.textContent))desc.textContent="Explore bathroom design directions and before-and-after inspiration from our remodeling gallery.";
 }
});
})();