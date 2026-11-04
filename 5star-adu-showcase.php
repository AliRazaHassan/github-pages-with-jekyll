<?php
/**
 * Plugin Name: 5 Star Constructions — ADU 3D Concept Showcase
 * Description: Three lightweight original interactive ADU concept models and floor-plan previews.
 * Version: 1.1.0
 * Author: 5 Star Constructions
 */
if (!defined('ABSPATH')) exit;

function fsc_adu_showcase_is_page() {
    if (is_admin()) return false;
    $path = trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    return is_post_type_archive('five_adu') || $path === 'adu-models' || $path === 'atelier/adu-models';
}
add_filter('body_class', function($classes) {
    if (fsc_adu_showcase_is_page()) $classes[] = 'fsc-adu-showcase-active';
    return $classes;
}, 15);
add_action('wp_enqueue_scripts', function() {
    if (!fsc_adu_showcase_is_page()) return;
    wp_enqueue_style('fsc-adu-showcase-css', plugin_dir_url(__FILE__) . 'assets/adu.css', [], '1.1.0');
    wp_enqueue_script('fsc-adu-showcase-js', plugin_dir_url(__FILE__) . 'assets/adu.js', [], '1.1.0', true);
    wp_enqueue_script('fsc-real-adu-js', plugin_dir_url(__FILE__) . 'assets/real-adu.js', ['fsc-adu-showcase-js'], '1.1.0', true);
});

// Preserve a selected concept in the existing Contact form without relying on JS.
add_action('template_redirect', function() {
    if (is_admin()) return;
    $path = trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    if ($path !== 'atelier/contact') return;
    $choice = isset($_GET['adu_concept']) ? sanitize_key(wp_unslash($_GET['adu_concept'])) : '';
    $names = ['studio'=>'Studio Retreat','courtyard'=>'Courtyard One','family'=>'Family Nest'];
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
add_action('wp_footer', function() {
    if (!fsc_adu_showcase_is_page()) return;
    ?>
<section id="fsc-real-adu" class="fsc-real-adu" data-models="[{&quot;id&quot;:&quot;s1&quot;,&quot;name&quot;:&quot;Model S1&quot;,&quot;area&quot;:328,&quot;beds&quot;:1,&quot;baths&quot;:1,&quot;cover&quot;:&quot;https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/model-s1/card.webp&quot;,&quot;plan&quot;:&quot;https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/model-s1/gallery-02.webp&quot;,&quot;modern&quot;:&quot;https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/styles/modern/compact/01.webp&quot;,&quot;source&quot;:&quot;https://www.blueheavenconstructions.com/adu-models/model-s1&quot;,&quot;floorAlt&quot;:&quot;Interior or plan imagery&quot;},{&quot;id&quot;:&quot;a1&quot;,&quot;name&quot;:&quot;Model A1&quot;,&quot;area&quot;:462,&quot;beds&quot;:1,&quot;baths&quot;:1,&quot;cover&quot;:&quot;https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/model-a1/card.webp&quot;,&quot;plan&quot;:&quot;https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/model-a1/floor-01.webp&quot;,&quot;modern&quot;:&quot;https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/styles/modern/compact/02.webp&quot;,&quot;source&quot;:&quot;https://www.blueheavenconstructions.com/adu-models/model-a1&quot;,&quot;floorAlt&quot;:&quot;Illustrative floor plan&quot;},{&quot;id&quot;:&quot;a2&quot;,&quot;name&quot;:&quot;Model A2&quot;,&quot;area&quot;:500,&quot;beds&quot;:1,&quot;baths&quot;:1,&quot;cover&quot;:&quot;https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/model-a2/card.webp&quot;,&quot;plan&quot;:&quot;https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/model-a2/floor-01.webp&quot;,&quot;modern&quot;:&quot;https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/styles/modern/compact/04.webp&quot;,&quot;source&quot;:&quot;https://www.blueheavenconstructions.com/adu-models/model-a2&quot;,&quot;floorAlt&quot;:&quot;Illustrative floor plan&quot;},{&quot;id&quot;:&quot;a3&quot;,&quot;name&quot;:&quot;Model A3&quot;,&quot;area&quot;:594,&quot;beds&quot;:1,&quot;baths&quot;:1,&quot;cover&quot;:&quot;https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/model-a3/card.webp&quot;,&quot;plan&quot;:&quot;https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/model-a3/floor-01.webp&quot;,&quot;modern&quot;:&quot;https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/styles/modern/mid/01.webp&quot;,&quot;source&quot;:&quot;https://www.blueheavenconstructions.com/adu-models/model-a3&quot;,&quot;floorAlt&quot;:&quot;Illustrative floor plan&quot;},{&quot;id&quot;:&quot;b3&quot;,&quot;name&quot;:&quot;Model B3&quot;,&quot;area&quot;:702,&quot;beds&quot;:2,&quot;baths&quot;:1,&quot;cover&quot;:&quot;https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/model-b3/card.webp&quot;,&quot;plan&quot;:&quot;https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/model-b3/gallery-01.webp&quot;,&quot;modern&quot;:&quot;https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/styles/modern/mid/03.webp&quot;,&quot;source&quot;:&quot;https://www.blueheavenconstructions.com/adu-models/model-b3&quot;,&quot;floorAlt&quot;:&quot;Interior or plan imagery&quot;},{&quot;id&quot;:&quot;r4&quot;,&quot;name&quot;:&quot;Model R4&quot;,&quot;area&quot;:780,&quot;beds&quot;:1,&quot;baths&quot;:1,&quot;cover&quot;:&quot;https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/model-r4/card.webp&quot;,&quot;plan&quot;:&quot;https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/model-r4/floor-01.webp&quot;,&quot;modern&quot;:&quot;https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/styles/modern/mid/07.webp&quot;,&quot;source&quot;:&quot;https://www.blueheavenconstructions.com/adu-models/model-r4&quot;,&quot;floorAlt&quot;:&quot;Illustrative floor plan&quot;}]" aria-labelledby="fsc-real-title">
 <div class="fsc-real-intro">
  <span class="fsc-real-eyebrow">ADU MODEL COLLECTION · AFFILIATED DESIGN CATALOGUE</span>
  <h1 id="fsc-real-title">Explore real <em>ADU designs.</em></h1>
  <p>Browse published accessory dwelling unit models, including exterior renders and original plan imagery from our affiliated Blue Heaven Constructions catalogue.</p>
  <p class="fsc-real-disclaimer"><strong>Published model designs, not completed project photographs.</strong> Renderings and floor plans are design references. Exact configurations, price, permitting and build feasibility require a property assessment.</p>
 </div>
 <div class="fsc-real-grid">
  <button type="button" class="fsc-real-card" data-adu-real-model="s1" aria-pressed="true"><img loading="lazy" decoding="async" src="https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/model-s1/card.webp" alt="Model S1 catalogue exterior rendering"/><span class="fsc-real-card-body"><span class="fsc-real-card-kicker">PUBLISHED ADU DESIGN</span><strong>Model S1</strong><small>328 sq ft · 1 bed · 1 bath</small><span class="fsc-real-card-view">Explore this model ↗</span></span></button>
<button type="button" class="fsc-real-card" data-adu-real-model="a1" aria-pressed="false"><img loading="lazy" decoding="async" src="https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/model-a1/card.webp" alt="Model A1 catalogue exterior rendering"/><span class="fsc-real-card-body"><span class="fsc-real-card-kicker">PUBLISHED ADU DESIGN</span><strong>Model A1</strong><small>462 sq ft · 1 bed · 1 bath</small><span class="fsc-real-card-view">Explore this model ↗</span></span></button>
<button type="button" class="fsc-real-card" data-adu-real-model="a2" aria-pressed="false"><img loading="lazy" decoding="async" src="https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/model-a2/card.webp" alt="Model A2 catalogue exterior rendering"/><span class="fsc-real-card-body"><span class="fsc-real-card-kicker">PUBLISHED ADU DESIGN</span><strong>Model A2</strong><small>500 sq ft · 1 bed · 1 bath</small><span class="fsc-real-card-view">Explore this model ↗</span></span></button>
<button type="button" class="fsc-real-card" data-adu-real-model="a3" aria-pressed="false"><img loading="lazy" decoding="async" src="https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/model-a3/card.webp" alt="Model A3 catalogue exterior rendering"/><span class="fsc-real-card-body"><span class="fsc-real-card-kicker">PUBLISHED ADU DESIGN</span><strong>Model A3</strong><small>594 sq ft · 1 bed · 1 bath</small><span class="fsc-real-card-view">Explore this model ↗</span></span></button>
<button type="button" class="fsc-real-card" data-adu-real-model="b3" aria-pressed="false"><img loading="lazy" decoding="async" src="https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/model-b3/card.webp" alt="Model B3 catalogue exterior rendering"/><span class="fsc-real-card-body"><span class="fsc-real-card-kicker">PUBLISHED ADU DESIGN</span><strong>Model B3</strong><small>702 sq ft · 2 bed · 1 bath</small><span class="fsc-real-card-view">Explore this model ↗</span></span></button>
<button type="button" class="fsc-real-card" data-adu-real-model="r4" aria-pressed="false"><img loading="lazy" decoding="async" src="https://bhc-project-storage.s3.eu-north-1.amazonaws.com/images/adu/models/model-r4/card.webp" alt="Model R4 catalogue exterior rendering"/><span class="fsc-real-card-body"><span class="fsc-real-card-kicker">PUBLISHED ADU DESIGN</span><strong>Model R4</strong><small>780 sq ft · 1 bed · 1 bath</small><span class="fsc-real-card-view">Explore this model ↗</span></span></button>
 </div>
 <div class="fsc-real-detail">
   <div class="fsc-real-detail-media">
    <div class="fsc-real-main-wrap"><img id="fsc-real-main-photo" alt="Selected ADU model exterior" loading="eager"/></div>
    <div id="fsc-real-thumbs" class="fsc-real-thumbs" role="group" aria-label="Explore original model drawings and styles"></div>
   </div>
   <div class="fsc-real-detail-copy">
    <span class="fsc-real-eyebrow">ORIGINAL DESIGN REFERENCE</span>
    <h2 id="fsc-real-model-name">Model S1</h2>
    <p id="fsc-real-model-stats">328 sq ft · 1 bedroom · 1 bathroom</p>
    <p>Choose another model above to view its published visual references. We can discuss adapting a layout to your lot, design priorities and local regulations.</p>
    <a id="fsc-real-ask" class="fsc-real-primary" href="/atelier/contact/?adu_concept=s1#consultation">Discuss this ADU design ↗</a>
    <a id="fsc-real-source" class="fsc-real-source" href="https://www.blueheavenconstructions.com/adu-models/model-s1" target="_blank" rel="noopener">View original model specifications ↗</a>
    <small>Images and model data attributed to Blue Heaven Constructions, the affiliated business. Construction status and buildability are not independently confirmed.</small>
   </div>
 </div>
 <div class="fsc-real-cta">
  <div><span class="fsc-real-eyebrow">YOUR PROPERTY, YOUR ADU</span><h2>Let's turn a design into <em>a realistic plan.</em></h2><p>Talk with the team about your available space and the ADU designs that might suit your property.</p></div>
  <div><a href="/atelier/contact/#consultation">Request an ADU consultation ↗</a><a href="tel:+19162251015">Call (916) 225-1015</a></div>
 </div>
 <details class="fsc-real-concept-disclosure" id="fsc-adu-prototype-disclosure"><summary>Explore an illustrative 3D geometry viewer <small>(not an official model)</small></summary><p>This interactive geometry is a generic visualization only and is not linked to the published designs above.</p></details>
</section>
    <?php
}, 16);

add_action('wp_footer', function() {
    if (!fsc_adu_showcase_is_page()) return;
?>
<section id="fsc-adu-v2" class="fsc-adu" aria-labelledby="fsc-adu-title">
  <div class="fsc-adu-intro">
    <div class="fsc-adu-eyebrow"><span></span> 5 STAR CONSTRUCTIONS / ACCESSORY DWELLING UNITS</div>
    <div class="fsc-adu-heading">
      <div>
        <h1 id="fsc-adu-title">More space. <em>More possibilities.</em></h1>
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
}, 17);
