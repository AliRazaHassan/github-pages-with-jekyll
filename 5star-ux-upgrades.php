<?php
/**
 * Plugin Name: 5 Star Constructions — Project Concierge & Gallery
 * Description: Accessible project enquiry assistant and curated homepage design gallery.
 * Version: 1.3.4
 * Author: 5 Star Constructions
 */
if (!defined('ABSPATH')) { exit; }

// Site-wide launch cleanup: honor original/client-supplied imagery and content.
// Update only obsolete navigation, contact destinations, truthful copy, and SEO.
add_action('init', function() {
    // Change only the known legacy ADU nav item, not other menu items.
    $item = get_post(29);
    if ($item && $item->post_type === 'nav_menu_item') {
        $url = (string)get_post_meta(29, '_menu_item_url', true);
        $host = strtolower((string)wp_parse_url($url, PHP_URL_HOST));
        $path = untrailingslashit((string)wp_parse_url($url, PHP_URL_PATH));
        if (in_array($host, ['blueheavenconstructions.com','www.blueheavenconstructions.com'], true) && $path === '/adu-models') {
            update_post_meta(29, '_menu_item_url', home_url('/adu-models/'));
        }
    }
}, 35);

add_filter('wp_nav_menu_objects', function($items) {
    foreach ($items as $item) {
        $url = (string)($item->url ?? '');
        $host = strtolower((string)wp_parse_url($url, PHP_URL_HOST));
        $path = untrailingslashit((string)wp_parse_url($url, PHP_URL_PATH));
        if (in_array($host, ['blueheavenconstructions.com','www.blueheavenconstructions.com'], true) && $path === '/adu-models') {
            $item->url = home_url('/adu-models/');
        }
    }
    return $items;
}, 130);

// Unique descriptions for currently published pages. Keep original titles and page copy.
function fsc_ux_page_meta_description($path) {
    $map = [
        '/' => '5 Star Constructions provides kitchen, bathroom, whole-home and commercial remodeling across the Sacramento area. Discuss your project and request an estimate.',
        '/atelier/about/' => 'Learn about 5 Star Constructions, our remodeling approach and how we plan kitchen, bathroom and home renovation projects in the Sacramento area.',
        '/atelier/contact/' => 'Contact 5 Star Constructions to discuss your Sacramento-area kitchen, bathroom, whole-home, commercial or ADU project. Call (916) 225-1015.',
        '/atelier/services/' => 'Explore kitchen remodeling, bathroom renovations, whole-house upgrades and commercial construction services from 5 Star Constructions.',
        '/atelier/services/kitchen/' => 'Plan a kitchen remodel around storage, layout, cabinetry and everyday living. Discuss your Sacramento-area kitchen project with 5 Star Constructions.',
        '/atelier/services/bathroom/' => 'Explore shower renovations, bathroom layouts, waterproofing, finishes and accessibility priorities with 5 Star Constructions.',
        '/atelier/services/whole-home/' => 'Discuss whole-home remodeling, coordinated room layouts, renovation phases and material planning with 5 Star Constructions.',
        '/atelier/services/commercial/' => 'Explore practical commercial remodeling and tenant improvement planning with 5 Star Constructions in the Sacramento area.',
        '/adu-models/' => 'Explore three interactive 3D ADU design concepts. Rotate, zoom and view interior layouts before discussing ADU feasibility with 5 Star Constructions.',
        '/bathroom-gallery/' => 'Browse bathroom remodeling inspiration, design directions and Before/After visuals. Discuss your bathroom goals with 5 Star Constructions.',
        '/atelier/projects/' => 'Browse kitchen, bathroom and whole-home design studies, transformation ideas and remodeling inspiration from 5 Star Constructions.',
        '/atelier/projects/kitchen/' => 'Explore the Gathering Place illustrative kitchen transformation concept, including an interactive Before/After and material inspiration.',
        '/atelier/projects/bathroom/' => 'Explore the A Quieter Ritual illustrative bathroom remodeling concept with Before/After comparison and finish ideas.',
        '/atelier/projects/living/' => 'Discover Space to Exhale, a whole-home design study focused on natural light, calmer interiors and connected living spaces.',
        '/atelier/studio/' => 'Explore materials, texture, light and palette inspiration to discuss during your project planning with 5 Star Constructions.',
        '/atelier/journal/' => 'Read practical renovation planning notes about kitchens, finish materials and whole-home design from 5 Star Constructions.',
        '/atelier/journal/kitchen/' => 'How to plan a kitchen around daily routines, storage, priorities and your remodel consultation.',
        '/atelier/journal/materials/' => 'How to compare timber, stone, finishes and real-life maintenance requirements when planning a home remodel.',
        '/atelier/journal/whole-home/' => 'Ideas for connecting rooms, planning transitions and choosing cohesive finishes for whole-home renovation.',
        '/atelier/privacy/' => 'Read how the 5 Star Constructions website handles project enquiries, browser-based design drafts and privacy requests.',
    ];
    return $map[$path] ?? null;
}

add_action('template_redirect', function() {
    if (is_admin() || wp_doing_ajax() || is_feed()) return;
    $path = '/'.trim((string)wp_parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/').'/';
    if ($path === '//') $path = '/';
    ob_start(function($html) use ($path) {
        if (!is_string($html) || stripos($html, '</html>') === false) return $html;
        // Correct cached menu HTML as well as database-backed nav objects.
        $html = str_ireplace(
            ['https://www.blueheavenconstructions.com/adu-models', 'https://blueheavenconstructions.com/adu-models'],
            home_url('/adu-models/'),
            $html
        );
        $html = str_ireplace('mailto:service@blueheavenconstructions.com', 'mailto:contact@5starconstructionsca.com', $html);
        $html = str_replace([
            'Sacramento, CA · Shared contact channel with Blue Heaven Construction.',
            'Shared contact channel with Blue Heaven Construction.',
            'The existing Blue Heaven contact channel remains available for project discussions.',
            'Explore the ADU designs available through our shared Blue Heaven Construction catalogue, then contact the team to discuss your property and priorities.',
        ], [
            'Serving Sacramento and surrounding communities.',
            'Serving Sacramento and surrounding communities.',
            'Discuss your project directly with the 5 Star Constructions team.',
            'Explore 5 Star ADU design concepts, then discuss the possibilities for your property.',
        ], $html);
        // About page: do not rely on another company's customer contact system.
        $html = str_replace(
            '5 Star Constructions and Blue Heaven Construction are owned by the same business. Discuss your project directly with the 5 Star Constructions team.',
            'Tell the 5 Star Constructions team about your remodeling goals. We can review your project details and discuss the next steps.',
            $html
        );

        // Preserve existing images, project wording and user-supplied gallery content.
        $description = fsc_ux_page_meta_description($path);
        if ($description !== null && stripos($html, '<head') !== false) {
            $meta = '<meta name="description" content="'.esc_attr($description).'"/>';
            $replaced = preg_replace('/<meta\b(?=[^>]*\bname\s*=\s*["\\x27]description["\\x27])[^>]*>/i', $meta, $html, 1, $count);
            if ($replaced !== null) $html = $replaced;
            if (empty($count)) $html = preg_replace('/<\\/head>/i', $meta.'</head>', $html, 1);
        }
        // WordPress custom ADU page is rendered without a canonical from the theme.
        if ($path === '/adu-models/' && !preg_match('/<link\b(?=[^>]*\brel\s*=\s*["\\x27]canonical["\\x27])[^>]*>/i', $html)) {
            $html = preg_replace('/<\\/head>/i', '<link rel="canonical" href="'.esc_url(home_url('/adu-models/')).'"/></head>', $html, 1);
        }
        // Existing contact dropdown under-represented services. No content/photos are removed.
        $html = str_replace(
            '<option>Other / ask about availability</option>',
            '<option>ADU design &amp; build</option><option>Commercial remodeling</option><option>Other / ask about availability</option>',
            $html
        );
        return $html;
    });
}, 0);

// Structured data contains only verified public details; no fabricated ratings,
// completed-project claims, business address or opening hours.
add_action('wp_head', function() {
    if (is_admin() || !is_singular('page') && !is_front_page() && !is_home()) return;
    $path = '/'.trim((string)wp_parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/').'/';
    if ($path === '//') $path = '/';
    $business = [
        '@context'=>'https://schema.org',
        '@type'=>'GeneralContractor',
        '@id'=>home_url('/#business'),
        'name'=>'5 Star Constructions',
        'url'=>home_url('/'),
        'telephone'=>'+1-916-225-1015',
        'areaServed'=>[['@type'=>'Place','name'=>'Sacramento, California']],
    ];
    $schemas = [$business];
    $services = [
      '/atelier/services/kitchen/' => 'Kitchen Remodeling',
      '/atelier/services/bathroom/' => 'Bathroom Remodeling',
      '/atelier/services/whole-home/' => 'Whole-House Remodeling',
      '/atelier/services/commercial/' => 'Commercial Remodeling',
    ];
    if (isset($services[$path])) {
        $schemas[] = [
            '@context'=>'https://schema.org','@type'=>'Service',
            'name'=>$services[$path],
            'provider'=>['@id'=>home_url('/#business')],
            'areaServed'=>'Sacramento, California',
            'url'=>home_url($path),
        ];
    }
    foreach ($schemas as $schema) {
        echo '<script type="application/ld+json">'.wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).'</script>'."\n";
    }
}, 25);

// One-time cleanup of the obsolete Bathroom Gallery link under Projects.
// Delete ONLY the verified WordPress nav-menu item; preserve the gallery page.
add_action('init', function() {
    $item = get_post(37);
    if (!$item || $item->post_type !== 'nav_menu_item') return;
    $parent = (int) get_post_meta(37, '_menu_item_menu_item_parent', true);
    $url = (string) get_post_meta(37, '_menu_item_url', true);
    $path = (string) wp_parse_url($url, PHP_URL_PATH);
    if ($parent === 30 && untrailingslashit($path) === '/bathroom-gallery') {
        wp_delete_post(37, true);
    }
}, 30);

// Also prevent an outdated cached menu object from rendering the removed link.
add_filter('wp_nav_menu_objects', function($items) {
    $items = array_values(array_filter($items, function($item) {
        return (int) $item->ID !== 37;
    }));
    $parent = null;
    $has_children = false;
    foreach ($items as $item) {
        if ((int) $item->ID === 30) $parent = $item;
        if ((int) $item->menu_item_parent === 30) $has_children = true;
    }
    if ($parent && !$has_children) {
        $parent->classes = array_values(array_diff((array) $parent->classes, ['menu-item-has-children']));
    }
    return $items;
}, 100);

// Embed a first-party Services photo menu into WordPress's navigation walker output.
// This guarantees a shared CSS :hover / focus-within boundary before any JS runs,
// including when the theme adds a detached duplicate panel later.
add_filter('wp_nav_menu_items', function($items, $args) {
    if (!is_string($items) || strpos($items, 'id="menu-item-28"') === false) return $items;
    if (strpos($items, 'data-fsc-embedded="1"') !== false) return $items;
    $start = strpos($items, 'id="menu-item-28"');
    $next = strpos($items, 'id="menu-item-29"', $start);
    $native_submenu = strpos($items, 'id="five-star-submenu-28"', $start);
    if ($native_submenu !== false && ($next === false || $native_submenu < $next)) {
        $sub_end = strpos($items, '</ul>', $native_submenu);
        if ($sub_end === false) return $items;
        $close = strpos($items, '</li>', $sub_end + 5);
    } else {
        $close = strpos($items, '</li>', $start);
    }
    if ($close === false || ($next !== false && $close > $next)) return $items;
    $asset = esc_url(home_url('/wp-content/themes/arqora-studio/assets/responsive/'));
    $links = [
        ['Bathroom Remodeling', home_url('/atelier/services/bathroom/'), $asset . 'bath-after-960.webp'],
        ['Kitchen Remodeling', home_url('/atelier/services/kitchen/'), $asset . 'kitchen-after-960.webp'],
        ['Whole-House Remodeling', home_url('/atelier/services/whole-home/'), $asset . 'living-1600.webp'],
        ['ADU Design', home_url('/adu-models/'), 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=700&q=85'],
    ];
    $panel = '<div class="five-star-services-mega" data-fsc-embedded="1" role="group" aria-label="Services menu">';
    foreach ($links as $link) {
        $panel .= '<a href="'.esc_url($link[1]).'"><img src="'.esc_url($link[2]).'" alt="'.esc_attr($link[0]).'" loading="lazy" decoding="async" /><span>'.esc_html($link[0]).'</span><b aria-hidden="true">↗</b></a>';
    }
    $panel .= '</div>';
    return substr_replace($items, $panel, $close, 0);
}, 150, 2);

function fsc_ux_is_front() {
    return is_front_page() || is_home() || is_page(5);
}

// Consistent page classes allow CSS to remove redundant sections without editing theme templates.
add_filter('body_class', function($classes) {
    if (fsc_ux_is_front()) $classes[] = 'fsc-streamlined-home';
    $path = trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    if (is_page('contact') || $path === 'atelier/contact') $classes[] = 'fsc-focused-contact';
    if (is_page('adu-models') || $path === 'adu-models') $classes[] = 'fsc-focused-adu';
    return $classes;
});

// Correct legacy hard-coded contact template copy before output is delivered.
// Narrowly scoped to the Contact page; no theme files, stored submissions or
// WordPress administrator details are changed.
add_action('template_redirect', function() {
    if (is_admin()) return;
    $path = trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    if (!is_page('contact') && $path !== 'atelier/contact') return;
    ob_start(function($html) {
        if (!is_string($html) || $html === '') return $html;
        return strtr($html, [
            'mailto:service@blueheavenconstructions.com' => 'mailto:contact@5starconstructionsca.com',
            'href="#verified-reviews" rel="noopener noreferrer" target="_blank"' => 'href="tel:+19162251015"',
            'Our optional project planner below prepares a local brief. To request a consultation, contact the team through Blue Heaven Construction, which is also owned by the business.' => 'Use the project enquiry form above or call the 5 Star Constructions team to discuss your remodel.',
            'Direct consultation requests go to the existing Blue Heaven Construction team; your private design brief stays on your device until you choose to share it.' => 'Project enquiries are saved for the 5 Star Constructions team. Email notifications depend on configured mail delivery.',
        ]);
    });
}, 2);

// Render core service links in initial homepage HTML, even when JavaScript is blocked.
// This fixes an empty .service-list in the custom theme, without touching its files.
add_action('template_redirect', function() {
    if (!fsc_ux_is_front() || is_admin()) return;
    ob_start(function($html) {
        $needle = '<div class="service-list"></div>';
        if (strpos($html, $needle) === false) return $html;
        $replacement = <<<'HTML'
<div class="service-list">
<a class="fsc-ux-service" href="/atelier/services/kitchen/"><span class="fsc-ux-service-num">01</span><span class="fsc-ux-service-copy"><strong>Kitchen Remodeling</strong><small>Layouts, cabinetry, finishes and everyday function.</small></span><span class="fsc-ux-service-arrow" aria-hidden="true">↗</span></a>
<a class="fsc-ux-service" href="/atelier/services/bathroom/"><span class="fsc-ux-service-num">02</span><span class="fsc-ux-service-copy"><strong>Bathroom Remodeling</strong><small>Showers, tubs, surfaces and smarter storage.</small></span><span class="fsc-ux-service-arrow" aria-hidden="true">↗</span></a>
<a class="fsc-ux-service" href="/atelier/services/whole-home/"><span class="fsc-ux-service-num">03</span><span class="fsc-ux-service-copy"><strong>Whole-Home Remodeling</strong><small>Plan connected spaces and a cohesive renovation.</small></span><span class="fsc-ux-service-arrow" aria-hidden="true">↗</span></a>
</div>
HTML;
        return str_replace($needle, $replacement, $html);
    });
}, 3);

add_action('wp_enqueue_scripts', function() {
    if (is_admin()) return;
    wp_enqueue_style('fsc-ux', plugin_dir_url(__FILE__) . 'assets/ux.css', [], '1.3.4');
    wp_enqueue_script('fsc-ux', plugin_dir_url(__FILE__) . 'assets/ux.js', [], '1.3.4', true);
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
    add_filter('wp_mail_from', 'fsc_ux_notification_sender', 100);
    add_filter('wp_mail_from_name', 'fsc_ux_notification_sender_name', 100);
    $sent = wp_mail('contact@5starconstructionsca.com', '5 Star website mail delivery test', 'This is a website notification delivery test requested by the site owner. No customer enquiry was submitted.');
    remove_filter('wp_mail_from', 'fsc_ux_notification_sender', 100);
    remove_filter('wp_mail_from_name', 'fsc_ux_notification_sender_name', 100);
    update_option('fsc_ux_mail_test_result', ['time'=>current_time('mysql'), 'ok'=>(bool)$sent], false);
    wp_safe_redirect(admin_url('options-general.php?page=fsc-ux-mail&tested=1'));
    exit;
});

function fsc_ux_notification_sender($from) { return 'website-notices@5starconstructionsca.com'; }
function fsc_ux_notification_sender_name($name) { return '5 Star Constructions Website'; }

// The default PHP mail transport may still be filtered by the recipient.
// Deliverability is not marked verified until the authenticated Mail API succeeds.
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
    // Keep the sender aligned with the active 5 Star domain mailbox rather
    // than WordPress's generic server noreply identity, which was flagged spam.
    add_filter('wp_mail_from', 'fsc_ux_notification_sender', 100);
    add_filter('wp_mail_from_name', 'fsc_ux_notification_sender_name', 100);
    $sent = wp_mail('contact@5starconstructionsca.com', 'New 5 Star website project inquiry', implode("\n", $lines), $headers);
    remove_filter('wp_mail_from', 'fsc_ux_notification_sender', 100);
    remove_filter('wp_mail_from_name', 'fsc_ux_notification_sender_name', 100);
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
    <div class="fsc-ux-gallery-heading">
      <div class="fsc-ux-gallery-heading-main">
        <span class="fsc-ux-eyebrow"><span class="fsc-ux-eyebrow-line" aria-hidden="true"></span> THE DESIGN EDIT <span aria-hidden="true">/</span> 15 VISUAL STORIES</span>
        <h2 id="fsc-ux-gallery-title">Every space has<br/><em>more potential.</em></h2>
      </div>
      <div class="fsc-ux-gallery-aside">
        <span class="fsc-ux-gallery-aside-number" aria-hidden="true">01 — 04</span>
        <p>Picture the kitchen you gather in. The bathroom you escape to. A whole home that feels like you — or an ADU with room for what’s next.</p>
        <span class="fsc-ux-gallery-services">KITCHEN <span>·</span> BATHROOM <span>·</span> WHOLE-HOME <span>·</span> ADU</span>
      </div>
    </div>
    <div class="fsc-ux-carousel-bar">
      <p id="fsc-ux-carousel-help"><span class="fsc-ux-enlarge-hint-icon" aria-hidden="true">↗</span> Select any image to expand it. Swipe to explore.</p>
      <div class="fsc-ux-carousel-controls" aria-label="Inspiration gallery controls">
        <span id="fsc-ux-carousel-count" aria-live="polite" aria-atomic="true">01 / 15</span>
        <button type="button" class="fsc-ux-carousel-prev" aria-label="Previous gallery image" aria-controls="fsc-ux-carousel-track" disabled>←</button>
        <button type="button" class="fsc-ux-carousel-next" aria-label="Next gallery image" aria-controls="fsc-ux-carousel-track">→</button>
      </div>
    </div>
    <div id="fsc-ux-carousel-track" class="fsc-ux-gallery-grid" role="region" aria-roledescription="carousel" aria-label="Kitchen, bathroom, whole-home and ADU design inspiration" aria-describedby="fsc-ux-carousel-help" tabindex="0">
      <button class="fsc-ux-gallery-card" type="button" data-fsc-index="0" data-fsc-large="https://5starconstructionsca.com/wp-content/themes/arqora-studio/assets/responsive/kitchen-after-1600.webp" data-fsc-credit="5 Star design study imagery · illustrative reference" data-fsc-source="" aria-label="Enlarge kitchen inspiration photo 1 of 15"><img src="https://5starconstructionsca.com/wp-content/themes/arqora-studio/assets/responsive/kitchen-after-1600.webp" alt="Modern kitchen remodeling design inspiration" loading="lazy" decoding="async" draggable="false" /><span class="fsc-ux-card-shade" aria-hidden="true"></span><span class="fsc-ux-card-meta" aria-hidden="true"><span class="fsc-ux-card-index">01 / 15</span><strong>KITCHEN</strong></span><span class="fsc-ux-card-zoom" aria-hidden="true">↗</span></button>
      <button class="fsc-ux-gallery-card" type="button" data-fsc-index="1" data-fsc-large="https://www.revivedesignandrenovation.com/wp-content/uploads/2024/05/Bathroom-Inspiration-Slide-1.jpg" data-fsc-credit="Bathroom design reference · Revive Design &amp; Renovation" data-fsc-source="https://www.revivedesignandrenovation.com/bathroom-gallery/" aria-label="Enlarge bathroom inspiration photo 2 of 15"><img src="https://www.revivedesignandrenovation.com/wp-content/uploads/2024/05/Bathroom-Inspiration-Slide-1.jpg" alt="Bathroom remodeling design inspiration" loading="lazy" decoding="async" draggable="false" onerror="this.onerror=null;this.src='https://5starconstructionsca.com/wp-content/themes/arqora-studio/assets/responsive/bath-after-960.webp';" /><span class="fsc-ux-card-shade" aria-hidden="true"></span><span class="fsc-ux-card-meta" aria-hidden="true"><span class="fsc-ux-card-index">02 / 15</span><strong>BATHROOM</strong></span><span class="fsc-ux-card-zoom" aria-hidden="true">↗</span></button>
      <button class="fsc-ux-gallery-card" type="button" data-fsc-index="2" data-fsc-large="https://5starconstructionsca.com/wp-content/themes/arqora-studio/assets/responsive/living-1600.webp" data-fsc-credit="5 Star design study imagery · illustrative reference" data-fsc-source="" aria-label="Enlarge whole-home inspiration photo 3 of 15"><img src="https://5starconstructionsca.com/wp-content/themes/arqora-studio/assets/responsive/living-1600.webp" alt="Whole-home remodeling living area inspiration" loading="lazy" decoding="async" draggable="false" /><span class="fsc-ux-card-shade" aria-hidden="true"></span><span class="fsc-ux-card-meta" aria-hidden="true"><span class="fsc-ux-card-index">03 / 15</span><strong>WHOLE-HOME</strong></span><span class="fsc-ux-card-zoom" aria-hidden="true">↗</span></button>
      <button class="fsc-ux-gallery-card" type="button" data-fsc-index="3" data-fsc-large="https://upload.wikimedia.org/wikipedia/commons/0/01/Exterior_of_a_Snap_ADU_Build.jpg" data-fsc-credit="ADU photo · SnapADU · CC BY-SA 4.0" data-fsc-source="https://commons.wikimedia.org/wiki/File:Exterior_of_a_Snap_ADU_Build.jpg" aria-label="Enlarge adu inspiration photo 4 of 15"><img src="https://upload.wikimedia.org/wikipedia/commons/0/01/Exterior_of_a_Snap_ADU_Build.jpg" alt="Completed detached backyard accessory dwelling unit design inspiration" loading="lazy" decoding="async" draggable="false" /><span class="fsc-ux-card-shade" aria-hidden="true"></span><span class="fsc-ux-card-meta" aria-hidden="true"><span class="fsc-ux-card-index">04 / 15</span><strong>ADU</strong></span><span class="fsc-ux-card-zoom" aria-hidden="true">↗</span></button>
      <button class="fsc-ux-gallery-card" type="button" data-fsc-index="4" data-fsc-large="https://images.unsplash.com/photo-1556911220-bff31c812dba?auto=format&amp;fit=crop&amp;w=1800&amp;q=85" data-fsc-credit="Interior design reference · Unsplash" data-fsc-source="" aria-label="Enlarge kitchen inspiration photo 5 of 15"><img src="https://images.unsplash.com/photo-1556911220-bff31c812dba?auto=format&amp;fit=crop&amp;w=1000&amp;q=82" alt="Bright remodeled kitchen with contemporary cabinetry" loading="lazy" decoding="async" draggable="false" /><span class="fsc-ux-card-shade" aria-hidden="true"></span><span class="fsc-ux-card-meta" aria-hidden="true"><span class="fsc-ux-card-index">05 / 15</span><strong>KITCHEN</strong></span><span class="fsc-ux-card-zoom" aria-hidden="true">↗</span></button>
      <button class="fsc-ux-gallery-card" type="button" data-fsc-index="5" data-fsc-large="https://www.revivedesignandrenovation.com/wp-content/uploads/2024/05/Bathroom-Inspiration-Slide-4.jpg" data-fsc-credit="Bathroom design reference · Revive Design &amp; Renovation" data-fsc-source="https://www.revivedesignandrenovation.com/bathroom-gallery/" aria-label="Enlarge bathroom inspiration photo 6 of 15"><img src="https://www.revivedesignandrenovation.com/wp-content/uploads/2024/05/Bathroom-Inspiration-Slide-4.jpg" alt="Bathroom remodeling design inspiration" loading="lazy" decoding="async" draggable="false" onerror="this.onerror=null;this.src='https://5starconstructionsca.com/wp-content/themes/arqora-studio/assets/responsive/bath-after-960.webp';" /><span class="fsc-ux-card-shade" aria-hidden="true"></span><span class="fsc-ux-card-meta" aria-hidden="true"><span class="fsc-ux-card-index">06 / 15</span><strong>BATHROOM</strong></span><span class="fsc-ux-card-zoom" aria-hidden="true">↗</span></button>
      <button class="fsc-ux-gallery-card" type="button" data-fsc-index="6" data-fsc-large="https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&amp;fit=crop&amp;w=1800&amp;q=85" data-fsc-credit="Interior design reference · Unsplash" data-fsc-source="" aria-label="Enlarge whole-home inspiration photo 7 of 15"><img src="https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&amp;fit=crop&amp;w=1000&amp;q=82" alt="Contemporary whole-home interior inspiration" loading="lazy" decoding="async" draggable="false" /><span class="fsc-ux-card-shade" aria-hidden="true"></span><span class="fsc-ux-card-meta" aria-hidden="true"><span class="fsc-ux-card-index">07 / 15</span><strong>WHOLE-HOME</strong></span><span class="fsc-ux-card-zoom" aria-hidden="true">↗</span></button>
      <button class="fsc-ux-gallery-card" type="button" data-fsc-index="7" data-fsc-large="https://upload.wikimedia.org/wikipedia/commons/c/c4/Craftsman_with_Backyard_Cottage_%28Converted_Garage%29.jpg" data-fsc-credit="ADU photo · Sightline Institute · CC BY 2.0" data-fsc-source="https://commons.wikimedia.org/wiki/File:Craftsman_with_Backyard_Cottage_(Converted_Garage).jpg" aria-label="Enlarge adu inspiration photo 8 of 15"><img src="https://upload.wikimedia.org/wikipedia/commons/c/c4/Craftsman_with_Backyard_Cottage_%28Converted_Garage%29.jpg" alt="Craftsman home with backyard cottage converted from a garage" loading="lazy" decoding="async" draggable="false" /><span class="fsc-ux-card-shade" aria-hidden="true"></span><span class="fsc-ux-card-meta" aria-hidden="true"><span class="fsc-ux-card-index">08 / 15</span><strong>ADU</strong></span><span class="fsc-ux-card-zoom" aria-hidden="true">↗</span></button>
      <button class="fsc-ux-gallery-card" type="button" data-fsc-index="8" data-fsc-large="https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&amp;fit=crop&amp;w=1800&amp;q=85" data-fsc-credit="Interior design reference · Unsplash" data-fsc-source="" aria-label="Enlarge kitchen inspiration photo 9 of 15"><img src="https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&amp;fit=crop&amp;w=1000&amp;q=82" alt="Modern kitchen and dining area inspiration" loading="lazy" decoding="async" draggable="false" /><span class="fsc-ux-card-shade" aria-hidden="true"></span><span class="fsc-ux-card-meta" aria-hidden="true"><span class="fsc-ux-card-index">09 / 15</span><strong>KITCHEN</strong></span><span class="fsc-ux-card-zoom" aria-hidden="true">↗</span></button>
      <button class="fsc-ux-gallery-card" type="button" data-fsc-index="9" data-fsc-large="https://www.revivedesignandrenovation.com/wp-content/uploads/2024/05/Bathroom-Inspiration-Slide-7.jpg" data-fsc-credit="Bathroom design reference · Revive Design &amp; Renovation" data-fsc-source="https://www.revivedesignandrenovation.com/bathroom-gallery/" aria-label="Enlarge bathroom inspiration photo 10 of 15"><img src="https://www.revivedesignandrenovation.com/wp-content/uploads/2024/05/Bathroom-Inspiration-Slide-7.jpg" alt="Bathroom remodeling design inspiration" loading="lazy" decoding="async" draggable="false" onerror="this.onerror=null;this.src='https://5starconstructionsca.com/wp-content/themes/arqora-studio/assets/responsive/bath-after-960.webp';" /><span class="fsc-ux-card-shade" aria-hidden="true"></span><span class="fsc-ux-card-meta" aria-hidden="true"><span class="fsc-ux-card-index">10 / 15</span><strong>BATHROOM</strong></span><span class="fsc-ux-card-zoom" aria-hidden="true">↗</span></button>
      <button class="fsc-ux-gallery-card" type="button" data-fsc-index="10" data-fsc-large="https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&amp;fit=crop&amp;w=1800&amp;q=85" data-fsc-credit="Interior design reference · Unsplash" data-fsc-source="" aria-label="Enlarge whole-home inspiration photo 11 of 15"><img src="https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&amp;fit=crop&amp;w=1000&amp;q=82" alt="Refined living room renovation inspiration" loading="lazy" decoding="async" draggable="false" /><span class="fsc-ux-card-shade" aria-hidden="true"></span><span class="fsc-ux-card-meta" aria-hidden="true"><span class="fsc-ux-card-index">11 / 15</span><strong>WHOLE-HOME</strong></span><span class="fsc-ux-card-zoom" aria-hidden="true">↗</span></button>
      <button class="fsc-ux-gallery-card" type="button" data-fsc-index="11" data-fsc-large="https://upload.wikimedia.org/wikipedia/commons/0/02/ADU_Alley_St_Albans_L7_Davidson_%286124624998%29.jpg" data-fsc-credit="ADU photo · Brett VA · CC BY 2.0" data-fsc-source="https://commons.wikimedia.org/wiki/File:ADU_Alley_St_Albans_L7_Davidson_(6124624998).jpg" aria-label="Enlarge adu inspiration photo 12 of 15"><img src="https://upload.wikimedia.org/wikipedia/commons/0/02/ADU_Alley_St_Albans_L7_Davidson_%286124624998%29.jpg" alt="Detached accessory dwelling unit along a residential lane" loading="lazy" decoding="async" draggable="false" /><span class="fsc-ux-card-shade" aria-hidden="true"></span><span class="fsc-ux-card-meta" aria-hidden="true"><span class="fsc-ux-card-index">12 / 15</span><strong>ADU</strong></span><span class="fsc-ux-card-zoom" aria-hidden="true">↗</span></button>
      <button class="fsc-ux-gallery-card" type="button" data-fsc-index="12" data-fsc-large="https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&amp;fit=crop&amp;w=1800&amp;q=85" data-fsc-credit="Interior design reference · Unsplash" data-fsc-source="" aria-label="Enlarge kitchen inspiration photo 13 of 15"><img src="https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&amp;fit=crop&amp;w=1000&amp;q=82" alt="High-end kitchen interior design inspiration" loading="lazy" decoding="async" draggable="false" /><span class="fsc-ux-card-shade" aria-hidden="true"></span><span class="fsc-ux-card-meta" aria-hidden="true"><span class="fsc-ux-card-index">13 / 15</span><strong>KITCHEN</strong></span><span class="fsc-ux-card-zoom" aria-hidden="true">↗</span></button>
      <button class="fsc-ux-gallery-card" type="button" data-fsc-index="13" data-fsc-large="https://www.revivedesignandrenovation.com/wp-content/uploads/2024/05/Bathroom-Inspiration-Slide-10.jpg" data-fsc-credit="Bathroom design reference · Revive Design &amp; Renovation" data-fsc-source="https://www.revivedesignandrenovation.com/bathroom-gallery/" aria-label="Enlarge bathroom inspiration photo 14 of 15"><img src="https://www.revivedesignandrenovation.com/wp-content/uploads/2024/05/Bathroom-Inspiration-Slide-10.jpg" alt="Bathroom remodeling design inspiration" loading="lazy" decoding="async" draggable="false" onerror="this.onerror=null;this.src='https://5starconstructionsca.com/wp-content/themes/arqora-studio/assets/responsive/bath-after-960.webp';" /><span class="fsc-ux-card-shade" aria-hidden="true"></span><span class="fsc-ux-card-meta" aria-hidden="true"><span class="fsc-ux-card-index">14 / 15</span><strong>BATHROOM</strong></span><span class="fsc-ux-card-zoom" aria-hidden="true">↗</span></button>
      <button class="fsc-ux-gallery-card" type="button" data-fsc-index="14" data-fsc-large="https://images.unsplash.com/photo-1600607687644-c7171b42498f?auto=format&amp;fit=crop&amp;w=1800&amp;q=85" data-fsc-credit="Interior design reference · Unsplash" data-fsc-source="" aria-label="Enlarge whole-home inspiration photo 15 of 15"><img src="https://images.unsplash.com/photo-1600607687644-c7171b42498f?auto=format&amp;fit=crop&amp;w=1000&amp;q=82" alt="Modern renovated interior with open living space" loading="lazy" decoding="async" draggable="false" /><span class="fsc-ux-card-shade" aria-hidden="true"></span><span class="fsc-ux-card-meta" aria-hidden="true"><span class="fsc-ux-card-index">15 / 15</span><strong>WHOLE-HOME</strong></span><span class="fsc-ux-card-zoom" aria-hidden="true">↗</span></button>
    </div>
    <div class="fsc-ux-gallery-notes">
      <p class="fsc-ux-gallery-disclosure"><span aria-hidden="true">✳</span> These are design inspirations, not completed 5 Star Constructions projects.</p>
      <details class="fsc-ux-gallery-credits"><summary>Image sources &amp; credits <span aria-hidden="true">＋</span></summary>
        <div class="fsc-ux-gallery-credits-content">    <p class="fsc-ux-gallery-disclosure">Design inspiration only; photos do not represent completed 5 Star Constructions projects. Bathroom references: Revive Design and Renovation; additional references: Unsplash and Wikimedia Commons. ADU photo credits (images displayed with gallery cropping): <a href="https://commons.wikimedia.org/wiki/File:Exterior_of_a_Snap_ADU_Build.jpg" target="_blank" rel="noopener noreferrer">SnapADU</a> (<a href="https://creativecommons.org/licenses/by-sa/4.0/" target="_blank" rel="noopener noreferrer">CC BY-SA 4.0</a>), <a href="https://commons.wikimedia.org/wiki/File:Craftsman_with_Backyard_Cottage_(Converted_Garage).jpg" target="_blank" rel="noopener noreferrer">Sightline Institute</a> (<a href="https://creativecommons.org/licenses/by/2.0/" target="_blank" rel="noopener noreferrer">CC BY 2.0</a>), <a href="https://commons.wikimedia.org/wiki/File:ADU_Alley_St_Albans_L7_Davidson_(6124624998).jpg" target="_blank" rel="noopener noreferrer">Brett VA</a> (<a href="https://creativecommons.org/licenses/by/2.0/" target="_blank" rel="noopener noreferrer">CC BY 2.0</a>).</p></div>
      </details>
    </div>

  </div>
  <div id="fsc-ux-lightbox" class="fsc-ux-lightbox" role="dialog" aria-modal="true" aria-labelledby="fsc-ux-lightbox-title" aria-describedby="fsc-ux-lightbox-caption" hidden>
    <button type="button" class="fsc-ux-lightbox-backdrop" aria-label="Close enlarged image" tabindex="-1"></button>
    <div class="fsc-ux-lightbox-dialog">
      <div class="fsc-ux-lightbox-top">
        <div><span class="fsc-ux-lightbox-eyebrow">THE DESIGN EDIT <span aria-hidden="true">/</span> <span id="fsc-ux-lightbox-count">01 / 15</span></span><h3 id="fsc-ux-lightbox-title">Design inspiration</h3></div>
        <button type="button" class="fsc-ux-lightbox-close" aria-label="Close image viewer">✕</button>
      </div>
      <div class="fsc-ux-lightbox-stage">
        <button type="button" class="fsc-ux-lightbox-prev" aria-label="Previous enlarged image">←</button>
        <img id="fsc-ux-lightbox-image" alt="" draggable="false"/>
        <button type="button" class="fsc-ux-lightbox-next" aria-label="Next enlarged image">→</button>
      </div>
      <div class="fsc-ux-lightbox-bottom">
        <div><p id="fsc-ux-lightbox-caption">Design inspiration — not a completed 5 Star project.</p><small id="fsc-ux-lightbox-credit"></small></div>
        <a id="fsc-ux-lightbox-source" target="_blank" rel="noopener noreferrer" hidden>View image source ↗</a>
      </div>
    </div>
  </div>
</section>

<?php endif; ?>
    <?php
}, 17);