(function(){
"use strict";
function ready(fn){if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",fn);else fn();}
ready(function(){
 var cfg=window.FSC_UX||{},root=document.getElementById("fsc-ux-root");if(!root)return;
 // Keep the Services mega-menu anchored to its trigger at every desktop width.
 // The theme still owns open/close behavior, so hover, focus and touch remain intact.
 // Keep mega-menu inside the Services LI so its cards are part of the
 // exact same hover/focus boundary as the trigger. Previously the menu was
 // a sibling at the header level and mouseleave closed it during travel.
 (function initServicesMegaMenu(){
   var header=document.querySelector("header.five-star-header");
   var trigger=header&&header.querySelector("#menu-item-28");
   if(!header||!trigger)return;
   var arrow=trigger.querySelector(".sub-menu-toggle");
   function position(){
     var mega=trigger.querySelector(".five-star-services-mega");
     if(!mega||window.innerWidth<=1000)return;
     var rect=trigger.getBoundingClientRect();
     var width=Math.min(900,window.innerWidth-36);
     // Keep the 900px photo grid in the viewport without losing its
     // pointer-connected relationship to the Services trigger.
     var left=Math.max(18-rect.left,Math.min(-12,window.innerWidth-18-rect.left-width));
     mega.style.setProperty("--fsc-services-menu-x",Math.round(left)+"px");
   }
   function sync(){
     if(window.innerWidth<=1000)return;
     var hover=trigger.matches(":hover")||trigger.contains(document.activeElement);
     trigger.classList.toggle("fsc-mega-open",hover||trigger.classList.contains("is-submenu-open"));
   }
   function attach(){
     // The theme may insert the photo menu AFTER our script starts. Watch for
     // that late node and reparent it immediately; never let it float as a
     // header-level sibling, which closes on Services pointerleave.
     var mega=header.querySelector(".five-star-services-mega");
     if(!mega)return;
     if(mega.parentElement!==trigger)trigger.appendChild(mega);
     if(mega.getAttribute("role")!=="group")mega.setAttribute("role","group");
     mega.setAttribute("aria-label","Remodeling services");
     position();
   }
   // Observing child-list changes only prevents feedback loops on hover.
   if(window.MutationObserver){
     var observer=new MutationObserver(attach);
     observer.observe(header,{childList:true,subtree:true});
   }
   trigger.addEventListener("pointerenter",function(){attach();trigger.classList.add("fsc-mega-open");},{passive:true});
   trigger.addEventListener("pointerleave",function(){trigger.classList.remove("fsc-mega-open");},{passive:true});
   trigger.addEventListener("focusin",function(){attach();trigger.classList.add("fsc-mega-open");});
   trigger.addEventListener("focusout",function(event){if(!trigger.contains(event.relatedTarget))setTimeout(sync,0);});
   if(arrow)arrow.addEventListener("click",function(){setTimeout(sync,0);});
   window.addEventListener("resize",position,{passive:true});
   window.addEventListener("load",attach,{once:true});
   attach();
 })();
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
   // Home audit cleanup: make core services discoverable even if the custom theme's
   // blank .service-list remains unpopulated, and relocate a single conversion CTA.
   var trustBand=document.querySelector("section.five-star-trust-strip");
   var expertiseSection=document.querySelector("section.expertise");
   if(trustBand&&expertiseSection)trustBand.insertAdjacentElement("afterend",expertiseSection);
   // Place the entire Service Areas / Who We Serve section directly before Reviews,
   // not under the services list. The map plugin can add its block late in the page.
   function placeServiceAreasAboveReviews(){
     var serviceAreas=document.getElementById("fsc-serve");
     var reviewsSection=document.querySelector("section.five-star-reviews");
     if(serviceAreas&&reviewsSection&&serviceAreas.nextElementSibling!==reviewsSection){
       reviewsSection.parentNode.insertBefore(serviceAreas,reviewsSection);
     }
   }
   placeServiceAreasAboveReviews();
   window.addEventListener("load",placeServiceAreasAboveReviews,{once:true});
   if(window.MutationObserver){
     var serviceAreaObserver=new MutationObserver(placeServiceAreasAboveReviews);
     serviceAreaObserver.observe(document.body,{childList:true,subtree:true});
     setTimeout(function(){serviceAreaObserver.disconnect();},5000);
   }
   var services=document.querySelector("section.expertise .service-list");
   if(services&&!services.querySelector("a,article")){
     var options=[
       {number:"01",title:"Kitchen Remodeling",desc:"Layouts, cabinets, finishes and everyday function.",href:"/atelier/services/kitchen/"},
       {number:"02",title:"Bathroom Remodeling",desc:"Showers, tubs, surfaces and smarter storage.",href:"/atelier/services/bathroom/"},
       {number:"03",title:"Whole-Home Remodeling",desc:"Plan connected spaces and a cohesive renovation.",href:"/atelier/services/whole-home/"}
     ];
     services.innerHTML=options.map(function(s){
       return '<a class="fsc-ux-service" href="'+s.href+'"><span class="fsc-ux-service-num">'+s.number+'</span><span class="fsc-ux-service-copy"><strong>'+s.title+'</strong><small>'+s.desc+'</small></span><span class="fsc-ux-service-arrow" aria-hidden="true">↗</span></a>';
     }).join("");
     var expertiseDesc=document.querySelector("section.expertise .section-head>p");
     if(expertiseDesc)expertiseDesc.textContent="Kitchen, bathroom and whole-home remodeling services across the Sacramento area.";
   }
   var reviews=document.querySelector("section.five-star-reviews");
   var lastCTA=document.querySelector("#fsc-ux-final-cta");
   if(reviews&&lastCTA)reviews.insertAdjacentElement("afterend",lastCTA);
   else if(lastCTA)document.querySelector("main")?.appendChild(lastCTA);
   var reviewLoop=document.querySelector("section.five-star-reviews a.review-link[href='#verified-reviews']");
   if(reviewLoop)reviewLoop.hidden=true;
   // Review display is clearly attributed to the affiliated team, not falsely sold as 5 Star reviews.
   var reviewHead=document.querySelector(".five-star-reviews .reviews-heading h2");
   if(reviewHead)reviewHead.textContent="Homeowners share their experience.";
   var reviewNote=document.querySelector(".five-star-reviews .reviews-heading p");
   if(reviewNote)reviewNote.textContent="Feedback originally published for the affiliated Blue Heaven Constructions team.";
   var gallery=document.getElementById("fsc-ux-gallery");
   var material=document.querySelector("section.material-feature");
   var comparison=document.querySelector("section.transformation-section");
   if(gallery&&comparison){comparison.insertAdjacentElement("afterend",gallery);}
   else if(gallery&&material){material.insertAdjacentElement("beforebegin",gallery);}
   else if(gallery&&expertiseSection){expertiseSection.insertAdjacentElement("afterend",gallery);}
   if(material)material.hidden=true;
   var redundant=document.querySelector("section.client-review-cta");if(redundant)redundant.hidden=true;
   var track=gallery?gallery.querySelector("#fsc-ux-carousel-track"):null;
   var prev=gallery?gallery.querySelector(".fsc-ux-carousel-prev"):null;
   var next=gallery?gallery.querySelector(".fsc-ux-carousel-next"):null;
   var counter=gallery?gallery.querySelector("#fsc-ux-carousel-count"):null;
   if(track&&prev&&next&&counter){
     var reducedMotion=window.matchMedia&&window.matchMedia("(prefers-reduced-motion: reduce)").matches;
     function visibleCards(){return Array.from(track.querySelectorAll(".fsc-ux-gallery-card:not([hidden])"));}
     function stride(){
       var cards=visibleCards();
       if(cards.length<2)return cards[0]?cards[0].getBoundingClientRect().width:1;
       return Math.max(1,cards[1].getBoundingClientRect().left-cards[0].getBoundingClientRect().left);
     }
     function updateCarousel(){
       var visible=visibleCards(),max=Math.max(0,track.scrollWidth-track.clientWidth);
       var pos=Math.max(0,Math.min(max,track.scrollLeft));
       prev.disabled=visible.length<2||max<3||pos<=3;
       next.disabled=visible.length<2||max<3||pos>=max-3;
       var current=visible.length?Math.min(visible.length,Math.max(1,Math.round(pos/stride())+1)):0;
       counter.textContent=String(current).padStart(2,"0")+" / "+String(visible.length).padStart(2,"0");
       track.setAttribute("aria-label","Remodeling design inspiration image carousel; "+current+" of "+visible.length);
     }
     function moveGallery(direction){
       if((direction<0&&prev.disabled)||(direction>0&&next.disabled))return;
       track.scrollBy({left:direction*stride(),behavior:reducedMotion?"auto":"smooth"});
     }
     prev.addEventListener("click",function(){moveGallery(-1);});
     next.addEventListener("click",function(){moveGallery(1);});
     track.addEventListener("keydown",function(e){
       if(e.target!==track)return;
       if(e.key==="ArrowLeft"||e.key==="ArrowRight"){
         e.preventDefault();
         moveGallery(e.key==="ArrowLeft"?-1:1);
       }
     });
     var scheduled=false;
     track.addEventListener("scroll",function(){
       if(scheduled)return;
       scheduled=true;
       requestAnimationFrame(function(){scheduled=false;updateCarousel();});
     },{passive:true});
     window.addEventListener("resize",updateCarousel,{passive:true});
     if(window.ResizeObserver){new ResizeObserver(updateCarousel).observe(track);}
     requestAnimationFrame(updateCarousel);
     window.addEventListener("load",updateCarousel,{once:true});

     // Premium on-site gallery lightbox: image click enlarges, never navigates away.
     // The modal is attached to body to escape transformed theme sections.
     (function initGalleryLightbox(){
       var modal=gallery.querySelector("#fsc-ux-lightbox");
       var cards=Array.from(track.querySelectorAll(".fsc-ux-gallery-card"));
       if(!modal||!cards.length)return;
       document.body.appendChild(modal);
       var large=modal.querySelector("#fsc-ux-lightbox-image");
       var count=modal.querySelector("#fsc-ux-lightbox-count");
       var title=modal.querySelector("#fsc-ux-lightbox-title");
       var caption=modal.querySelector("#fsc-ux-lightbox-caption");
       var credit=modal.querySelector("#fsc-ux-lightbox-credit");
       var source=modal.querySelector("#fsc-ux-lightbox-source");
       var close=modal.querySelector(".fsc-ux-lightbox-close");
       var back=modal.querySelector(".fsc-ux-lightbox-backdrop");
       var left=modal.querySelector(".fsc-ux-lightbox-prev");
       var right=modal.querySelector(".fsc-ux-lightbox-next");
       var stage=modal.querySelector(".fsc-ux-lightbox-stage");
       var position=0,lastFocus=null,swipeStart=null;
       var total=cards.length;
       function change(index){
         position=((index%total)+total)%total;
         var card=cards[position],image=card.querySelector("img");
         var type=(card.getAttribute("data-fsc-credit")||"").split(" · ")[0];
         var cardMeta=card.querySelector(".fsc-ux-card-meta strong");
         var category=cardMeta?cardMeta.textContent:"DESIGN INSPIRATION";
         var full=card.getAttribute("data-fsc-large")||image.currentSrc||image.src;
         var fallback=image.currentSrc||image.src;
         large.onerror=function(){
           if(large.src!==fallback){large.src=fallback;return;}
           large.onerror=null;
           large.alt="This image could not load. Choose another picture.";
         };
         large.src=full;
         large.alt=image.alt||"Enlarged "+category.toLowerCase()+" reference photo";
         count.textContent=String(position+1).padStart(2,"0")+" / "+String(total).padStart(2,"0");
         title.textContent=category.replace("-", " ")+" inspiration";
         caption.textContent="Design inspiration only · Not a completed 5 Star Constructions project.";
         credit.textContent=card.getAttribute("data-fsc-credit")||type;
         var url=card.getAttribute("data-fsc-source");
         if(url){source.href=url;source.hidden=false;}
         else{source.hidden=true;source.removeAttribute("href");}
       }
       function open(index){
         lastFocus=document.activeElement;
         change(index);
         modal.hidden=false;
         document.body.classList.add("fsc-gallery-lightbox-open");
         close.focus({preventScroll:true});
       }
       function dismiss(){
         if(modal.hidden)return;
         modal.hidden=true;
         document.body.classList.remove("fsc-gallery-lightbox-open");
         large.onerror=null;large.removeAttribute("src");
         if(lastFocus&&lastFocus.focus)lastFocus.focus({preventScroll:true});
       }
       cards.forEach(function(card,index){
         card.addEventListener("click",function(){open(index);});
       });
       close.addEventListener("click",dismiss);
       back.addEventListener("click",dismiss);
       left.addEventListener("click",function(){change(position-1);});
       right.addEventListener("click",function(){change(position+1);});
       document.addEventListener("keydown",function(e){
         if(modal.hidden)return;
         if(e.key==="Escape"){e.preventDefault();dismiss();return;}
         if(e.key==="ArrowLeft"||e.key==="ArrowRight"){
           e.preventDefault();change(position+(e.key==="ArrowLeft"?-1:1));return;
         }
         if(e.key==="Tab"){
           var focusable=Array.from(modal.querySelectorAll("button:not([hidden]):not([disabled]),a[href]:not([hidden])"))
             .filter(function(node){return node!==back&&!node.closest("[hidden]");});
           if(!focusable.length)return;
           var first=focusable[0],last=focusable[focusable.length-1];
           if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus();}
           else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus();}
         }
       });
       stage.addEventListener("pointerdown",function(e){
         if(e.target!==large)return;
         swipeStart={id:e.pointerId,x:e.clientX,y:e.clientY};
       });
       stage.addEventListener("pointerup",function(e){
         if(!swipeStart||e.pointerId!==swipeStart.id)return;
         var dx=e.clientX-swipeStart.x,dy=e.clientY-swipeStart.y;
         swipeStart=null;
         if(Math.abs(dx)>55&&Math.abs(dx)>Math.abs(dy)*1.2)change(position+(dx<0?1:-1));
       });
       stage.addEventListener("pointercancel",function(){swipeStart=null;});
     })();
   }
 }

 // Contact should have one actual form, not an unrelated three-step offline draft that
 // can look like a submitted enquiry. Legacy planner markup remains in theme, hidden.
 if(document.body.classList.contains("fsc-focused-contact")){
   var direct=document.querySelector(".contact-direct .kicker");
   if(direct)direct.textContent="NEED A QUICK ANSWER?";
   var directTitle=document.querySelector(".contact-direct h2");
   if(directTitle)directTitle.innerHTML="Prefer to talk?<br><em>Call our team.</em>";
   var directText=document.querySelector(".contact-direct p:not(.arq-contact-assurance)");
   if(directText)directText.textContent="Use the form above or speak with our team about your project.";
   var directSmall=document.querySelector(".contact-direct .arq-contact-assurance");
   if(directSmall)directSmall.textContent="Your enquiry is saved securely for the 5 Star team. Email notification delivery may depend on mail service configuration.";
 }
 // The ADU catalogue currently has no models. Remove the broken self-link instead
 // of advertising a non-existent collection, and give users a working next step.
 if(document.body.classList.contains("fsc-focused-adu")){
   var aduTitle=document.querySelector(".five-adu-wrap h1");
   if(aduTitle)aduTitle.textContent="ADU Design & Construction";
   var aduIntro=document.querySelector(".five-adu-wrap p");
   if(aduIntro)aduIntro.textContent="Thinking about an accessory dwelling unit? Contact our team to discuss your property, your goals and available design options.";
   var aduEmpty=document.querySelector(".five-star-adu-empty");
   if(aduEmpty){
     var msg=aduEmpty.querySelector("p");
     if(msg)msg.textContent="The ADU model catalogue is being updated. You can still request a property-specific consultation now.";
     var link=aduEmpty.querySelector("a");
     if(link){link.href="tel:+19162251015";link.textContent="Call (916) 225-1015 ↗";}
   }
 }
 // The Services index previously contained headings only. Add direct routes to
 // the site's three live remodeling service pages; no extra editorial filler.
 if(/^\/atelier\/services\/?$/.test(location.pathname)){
   var servicesMain=document.querySelector("main");
   if(servicesMain&&!servicesMain.querySelector(".fsc-ux-service-index")){
     var index=document.createElement("section");
     index.className="fsc-ux-service-index";
     index.setAttribute("aria-label","5 Star remodeling services");
     index.innerHTML='<div class="fsc-ux-service-index-inner">'
       +'<a href="/atelier/services/kitchen/"><span>01 / KITCHEN</span><strong>Kitchen Remodeling</strong><small>Layout, cabinetry and finishes</small><b aria-hidden="true">↗</b></a>'
       +'<a href="/atelier/services/bathroom/"><span>02 / BATHROOM</span><strong>Bathroom Remodeling</strong><small>Showers, tubs and complete renovations</small><b aria-hidden="true">↗</b></a>'
       +'<a href="/atelier/services/whole-home/"><span>03 / WHOLE HOME</span><strong>Whole-Home Remodeling</strong><small>Connected spaces and cohesive updates</small><b aria-hidden="true">↗</b></a>'
       +'</div><p>Ready to discuss your project? <a href="tel:+19162251015">Call (916) 225-1015</a> or <a href="/atelier/contact/#consultation">send project details</a>.</p>';
     servicesMain.appendChild(index);
   }
 }
 // Portfolio categories not backed by a core live service page are temporarily
 // hidden rather than deleted, pending verified case studies.
 if(/^\/atelier\/projects\/?$/.test(location.pathname)){
   document.querySelectorAll(".revive-gallery-group").forEach(function(group){
     var label=(group.querySelector(".revive-group-title .kicker")||{}).textContent||"";
     if(/COMMERCIAL REMODELING|TENANT IMPROVEMENTS/i.test(label))group.hidden=true;
   });
 }
 if(location.pathname.indexOf("/bathroom-gallery")!==-1){
   var desc=document.querySelector(".client-gallery-intro > p:not(.gallery-disclosure)");
   if(desc&&/real bathroom transformations/i.test(desc.textContent))desc.textContent="Explore bathroom design directions and before-and-after inspiration from our remodeling gallery.";
 }
});
})();