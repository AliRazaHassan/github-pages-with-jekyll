<?php
/**
 * Plugin Name: 5 Star Constructions — Independent ADU Inspiration
 * Description: Independent completed ADU case studies and licensed reference photographs, with an optional conceptual 3D viewer.
 * Version: 1.3.1
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
    wp_enqueue_style('fsc-adu-showcase-css', plugin_dir_url(__FILE__) . 'assets/adu.css', [], '1.3.1');
    wp_enqueue_script('fsc-adu-showcase-js', plugin_dir_url(__FILE__) . 'assets/adu.js', [], '1.3.1', true);
    wp_enqueue_script('fsc-real-adu-js', plugin_dir_url(__FILE__) . 'assets/real-adu.js', ['fsc-adu-showcase-js'], '1.3.1', true);
});

// Preserve a selected concept in the existing Contact form without relying on JS.
add_action('template_redirect', function() {
    if (is_admin()) return;
    $path = trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    if ($path !== 'atelier/contact') return;
    $choice = isset($_GET['adu_concept']) ? sanitize_key(wp_unslash($_GET['adu_concept'])) : '';
    $names = ['studio'=>'Studio Retreat','courtyard'=>'Courtyard One','family'=>'Family Nest','detached'=>'Detached ADU inspiration','garage'=>'Garage conversion inspiration','courtyardadu'=>'Backyard ADU inspiration'];
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
<section id="fsc-real-adu" class="fsc-real-adu" aria-labelledby="fsc-real-title">
  <div class="fsc-real-intro">
    <span class="fsc-real-eyebrow">INDEPENDENT ADU INSPIRATION · REAL BUILDS</span>
    <h1 id="fsc-real-title">Discover real <em>ADU possibilities.</em></h1>
    <p>See photographed accessory dwelling units and independently documented completed California projects. Compare styles, layouts and uses before requesting a property-specific consultation.</p>
    <p class="fsc-real-disclaimer"><strong>Third-party inspiration, not 5 Star Constructions projects.</strong> The buildings below were photographed or documented by independent creators/builders. They are reference examples, not our completed work, available 5 Star plans, or construction guarantees.</p>
  </div>
  <div class="fsc-real-grid fsc-real-photo-gallery" aria-label="Freely licensed photographs of actual accessory dwelling units">
<article class="fsc-real-card fsc-adu-licensed-photo"><a href="https://commons.wikimedia.org/wiki/File:Exterior_of_a_Snap_ADU_Build.jpg" target="_blank" rel="noopener noreferrer"><img loading="eager" decoding="async" src="https://thumb.wikimedia.org/wikipedia/commons/thumb/0/01/Exterior_of_a_Snap_ADU_Build.jpg/960px-Exterior_of_a_Snap_ADU_Build.jpg" alt="A real finished ADU exterior, Oceanside, California"/></a><div class="fsc-real-card-body"><span class="fsc-real-card-kicker">REAL-WORLD PHOTO REFERENCE</span><strong>Completed Detached ADU</strong><small>A real finished ADU exterior, Oceanside, California</small><div class="fsc-adu-license">Photo: <a href="https://commons.wikimedia.org/wiki/File:Exterior_of_a_Snap_ADU_Build.jpg" target="_blank" rel="noopener noreferrer">SnapADU</a> · <a href="https://creativecommons.org/licenses/by-sa/4.0/" target="_blank" rel="noopener noreferrer">CC BY-SA 4.0</a> · unmodified photo</div></div></article>
<article class="fsc-real-card fsc-adu-licensed-photo"><a href="https://commons.wikimedia.org/wiki/File:Craftsman_with_Backyard_Cottage_(Converted_Garage).jpg" target="_blank" rel="noopener noreferrer"><img loading="lazy" decoding="async" src="https://upload.wikimedia.org/wikipedia/commons/c/c4/Craftsman_with_Backyard_Cottage_%28Converted_Garage%29.jpg" alt="Existing garage converted into a backyard secondary suite"/></a><div class="fsc-real-card-body"><span class="fsc-real-card-kicker">REAL-WORLD PHOTO REFERENCE</span><strong>Backyard Cottage Conversion</strong><small>Existing garage converted into a backyard secondary suite</small><div class="fsc-adu-license">Photo: <a href="https://commons.wikimedia.org/wiki/File:Craftsman_with_Backyard_Cottage_(Converted_Garage).jpg" target="_blank" rel="noopener noreferrer">Sightline Institute</a> · <a href="https://creativecommons.org/licenses/by/2.0/" target="_blank" rel="noopener noreferrer">CC BY 2.0</a> · unmodified photo</div></div></article>
<article class="fsc-real-card fsc-adu-licensed-photo"><a href="https://commons.wikimedia.org/wiki/File:ADU_Alley_St_Albans_L7_Davidson_(6124624998).jpg" target="_blank" rel="noopener noreferrer"><img loading="lazy" decoding="async" src="https://thumb.wikimedia.org/wikipedia/commons/thumb/0/02/ADU_Alley_St_Albans_L7_Davidson_%286124624998%29.jpg/960px-ADU_Alley_St_Albans_L7_Davidson_%286124624998%29.jpg" alt="Attached and above-garage ADU buildings, Davidson, North Carolina"/></a><div class="fsc-real-card-body"><span class="fsc-real-card-kicker">REAL-WORLD PHOTO REFERENCE</span><strong>Neighborhood ADU Example</strong><small>Attached and above-garage ADU buildings, Davidson, North Carolina</small><div class="fsc-adu-license">Photo: <a href="https://commons.wikimedia.org/wiki/File:ADU_Alley_St_Albans_L7_Davidson_(6124624998).jpg" target="_blank" rel="noopener noreferrer">Brett VA</a> · <a href="https://creativecommons.org/licenses/by/2.0/" target="_blank" rel="noopener noreferrer">CC BY 2.0</a> · unmodified photo</div></div></article>
  </div>
  <section class="fsc-adu-external-section" aria-labelledby="fsc-adu-external-title">
    <div class="fsc-adu-external-heading"><span class="fsc-real-eyebrow">ACTUAL BUILT PROJECT REFERENCES</span><h2 id="fsc-adu-external-title">Explore documented <em>ADU projects.</em></h2><p>Independent builders publish these completed projects. Original project pages provide photos and further details.</p></div>
    <div class="fsc-adu-external-grid">
<article class="fsc-adu-external-case"><span class="fsc-real-card-kicker">INDEPENDENT COMPLETED BUILD</span><h3>Hillside Terrace Detached ADU</h3><p>Vista, California</p><p>3 bedrooms · 2 bathrooms · 1,000 sq ft</p><a href="https://www.nucave.com/projects/" target="_blank" rel="noopener noreferrer">View project on Nucave Construction ↗</a></article>
<article class="fsc-adu-external-case"><span class="fsc-real-card-kicker">INDEPENDENT COMPLETED BUILD</span><h3>Oceanside Pacifica ADU</h3><p>Oceanside, California</p><p>1 bedroom · 1 bathroom · 498 sq ft</p><a href="https://www.nucave.com/projects/" target="_blank" rel="noopener noreferrer">View project on Nucave Construction ↗</a></article>
<article class="fsc-adu-external-case"><span class="fsc-real-card-kicker">INDEPENDENT COMPLETED BUILD</span><h3>Studio ADU</h3><p>Rocklin, California</p><p>344 sq ft · 1 bathroom</p><a href="https://imkatconstruction.com/bay-area/adu-projects/" target="_blank" rel="noopener noreferrer">View project on IMKAT Construction ↗</a></article>
<article class="fsc-adu-external-case"><span class="fsc-real-card-kicker">INDEPENDENT COMPLETED BUILD</span><h3>624 SQFT ADU</h3><p>Sacramento, California</p><p>2 bedrooms · 1 bathroom · 624 sq ft</p><a href="https://imkatconstruction.com/bay-area/adu-projects/" target="_blank" rel="noopener noreferrer">View project on IMKAT Construction ↗</a></article>
    </div>
  </section>
  <div class="fsc-real-cta">
    <div><span class="fsc-real-eyebrow">DISCUSS YOUR PROPERTY</span><h2>Your ADU starts with <em>your site.</em></h2><p>Share your lot size, proposed use and preferred layout. Our team can discuss next steps for the Sacramento area.</p></div>
    <div><a href="/atelier/contact/?adu_concept=detached#consultation">Request an ADU consultation ↗</a><a href="tel:+19162251015">Call (916) 225-1015</a></div>
  </div>
  <details class="fsc-real-concept-disclosure" id="fsc-adu-prototype-disclosure">
    <summary>Optional 3D concept explorer <small>— generic geometry, not a real project model</small></summary>
    <p>The optional viewer illustrates possible room zoning; it is not a digital model of the photographed or linked completed ADUs.</p>
  </details>
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
