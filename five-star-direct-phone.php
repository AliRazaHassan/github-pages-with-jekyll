<?php
/**
 * Plugin Name: 5 Star Constructions — Direct Call Number
 * Description: Shows the official call number site-wide, updates outdated call links, and adds a call shortcut to the shared site header.
 * Version: 1.0.0
 * Author: 5 Star Constructions
 * License: GPL-2.0-or-later
 */
defined('ABSPATH') || exit;

function five_star_direct_phone_rewrite($html) {
    if (!is_string($html) || $html === '' || stripos($html, '<html') === false) {
        return $html;
    }

    // Replace the prior shared contact number wherever it appears in the rendered markup.
    $html = str_replace(
        array(
            'tel:+19165479596', 'tel:19165479596', 'tel:9165479596',
            '+19165479596', '19165479596',
            '(916) 547-9596', '916-547-9596', '916.547.9596', '916 547 9596'
        ),
        array(
            'tel:+19162251015', 'tel:+19162251015', 'tel:+19162251015',
            '+19162251015', '19162251015',
            '(916) 225-1015', '916-225-1015', '916.225.1015', '916 225 1015'
        ),
        $html
    );

    // Insert the phone beside the existing estimate CTA on all WordPress pages.
    if (strpos($html, 'five-star-direct-call') === false) {
        $call = '<a class="five-star-direct-call" href="tel:+19162251015" aria-label="Call 5 Star Constructions at (916) 225-1015"><svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 3.09 5.18 2 2 0 0 1 5.08 3h3a2 2 0 0 1 2 1.72c.13.96.37 1.9.72 2.8a2 2 0 0 1-.45 2.11L9.09 10.9a16 16 0 0 0 4.01 4.01l1.27-1.26a2 2 0 0 1 2.11-.45c.9.35 1.84.59 2.8.72A2 2 0 0 1 22 16.92z"/></svg><span>(916) 225-1015</span></a>';
        $html = preg_replace('/(?=<a\b[^>]*\bclass=["\'][^"\']*\bheader-cta\b[^"\']*["\'][^>]*>)/i', $call, $html, 1);
    }

    // Preserve the existing theme layout, with compact icon styling on smaller screens.
    if (strpos($html, 'id="five-star-direct-call-styles"') === false) {
        $css = '<style id="five-star-direct-call-styles">
        .five-star-header .five-star-direct-call{display:inline-flex;align-items:center;justify-content:center;gap:7px;flex-shrink:0;white-space:nowrap;color:inherit;text-decoration:none;font:600 13px/1.2 inherit;padding:9px 11px;border:1px solid currentColor;border-radius:999px;transition:opacity .2s}
        .five-star-header .five-star-direct-call:hover{opacity:.72}
        .five-star-header .five-star-direct-call svg{width:16px;height:16px;flex-shrink:0}
        @media(max-width:1280px){.five-star-header .five-star-direct-call span{display:none}.five-star-header .five-star-direct-call{padding:9px;border-radius:50%;min-width:36px;min-height:36px}}
        @media(max-width:640px){.five-star-header .five-star-direct-call{min-width:34px;min-height:34px;padding:8px;margin-left:auto;margin-right:8px}}
        </style>';
        $html = str_replace('</head>', $css . '</head>', $html);
    }
    return $html;
}

add_action('template_redirect', function () {
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST) || is_feed()) {
        return;
    }
    ob_start('five_star_direct_phone_rewrite');
}, 0);
