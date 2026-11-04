<?php
/**
 * Plugin Name: 5 Star Constructions — Project Concierge & Gallery
 * Description: Accessible project enquiry assistant and curated homepage design gallery.
 * Version: 1.0.1
 * Author: 5 Star Constructions
 */
if (!defined('ABSPATH')) { exit; }

function fsc_ux_is_front() {
    return is_front_page() || is_home() || is_page(5);
}

add_action('wp_enqueue_scripts', function() {
    if (is_admin()) return;
    wp_enqueue_style('fsc-ux', plugin_dir_url(__FILE__) . 'assets/ux.css', [], '1.0.1');
    wp_enqueue_script('fsc-ux', plugin_dir_url(__FILE__) . 'assets/ux.js', [], '1.0.1', true);
    wp_add_inline_script('fsc-ux', 'window.FSC_UX=' . wp_json_encode([
        'endpoint' => esc_url_raw(rest_url('fsc/v1/inquiry')),
        'isHome' => fsc_ux_is_front(),
    ]) . ';', 'before');
});

add_action('rest_api_init', function() {
    register_rest_route('fsc/v1', '/inquiry', [
        'methods' => 'POST',
        'callback' => 'fsc_ux_receive_inquiry',
        'permission_callback' => '__return_true',
    ]);
});

function fsc_ux_receive_inquiry(WP_REST_Request $request) {
    $p = $request->get_json_params();
    if (!is_array($p)) return new WP_Error('invalid', 'Invalid request.', ['status'=>400]);
    if (!empty($p['website'])) return new WP_Error('invalid', 'Invalid request.', ['status'=>400]);
    $created = (int)($p['started_at'] ?? 0);
    if (!$created || abs(time() - $created) > 86400 || time() - $created < 2) {
        return new WP_Error('invalid', 'Please complete the form normally.', ['status'=>400]);
    }
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
    $key = 'fsc_ux_'.md5($ip.wp_salt('auth'));
    $count = (int)get_transient($key);
    if ($count >= 5) return new WP_Error('rate_limit','Please try again later, or call (916) 225-1015.',['status'=>429]);
    set_transient($key, $count+1, HOUR_IN_SECONDS);
    $name = sanitize_text_field((string)($p['name'] ?? ''));
    $email = sanitize_email((string)($p['email'] ?? ''));
    $phone = sanitize_text_field((string)($p['phone'] ?? ''));
    $location = sanitize_text_field((string)($p['location'] ?? ''));
    $service = sanitize_text_field((string)($p['service'] ?? ''));
    $timing = sanitize_text_field((string)($p['timing'] ?? ''));
    $details = sanitize_textarea_field((string)($p['details'] ?? ''));
    $consent = !empty($p['consent']);
    $services = ['Kitchen remodeling','Bathroom remodeling','Whole-home remodeling','ADU design & build','Other remodeling'];
    if (!$consent || strlen($name) < 2 || strlen($name) > 100 || strlen($location) < 2 || strlen($location) > 120) {
        return new WP_Error('invalid','Please provide your name, project location and consent.',['status'=>400]);
    }
    if ((!is_email($email) && strlen(preg_replace('/\D+/', '', $phone)) < 7) || strlen($phone) > 35 || strlen($details) > 3000 || !in_array($service, $services, true)) {
        return new WP_Error('invalid','Please provide a valid phone or email and project type.',['status'=>400]);
    }
    $lines = [
        'New website project inquiry – 5 Star Constructions',
        'Name: ' . $name,
        'Email: ' . ($email ?: 'Not provided'),
        'Phone: ' . ($phone ?: 'Not provided'),
        'Service: ' . $service,
        'Location: ' . $location,
        'Timing: ' . $timing,
        'Details:',
        $details,
        'Source: 5 Star website project concierge',
        'Customer provided contact permission: yes',
    ];
    $headers = ['Content-Type: text/plain; charset=UTF-8'];
    if (is_email($email)) { $headers[] = 'Reply-To: ' . $name . ' <' . $email . '>'; }
    $sent = wp_mail('contact@5starconstructionsca.com', 'New 5 Star website project inquiry', implode("\n", $lines), $headers);
    if (!$sent) {
        return new WP_Error('delivery','Email could not be handed to the mail service. Please call (916) 225-1015 or email contact@5starconstructionsca.com.',['status'=>503]);
    }
    return new WP_REST_Response(['ok'=>true,'message'=>'Your request has been submitted.'], 200);
}

add_action('wp_footer', function() {
    if (is_admin()) return;
    $asset = esc_url(get_stylesheet_directory_uri() . '/assets/responsive/');
    ?>
<div id="fsc-ux-root">
  <button type="button" id="fsc-ux-launch" aria-haspopup="dialog" aria-expanded="false" aria-controls="fsc-ux-dialog">
    <span aria-hidden="true" class="fsc-ux-bubble">✳</span><span>Ask about your project</span>
  </button>
  <div id="fsc-ux-backdrop" hidden></div>
  <section id="fsc-ux-dialog" role="dialog" aria-modal="true" aria-labelledby="fsc-ux-title" hidden>
    <header class="fsc-ux-head">
      <div class="fsc-ux-head-brand"><span class="fsc-ux-head-mark" aria-hidden="true">5★</span><div><strong id="fsc-ux-title">5 Star Project Concierge</strong><small>Guided project enquiries · Sacramento area</small></div></div>
      <button type="button" id="fsc-ux-close" aria-label="Close project concierge">×</button>
    </header>
    <div class="fsc-ux-log" id="fsc-ux-log" role="log" aria-live="polite" aria-relevant="additions">
      <div class="fsc-ux-message bot">Hi! Planning a kitchen, bathroom, whole-home remodel or ADU? Tell me what you need, or start a free estimate request.</div>
    </div>
    <div class="fsc-ux-actions" id="fsc-ux-actions">
      <button type="button" data-fsc-action="estimate">Get a free estimate</button>
      <button type="button" data-fsc-action="kitchen">Kitchen</button>
      <button type="button" data-fsc-action="bathroom">Bathroom</button>
      <button type="button" data-fsc-action="whole">Whole home</button>
      <button type="button" data-fsc-action="adu">ADU</button>
      <button type="button" data-fsc-action="areas">Service areas</button>
    </div>
    <form id="fsc-ux-question" class="fsc-ux-question">
      <label class="fsc-ux-sr" for="fsc-ux-input">Ask a question about remodeling</label>
      <input id="fsc-ux-input" type="text" maxlength="500" placeholder="Ask about remodeling, costs, permits…"/>
      <button type="submit" aria-label="Send your question">Send</button>
    </form>
    <form id="fsc-ux-lead" hidden novalidate>
      <h3>Tell us about your project</h3>
      <p>No obligation. Our team will review your requirements before discussing costs.</p>
      <div class="fsc-ux-lead-grid">
        <label>Project type <select name="service" required>
          <option value="">Choose a service</option>
          <option>Kitchen remodeling</option><option>Bathroom remodeling</option><option>Whole-home remodeling</option><option>ADU design &amp; build</option><option>Other remodeling</option>
        </select></label>
        <label>City or ZIP <input name="location" maxlength="120" required placeholder="Sacramento, CA"/></label>
        <label>Desired timing <select name="timing"><option>Just exploring</option><option>As soon as possible</option><option>Within 1–3 months</option><option>3+ months</option></select></label>
        <label class="fsc-ux-wide">Project details <textarea name="details" maxlength="3000" rows="2" placeholder="What would you like to change?"></textarea></label>
        <label>Your name <input name="name" maxlength="100" required autocomplete="name" placeholder="Full name"/></label>
        <label>Phone <input name="phone" maxlength="35" type="tel" autocomplete="tel" placeholder="(916) 555-0123"/></label>
        <label class="fsc-ux-wide">Email <input name="email" type="email" maxlength="150" autocomplete="email" placeholder="you@example.com"/></label>
      </div>
      <label class="fsc-ux-consent"><input name="consent" type="checkbox" required/> I agree to be contacted about this enquiry. <a href="/atelier/privacy/" target="_blank" rel="noopener">Privacy</a></label>
      <input type="text" name="website" class="fsc-ux-hp" tabindex="-1" autocomplete="off" aria-hidden="true"/>
      <div id="fsc-ux-feedback" role="status" aria-live="polite"></div>
      <div class="fsc-ux-submit"><button type="button" id="fsc-ux-back">Back</button><button type="submit" id="fsc-ux-submit">Send project request</button></div>
    </form>
    <footer class="fsc-ux-bottom">
      <a href="tel:+19162251015">Call (916) 225-1015</a><span>·</span><a href="mailto:contact@5starconstructionsca.com">Email the team</a>
    </footer>
  </section>
</div>
<?php if (fsc_ux_is_front()): ?>
<section id="fsc-ux-gallery" class="fsc-ux-gallery" aria-labelledby="fsc-ux-gallery-title">
  <div class="fsc-ux-gallery-inner">
    <div class="fsc-ux-gallery-heading"><div><span class="fsc-ux-eyebrow">DESIGN INSPIRATION / GALLERY</span><h2 id="fsc-ux-gallery-title">Explore the <em>possibilities.</em></h2><p>Discover kitchen, bathroom, whole-home and ADU ideas. See the full gallery for before-and-after inspiration.</p></div><a href="/atelier/projects/" class="fsc-ux-gallery-link">Explore the full gallery ↗</a></div>
    <div class="fsc-ux-filters" role="group" aria-label="Gallery categories">
      <button type="button" class="active" data-fsc-filter="all" aria-pressed="true">All</button>
      <button type="button" data-fsc-filter="kitchen" aria-pressed="false">Kitchen</button>
      <button type="button" data-fsc-filter="bathroom" aria-pressed="false">Bathroom</button>
      <button type="button" data-fsc-filter="whole" aria-pressed="false">Whole home</button>
      <button type="button" data-fsc-filter="adu" aria-pressed="false">ADU</button>
    </div>
    <div class="fsc-ux-gallery-grid">
      <a data-fsc-category="kitchen" href="/atelier/projects/kitchen/" class="fsc-ux-gallery-card"><img loading="lazy" src="<?php echo $asset; ?>kitchen-after-960.webp" alt="Illustrative natural wood kitchen design with island"/><span>KITCHEN REMODELING</span><strong>Spaces to gather</strong><small>Explore kitchen concepts ↗</small></a>
      <a data-fsc-category="bathroom" href="/bathroom-gallery/" class="fsc-ux-gallery-card"><img loading="lazy" src="<?php echo $asset; ?>bath-after-960.webp" alt="Illustrative natural stone bathroom design"/><span>BATHROOM REMODELING</span><strong>Calmer daily rituals</strong><small>View bathroom gallery ↗</small></a>
      <a data-fsc-category="whole" href="/atelier/projects/living/" class="fsc-ux-gallery-card"><img loading="lazy" src="<?php echo $asset; ?>living-960.webp" alt="Illustrative warm minimalist living space"/><span>WHOLE-HOME REMODELING</span><strong>Room to live beautifully</strong><small>Explore whole-home concepts ↗</small></a>
      <a data-fsc-category="adu" href="/adu-models/" class="fsc-ux-gallery-card fsc-ux-adu"><div class="fsc-ux-adu-art" aria-hidden="true"><span>ADU</span></div><span>ADU DESIGN &amp; BUILD</span><strong>More possibilities at home</strong><small>Explore ADU models ↗</small></a>
    </div>
    <p class="fsc-ux-gallery-disclosure">Images shown are illustrative design inspiration, not verified photographs of completed 5 Star client projects.</p>
  </div>
</section>
<?php endif; ?>
    <?php
}, 17);