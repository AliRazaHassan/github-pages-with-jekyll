<?php
/**
 * Plugin Name: 5 Star Constructions — Who We Serve Map
 * Description: Homepage service areas with city/county switcher and interactive OpenStreetMap.
 * Version: 1.0.0
 * Author: 5 Star Constructions
 */
if (!defined('ABSPATH')) exit;

function fsc_who_we_serve_visible() {
    if (is_admin()) return false;
    $path = trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    return is_front_page() || is_home() || is_page('atelier') || is_page(5) || $path === '' || $path === 'atelier';
}

add_action('wp_footer', function() {
    if (!fsc_who_we_serve_visible()) return;
    $cities = [
        ['Sacramento', 38.5816, -121.4944],
        ['Natomas', 38.6455, -121.5147],
        ['Land Park', 38.5456, -121.5064],
        ['Oak Park', 38.5513, -121.4735],
        ['Carmichael', 38.6171, -121.3283],
        ['Elk Grove', 38.4088, -121.3716],
        ['Folsom', 38.6780, -121.1761],
        ['Roseville', 38.7521, -121.2880],
        ['Rocklin', 38.7907, -121.2358],
        ['Rancho Cordova', 38.5891, -121.3027],
        ['Davis', 38.5449, -121.7405],
        ['Vacaville', 38.3566, -121.9877],
        ['Woodland', 38.6785, -121.7733],
    ];
    $counties = [
        ['Sacramento County', 38.45, -121.35],
        ['Yolo County', 38.65, -121.90],
        ['Placer County', 39.10, -120.76],
        ['Yuba County', 39.27, -121.35],
        ['Sutter County', 39.06, -121.70],
    ];
    ?>
<style>
#fsc-serve,#fsc-serve *{box-sizing:border-box}
#fsc-serve{position:relative;z-index:1;width:100%;padding:72px max(24px,calc((100vw - 1330px)/2));background:#fafafa;color:#242424;font-family:inherit}
#fsc-serve .fsc-layout{width:100%;margin:auto;display:grid;grid-template-columns:1.06fr 1fr;gap:30px;align-items:stretch}
#fsc-serve .fsc-copy{display:flex;min-width:0;flex-direction:column;justify-content:flex-start}
#fsc-serve .fsc-eyebrow{margin:0 0 14px;font-size:10px;letter-spacing:.34em;text-transform:uppercase;color:#253847;font-weight:600}
#fsc-serve h2{margin:0 0 16px;color:#202124;font-weight:750;letter-spacing:-.035em;font-size:clamp(32px,3.5vw,46px);line-height:1.1}
#fsc-serve .fsc-sub{font-size:15px;line-height:1.55;color:#222;max-width:460px;margin:0 0 20px}
#fsc-serve .fsc-tabs{display:flex;gap:5px;margin:0 0 21px}
#fsc-serve button.fsc-tab{min-width:116px;height:35px;border:0;border-radius:99px;background:#eaeaea;color:#666;font:500 12px/1.1 inherit;padding:0 20px;cursor:pointer;box-shadow:none}
#fsc-serve button.fsc-tab[aria-selected=true]{background:#202224;color:white}
#fsc-serve button:focus-visible{outline:3px solid #7faaa0;outline-offset:2px}
#fsc-serve .fsc-tags-card{margin-top:auto;background:#fff;border:1px solid #e7e7e7;border-radius:10px;padding:14px;min-height:92px}
#fsc-serve .fsc-tags{list-style:none;padding:0;margin:0;display:flex;flex-wrap:wrap;gap:7px}
#fsc-serve .fsc-tags li{list-style:none;margin:0;padding:0}
#fsc-serve button.fsc-place{display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:8px 10px;border:0;border-radius:99px;background:#f3f3f3;color:#5d6870;font:500 11px/1.25 inherit;cursor:pointer;white-space:nowrap;box-shadow:none;transition:background .18s ease}
#fsc-serve button.fsc-place:hover,#fsc-serve button.fsc-place.active{background:#dce8dd;color:#1d3740}
#fsc-serve .fsc-more{font-size:11px;padding:9px 6px;color:#727272}
#fsc-serve .fsc-map{height:295px;position:relative;border-radius:12px;overflow:hidden;background:#dce7dd;box-shadow:0 2px 14px rgba(20,44,25,.06)}
#fsc-serve .fsc-map iframe{position:absolute;inset:0;border:0;width:100%;height:100%;filter:saturate(.75) contrast(.97);background:#e2e9df}
#fsc-serve .fsc-map:after{content:"";position:absolute;inset:0;pointer-events:none;background:rgba(205,220,207,.09)}
#fsc-serve .fsc-map-caption{position:absolute;z-index:2;top:14px;left:14px;background:#fffffff2;border-radius:8px;padding:9px 11px;font-size:12px;font-weight:600;box-shadow:0 2px 14px #0002}
#fsc-serve .fsc-map-link{position:absolute;z-index:2;bottom:12px;right:12px;background:#fffffff2;padding:8px 11px;border-radius:7px;color:#243b4b;font-size:11px;text-decoration:none;box-shadow:0 2px 12px #0002}
#fsc-serve [hidden]{display:none!important}
@media(max-width:930px){#fsc-serve{padding:54px 22px}#fsc-serve .fsc-layout{grid-template-columns:1fr}#fsc-serve .fsc-map{height:280px}#fsc-serve .fsc-tags-card{margin-top:0}}
@media(max-width:480px){#fsc-serve{padding:45px 16px}#fsc-serve h2{font-size:33px}#fsc-serve .fsc-map{height:240px}#fsc-serve .fsc-tags-card{padding:11px}#fsc-serve button.fsc-place{font-size:10px;padding:7px 8px}}
@media(prefers-reduced-motion:reduce){#fsc-serve button.fsc-place{transition:none}}
</style>
<section id="fsc-serve" aria-labelledby="fsc-serve-title">
  <div class="fsc-layout">
    <div class="fsc-copy">
      <p class="fsc-eyebrow">SERVICE AREAS</p>
      <h2 id="fsc-serve-title">Who We Serve</h2>
      <p class="fsc-sub">Bringing expert renovations to your community. Explore the areas we proudly serve.</p>
      <div class="fsc-tabs" role="tablist" aria-label="Service areas">
        <button type="button" id="fsc-cities-tab" class="fsc-tab" role="tab" aria-controls="fsc-cities-panel" aria-selected="true">Cities</button>
        <button type="button" id="fsc-counties-tab" class="fsc-tab" role="tab" aria-controls="fsc-counties-panel" aria-selected="false">Counties</button>
      </div>
      <div class="fsc-tags-card">
        <div id="fsc-cities-panel" role="tabpanel" aria-labelledby="fsc-cities-tab">
          <ul class="fsc-tags">
            <?php foreach ($cities as $a): ?>
              <li><button type="button" class="fsc-place" data-lat="<?php echo esc_attr($a[1]); ?>" data-lon="<?php echo esc_attr($a[2]); ?>" data-name="<?php echo esc_attr($a[0]); ?>">⌖ <?php echo esc_html($a[0]); ?></button></li>
            <?php endforeach; ?>
            <li class="fsc-more">and many more...</li>
          </ul>
        </div>
        <div id="fsc-counties-panel" role="tabpanel" aria-labelledby="fsc-counties-tab" hidden>
          <ul class="fsc-tags">
            <?php foreach ($counties as $a): ?>
              <li><button type="button" class="fsc-place" data-lat="<?php echo esc_attr($a[1]); ?>" data-lon="<?php echo esc_attr($a[2]); ?>" data-name="<?php echo esc_attr($a[0]); ?>">⌖ <?php echo esc_html($a[0]); ?></button></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
    <div class="fsc-map" aria-label="Interactive map showing the Sacramento area">
      <iframe title="Interactive Sacramento service-area map" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.openstreetmap.org/export/embed.html?bbox=-121.71%2C38.40%2C-121.27%2C38.78&amp;layer=mapnik&amp;marker=38.5816%2C-121.4944"></iframe>
      <div class="fsc-map-caption" aria-live="polite">Sacramento, CA</div>
      <a class="fsc-map-link" target="_blank" rel="noopener noreferrer" href="https://www.openstreetmap.org/?mlat=38.5816&amp;mlon=-121.4944#map=12/38.5816/-121.4944">Open larger map ↗</a>
    </div>
  </div>
</section>
<script>
(function(){
 function init(){
  var root=document.getElementById('fsc-serve');if(!root||root.dataset.ready)return;root.dataset.ready='1';
  var tabs=[root.querySelector('#fsc-cities-tab'),root.querySelector('#fsc-counties-tab')];
  var panels=[root.querySelector('#fsc-cities-panel'),root.querySelector('#fsc-counties-panel')];
  function toggle(i){for(var j=0;j<2;j++){tabs[j].setAttribute('aria-selected',String(i===j));panels[j].hidden=i!==j;}}
  tabs[0].addEventListener('click',function(){toggle(0)});tabs[1].addEventListener('click',function(){toggle(1)});
  var iframe=root.querySelector('iframe'),caption=root.querySelector('.fsc-map-caption'),link=root.querySelector('.fsc-map-link');
  root.querySelectorAll('button.fsc-place').forEach(function(btn){btn.addEventListener('click',function(){
    var lat=Number(btn.dataset.lat),lon=Number(btn.dataset.lon),nm=btn.dataset.name;
    if(!Number.isFinite(lat)||!Number.isFinite(lon))return;
    var spread=nm.indexOf('County')!==-1?0.5:0.18;
    var b=[lon-spread,lat-spread*.73,lon+spread,lat+spread*.73].map(function(n){return n.toFixed(5)}).join(',');
    iframe.src='https://www.openstreetmap.org/export/embed.html?bbox='+encodeURIComponent(b)+'&layer=mapnik&marker='+encodeURIComponent(lat+','+lon);
    caption.textContent=nm+', CA';iframe.title='Interactive map of '+nm+', California';
    link.href='https://www.openstreetmap.org/?mlat='+lat+'&mlon='+lon+'#map='+(spread>.3?9:12)+'/'+lat+'/'+lon;
    root.querySelectorAll('button.fsc-place').forEach(function(b){b.classList.remove('active')});btn.classList.add('active');
  })});
  function reposition(){
    var target=document.querySelector('section.expertise,#services,section.services,section[id*=services]');
    if(!target){var heads=[].slice.call(document.querySelectorAll('main h2,main h3,section h2,section h3,main p,section p'));var h=heads.find(function(e){return /^(our services|services|what we do)$/i.test((e.textContent||'').trim())});target=h?h.closest('section'):null;}
    if(target&&target!==root&&!root.contains(target)&&target.parentElement){target.insertAdjacentElement('afterend',root);return;}
    var footer=document.querySelector('footer');if(footer&&footer.parentElement){footer.insertAdjacentElement('beforebegin',root);return;}
    var main=document.querySelector('main');if(main)main.appendChild(root);
  }
  reposition();
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
</script>
    <?php
}, 19);
