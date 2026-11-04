<?php
/**
 * Plugin Name: 5 Star Constructions — Project Concierge & Gallery
 * Description: Accessible project enquiry assistant and curated homepage design gallery.
 * Version: 1.1.1
 * Author: 5 Star Constructions
 */
if (!defined('ABSPATH')) { exit; }

function fsc_ux_is_front() {
    return is_front_page() || is_home() || is_page(5);
}

add_action('wp_enqueue_scripts', function() {
    if (is_admin()) return;
    wp_enqueue_style('fsc-ux', plugin_dir_url(__FILE__) . 'assets/ux.css', [], '1.1.1');
    wp_enqueue_script('fsc-ux', plugin_dir_url(__FILE__) . 'assets/ux.js', [], '1.1.1', true);
    wp_add_inline_script('fsc-ux', 'window.FSC_UX=' . wp_json_encode([
        'endpoint' => esc_url_raw(rest_url('fsc/v1/inquiry')),
        'isHome' => fsc_ux_is_front(),
    ]) . ';', 'before');
});

add_action('init', function() {
    register_post_type('fsc_project_lead', [
        'labels' => ['name'=>'Project enquiries','singular_name'=>'Project enquiry','menu_name'=>'Project enquiries'],
        'public' => false,
        'publicly_queryable' => false,
        'exclude_from_search' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => false,
        'supports' => ['title','editor'],
        'menu_icon' => 'dashicons-email-alt',
    ]);
});

// Send any legacy Blue Heaven-addressed 5 Star notifications to the new company inbox.
add_filter('wp_mail', function($args) {
    if (isset($args['to'])) {
        if (is_array($args['to'])) {
            $args['to'] = array_map(function($address) {
                return str_ireplace('service@blueheavenconstructions.com', 'contact@5starconstructionsca.com', $address);
            }, $args['to']);
        } elseif (is_string($args['to'])) {
            $args['to'] = str_ireplace('service@blueheavenconstructions.com', 'contact@5starconstructionsca.com', $args['to']);
        }
    }
    return $args;
});


// Authenticated Hostinger Mail API transport for website notifications.
// The token is saved in private WordPress settings and never committed to source code.
function fsc_ux_mail_api_send($subject, $message) {
    $token = (string)get_option('fsc_ux_mail_api_token', '');
    $mailbox_id = (string)get_option('fsc_ux_mailbox_id', '');
    if ($token === '' || !preg_match('/^AC[A-Za-z0-9]+$/', $mailbox_id)) {
        return new WP_Error('not_configured', 'Hostinger Mail API is not configured.');
    }
    $payload = [
        'to' => ['contact@5starconstructionsca.com'],
        'displayName' => '5 Star Website Enquiries',
        'subject' => sanitize_text_field($subject),
        'text' => is_string($message) ? wp_strip_all_tags($message) : '',
    ];
    $response = wp_remote_post('https://api.mail.hostinger.com/api/v1/mailboxes/' . rawurlencode($mailbox_id) . '/send', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ],
        'body' => wp_json_encode($payload),
        'timeout' => 20,
        'redirection' => 0,
        'sslverify' => true,
    ]);
    if (is_wp_error($response)) {
        error_log('5 Star mail API transport failed: ' . $response->get_error_code());
        return new WP_Error('transport_failed', 'Mail transport connection failed.');
    }
    $status = (int)wp_remote_retrieve_response_code($response);
    if ($status !== 204) {
        error_log('5 Star mail API returned HTTP ' . $status);
        return new WP_Error('send_failed', 'Authenticated mail API returned HTTP ' . $status);
    }
    return true;
}

// Reroute only first-party 5 Star website notifications to the authenticated mail API.
// Leave unrelated WordPress/password-reset mail unchanged.
add_filter('pre_wp_mail', function($pre, $atts) {
    $recipient = isset($atts['to']) ? $atts['to'] : '';
    if (is_array($recipient)) {
        if (count($recipient) !== 1) return $pre;
        $recipient = reset($recipient);
    }
    if (!is_string($recipient)) return $pre;
    if (strtolower(trim($recipient)) !== 'contact@5starconstructionsca.com') return $pre;
    if (!(string)get_option('fsc_ux_mail_api_token', '')) return $pre;
    $sent = fsc_ux_mail_api_send((string)($atts['subject'] ?? '5 Star website enquiry'), $atts['message'] ?? '');
    return is_wp_error($sent) ? false : true;
}, 20, 2);

add_action('admin_menu', function() {
    add_options_page('5 Star Mail Delivery', '5 Star Mail Delivery', 'manage_options', 'fsc-ux-mail', 'fsc_ux_mail_settings_page');
});
function fsc_ux_mail_settings_page() {
    if (!current_user_can('manage_options')) return;
    $configured = (bool)get_option('fsc_ux_mail_api_token', '');
    $last = get_option('fsc_ux_mail_test_result', []);
    ?>
    <div class="wrap" style="max-width:780px">
      <h1>5 Star Website Mail Delivery</h1>
      <p>Send website project enquiries through an authenticated Hostinger Mail API mailbox. Notifications are addressed to <strong>contact@5starconstructionsca.com</strong>.</p>
      <p><strong>API configuration:</strong> <?php echo $configured ? 'Configured' : 'Not configured'; ?></p>
      <p>To activate authenticated delivery: in <a href="https://hpanel.hostinger.com/" target="_blank" rel="noopener noreferrer">Hostinger hPanel</a>, open Emails → Agentic Mail → API, create a token restricted to <strong>website-notices@5starconstructionsca.com</strong>, and paste it below. Keep the token private. The sender mailbox ID is prefilled.</p>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('fsc_ux_save_mail_settings'); ?>
        <input type="hidden" name="action" value="fsc_ux_save_mail_settings"/>
        <table class="form-table"><tbody>
          <tr><th><label for="fsc-mailbox-id">Sender mailbox resource ID</label></th>
          <td><input type="text" name="fsc_mailbox_id" id="fsc-mailbox-id" class="regular-text" value="<?php echo esc_attr(get_option('fsc_ux_mailbox_id', 'AC3a6e683018f926fc4c3822af8123')); ?>" autocomplete="off" required/></td></tr>
          <tr><th><label for="fsc-api-token">Hostinger Mail API token</label></th>
          <td><input type="password" name="fsc_api_token" id="fsc-api-token" class="regular-text" value="" autocomplete="new-password" placeholder="<?php echo $configured ? 'Configured — leave empty to keep' : 'Paste a mailbox-scoped token'; ?>"/>
          <p class="description">Token is not displayed again. Use a token scoped to the website notification mailbox only.</p></td></tr>
        </tbody></table>
        <?php submit_button('Save Mail API Settings'); ?>
      </form>
      <hr/>
      <h2>Send a test notification</h2>
      <p>The test goes only to the 5 Star contact mailbox. Afterward verify its Inbox and Junk folder and the Hostinger delivery logs.</p>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('fsc_ux_test_mail'); ?>
        <input type="hidden" name="action" value="fsc_ux_test_mail"/>
        <?php submit_button('Send a test notification', 'secondary'); ?>
      </form>
      <?php if (is_array($last) && !empty($last['time'])): ?>
       <p><strong>Last test:</strong> <?php echo esc_html($last['time']); ?> — <?php echo !empty($last['ok']) ? 'Accepted by mail transport' : 'Transport returned failure'; ?></p>
      <?php endif; ?>
    </div>
    <?php
}
add_action('admin_post_fsc_ux_save_mail_settings', function() {
    if (!current_user_can('manage_options')) wp_die('Forbidden', '', ['response'=>403]);
    check_admin_referer('fsc_ux_save_mail_settings');
    $id = sanitize_text_field(wp_unslash($_POST['fsc_mailbox_id'] ?? ''));
    if (!preg_match('/^AC[A-Za-z0-9]+$/', $id)) wp_die('Invalid mailbox ID');
    update_option('fsc_ux_mailbox_id', $id, false);
    $token = trim((string)wp_unslash($_POST['fsc_api_token'] ?? ''));
    if ($token !== '') {
        if (strlen($token) < 32 || strlen($token) > 1000 || preg_match('/\s/', $token)) wp_die('Invalid token');
        update_option('fsc_ux_mail_api_token', $token, false);
    }
    wp_safe_redirect(admin_url('options-general.php?page=fsc-ux-mail&saved=1'));
    exit;
});
add_action('admin_post_fsc_ux_test_mail', function() {
    if (!current_user_can('manage_options')) wp_die('Forbidden', '', ['response'=>403]);
    check_admin_referer('fsc_ux_test_mail');
    $sent = wp_mail('contact@5starconstructionsca.com', '5 Star website mail delivery test', 'This is a website notification delivery test requested by the site owner. No customer enquiry was submitted.');
    update_option('fsc_ux_mail_test_result', ['time'=>current_time('mysql'), 'ok'=>(bool)$sent], false);
    wp_safe_redirect(admin_url('options-general.php?page=fsc-ux-mail&tested=1'));
    exit;
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
    $lead_id = wp_insert_post([
        'post_type' => 'fsc_project_lead',
        'post_status' => 'private',
        'post_title' => 'Project enquiry – ' . $name . ' – ' . current_time('mysql'),
        'post_content' => implode("\n", $lines),
    ], true);
    if (is_wp_error($lead_id) || !$lead_id) {
        return new WP_Error('storage','Your enquiry could not be saved. Please call (916) 225-1015.',['status'=>503]);
    }
    $authenticated_mail = (bool)get_option('fsc_ux_mail_api_token', '');
    $sent = wp_mail('contact@5starconstructionsca.com', 'New 5 Star website project inquiry', implode("\n", $lines), $headers);
    $verified_submission = $authenticated_mail && (bool)$sent;
    update_post_meta($lead_id, '_fsc_mail_status', $verified_submission ? 'accepted_by_hostinger_api' : 'delivery_unverified');
    return new WP_REST_Response([
        'ok' => true,
        'stored' => true,
        'notification_sent' => $verified_submission,
        'message' => $verified_submission ? 'Enquiry saved and accepted by authenticated mail API.' : 'Enquiry saved. Mail delivery has not been verified; please call if urgent.',
    ], 200);
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
    <div class="fsc-ux-gallery-heading"><div><span class="fsc-ux-eyebrow">BATHROOM DESIGN INSPIRATION</span><h2 id="fsc-ux-gallery-title">Beautiful spaces, <em>better living.</em></h2><p>Explore bathroom remodeling inspiration for your next project.</p></div></div>
    <div class="fsc-ux-carousel-bar">
      <p id="fsc-ux-carousel-help">Swipe or use the arrows to view more photos.</p>
      <div class="fsc-ux-carousel-controls" aria-label="Bathroom gallery carousel controls">
        <span id="fsc-ux-carousel-count" aria-live="polite" aria-atomic="true">01 / 12</span>
        <button type="button" class="fsc-ux-carousel-prev" aria-label="Previous bathroom photo" aria-controls="fsc-ux-carousel-track" disabled>←</button>
        <button type="button" class="fsc-ux-carousel-next" aria-label="Next bathroom photo" aria-controls="fsc-ux-carousel-track">→</button>
      </div>
    </div>
    <div id="fsc-ux-carousel-track" class="fsc-ux-gallery-grid" role="region" aria-roledescription="carousel" aria-label="Bathroom inspiration photo carousel" aria-describedby="fsc-ux-carousel-help" tabindex="0">
      <div class="fsc-ux-gallery-card" role="group" aria-label="Bathroom image 1 of 12"><img src="https://www.revivedesignandrenovation.com/wp-content/uploads/2024/05/Bathroom-Inspiration-Slide-1.jpg" alt="Bathroom design inspiration photograph 1 from Revive Design and Renovation" loading="lazy" decoding="async" draggable="false" referrerpolicy="no-referrer-when-downgrade" onerror="this.onerror=null;this.src='https://5starconstructionsca.com/wp-content/themes/arqora-studio/assets/responsive/bath-after-960.webp';" /></div>
      <div class="fsc-ux-gallery-card" role="group" aria-label="Bathroom image 2 of 12"><img src="https://www.revivedesignandrenovation.com/wp-content/uploads/2024/05/Bathroom-Inspiration-Slide-2.jpg" alt="Bathroom design inspiration photograph 2 from Revive Design and Renovation" loading="lazy" decoding="async" draggable="false" referrerpolicy="no-referrer-when-downgrade" onerror="this.onerror=null;this.src='https://5starconstructionsca.com/wp-content/themes/arqora-studio/assets/responsive/bath-after-960.webp';" /></div>
      <div class="fsc-ux-gallery-card" role="group" aria-label="Bathroom image 3 of 12"><img src="https://www.revivedesignandrenovation.com/wp-content/uploads/2024/05/Bathroom-Inspiration-Slide-3.jpg" alt="Bathroom design inspiration photograph 3 from Revive Design and Renovation" loading="lazy" decoding="async" draggable="false" referrerpolicy="no-referrer-when-downgrade" onerror="this.onerror=null;this.src='https://5starconstructionsca.com/wp-content/themes/arqora-studio/assets/responsive/bath-after-960.webp';" /></div>
      <div class="fsc-ux-gallery-card" role="group" aria-label="Bathroom image 4 of 12"><img src="https://www.revivedesignandrenovation.com/wp-content/uploads/2024/05/Bathroom-Inspiration-Slide-4.jpg" alt="Bathroom design inspiration photograph 4 from Revive Design and Renovation" loading="lazy" decoding="async" draggable="false" referrerpolicy="no-referrer-when-downgrade" onerror="this.onerror=null;this.src='https://5starconstructionsca.com/wp-content/themes/arqora-studio/assets/responsive/bath-after-960.webp';" /></div>
      <div class="fsc-ux-gallery-card" role="group" aria-label="Bathroom image 5 of 12"><img src="https://www.revivedesignandrenovation.com/wp-content/uploads/2024/05/Bathroom-Inspiration-Slide-5.jpg" alt="Bathroom design inspiration photograph 5 from Revive Design and Renovation" loading="lazy" decoding="async" draggable="false" referrerpolicy="no-referrer-when-downgrade" onerror="this.onerror=null;this.src='https://5starconstructionsca.com/wp-content/themes/arqora-studio/assets/responsive/bath-after-960.webp';" /></div>
      <div class="fsc-ux-gallery-card" role="group" aria-label="Bathroom image 6 of 12"><img src="https://www.revivedesignandrenovation.com/wp-content/uploads/2024/05/Bathroom-Inspiration-Slide-6.jpg" alt="Bathroom design inspiration photograph 6 from Revive Design and Renovation" loading="lazy" decoding="async" draggable="false" referrerpolicy="no-referrer-when-downgrade" onerror="this.onerror=null;this.src='https://5starconstructionsca.com/wp-content/themes/arqora-studio/assets/responsive/bath-after-960.webp';" /></div>
      <div class="fsc-ux-gallery-card" role="group" aria-label="Bathroom image 7 of 12"><img src="https://www.revivedesignandrenovation.com/wp-content/uploads/2024/05/Bathroom-Inspiration-Slide-7.jpg" alt="Bathroom design inspiration photograph 7 from Revive Design and Renovation" loading="lazy" decoding="async" draggable="false" referrerpolicy="no-referrer-when-downgrade" onerror="this.onerror=null;this.src='https://5starconstructionsca.com/wp-content/themes/arqora-studio/assets/responsive/bath-after-960.webp';" /></div>
      <div class="fsc-ux-gallery-card" role="group" aria-label="Bathroom image 8 of 12"><img src="https://www.revivedesignandrenovation.com/wp-content/uploads/2024/05/Bathroom-Inspiration-Slide-8.jpg" alt="Bathroom design inspiration photograph 8 from Revive Design and Renovation" loading="lazy" decoding="async" draggable="false" referrerpolicy="no-referrer-when-downgrade" onerror="this.onerror=null;this.src='https://5starconstructionsca.com/wp-content/themes/arqora-studio/assets/responsive/bath-after-960.webp';" /></div>
      <div class="fsc-ux-gallery-card" role="group" aria-label="Bathroom image 9 of 12"><img src="https://www.revivedesignandrenovation.com/wp-content/uploads/2024/05/Bathroom-Inspiration-Slide-9.jpg" alt="Bathroom design inspiration photograph 9 from Revive Design and Renovation" loading="lazy" decoding="async" draggable="false" referrerpolicy="no-referrer-when-downgrade" onerror="this.onerror=null;this.src='https://5starconstructionsca.com/wp-content/themes/arqora-studio/assets/responsive/bath-after-960.webp';" /></div>
      <div class="fsc-ux-gallery-card" role="group" aria-label="Bathroom image 10 of 12"><img src="https://www.revivedesignandrenovation.com/wp-content/uploads/2024/05/Bathroom-Inspiration-Slide-10.jpg" alt="Bathroom design inspiration photograph 10 from Revive Design and Renovation" loading="lazy" decoding="async" draggable="false" referrerpolicy="no-referrer-when-downgrade" onerror="this.onerror=null;this.src='https://5starconstructionsca.com/wp-content/themes/arqora-studio/assets/responsive/bath-after-960.webp';" /></div>
      <div class="fsc-ux-gallery-card" role="group" aria-label="Bathroom image 11 of 12"><img src="https://www.revivedesignandrenovation.com/wp-content/uploads/2026/04/Home-Slide-Bathroom-4-7-v2.jpg" alt="Modern bathroom remodeling inspiration from Revive Design and Renovation" loading="lazy" decoding="async" draggable="false" referrerpolicy="no-referrer-when-downgrade" onerror="this.onerror=null;this.src='https://5starconstructionsca.com/wp-content/themes/arqora-studio/assets/responsive/bath-after-960.webp';" /></div>
      <div class="fsc-ux-gallery-card" role="group" aria-label="Bathroom image 12 of 12"><img src="https://www.revivedesignandrenovation.com/wp-content/uploads/gallery/bathroom-gallery-lightbox-24/Bathroom-Gallery-Lightbox-24-1.jpg" alt="Bathroom gallery inspiration from Revive Design and Renovation" loading="lazy" decoding="async" draggable="false" referrerpolicy="no-referrer-when-downgrade" onerror="this.onerror=null;this.src='https://5starconstructionsca.com/wp-content/themes/arqora-studio/assets/responsive/bath-after-960.webp';" /></div>
    </div>
    <p class="fsc-ux-gallery-disclosure">Images courtesy of Revive Design and Renovation, provided for design inspiration. These are not presented as completed 5 Star Constructions projects.</p>
  </div>
</section>
<?php endif; ?>
    <?php
}, 17);