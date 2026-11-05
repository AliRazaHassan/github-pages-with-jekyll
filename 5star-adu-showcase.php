<?php
/**
 * Plugin Name: 5 Star Constructions — Branded ADU 3D Viewer
 * Description: First-party interactive ADU concept viewer with orbit, zoom, interior cutaway and accessible fallback.
 * Version: 1.6.2
 * Author: 5 Star Constructions
 */
if (!defined('ABSPATH')) exit;


// First-party 3D asset URLs. The GLB files are in this WordPress installation's
// uploads folder and have passed binary GLB v2 validation.
add_action('wp_head', function() {
    if (!fsc_adu_showcase_is_page()) return;
    $up=wp_upload_dir();
    $base=trailingslashit($up['baseurl']).'5star-adu-models/';
    $models=[
      'glass-house'=>$base.'glass-house.glb',
      'modern-house'=>$base.'modern-house.glb',
      'bungalow'=>$base.'bungalow.glb'
    ];
    echo '<script type="importmap" id="fsc-adu-import-map">'.wp_json_encode(['imports'=>[
      'three'=>$base.'vendor/build/three.module.js',
      'three/addons/'=>$base.'vendor/examples/jsm/'
    ]],JSON_UNESCAPED_SLASHES).'</script>'."\n";
    echo '<script id="fsc-adu-model-paths">window.fscADUModelUrls='.wp_json_encode($models,JSON_UNESCAPED_SLASHES).';</script>'."\n";
}, 1);

function fsc_adu_showcase_is_page() {
    if (is_admin()) return false;
    $path = trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    return is_post_type_archive('five_adu') || $path === 'adu-models' || $path === 'atelier/adu-models';
}
// The legacy catalogue must stay active to serve /adu-models/, but its empty
// placeholder and linked former-company copy are redundant in the new 3D page.
// Remove ONLY those two known empty sections from the delivered page.
add_action('template_redirect', function() {
    if (!fsc_adu_showcase_is_page()) return;
    ob_start(function($html) {
        if (!is_string($html) || stripos($html, '</html>') === false) return $html;
        $patterns = [
            '~<section\b(?=[^>]*\bclass=["\x27][^"\x27]*\bfive-adu-wrap\b)[^>]*>[\s\S]*?</section>~i',
            '~<section\b(?=[^>]*\bclass=["\x27][^"\x27]*\bfive-star-adu-empty\b)[^>]*>[\s\S]*?</section>~i',
        ];
        foreach ($patterns as $pattern) {
            $updated = preg_replace($pattern, '', $html, 1);
            if (is_string($updated)) $html = $updated;
        }
        return $html;
    });
}, 2);

add_filter('body_class', function($classes) {
    if (fsc_adu_showcase_is_page()) $classes[] = 'fsc-adu-showcase-active';
    return $classes;
}, 15);
add_action('wp_enqueue_scripts', function() {
    if (!fsc_adu_showcase_is_page()) return;
    wp_enqueue_style('fsc-adu-showcase-css', plugin_dir_url(__FILE__) . 'assets/adu.css', [], '1.6.2');
    wp_enqueue_script('fsc-adu-showcase-js', plugin_dir_url(__FILE__) . 'assets/adu.js', [], '1.6.2', true);
    wp_enqueue_script('fsc-real-adu-js', plugin_dir_url(__FILE__) . 'assets/real-adu.js', ['fsc-adu-showcase-js'], '1.6.2', true);
});

// Preserve a selected concept in the existing Contact form without relying on JS.
add_action('template_redirect', function() {
    if (is_admin()) return;
    $path = trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    if ($path !== 'atelier/contact') return;
    $choice = isset($_GET['adu_concept']) ? sanitize_key(wp_unslash($_GET['adu_concept'])) : '';
    $names = ['studio'=>'Studio Retreat','courtyard'=>'Courtyard One','family'=>'Family Nest','glass-house'=>'Glass House 3D reference','modern-house'=>'Modern House 3D reference','bungalow'=>'Craftsman Bungalow 3D reference','detached'=>'Detached ADU inspiration','garage'=>'Garage conversion inspiration','courtyardadu'=>'Backyard ADU inspiration'];
    if (!isset($names[$choice])) return;
    $message = esc_textarea('I am interested in the ' . $names[$choice] . ' ADU concept. Please discuss property feasibility, planning requirements and the next steps.');
    ob_start(function($html) use ($message) {
        if (!is_string($html) || $html === '') return $html;
        $html = preg_replace_callback('/(<textarea\b(?=[^>]*\bname=["\']message["\'])[^>]*>)([\s\S]*?)(<\/textarea>)/i', function($m) use ($message) {
            return $m[1] . $message . $m[3];
        }, $html, 1);
        return str_replace('<option>Other / ask about availability</option>', '<option selected>Other / ask about availability</option>', $html);
    });
}, 5);
function fsc_adu_render_3d_showcase() {
    static $rendered=false;
    if ($rendered || !fsc_adu_showcase_is_page()) return;
    $rendered=true;
    ?>
<section id="fsc-real-adu" class="fsc-real-adu fsc-adu-3d-page" aria-labelledby="fsc-real-title">
  <div class="fsc-real-intro">
    <span class="fsc-real-eyebrow">5 STAR CONSTRUCTIONS / INTERACTIVE ADU DESIGN</span>
    <h1 id="fsc-real-title">Explore ADUs <em>in 3D.</em></h1>
    <p>Browse three downloadable architectural 3D models, now hosted directly on our website. Rotate, zoom and inspect the real model geometry to explore styles for your future space.</p>
    <p class="fsc-real-disclaimer"><strong>Independent 3D design references (CC0).</strong> These are architectural visualization assets, not completed 5 Star projects, approved ADU layouts or build-ready construction plans. Modern House is a two-storey reference; sizes and permitted layouts require a property-specific review.</p>
  </div>
  <section class="fsc-adu-3d-section" aria-label="5 Star interactive 3D architectural model viewer">
    <div class="fsc-adu-3d-heading">
      <div><span class="fsc-real-eyebrow">YOUR ADU, YOUR SPACE</span>
        <h2 id="fsc-adu-3d-title">Glass <em>House</em></h2>
        <p id="fsc-adu-3d-description">Contemporary glazed architecture · Actual downloadable GLB model, not a slideshow.</p>
      </div>
      <span class="fsc-adu-own-brand"><span aria-hidden="true">✦</span> 5 STAR INTERACTIVE 3D</span>
    </div>
    <div class="fsc-adu-3d-selector" role="group" aria-label="Choose an ADU design concept">
      <button type="button" data-fsc-3d-model="glass-house" class="is-current" aria-pressed="true">
        <span>01 / CONTEMPORARY</span><strong>Glass House</strong><small>Glass pavilion · furnished interior</small>
      </button>
      <button type="button" data-fsc-3d-model="modern-house" aria-pressed="false">
        <span>02 / MODERN</span><strong>Modern House</strong><small>Two-storey design reference</small>
      </button>
      <button type="button" data-fsc-3d-model="bungalow" aria-pressed="false">
        <span>03 / CRAFTSMAN</span><strong>Craftsman Bungalow</strong><small>Single-storey home concept</small>
      </button>
    </div>
    <div class="fsc-adu-3d-frame" id="fsc-adu-3d-frame">
      <canvas id="fsc-adu-own-canvas" tabindex="0" aria-label="Interactive three-dimensional ADU concept. Drag to rotate, scroll or pinch to zoom."></canvas>
      <div id="fsc-adu-3d-loading" class="fsc-adu-loading" aria-live="polite">Loading locally hosted 3D model…</div>
      <div class="fsc-adu-own-overlay" aria-hidden="true"><span>5 STAR CONSTRUCTIONS</span><small>REAL 3D GEOMETRY · CONCEPT REFERENCE</small></div>
      <div id="fsc-adu-3d-no-webgl" class="fsc-adu-3d-no-webgl" hidden>
        <strong>Lightweight 3D mode</strong>
        <p>Your browser can't display the detailed 3D scene. The compatible interactive concept viewer is available below.</p>
        <button type="button" id="fsc-adu-3d-open-fallback">Open lightweight 3D viewer</button>
      </div>
    </div>
    <div class="fsc-adu-3d-toolbar" role="group" aria-label="3D view controls">
      <div class="fsc-adu-orbit-actions">
        <button type="button" data-fsc-3d-action="left" aria-label="Rotate left"><span aria-hidden="true">↶</span> Left</button>
        <button type="button" data-fsc-3d-action="right" aria-label="Rotate right">Right <span aria-hidden="true">↷</span></button>
        <button type="button" data-fsc-3d-action="zoom-in">＋ Zoom</button>
        <button type="button" data-fsc-3d-action="zoom-out">－ Zoom</button>
      </div>
      <div class="fsc-adu-orbit-actions">
        <button type="button" data-fsc-3d-action="wireframe" aria-pressed="false">Wireframe</button>
        <button type="button" data-fsc-3d-action="reset">Reset view</button><button type="button" data-fsc-3d-action="fullscreen">Full screen</button>
      </div>
    </div>
    <p class="fsc-adu-3d-hint">Drag or swipe to orbit · Scroll or pinch to zoom · Switch actual 3D model files above. All models load from this website.</p>
    <div id="fsc-adu-fallback" hidden>
      <p>Fallback is a simplified illustrative model for browsers without WebGL. The main viewer loads real GLB files.</p>
    </div>
    <div class="fsc-adu-3d-cta">
      <div><h3>Have a property in mind? <em>Let's talk ADUs.</em></h3>
        <p>Share your property size and priorities, and our team can discuss local rules, possibilities and next steps.</p>
      </div>
      <div>
        <a id="fsc-adu-own-consult" href="/atelier/contact/?adu_concept=glass-house#consultation">Request ADU consultation ↗</a>
        <a href="tel:+19162251015">Call (916) 225-1015</a>
      </div>
    </div>
  </section>
</section>
    <?php
}
add_action('get_footer','fsc_adu_render_3d_showcase',16);
add_action('wp_footer','fsc_adu_render_3d_showcase',16);

function fsc_adu_render_fallback_model() {
    static $rendered=false;
    if ($rendered || !fsc_adu_showcase_is_page()) return;
    $rendered=true;
?>
<section id="fsc-adu-v2" class="fsc-adu" aria-labelledby="fsc-adu-title">
  <div class="fsc-adu-intro">
    <div class="fsc-adu-eyebrow"><span></span> 5 STAR CONSTRUCTIONS / ACCESSORY DWELLING UNITS</div>
    <div class="fsc-adu-heading">
      <div>
        <h2 id="fsc-adu-title">More space. <em>More possibilities.</em></h2>
        <p>Explore three design concepts for a future ADU. Rotate each 3D concept, explore the floor plan, and talk with our team about what your property could support.</p>
      </div>
      <a class="fsc-adu-top-cta" href="tel:+19162251015">Call (916) 225-1015 <span aria-hidden="true">↗</span></a>
    </div>
    <div class="fsc-adu-disclaimer" role="note"><strong>Design concepts, not completed projects.</strong> These are original illustrative models, not construction drawings or approved ADU plans. Sizes, floor plans, setbacks, codes and feasibility require site-specific review.</div>
  </div>

  <div class="fsc-adu-models" role="group" aria-label="Choose an ADU concept">
    <button class="fsc-adu-choice is-current" type="button" data-adu-model="studio" aria-pressed="true">
      <span class="fsc-adu-choice-number">01 / COMPACT STUDIO</span>
      <strong>Studio Retreat</strong><span class="fsc-adu-choice-meta">Approx. 420 sq ft <span class="fsc-adu-dot">·</span> Studio / 1 bath</span>
      <svg class="fsc-adu-choice-sketch" viewBox="0 0 270 108" aria-hidden="true"><path d="M20 88V39L80 18L240 30V88Z" fill="#d9cbb9" stroke="#28475c" stroke-width="2"/><path d="M20 39L80 18L240 30L184 50Z" fill="#435568"/><path d="M73 47H137V77H73Z" fill="#7d9cac"/><path d="M168 49H211V87H168Z" fill="#946b53"/><path d="M28 88H245" stroke="#6d7e83" stroke-width="3"/></svg>
    </button>
    <button class="fsc-adu-choice" type="button" data-adu-model="courtyard" aria-pressed="false">
      <span class="fsc-adu-choice-number">02 / ONE BEDROOM</span>
      <strong>Courtyard One</strong><span class="fsc-adu-choice-meta">Approx. 600 sq ft <span class="fsc-adu-dot">·</span> 1 bed / 1 bath</span>
      <svg class="fsc-adu-choice-sketch" viewBox="0 0 270 108" aria-hidden="true"><path d="M18 88V42L100 15L250 31V88Z" fill="#efe1d1" stroke="#28475c" stroke-width="2"/><path d="M18 42L100 15L250 31L168 59Z" fill="#4d5963"/><path d="M43 49H130V81H43Z" fill="#6b96ab"/><path d="M190 50H227V88H190Z" fill="#ab7558"/><path d="M24 89H250" stroke="#6d7e83" stroke-width="3"/></svg>
    </button>
    <button class="fsc-adu-choice" type="button" data-adu-model="family" aria-pressed="false">
      <span class="fsc-adu-choice-number">03 / TWO BEDROOM</span>
      <strong>Family Nest</strong><span class="fsc-adu-choice-meta">Approx. 820 sq ft <span class="fsc-adu-dot">·</span> 2 beds / 1 bath</span>
      <svg class="fsc-adu-choice-sketch" viewBox="0 0 270 108" aria-hidden="true"><path d="M14 89V38L105 15L256 35V89Z" fill="#dce4e0" stroke="#28475c" stroke-width="2"/><path d="M14 38L105 15L256 35L165 59Z" fill="#384b5b"/><path d="M36 48H98V80H36ZM115 51H161V81H115Z" fill="#789cad"/><path d="M205 57H240V89H205Z" fill="#aa8361"/><path d="M17 90H256" stroke="#6d7e83" stroke-width="3"/></svg>
    </button>
  </div>

  <div class="fsc-adu-stage">
    <div class="fsc-adu-stage-heading">
      <div><span class="fsc-adu-small-label">INTERACTIVE CONCEPT VIEWER</span><h2 id="fsc-adu-model-title">Studio Retreat</h2><p id="fsc-adu-model-subtitle">Compact living with a warm modern exterior.</p></div>
      <div class="fsc-adu-view-tabs" role="group" aria-label="Viewer display mode">
        <button type="button" data-adu-mode="3d" class="is-current" aria-pressed="true">3D exterior</button>
        <button type="button" data-adu-mode="open" aria-pressed="false">Roof off</button>
        <button type="button" data-adu-mode="plan" aria-pressed="false">Floor plan</button>
      </div>
    </div>
    <div class="fsc-adu-stage-grid">
      <div class="fsc-adu-visual">
        <div class="fsc-adu-canvas-wrap">
          <canvas id="fsc-adu-canvas" aria-label="Rotatable 3D architectural concept. Drag to rotate or use the view buttons below." tabindex="0">Your browser does not support canvas. The concept descriptions and measurements remain available below.</canvas>
          <div class="fsc-adu-canvas-badge">CONCEPT VISUALIZATION <span>·</span> NOT TO SCALE</div>
          <div class="fsc-adu-hint"><span aria-hidden="true">↔</span> Drag to rotate <span>·</span> Scroll or use +/− to zoom</div>
        </div>
        <div class="fsc-adu-controls" role="group" aria-label="3D viewer controls">
          <button type="button" id="fsc-adu-left" aria-label="Rotate model left"><span aria-hidden="true">↶</span> Rotate</button>
          <button type="button" id="fsc-adu-right" aria-label="Rotate model right"><span aria-hidden="true">↷</span> Rotate</button>
          <button type="button" id="fsc-adu-zoom-in" aria-label="Zoom in">+</button>
          <button type="button" id="fsc-adu-zoom-out" aria-label="Zoom out">−</button>
          <button type="button" id="fsc-adu-reset">Reset view</button>
        </div>
        <p class="fsc-adu-visual-note">This is a lightweight interactive architectural concept rendered without WebGL. It does not represent survey data, engineering or a build-ready 3D file.</p>
      </div>
      <aside class="fsc-adu-spec">
        <span class="fsc-adu-small-label">THE CONCEPT AT A GLANCE</span>
        <h3 id="fsc-adu-spec-title">Studio Retreat</h3>
        <p id="fsc-adu-spec-description">An efficient open living space with room for cooking, relaxing and a private bath.</p>
        <dl class="fsc-adu-specs">
          <div><dt>Illustrative area</dt><dd id="fsc-adu-area">420 sq ft</dd></div>
          <div><dt>Concept layout</dt><dd id="fsc-adu-layout">Studio · 1 bathroom</dd></div>
          <div><dt>Approx. footprint</dt><dd id="fsc-adu-footprint">20 × 21 ft</dd></div>
          <div><dt>Design direction</dt><dd id="fsc-adu-style">Warm modern</dd></div>
        </dl>
        <h4>Concept features</h4>
        <ul id="fsc-adu-features"><li>Flexible open living and sleeping zone</li><li>Dedicated bathroom</li><li>Compact modern kitchen</li></ul>
        <div class="fsc-adu-next-step">
          <p>Have a site in mind? A project-specific consultation is the next step.</p>
          <a id="fsc-adu-consult" href="/atelier/contact/?adu_concept=studio#consultation">Ask about this concept <span aria-hidden="true">↗</span></a>
        </div>
      </aside>
    </div>
  </div>
  <div class="fsc-adu-process">
    <div><span class="fsc-adu-small-label">HOW TO GET STARTED</span><h2>From concept to <em>your property.</em></h2></div>
    <div class="fsc-adu-steps">
      <div><span>01</span><strong>Review your site</strong><p>Location, access, utilities and local planning rules.</p></div>
      <div><span>02</span><strong>Confirm the scope</strong><p>Layout, desired use, approximate size and priorities.</p></div>
      <div><span>03</span><strong>Discuss next steps</strong><p>Feasibility, permitting and a tailored estimate.</p></div>
    </div>
  </div>
  <div class="fsc-adu-bottom"><div><span class="fsc-adu-small-label">LET'S TALK ABOUT YOUR ADU</span><h2>Room for what's next.</h2><p>Explore the possibilities with the 5 Star Constructions team.</p></div><div><a href="tel:+19162251015">Call (916) 225-1015</a><a href="/atelier/contact/#consultation">Request a free consultation ↗</a></div></div>
</section>
<?php
}
add_action('get_footer','fsc_adu_render_fallback_model',17);
add_action('wp_footer','fsc_adu_render_fallback_model',17);
